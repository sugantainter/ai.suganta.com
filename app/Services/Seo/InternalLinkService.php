<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class InternalLinkService
{
    public function __construct(
        protected SlugAnalyzerService $analyzer,
    ) {}

    /**
     * Generate related internal links dynamically based on the current page.
     */
    public function generate(string $slug, array $analysis): array
    {
        $pageType = $analysis['pageType'] ?? 'general';
        $links = [];

        $tools = array_keys(SlugAnalyzerService::TOOLS);
        $professions = array_keys(SlugAnalyzerService::PROFESSIONS);
        $subjects = array_keys(SlugAnalyzerService::SUBJECTS);
        $industries = array_keys(SlugAnalyzerService::INDUSTRIES);

        $seed = crc32($slug);
        srand($seed);

        switch ($pageType) {
            case 'comparison':
                $tool1 = $analysis['tool1_key'];
                $tool2 = $analysis['tool2_key'];
                $otherTools = array_diff($tools, [$tool1, $tool2]);
                $t3 = $this->pickRandom($otherTools);
                $t4 = $this->pickRandom($otherTools, [$t3]);

                $links = array_merge($links, [
                    $this->link("/{$tool1}-vs-claude", "{$this->formatTool($tool1)} vs Claude Comparison"),
                    $this->link("/{$tool2}-vs-claude", "{$this->formatTool($tool2)} vs Claude Comparison"),
                    $this->link('/claude-vs-chatgpt', 'Claude vs ChatGPT Core Review'),
                    $this->link("/alternatives-to-{$tool1}", "Best Alternatives to {$this->formatTool($tool1)}"),
                    $this->link("/alternatives-to-{$tool2}", "Best Alternatives to {$this->formatTool($tool2)}"),
                    $this->link('/best-ai-tools-for-'.$this->pickRandom($professions), 'Best AI Platforms for Professionals'),
                    $this->link('/best-ai-tools-for-learning-'.$this->pickRandom($subjects), 'Top AI Study Tools by Subject'),
                ]);
                break;

            case 'listicle':
                $modifier = Str::slug($analysis['modifier']);
                $modifierType = $analysis['modifier_type'];

                if ($modifierType === 'profession') {
                    $otherProfs = array_diff($professions, [$modifier]);
                    $p1 = $this->pickRandom($otherProfs);
                    $p2 = $this->pickRandom($otherProfs, [$p1]);

                    $links = array_merge($links, [
                        $this->link("/best-ai-tools-for-{$p1}", 'Best AI Tools for '.ucfirst($p1)),
                        $this->link("/best-ai-tools-for-{$p2}", 'Best AI Tools for '.ucfirst($p2)),
                    ]);
                } else {
                    $otherSubjs = array_diff($subjects, [$modifier]);
                    $s1 = $this->pickRandom($otherSubjs);
                    $s2 = $this->pickRandom($otherSubjs, [$s1]);

                    $links = array_merge($links, [
                        $this->link("/best-ai-tools-for-learning-{$s1}", 'Best AI Tools for Learning '.ucfirst($s1)),
                        $this->link("/best-ai-tools-for-learning-{$s2}", 'Best AI Tools for Learning '.ucfirst($s2)),
                    ]);
                }

                $t1 = $this->pickRandom($tools);
                $t2 = $this->pickRandom($tools, [$t1]);
                $ind = $this->pickRandom($industries);

                $links = array_merge($links, [
                    $this->link("/{$t1}-vs-{$t2}", "{$this->formatTool($t1)} vs {$this->formatTool($t2)} Head-to-Head"),
                    $this->link("/alternatives-to-{$t1}", "Best {$this->formatTool($t1)} Competitors"),
                    $this->link("/ai-workflows/{$ind}", 'Automating Workflows in '.ucfirst($ind)),
                ]);
                break;

            case 'alternatives':
                $tool = $analysis['tool_key'];
                $otherTools = array_diff($tools, [$tool]);
                $t1 = $this->pickRandom($otherTools);
                $t2 = $this->pickRandom($otherTools, [$t1]);

                $links = array_merge($links, [
                    $this->link("/{$tool}-vs-{$t1}", "{$this->formatTool($tool)} vs {$this->formatTool($t1)} comparison"),
                    $this->link("/{$tool}-vs-{$t2}", "{$this->formatTool($tool)} vs {$this->formatTool($t2)} comparison"),
                    $this->link("/alternatives-to-{$t1}", "Best Alternatives to {$this->formatTool($t1)}"),
                    $this->link("/alternatives-to-{$t2}", "Best Alternatives to {$this->formatTool($t2)}"),
                    $this->link("/how-to-use-{$tool}", "Getting Started Tutorial for {$this->formatTool($tool)}"),
                ]);
                break;

            case 'education':
            case 'guide':
            case 'workflow':
            default:
                $t1 = $this->pickRandom($tools);
                $t2 = $this->pickRandom($tools, [$t1]);
                $p = $this->pickRandom($professions);
                $s = $this->pickRandom($subjects);
                $ind = $this->pickRandom($industries);

                $links = array_merge($links, [
                    $this->link("/{$t1}-vs-{$t2}", "{$this->formatTool($t1)} vs {$this->formatTool($t2)} head-to-head"),
                    $this->link("/best-ai-tools-for-{$p}", 'Top AI Helpers for '.ucfirst($p)),
                    $this->link("/best-ai-tools-for-learning-{$s}", 'Master '.ucfirst($s).' using AI models'),
                    $this->link("/ai-tools-for-{$s}", 'AI Tools for '.ucfirst($s)),
                    $this->link("/ai-workflows/{$ind}", 'Design Cognitive AI Workflows for '.ucfirst($ind)),
                    $this->link('/ai-agents/coding', 'Building Autonomous AI Coding Agents'),
                    $this->link('/future-of-ai-in-education', 'Future Trends: AI in Modern Classrooms'),
                ]);
                break;
        }

        return array_values(array_filter($links));
    }

    /**
     * Build a link only when the slug resolves to a valid programmatic SEO page.
     *
     * @return array{url: string, title: string}|null
     */
    private function link(string $path, string $title): ?array
    {
        $slug = trim($path, '/');
        if ($slug === '' || ! ($this->analyzer->analyze($slug)['isValid'] ?? false)) {
            return null;
        }

        return [
            'url' => '/'.$slug,
            'title' => $title,
        ];
    }

    private function pickRandom(array $items, array $exclude = []): string
    {
        $filtered = array_values(array_diff($items, $exclude));
        if (empty($filtered)) {
            return $items[0] ?? '';
        }

        return $filtered[rand(0, count($filtered) - 1)];
    }

    private function formatTool(string $key): string
    {
        return SlugAnalyzerService::TOOLS[$key] ?? ucwords(str_replace('-', ' ', $key));
    }
}
