<?php
/**
 * Groq AI Provider
 *
 * Integrates with Groq Cloud API (OpenAI-compatible endpoint).
 * Fast LLM inference with models such as llama-3.3-70b-versatile.
 */

declare(strict_types=1);

namespace App\Services\Ai;

class GroqAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private bool $enabled;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $apiUrl = null)
    {
        $this->apiKey  = trim($apiKey ?? (string)env('GROQ_API_KEY', ''));
        $this->model   = trim($model ?? (string)env('GROQ_MODEL', 'llama-3.3-70b-versatile'));
        $this->apiUrl  = trim($apiUrl ?? (string)env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'));
        $this->enabled = !empty($this->apiKey);
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->apiKey) && function_exists('curl_init');
    }

    public function getProviderName(): string
    {
        return "Groq AI ({$this->model})";
    }

    /**
     * Generate insight using Groq API.
     */
    public function generateInsight(string $userPrompt, string $factualContext, array $metrics): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Groq provider is disabled or API key missing');
        }

        // Build system instruction consistent with other AI providers
        $systemPrompt = "Anda adalah Asisten AI pada Sistem Manajemen Kas. Anda dirancang khusus untuk membantu pengelolaan keuangan kas.\n\n" .
                        "ATURAN JAWABAN:\n" .
                        "1. Jawab inti pertanyaan secara langsung, singkat, dan tepat sasaran.\n" .
                        "2. JANGAN MENGARANG DATA. Semua nominal angka, saldo, transaksi, keterangan, nama anggota, dan tagihan wajib bersumber dari FAKTA KEUANGAN KAS TERVERIFIKASI yang disediakan.\n" .
                        "3. Jika informasi tidak ditemukan di fakta database, sampaikan secara jujur.\n\n" .
                        "FAKTA KEUANGAN KAS TERVERIFIKASI:\n" . $factualContext . "\n\n" .
                        "METRIKS SISTEM:\n" . json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => 0.2,
            'max_tokens'  => 1500,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr     = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new \RuntimeException('Koneksi ke Groq API gagal: ' . $curlErr);
        }

        if ($httpCode !== 200) {
            $decoded = json_decode((string)$rawResponse, true);
            $msg = $decoded['error']['message'] ?? 'Status HTTP ' . $httpCode;
            throw new \RuntimeException('Groq API Error: ' . $msg);
        }

        $data = json_decode((string)$rawResponse, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Format respons dari Groq API tidak sesuai ekspektasi');
        }

        return trim((string)$data['choices'][0]['message']['content']);
    }
}

