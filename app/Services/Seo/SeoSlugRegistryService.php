<?php

namespace App\Services\Seo;

class SeoSlugRegistryService
{
    public function __construct(
        protected SlugAnalyzerService $analyzer,
    ) {}

    /**
     * All programmatic SEO slugs that pass SlugAnalyzer validation.
     *
     * @return list<string>
     */
    public function allValidSlugs(): array
    {
        $candidates = array_unique(array_merge(
            $this->workflowSlugs(),
            $this->comparisonSlugs(),
            $this->alternativeSlugs(),
            $this->listicleSlugs(),
            $this->educationSlugs(),
            $this->guideSlugs(),
            $this->futureSlugs(),
        ));

        sort($candidates);

        return array_values(array_filter($candidates, function (string $slug): bool {
            return ($this->analyzer->analyze($slug)['isValid'] ?? false) === true;
        }));
    }

    /**
     * @return list<array{slug: string, pageType: string}>
     */
    public function allValidEntries(): array
    {
        $entries = [];

        foreach ($this->allValidSlugs() as $slug) {
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
