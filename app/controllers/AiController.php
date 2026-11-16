<?php
/**
 * AI Financial Insight Controller
 * Implements strict layered architecture:
 * Controller -> Financial Data Service -> AI Service -> AI Provider
 * Accessible only to Super User for management view; ask endpoint supports role-scoped AI assistance.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Services\FinancialDataService;
use App\Services\AiService;
use App\Models\AiLog;

class AiController extends Controller {
    public function __construct() {
        Auth::requireLogin();

        // Enforce Super User access for AI management views, while allowing role-scoped ask endpoint
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $urlParam = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
        $isAskEndpoint = str_ends_with($urlParam, 'ask') || str_contains($requestUri, 'ai/ask');

        if (!$isAskEndpoint) {
            Auth::requireSuperUser();
        }
    }

    /**
     * Display AI Financial Insight Interface (Super User Only)
     */
    public function index(): void {
        Auth::requireSuperUser();

        // Step 1: Controller queries Financial Data Service
        $userRole = Auth::role() ?? 'super_user';
        $userId = Auth::id();
        $memberProfile = Auth::member();
        $userAccount = Auth::user() ?? [];

        $financialService = new FinancialDataService();
        $metrics = $financialService->getFinancialMetrics($userRole, $userId, $memberProfile, $userAccount);

        // Step 2: Initialize AI Service
        $aiService = new AiService($financialService);
        $providerName = $aiService->getProvider()->getProviderName();

        // Step 3: Fetch recent chat history from ai_logs
        $aiLogModel = new AiLog();
        $history = $aiLogModel->getHistoryByUser(Auth::id() ?? 0, 15);

        $latestResult = Session::getFlash('ai_result');

        $this->view('ai.index', [
            'title'        => 'AI Financial Insight — ' . APP_NAME,
            'metrics'      => $metrics,
            'providerName' => $providerName,
            'history'      => $history,
            'latestResult' => $latestResult,
        ]);
    }

    /**
     * Process AI Question
     * Pipeline: Controller -> Financial Data Service -> AI Service -> AI Provider
     * Strictly returns valid JSON on AJAX requests under all conditions.
     */
    public function ask(): void {
        // Guarantee clean output buffer to prevent PHP notices/warnings from corrupting JSON
        if (ob_get_level() === 0) {
            ob_start();
        }

        try {
            if (!$this->isPost()) {
                if (ob_get_length()) ob_clean();
                $this->redirect('ai');
                return;
            }

            if (!$this->validateCsrf()) {
                if (ob_get_length()) ob_clean();
                if ($this->isAjaxRequest()) {
                    $this->json(['success' => false, 'message' => 'Token CSRF tidak valid.'], 403);
                    return;
                }
                Session::flash('error', 'Token keamanan CSRF tidak valid. Silakan coba lagi.');
                $this->redirect('ai');
                return;
            }

            $question = trim((string)($this->post('question') ?? ''));
            // Strip non-printable control characters
            $question = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $question);

            if ($question === '') {
                if (ob_get_length()) ob_clean();
                if ($this->isAjaxRequest()) {
                    $this->json(['success' => false, 'message' => 'Pertanyaan analisis tidak boleh kosong.'], 422);
                    return;
                }
                Session::flash('error', 'Pertanyaan analisis tidak boleh kosong.');
                $this->redirect('ai');
                return;
            }

            if (mb_strlen($question) > 1000) {
                if (ob_get_length()) ob_clean();
                if ($this->isAjaxRequest()) {
                    $this->json(['success' => false, 'message' => 'Pertanyaan analisis maksimal 1.000 karakter.'], 422);
                    return;
                }
                Session::flash('error', 'Pertanyaan analisis maksimal 1.000 karakter.');
                $this->redirect('ai');
                return;
            }

            // 1. Controller calls Financial Data Service (Read-Only facts with Role Scoping)
            $userRole = Auth::role() ?? 'guest';
            $userId = Auth::id();
            $memberProfile = Auth::member();
            $userAccount = Auth::user() ?? [];

            $financialService = new FinancialDataService();

            // Fetch conversation context from session
            $sessionContext = Session::get('ai_conversation_context') ?? [];

            // Dynamic factual context and domain queries customized to the question
            $contextData = $financialService->getFactualContextForQuestion($question, $userRole, $userId, $memberProfile, $userAccount, $sessionContext);
            $metrics = $contextData['metrics'];
            $factualContext = $contextData['context'];

            // Persist updated conversation context to session
            if (isset($metrics['current_context'])) {
                Session::set('ai_conversation_context', $metrics['current_context']);
            }

            // 2. Controller passes facts to AI Service
            $aiService = new AiService($financialService);

            // 3. AI Service invokes AI Provider and safely logs request/response
            $result = $aiService->analyze($question, $metrics, $factualContext, $userId, $userRole);

            if (ob_get_length()) ob_clean();

            if ($this->isAjaxRequest()) {
                $this->json([
                    'success'    => true,
                    'question'   => $result['question'],
                    'response'   => $result['response'],
                    'provider'   => $result['provider'],
                    'created_at' => date('d M Y H:i'),
                    'log_id'     => $result['log_id'],
                ]);
                return;
            }

            Session::flash('ai_result', $result);
            $this->redirect('ai');
        } catch (\Throwable $e) {
            error_log("Error in AiController::ask: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            if (ob_get_length()) ob_clean();

            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan sistem saat memproses pertanyaan. Silakan coba beberapa saat lagi.',
                ], 500);
                return;
            }

            Session::flash('error', 'Terjadi kesalahan sistem saat memproses pertanyaan.');
            $this->redirect('ai');
        }
    }

    /**
     * Clear user's AI interaction logs
     */
    public function clearHistory(): void {
        if (!$this->isPost() || !$this->validateCsrf()) {
            $this->redirect('ai');
            return;
        }

        $userId = Auth::id();
        if ($userId) {
            $aiLogModel = new AiLog();
            $aiLogModel->deleteByUser($userId);
            Session::remove('ai_conversation_context');
            Session::flash('success', 'Riwayat interaksi AI berhasil dibersihkan.');
        }

        $this->redirect('ai');
    }

    /**
     * Check if current request is AJAX / JSON
     */
    private function isAjaxRequest(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || isset($_POST['ajax']);
    }
}
