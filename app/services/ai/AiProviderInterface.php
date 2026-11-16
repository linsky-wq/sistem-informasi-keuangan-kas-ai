<?php
/**
 * AI Provider Interface
 * Defines contract for AI providers (Gemini, Mock/Development Fallback)
 */

namespace App\Services\Ai;

interface AiProviderInterface {
    /**
     * Generate financial analysis and insight based on factual context
     */
    public function generateInsight(string $userPrompt, string $factualContext, array $metrics): string;

    /**
     * Check whether this provider is currently configured and operational
     */
    public function isAvailable(): bool;

    /**
     * Get human-readable name of provider
     */
    public function getProviderName(): string;
}
