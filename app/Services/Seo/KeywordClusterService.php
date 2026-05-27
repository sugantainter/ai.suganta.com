<?php

namespace App\Services\Seo;

class KeywordClusterService
{
    /**
     * Generate dynamic keyword clusters for a page based on entities.
     */
    public function generate(array $analysis): array
    {
        if (empty($analysis) || !$analysis['isValid']) {
            return ['ai tools', 'artificial intelligence', 'productivity applications'];
        }

        $pageType = $analysis['pageType'] ?? '';
        $clusters = [];

        switch ($pageType) {
            case 'comparison':
                $tool1 = $analysis['tool1'];
                $tool2 = $analysis['tool2'];
                $modifier = $analysis['modifier'] ?? '';
                
                $clusters[] = "{$tool1} vs {$tool2} comparison";
                $clusters[] = "difference between {$tool1} and {$tool2}";
                $clusters[] = "is {$tool1} better than {$tool2}";
                if ($modifier) {
                    $clusters[] = "{$tool1} vs {$tool2} for {$modifier}";
                    $clusters[] = "best AI chatbot for {$modifier}";
                }
                $clusters[] = "{$tool1} and {$tool2} pricing features";
                $clusters[] = "pros and cons of {$tool1} and {$tool2}";
                break;

            case 'listicle':
                $modifier = $analysis['modifier'];
                $category = $analysis['variables']['category'] ?? 'Productivity';
                
                $clusters[] = "best AI tools for {$modifier}";
                $clusters[] = "top {$modifier} AI platforms";
                $clusters[] = "free AI helpers for {$modifier}";
                $clusters[] = "AI tools to boost {$modifier} productivity";
                $clusters[] = "must-have artificial intelligence apps for {$modifier}";
                $clusters[] = "best software for {$modifier} in 2026";
                break;

            case 'alternatives':
                $tool = $analysis['tool'];
                
                $clusters[] = "best alternatives to {$tool}";
                $clusters[] = "competitors of {$tool}";
                $clusters[] = "apps like {$tool}";
                $clusters[] = "sites similar to {$tool}";
                $clusters[] = "cheaper alternatives to {$tool}";
                $clusters[] = "free {$tool} replacement software";
                break;

            case 'education':
                $target = $analysis['subject'] ?? $analysis['profession'] ?? 'students';
                
                $clusters[] = "AI tools for learning {$target}";
                $clusters[] = "educational artificial intelligence for {$target}";
                $clusters[] = "how to study {$target} with AI";
                $clusters[] = "free classroom resources for {$target}";
                $clusters[] = "AI software for exam prep in {$target}";
                $clusters[] = "best learning assistants for {$target}";
                break;

            case 'guide':
                $tool = $analysis['tool'] ?? $analysis['topic'] ?? 'AI tools';
                $modifier = $analysis['modifier'] ?? '';

                $clusters[] = "how to use {$tool}";
                if ($modifier) {
                    $clusters[] = "how to use {$tool} for {$modifier}";
                    $clusters[] = "step-by-step {$tool} tutorial for {$modifier}";
                }
                $clusters[] = "{$tool} tutorial for beginners";
                $clusters[] = "best tips and tricks for {$tool}";
                $clusters[] = "getting started guide for {$tool}";
                break;

            case 'workflow':
                $target = $analysis['industry'] ?? $analysis['profession'] ?? 'business';
                $subfolder = $analysis['subfolder'] ?? 'ai-workflows';

                $clusters[] = "AI automation workflows in {$target}";
                $clusters[] = "how to build AI agents for {$target}";
                $clusters[] = "integrating artificial intelligence in {$target}";
                $clusters[] = "enterprise AI agent design for {$target}";
                $clusters[] = "streamlining workflows with LLMs in {$target}";
                $clusters[] = "future of AI agents in {$target}";
                break;

            default:
                $clusters = ['ai software', 'artificial intelligence models', 'cognitive helpers'];
                break;
        }

        return $clusters;
    }
}
