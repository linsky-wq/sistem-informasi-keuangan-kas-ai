<?php
/**
 * AI Service
 * Orchestrates Financial Data Service and AI Provider
 * Pipeline: Controller -> Financial Data Service -> AI Service -> AI Provider
 */

namespace App\Services;

use App\Services\Ai\AiProviderInterface;
use App\Services\Ai\GeminiAiProvider;
use App\Services\Ai\NvidiaAiProvider;
use App\Services\Ai\GroqAiProvider;
use App\Services\Ai\MockAiProvider;
use App\Models\AiLog;
use App\Models\AiSetting;
use App\Models\Transaction;

class AiService {
    private FinancialDataService $financialDataService;
    private AiProviderInterface $provider;
    private AiProviderInterface $fallbackProvider;
    private AiLog $aiLogModel;

    public function __construct(?FinancialDataService $financialDataService = null, ?AiProviderInterface $provider = null) {
        $this->financialDataService = $financialDataService ?? new FinancialDataService();
        $this->aiLogModel = new AiLog();
        $this->fallbackProvider = new MockAiProvider();

        if ($provider !== null) {
            $this->provider = $provider;
        } else {
            $aiSetting = new AiSetting();
            $selected = strtolower(trim((string)$aiSetting->get('ai_provider', 'auto')));

            if ($selected === 'groq') {
                $groqKey = (string)$aiSetting->get('groq_api_key', '');
                $groqModel = (string)$aiSetting->get('groq_model', 'llama-3.3-70b-versatile');
                $groqUrl = (string)$aiSetting->get('groq_api_url', 'https://api.groq.com/openai/v1/chat/completions');
                $groq = new GroqAiProvider($groqKey, $groqModel, $groqUrl);
                $this->provider = $groq->isAvailable() ? $groq : $this->fallbackProvider;
            } elseif ($selected === 'nvidia') {
                $nvidiaKey = (string)$aiSetting->get('nvidia_api_key', '');
                $nvidiaModel = (string)$aiSetting->get('nvidia_model', 'meta/llama-3.1-70b-instruct');
                $nvidiaUrl = (string)$aiSetting->get('nvidia_api_url', 'https://integrate.api.nvidia.com/v1/chat/completions');
                $nvidiaEnabled = (bool)$aiSetting->get('nvidia_enabled', true);
                $nvidia = new NvidiaAiProvider($nvidiaKey, $nvidiaModel, $nvidiaUrl, $nvidiaEnabled);
                $this->provider = $nvidia->isAvailable() ? $nvidia : $this->fallbackProvider;
            } elseif ($selected === 'gemini') {
                $geminiKey = (string)$aiSetting->get('gemini_api_key', '');
                $geminiModel = (string)$aiSetting->get('gemini_model', 'gemini-1.5-flash');
                $gemini = new GeminiAiProvider($geminiKey, $geminiModel);
                $this->provider = $gemini->isAvailable() ? $gemini : $this->fallbackProvider;
            } elseif ($selected === 'mock') {
                $this->provider = $this->fallbackProvider;
            } else {
                // 'auto' mode: preserve original priority (NVIDIA NIM -> Gemini -> Groq -> Mock fallback)
                $nvidia = new NvidiaAiProvider();
                if ($nvidia->isAvailable()) {
                    $this->provider = $nvidia;
                } else {
                    $gemini = new GeminiAiProvider();
                    if ($gemini->isAvailable()) {
                        $this->provider = $gemini;
                    } else {
                        $groq = new GroqAiProvider();
                        if ($groq->isAvailable()) {
                            $this->provider = $groq;
                        } else {
                            $this->provider = $this->fallbackProvider;
                        }
                    }
                }
            }
        }
    }

    /**
     * Get active AI provider
     */
    public function getProvider(): AiProviderInterface {
        return $this->provider;
    }

    /**
     * Set custom AI provider
     */
    public function setProvider(AiProviderInterface $provider): void {
        $this->provider = $provider;
    }

    /**
     * Get Financial Data Service instance
     */
    public function getFinancialDataService(): FinancialDataService {
        return $this->financialDataService;
    }

    /**
     * Analyze financial question with given metrics and factual context
     * Direct pipeline step: Controller -> FinancialDataService -> AI Service -> AI Provider
     */
    public function analyze(string $question, array $metrics, string $factualContext, ?int $userId = null, ?string $userRole = null): array {
        $cleanQuestion = trim($question);
        if ($cleanQuestion === '') {
            $cleanQuestion = 'Bagaimana kondisi keuangan bulan ini?';
        }

        // Handle identity queries without calling AI provider
        $lower = strtolower($cleanQuestion);
        $identityKeywords = [
            'siapa saya',
            'siapa nama saya',
            'saya login sebagai siapa',
            'apa role saya',
            'apa saja yang bisa saya lakukan',
            'apa hak akses saya',
        ];
        foreach ($identityKeywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                if ($userId === null) {
                    return [
                        'success' => true,
                        'question' => $cleanQuestion,
                        'response' => 'Silakan login terlebih dahulu untuk mengetahui identitas Anda.',
                        'provider' => 'Local',
                        'metrics' => $metrics,
                        'log_id' => null,
                    ];
                }
                $name = $metrics['user_name'] ?? $metrics['user_username'] ?? 'Pengguna';
                $roleKey = $metrics['user_role'] ?? $userRole ?? '';
                $roleLabel = $metrics['user_role_label'] ?? $userRole ?? 'Pengguna';
                $roleResponses = [
                    'super_user' => "Anda sedang login sebagai {$name}, dengan role Admin. Anda dapat mengakses pengelolaan sistem sesuai hak akses akun Anda.",
                    'bendahara'  => "Anda sedang login sebagai {$name}, dengan role Bendahara. Anda dapat mengelola pembayaran, memverifikasi transaksi, dan melihat laporan sesuai hak akses Anda.",
                    'member'     => "Anda sedang login sebagai {$name}, dengan role Anggota. Anda dapat melihat tagihan, status pembayaran, dan riwayat transaksi milik Anda sendiri.",
                    'anggota'    => "Anda sedang login sebagai {$name}, dengan role Anggota. Anda dapat melihat tagihan, status pembayaran, dan riwayat transaksi milik Anda sendiri.",
                ];
                $response = $roleResponses[$roleKey] ?? "Anda sedang login sebagai {$name}, dengan role {$roleLabel}.";
                return [
                    'success' => true,
                    'question' => $cleanQuestion,
                    'response' => $response,
                    'provider' => 'Local',
                    'metrics' => $metrics,
                    'log_id' => null,
                ];
            }
        }

        // Database connection failure safeguard
        if (($metrics['query_results']['intent'] ?? '') === 'database_error' || ($metrics['db_connected'] ?? true) === false) {
            return [
                'success'  => true,
                'question' => $cleanQuestion,
                'response' => 'Mohon maaf, data sistem belum dapat diperiksa saat ini karena koneksi database sedang bermasalah atau tidak dapat diakses.',
                'provider' => 'System Safeguard',
                'metrics'  => $metrics,
                'log_id'   => null,
            ];
        }

        // Attach user_role into metrics if provided or detect from Auth
        if ($userRole !== null) {
            $metrics['user_role'] = $userRole;
        } elseif (!isset($metrics['user_role'])) {
            $metrics['user_role'] = \App\Core\Auth::role() ?? 'super_user';
        }

        // Original provider handling (unchanged)
        $providerName = $this->provider->getProviderName();
        $response = '';

        try {
            // Attempt generation with primary provider
            $response = $this->provider->generateInsight($cleanQuestion, $factualContext, $metrics);
        } catch (\Throwable $e) {
            // Fallback to Mock Provider if primary provider fails (e.g. invalid API key, timeout, rate limit)
            $response = $this->fallbackProvider->generateInsight($cleanQuestion, $factualContext, $metrics);
            $safeError = $this->sanitizeCredentials($e->getMessage());
            // Mask any URLs or query parameters that might contain keys
            $safeError = preg_replace('/(\?key=)[^&\s]+/i', '$1[REDACTED]', $safeError);
            $providerName = $this->fallbackProvider->getProviderName() . " (Fallback: " . $safeError . ")";
        }

        // Sanitize credentials before logging
        $safeQuestion = $this->sanitizeCredentials($cleanQuestion);
        $safeResponse = $this->sanitizeCredentials($response);

        // Record to ai_logs table
        $logId = null;
        try {
            $logId = $this->aiLogModel->record($userId, $safeQuestion, $safeResponse);
        } catch (\Throwable $logErr) {
            // Do not break user experience if logging encounters an issue
            error_log("Gagal mencatat ai_logs: " . $logErr->getMessage());
        }

        return [
            'success'   => true,
            'question'  => $cleanQuestion,
            'response'  => $response,
            'provider'  => $providerName,
            'metrics'   => $metrics,
            'log_id'    => $logId,
        ];
    }

    /**
     * Ask question with automated retrieval from FinancialDataService
     */
    public function ask(string $question, ?int $userId = null, ?string $userRole = null, ?array $sessionContext = null): array {
        $role = $userRole ?? (\App\Core\Auth::role() ?? 'super_user');
        $contextData = $this->financialDataService->getFactualContextForQuestion($question, $role, $userId, null, null, $sessionContext);
        $metrics = $contextData['metrics'];
        $factualContext = $contextData['context'];

        return $this->analyze($question, $metrics, $factualContext, $userId, $role);
    }

    /**
     * Sanitize any credentials or sensitive tokens from being written to logs
     */
    public function sanitizeCredentials(string $text): string {
        // Redact potential API keys (e.g. AIzaSy..., 32+ char hex/base64 tokens)
        $text = preg_replace('/(AIza[0-9A-Za-z\-_]{20,})/i', '[REDACTED_API_KEY]', $text);
        $text = preg_replace('/(api[_-]?key[:=]\s*)([A-Za-z0-9\-_]{16,})/i', '$1[REDACTED_KEY]', $text);
        $text = preg_replace('/(password[:=]\s*)([^\s,;&]+)/i', '$1[REDACTED_PASSWORD]', $text);
        $text = preg_replace('/(bearer\s+)([A-Za-z0-9\-_\.]{20,})/i', '$1[REDACTED_TOKEN]', $text);
        $text = preg_replace('/(secret[:=]\s*)([^\s,;&]+)/i', '$1[REDACTED_SECRET]', $text);

        return $text;
    }
}