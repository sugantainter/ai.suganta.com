<?php

namespace App\Services\Seo;

class FAQGenerationService
{
    /**
     * Generate dynamic contextual FAQs if needed.
     */
    public function generate(array $analysis): array
    {
        $pageType = $analysis['pageType'] ?? 'general';
        
        switch ($pageType) {
            case 'comparison':
                $t1 = $analysis['tool1'] ?? 'Tool A';
                $t2 = $analysis['tool2'] ?? 'Tool B';
                return [
                    [
                        'question' => "Which is better for beginners, {$t1} or {$t2}?",
                        'answer' => "{$t1} usually provides a smoother onboarding process and more intuitive chat controls, making it beginner-friendly. However, {$t2} offers superior performance for advanced users who require deep tool integration."
                    ],
                    [
                        'question' => "Are {$t1} and {$t2} free to use?",
                        'answer' => "Yes, both platforms offer free tiers with access to their core AI models. Subscribing to their pro plans ($20/month) removes speed restrictions and unlocks advanced logical capabilities."
                    ],
                    [
                        'question' => "Which of these AI engines has faster response times?",
                        'answer' => "Under benchmark tests, {$t2} typically returns tokens faster, offering sub-second latencies, whereas {$t1} can face slight throttling during peak internet usage hours."
                    ]
                ];

            case 'listicle':
                $modifier = $analysis['modifier'] ?? 'users';
                return [
                    [
                        'question' => "What is the best overall AI software for {$modifier}?",
                        'answer' => "SuGanta AI Hub ranks as our top choice because it aggregates multiple models (Claude, ChatGPT, Gemini) under a single dashboard, saving subscription fees."
                    ],
                    [
                        'question' => "Do I need technical skills to use these AI tools?",
                        'answer' => "No. Most modern platforms feature clean conversational interfaces. Simply typing queries in plain English is sufficient to yield high-quality results."
                    ]
                ];

            case 'alternatives':
                $tool = $analysis['tool'] ?? 'the primary tool';
                return [
                    [
                        'question' => "Why should I switch from {$tool}?",
                        'answer' => "Users switch to find larger document context windows, cheaper API pricing structures, or to bypass downtime limits during high-traffic hours."
                    ],
                    [
                        'question' => "Which is the most cost-effective alternative to {$tool}?",
                        'answer' => "For heavy api usage, DeepSeek Coder is extremely cost-effective. For general chat, SuGanta Hub offers the best value by bundling top models together."
                    ]
                ];

            case 'education':
                $target = $analysis['subject'] ?? $analysis['profession'] ?? 'learning';
                return [
                    [
                        'question' => "How can AI help me study {$target}?",
                        'answer' => "AI models are excellent at summarizing long academic papers, drafting mock exam questions, explaining mathematical formulas, and debugging source code instantly."
                    ],
                    [
                        'question' => "Is using AI for {$target} classwork allowed?",
                        'answer' => "Using AI to understand concepts and check your answers is an active learning tool. However, copy-pasting essay drafts or solutions directly for grade submission is generally against academic integrity guidelines."
                    ]
                ];

            default:
                return [
                    [
                        'question' => "How does the SuGanta AI Hub operate?",
                        'answer' => "SuGanta AI Hub connects directly to top-tier LLM providers via API keys, presenting them within a unified, responsive client interface with integrated settings."
                    ]
                ];
        }
    }
}
