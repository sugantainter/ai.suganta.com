<?php

namespace App\Services\Seo;

class IntentDetectionService
{
    /**
     * Detect search intent and provide generation modifiers.
     */
    public function detect(array $analysis): array
    {
        if (empty($analysis) || !$analysis['isValid']) {
            return [
                'intent' => 'informational',
                'tone' => 'neutral',
                'target_audience' => 'general public',
                'primary_cta' => 'Learn More',
            ];
        }

        $pageType = $analysis['pageType'] ?? '';

        switch ($pageType) {
            case 'comparison':
                return [
                    'intent' => 'commercial_investigation',
                    'intent_label' => 'Comparative Analysis',
                    'tone' => 'unbiased, analytical, technical, comparative',
                    'target_audience' => 'decision makers, students, developers seeking the optimal tool',
                    'primary_cta' => 'Compare Features',
                    'focus_keywords' => ['vs', 'comparison', 'which is better', 'difference', 'pros and cons', 'pricing'],
                ];

            case 'listicle':
                $modifierType = $analysis['modifier_type'] ?? '';
                $audience = ($modifierType === 'profession') ? $analysis['modifier'] : 'professionals';
                return [
                    'intent' => 'commercial_investigation',
                    'intent_label' => 'Resource Listicle',
                    'tone' => 'authoritative, encouraging, highly structured, recommendation-focused',
                    'target_audience' => $audience,
                    'primary_cta' => 'Explore Tools',
                    'focus_keywords' => ['best', 'top', 'list', 'software', 'tools for ' . $audience, 'pricing overview'],
                ];

            case 'alternatives':
                $tool = $analysis['tool'] ?? 'AI tool';
                return [
                    'intent' => 'commercial_investigation',
                    'intent_label' => 'Alternative Finder',
                    'tone' => 'objective, comparative, helpful, solution-oriented',
                    'target_audience' => 'current users of ' . $tool . ' looking to switch or find backups',
                    'primary_cta' => 'Find Alternatives',
                    'focus_keywords' => ['alternatives to ' . $tool, 'sites like ' . $tool, 'competitors', 'cheaper options'],
                ];

            case 'education':
                $target = $analysis['subject'] ?? $analysis['profession'] ?? 'learners';
                return [
                    'intent' => 'informational_educational',
                    'intent_label' => 'Academic & Exam Guide',
                    'tone' => 'instructional, academic, step-by-step, simple yet thorough',
                    'target_audience' => 'students, teachers, exam candidates for ' . $target,
                    'primary_cta' => 'Start Studying',
                    'focus_keywords' => ['study guide', 'learning tips', 'classroom tools', 'how to study ' . $target, 'exam preparation'],
                ];

            case 'guide':
                $tool = $analysis['tool'] ?? 'AI systems';
                return [
                    'intent' => 'informational_tutorial',
                    'intent_label' => 'How-To Guide',
                    'tone' => 'clear, actionable, technical yet accessible, procedural',
                    'target_audience' => 'hands-on users, self-directed learners, developers',
                    'primary_cta' => 'View Tutorial',
                    'focus_keywords' => ['how to use', 'tutorial', 'step-by-step guide', 'setup instruction', 'getting started'],
                ];

            case 'workflow':
                $industry = $analysis['industry'] ?? 'modern work';
                return [
                    'intent' => 'commercial_transactional',
                    'intent_label' => 'Enterprise Workflow Engineering',
                    'tone' => 'visionary, corporate, productivity-focused, tactical',
                    'target_audience' => 'project managers, tech leads, agency owners in ' . $industry,
                    'primary_cta' => 'Optimize Workflow',
                    'focus_keywords' => ['automation', 'efficiency', 'ai integration', 'workflow optimization in ' . $industry, 'agents'],
                ];

            default:
                return [
                    'intent' => 'informational',
                    'intent_label' => 'General Inquiry',
                    'tone' => 'informative, engaging',
                    'target_audience' => 'general searchers',
                    'primary_cta' => 'Read Article',
                    'focus_keywords' => [],
                ];
        }
    }
}
