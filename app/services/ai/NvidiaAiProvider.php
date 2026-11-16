<?php
/**
 * NVIDIA AI Provider
 *
 * Integrates with NVIDIA NIM API (OpenAI‑compatible endpoint).
 * The provider is optional and activated via the following .env variables:
 *   NVIDIA_ENABLED   = true|false
 *   NVIDIA_API_KEY   = <your‑nvidia‑api‑key>
 *   NVIDIA_API_URL   = https://integrate.api.nvidia.com/v1/chat/completions
 *   NVIDIA_MODEL     = <model‑name> (defaults to "meta/llama-3.1-70b-instruct")
 *
 * The class follows the same contract as other providers – it returns a plain
 * string response (or throws on fatal errors that the caller will catch).
 */

namespace App\Services\Ai;

class NvidiaAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private bool $enabled;

    public function __construct(?string $apiKey = null, ?string $model = null, ?string $apiUrl = null, ?bool $enabled = null)
    {
        $this->enabled = $enabled !== null ? $enabled : (strtolower((string)(getenv('NVIDIA_ENABLED') ?: 'false')) === 'true');
        $this->apiKey  = $apiKey !== null ? $apiKey : (getenv('NVIDIA_API_KEY') ?: '');
        $this->apiUrl  = $apiUrl !== null ? $apiUrl : (getenv('NVIDIA_API_URL') ?: 'https://integrate.api.nvidia.com/v1/chat/completions');
        $this->model   = $model !== null ? $model : (getenv('NVIDIA_MODEL') ?: 'meta/llama-3.1-70b-instruct');
    }

    public function isAvailable(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    public function getProviderName(): string
    {
        return 'NVIDIA';
    }

    /**
     * Generate insight using NVIDIA NIM.
     *
     * @param string $userPrompt      The user question.
     * @param string $factualContext  Context built from the database.
     * @param array  $metrics         Additional metrics passed to the prompt.
     * @return string                  The AI response.
     */
    public function generateInsight(string $userPrompt, string $factualContext, array $metrics): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('NVIDIA provider is disabled or API key missing');
        }

        // Build system prompt – keep it consistent with other providers.
        $systemPrompt = "You are an AI assistant for a cash management system. Use the provided factual context and metrics to answer the user's question. Do not fabricate data.\n\n" .
                        "Factual Context:\n" . $factualContext . "\n\nMetrics:\n" . json_encode($metrics, JSON_PRETTY_PRINT);

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => 0.2,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 15,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr     = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false) {
            throw new \RuntimeException('cURL error while contacting NVIDIA API: ' . $curlErr);
        }

        if ($httpCode !== 200) {
            $decoded = json_decode($rawResponse, true);
            $msg = $decoded['error']['message'] ?? 'Unexpected HTTP status ' . $httpCode;
            throw new \RuntimeException('NVIDIA API error: ' . $msg);
        }

        $data = json_decode($rawResponse, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Malformed response from NVIDIA API');
        }

        $content = trim($data['choices'][0]['message']['content'] ?? '');
        if ($content === '') {
            throw new \RuntimeException('Empty response from NVIDIA API');
        }

        return $content;
    }
}

