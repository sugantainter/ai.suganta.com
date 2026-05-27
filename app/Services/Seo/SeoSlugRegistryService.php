<?php

namespace App\Services\Seo;

class SeoSlugRegistryService
{
    public function __construct(
        protected SlugAnalyzerService $analyzer,
    ) {}

    /**
     * Lazy Generator to yield all 1M+ dynamic SEO slugs without memory overhead.
     *
     * @return \Generator<string>
     */
    public function getSlugsGenerator(): \Generator
    {
        // 1. workflows
        foreach (['ai-workflows', 'ai-agents'] as $prefix) {
            foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $key) {
                yield "{$prefix}/{$key}";
            }
            foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $key) {
                yield "{$prefix}/{$key}";
            }
        }

        // 2. alternatives
        foreach (array_keys(SlugAnalyzerService::TOOLS) as $tool) {
            yield "alternatives-to-{$tool}";
            yield "{$tool}-alternatives";
        }

        // 3. listicles
        $year = (int) config('seo.sitemap.listicle_year_suffix', (int) date('Y'));
        foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
            yield "best-ai-tools-for-{$profession}";
        }
        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            yield "best-ai-tools-for-learning-{$subject}";
        }
        foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $industry) {
            yield "top-{$industry}-ai-tools";
            yield "top-{$industry}-ai-tools-in-{$year}";
        }

        // 4. education
        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            yield "ai-tools-for-{$subject}";
            yield "free-ai-tools-for-{$subject}";
        }
        foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
            yield "ai-tools-for-{$profession}";
            yield "free-ai-tools-for-{$profession}";
        }

        // 5. future
        foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $industry) {
            yield "future-of-ai-in-{$industry}";
        }
        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            yield "future-of-ai-in-{$subject}";
        }

        // 6. guides
        foreach (array_keys(SlugAnalyzerService::TOOLS) as $tool) {
            yield "how-to-use-{$tool}";
            foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
                yield "how-to-use-{$tool}-for-{$subject}";
            }
            foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
                yield "how-to-use-{$tool}-for-{$profession}";
            }
            yield "guide-to-{$tool}";
        }
        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            yield "guide-to-{$subject}";
        }

        // 7. comparisons
        $tools = array_keys(SlugAnalyzerService::TOOLS);
        $professions = array_keys(SlugAnalyzerService::PROFESSIONS);
        $subjects = array_keys(SlugAnalyzerService::SUBJECTS);
        foreach ($tools as $tool1) {
            foreach ($tools as $tool2) {
                if ($tool1 === $tool2) {
                    continue;
                }
                yield "{$tool1}-vs-{$tool2}";
                foreach ($professions as $profession) {
                    yield "{$tool1}-vs-{$tool2}-for-{$profession}";
                }
                foreach ($subjects as $subject) {
                    yield "{$tool1}-vs-{$tool2}-for-{$subject}";
                }
            }
        }
    }

    /**
     * All programmatic SEO slugs that pass SlugAnalyzer validation.
     *
     * @return list<string>
     */
    public function allValidSlugs(int $limit = 20000): array
    {
        $slugs = [];
        foreach ($this->getSlugsGenerator() as $slug) {
            $slugs[] = $slug;
            if (count($slugs) >= $limit) {
                break;
            }
        }
        return $slugs;
    }

    /**
     * @return list<array{slug: string, pageType: string}>
     */
    public function allValidEntries(int $limit = 20000): array
    {
        $entries = [];

        foreach ($this->allValidSlugs($limit) as $slug) {
            $analysis = $this->analyzer->analyze($slug);
            $entries[] = [
                'slug' => $slug,
                'pageType' => $analysis['pageType'] ?? 'general',
            ];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    protected function workflowSlugs(): array
    {
        $slugs = [];

        foreach (['ai-workflows', 'ai-agents'] as $prefix) {
            foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $key) {
                $slugs[] = "{$prefix}/{$key}";
            }
            foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $key) {
                $slugs[] = "{$prefix}/{$key}";
            }
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function comparisonSlugs(): array
    {
        $tools = array_keys(SlugAnalyzerService::TOOLS);
        $professions = array_keys(SlugAnalyzerService::PROFESSIONS);
        $subjects = array_keys(SlugAnalyzerService::SUBJECTS);
        $slugs = [];

        foreach ($tools as $tool1) {
            foreach ($tools as $tool2) {
                if ($tool1 === $tool2) {
                    continue;
                }

                $slugs[] = "{$tool1}-vs-{$tool2}";

                foreach ($professions as $profession) {
                    $slugs[] = "{$tool1}-vs-{$tool2}-for-{$profession}";
                }

                foreach ($subjects as $subject) {
                    $slugs[] = "{$tool1}-vs-{$tool2}-for-{$subject}";
                }
            }
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function alternativeSlugs(): array
    {
        $slugs = [];

        foreach (array_keys(SlugAnalyzerService::TOOLS) as $tool) {
            $slugs[] = "alternatives-to-{$tool}";
            $slugs[] = "{$tool}-alternatives";
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function listicleSlugs(): array
    {
        $slugs = [];
        $year = (int) config('seo.sitemap.listicle_year_suffix', (int) date('Y'));

        foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
            $slugs[] = "best-ai-tools-for-{$profession}";
        }

        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            $slugs[] = "best-ai-tools-for-learning-{$subject}";
        }

        foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $industry) {
            $slugs[] = "top-{$industry}-ai-tools";
            $slugs[] = "top-{$industry}-ai-tools-in-{$year}";
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function educationSlugs(): array
    {
        $slugs = [];

        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            $slugs[] = "ai-tools-for-{$subject}";
            $slugs[] = "free-ai-tools-for-{$subject}";
        }

        foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
            $slugs[] = "ai-tools-for-{$profession}";
            $slugs[] = "free-ai-tools-for-{$profession}";
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function guideSlugs(): array
    {
        $slugs = [];

        foreach (array_keys(SlugAnalyzerService::TOOLS) as $tool) {
            $slugs[] = "how-to-use-{$tool}";

            foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
                $slugs[] = "how-to-use-{$tool}-for-{$subject}";
            }

            foreach (array_keys(SlugAnalyzerService::PROFESSIONS) as $profession) {
                $slugs[] = "how-to-use-{$tool}-for-{$profession}";
            }

            $slugs[] = "guide-to-{$tool}";
        }

        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            $slugs[] = "guide-to-{$subject}";
        }

        return $slugs;
    }

    /**
     * @return list<string>
     */
    protected function futureSlugs(): array
    {
        $slugs = [];

        foreach (array_keys(SlugAnalyzerService::INDUSTRIES) as $industry) {
            $slugs[] = "future-of-ai-in-{$industry}";
        }

        foreach (array_keys(SlugAnalyzerService::SUBJECTS) as $subject) {
            $slugs[] = "future-of-ai-in-{$subject}";
        }

        return $slugs;
    }
}
