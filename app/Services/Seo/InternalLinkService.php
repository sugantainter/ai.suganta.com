<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class InternalLinkService
{
    /**
     * Generate related internal links dynamically based on the current page.
     */
    public function generate(string $slug, array $analysis): array
    {
        $pageType = $analysis['pageType'] ?? 'general';
        $links = [];

        // Vocabulary references
        $tools = array_keys(SlugAnalyzerService::TOOLS);
        $professions = array_keys(SlugAnalyzerService::PROFESSIONS);
        $subjects = array_keys(SlugAnalyzerService::SUBJECTS);
        $industries = array_keys(SlugAnalyzerService::INDUSTRIES);

        // Seed deterministic random to ensure links are stable per slug
        $seed = crc32($slug);
        srand($seed);

        switch ($pageType) {
            case 'comparison':
                $tool1 = $analysis['tool1_key'];
                $tool2 = $analysis['tool2_key'];
                
                // Add comparisons with other tools
                $otherTools = array_diff($tools, [$tool1, $tool2]);
                $t3 = $this->pickRandom($otherTools);
                $t4 = $this->pickRandom($otherTools, [$t3]);

                $links[] = [
                    'url' => "/{$tool1}-vs-claude",
                    'title' => "{$this->formatTool($tool1)} vs Claude Comparison"
                ];
                $links[] = [
                    'url' => "/{$tool2}-vs-claude",
                    'title' => "{$this->formatTool($tool2)} vs Claude Comparison"
                ];
                $links[] = [
                    'url' => "/claude-vs-chatgpt",
                    'title' => "Claude vs ChatGPT Core Review"
                ];

                // Alternatives
                $links[] = [
                    'url' => "/alternatives-to-{$tool1}",
                    'title' => "Best Alternatives to {$this->formatTool($tool1)}"
                ];
                $links[] = [
                    'url' => "/alternatives-to-{$tool2}",
                    'title' => "Best Alternatives to {$this->formatTool($tool2)}"
                ];

                // Lists
                $prof = $this->pickRandom($professions);
                $links[] = [
                    'url' => "/best-ai-tools-for-{$prof}",
                    'title' => "Best AI Platforms for " . ucfirst($prof)
                ];

                // Education
                $subj = $this->pickRandom($subjects);
                $links[] = [
                    'url' => "/ai-tools-for-learning-{$subj}",
                    'title' => "Top AI Study Tools for Learning " . ucfirst($subj)
                ];
                break;

            case 'listicle':
                $modifier = Str::slug($analysis['modifier']);
                $modifierType = $analysis['modifier_type'];

                // Add alternative listicles
                if ($modifierType === 'profession') {
                    $otherProfs = array_diff($professions, [$modifier]);
                    $p1 = $this->pickRandom($otherProfs);
                    $p2 = $this->pickRandom($otherProfs, [$p1]);

                    $links[] = [
                        'url' => "/best-ai-tools-for-{$p1}",
                        'title' => "Best AI Tools for " . ucfirst($p1)
                    ];
                    $links[] = [
                        'url' => "/best-ai-tools-for-{$p2}",
                        'title' => "Best AI Tools for " . ucfirst($p2)
                    ];
                } else {
                    $otherSubjs = array_diff($subjects, [$modifier]);
                    $s1 = $this->pickRandom($otherSubjs);
                    $s2 = $this->pickRandom($otherSubjs, [$s1]);

                    $links[] = [
                        'url' => "/best-ai-tools-for-learning-{$s1}",
                        'title' => "Best AI Tools for Learning " . ucfirst($s1)
                    ];
                    $links[] = [
                        'url' => "/best-ai-tools-for-learning-{$s2}",
                        'title' => "Best AI Tools for Learning " . ucfirst($s2)
                    ];
                }

                // Add comparisons & alternatives
                $t1 = $this->pickRandom($tools);
                $t2 = $this->pickRandom($tools, [$t1]);

                $links[] = [
                    'url' => "/{$t1}-vs-{$t2}",
                    'title' => "{$this->formatTool($t1)} vs {$this->formatTool($t2)} Head-to-Head"
                ];
                $links[] = [
                    'url' => "/alternatives-to-{$t1}",
                    'title' => "Best {$this->formatTool($t1)} Competitors"
                ];

                // Workflows
                $ind = $this->pickRandom($industries);
                $links[] = [
                    'url' => "/ai-workflows/{$ind}",
                    'title' => "Automating Workflows in " . ucfirst($ind)
                ];
                break;

            case 'alternatives':
                $tool = $analysis['tool_key'];

                // Related comparisons
                $otherTools = array_diff($tools, [$tool]);
                $t1 = $this->pickRandom($otherTools);
                $t2 = $this->pickRandom($otherTools, [$t1]);

                $links[] = [
                    'url' => "/{$tool}-vs-{$t1}",
                    'title' => "{$this->formatTool($tool)} vs {$this->formatTool($t1)} comparison"
                ];
                $links[] = [
                    'url' => "/{$tool}-vs-{$t2}",
                    'title' => "{$this->formatTool($tool)} vs {$this->formatTool($t2)} comparison"
                ];

                // Other alternatives
                $links[] = [
                    'url' => "/alternatives-to-{$t1}",
                    'title' => "Best Alternatives to {$this->formatTool($t1)}"
                ];
                $links[] = [
                    'url' => "/alternatives-to-{$t2}",
                    'title' => "Best Alternatives to {$this->formatTool($t2)}"
                ];

                // Guides
                $links[] = [
                    'url' => "/how-to-use-{$tool}",
                    'title' => "Getting Started Tutorial for {$this->formatTool($tool)}"
                ];
                break;

            case 'education':
            case 'guide':
            case 'workflow':
            default:
                // Mix of general links
                $t1 = $this->pickRandom($tools);
                $t2 = $this->pickRandom($tools, [$t1]);
                $p = $this->pickRandom($professions);
                $s = $this->pickRandom($subjects);
                $ind = $this->pickRandom($industries);

                $links[] = [
                    'url' => "/{$t1}-vs-{$t2}",
                    'title' => "{$this->formatTool($t1)} vs {$this->formatTool($t2)} head-to-head"
                ];
                $links[] = [
                    'url' => "/best-ai-tools-for-{$p}",
                    'title' => "Top AI Helpers for " . ucfirst($p)
                ];
                $links[] = [
                    'url' => "/ai-tools-for-learning-{$s}",
                    'title' => "Master " . ucfirst($s) . " using AI models"
                ];
                $links[] = [
                    'url' => "/ai-workflows/{$ind}",
                    'title' => "Design Cognitive AI Workflows for " . ucfirst($ind)
                ];
                $links[] = [
                    'url' => "/ai-agents/coding",
                    'title' => "Building Autonomous AI Coding Agents"
                ];
                $links[] = [
                    'url' => "/future-of-ai-in-education",
                    'title' => "Future Trends: AI in Modern Classrooms"
                ];
                break;
        }

        return $links;
    }

    /**
     * Pick a random item from an array, excluding optional items.
     */
    private function pickRandom(array $items, array $exclude = []): string
    {
        $filtered = array_values(array_diff($items, $exclude));
        if (empty($filtered)) {
            return $items[0] ?? '';
        }
        return $filtered[rand(0, count($filtered) - 1)];
    }

    /**
     * Formatter
     */
    private function formatTool(string $key): string
    {
        return SlugAnalyzerService::TOOLS[$key] ?? ucwords(str_replace('-', ' ', $key));
    }
}
