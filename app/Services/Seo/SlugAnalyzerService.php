<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class SlugAnalyzerService
{
    // Pure PHP Keyword Vocabularies
    public const TOOLS = [
        'chatgpt' => 'ChatGPT',
        'gemini' => 'Gemini',
        'claude' => 'Claude',
        'perplexity' => 'Perplexity',
        'copilot' => 'GitHub Copilot',
        'midjourney' => 'Midjourney',
        'cursor-ai' => 'Cursor AI',
        'deepseek' => 'DeepSeek',
        'v0' => 'v0.dev',
        'sora' => 'Sora AI',
        'jasper' => 'Jasper AI',
        'writesonic' => 'Writesonic',
        'grok' => 'Grok',
        'replit-agent' => 'Replit Agent',
        'bolt-new' => 'Bolt.new',
        'lovable' => 'Lovable.dev',
        'dall-e' => 'DALL-E',
        'stable-diffusion' => 'Stable Diffusion',
        'llama' => 'LLaMA',
        'mistral' => 'Mistral AI',
    ];

    public const PROFESSIONS = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'developers' => 'Developers',
        'marketers' => 'Marketers',
        'designers' => 'Designers',
        'writers' => 'Writers',
        'researchers' => 'Researchers',
        'engineers' => 'Engineers',
        'analysts' => 'Analysts',
        'educators' => 'Educators',
        'professors' => 'Professors',
        'academic-writers' => 'Academic Writers',
    ];

    public const SUBJECTS = [
        'python' => 'Python Programming',
        'javascript' => 'JavaScript',
        'react' => 'React',
        'laravel' => 'Laravel',
        'php' => 'PHP',
        'math' => 'Mathematics',
        'science' => 'Science',
        'physics' => 'Physics',
        'chemistry' => 'Chemistry',
        'biology' => 'Biology',
        'history' => 'History',
        'english' => 'English',
        'calculus' => 'Calculus',
        'linear-algebra' => 'Linear Algebra',
        'statistics' => 'Statistics',
        'data-science' => 'Data Science',
        'machine-learning' => 'Machine Learning',
        'jee' => 'JEE Entrance Exam',
        'neet' => 'NEET Exam',
        'sat' => 'SAT',
        'gre' => 'GRE',
        'gate' => 'GATE',
    ];

    public const INDUSTRIES = [
        'education' => 'Education',
        'seo' => 'SEO',
        'marketing' => 'Marketing',
        'coding' => 'Coding',
        'finance' => 'Finance',
        'healthcare' => 'Healthcare',
        'law' => 'Law',
        'design' => 'Design',
        'writing' => 'Writing',
    ];

    /**
     * Analyze slug and detect page type, entities, and attributes.
     */
    public function analyze(string $slug): array
    {
        $slug = trim(strtolower($slug), '/');

        // Route exclusions (sanity check for known static client-side routes)
        $excluded = ['settings', 'contact', 'feedback', 'share', 'login', 'register', 'dashboard', 'chat', 'admin', 'profile'];
        if (in_array(explode('/', $slug)[0], $excluded)) {
            return ['isValid' => false];
        }

        // 1. Check for workflows/agents with subfolders: (e.g. ai-workflows/education, ai-agents/coding)
        if (preg_match('/^(ai-workflows|ai-agents)\/(.+)$/', $slug, $matches)) {
            $subfolder = $matches[1];
            $target = $matches[2];
            
            $industry = $this->resolveEntity($target, self::INDUSTRIES);
            $profession = $this->resolveEntity($target, self::PROFESSIONS);
            
            return [
                'isValid' => true,
                'pageType' => 'workflow',
                'subfolder' => $subfolder,
                'raw_target' => $target,
                'industry' => $industry ?? $target,
                'profession' => $profession,
                'variables' => [
                    'type' => $subfolder === 'ai-workflows' ? 'Workflow' : 'Agent',
                    'target' => $industry ?? $profession ?? ucfirst($target)
                ]
            ];
        }

        // 2. Comparisons: {tool1}-vs-{tool2} or {tool1}-vs-{tool2}-for-{profession/subject}
        if (preg_match('/^([a-z0-9\-]+)-vs-([a-z0-9\-]+)(?:-for-([a-z0-9\-]+))?$/', $slug, $matches)) {
            $tool1Key = $matches[1];
            $tool2Key = $matches[2];
            $modifierKey = $matches[3] ?? null;

            $tool1 = $this->resolveEntity($tool1Key, self::TOOLS);
            $tool2 = $this->resolveEntity($tool2Key, self::TOOLS);

            // Ensure we are comparing actual tools (or at least one is known to prevent false matches)
            if ($tool1 || $tool2) {
                $tool1Name = $tool1 ?? $this->formatName($tool1Key);
                $tool2Name = $tool2 ?? $this->formatName($tool2Key);
                
                $profession = $modifierKey ? $this->resolveEntity($modifierKey, self::PROFESSIONS) : null;
                $subject = $modifierKey ? $this->resolveEntity($modifierKey, self::SUBJECTS) : null;

                return [
                    'isValid' => true,
                    'pageType' => 'comparison',
                    'tool1' => $tool1Name,
                    'tool1_key' => $tool1Key,
                    'tool2' => $tool2Name,
                    'tool2_key' => $tool2Key,
                    'modifier' => $profession ?? $subject ?? ($modifierKey ? $this->formatName($modifierKey) : null),
                    'modifier_type' => $profession ? 'profession' : ($subject ? 'subject' : null),
                    'variables' => [
                        'tool1' => $tool1Name,
                        'tool2' => $tool2Name,
                        'modifier' => $profession ?? $subject
                    ]
                ];
            }
        }

        // 3. Alternatives: alternatives-to-{tool} or {tool}-alternatives
        if (preg_match('/^alternatives-to-([a-z0-9\-]+)$/', $slug, $matches) || preg_match('/^([a-z0-9\-]+)-alternatives$/', $slug, $matches)) {
            $toolKey = $matches[1];
            $tool = $this->resolveEntity($toolKey, self::TOOLS);

            if ($tool) {
                return [
                    'isValid' => true,
                    'pageType' => 'alternatives',
                    'tool' => $tool,
                    'tool_key' => $toolKey,
                    'variables' => [
                        'tool' => $tool
                    ]
                ];
            }
        }

        // 4. Listicles: best-ai-tools-for-{profession} or best-ai-tools-for-learning-{subject} or top-{industry}-ai-tools
        if (preg_match('/^best-ai-tools-for-learning-([a-z0-9\-]+)$/', $slug, $matches)) {
            $subjectKey = $matches[1];
            $subject = $this->resolveEntity($subjectKey, self::SUBJECTS);

            if ($subject) {
                return [
                    'isValid' => true,
                    'pageType' => 'listicle',
                    'modifier' => $subject,
                    'modifier_type' => 'subject',
                    'variables' => [
                        'modifier' => $subject,
                        'category' => 'Learning'
                    ]
                ];
            }
        }

        if (preg_match('/^best-ai-tools-for-([a-z0-9\-]+)$/', $slug, $matches)) {
            $professionKey = $matches[1];
            $profession = $this->resolveEntity($professionKey, self::PROFESSIONS);

            if ($profession) {
                return [
                    'isValid' => true,
                    'pageType' => 'listicle',
                    'modifier' => $profession,
                    'modifier_type' => 'profession',
                    'variables' => [
                        'modifier' => $profession,
                        'category' => 'Productivity'
                    ]
                ];
            }
        }

        if (preg_match('/^top-([a-z0-9\-]+)-ai-tools(?:-in-[0-9]+)?$/', $slug, $matches)) {
            $industryKey = $matches[1];
            $industry = $this->resolveEntity($industryKey, self::INDUSTRIES);

            if ($industry) {
                return [
                    'isValid' => true,
                    'pageType' => 'listicle',
                    'modifier' => $industry,
                    'modifier_type' => 'industry',
                    'variables' => [
                        'modifier' => $industry,
                        'category' => 'Industry'
                    ]
                ];
            }
        }

        // 5. Education/Topic: ai-tools-for-{subject} or free-ai-tools-for-{profession/subject}
        if (preg_match('/^ai-tools-for-([a-z0-9\-]+)$/', $slug, $matches) || preg_match('/^free-ai-tools-for-([a-z0-9\-]+)$/', $slug, $matches)) {
            $targetKey = $matches[1];
            $subject = $this->resolveEntity($targetKey, self::SUBJECTS);
            $profession = $this->resolveEntity($targetKey, self::PROFESSIONS);

            if ($subject || $profession) {
                return [
                    'isValid' => true,
                    'pageType' => 'education',
                    'subject' => $subject,
                    'profession' => $profession,
                    'target_key' => $targetKey,
                    'is_free' => str_contains($slug, 'free-'),
                    'variables' => [
                        'target' => $subject ?? $profession
                    ]
                ];
            }
        }

        // 6. How-To / Guides: how-to-use-{tool}-for-{subject/profession} or how-to-use-{tool} or guide-to-{topic}
        if (preg_match('/^how-to-use-([a-z0-9\-]+)-for-([a-z0-9\-]+)$/', $slug, $matches)) {
            $toolKey = $matches[1];
            $targetKey = $matches[2];
            
            $tool = $this->resolveEntity($toolKey, self::TOOLS);
            $subject = $this->resolveEntity($targetKey, self::SUBJECTS);
            $profession = $this->resolveEntity($targetKey, self::PROFESSIONS);

            if ($tool && ($subject || $profession)) {
                return [
                    'isValid' => true,
                    'pageType' => 'guide',
                    'tool' => $tool,
                    'tool_key' => $toolKey,
                    'modifier' => $subject ?? $profession,
                    'modifier_type' => $subject ? 'subject' : 'profession',
                    'variables' => [
                        'tool' => $tool,
                        'modifier' => $subject ?? $profession
                    ]
                ];
            }
        }

        if (preg_match('/^how-to-use-([a-z0-9\-]+)$/', $slug, $matches)) {
            $toolKey = $matches[1];
            $tool = $this->resolveEntity($toolKey, self::TOOLS);

            if ($tool) {
                return [
                    'isValid' => true,
                    'pageType' => 'guide',
                    'tool' => $tool,
                    'tool_key' => $toolKey,
                    'variables' => [
                        'tool' => $tool
                    ]
                ];
            }
        }

        if (preg_match('/^guide-to-([a-z0-9\-]+)$/', $slug, $matches)) {
            $topicKey = $matches[1];
            $tool = $this->resolveEntity($topicKey, self::TOOLS);
            $subject = $this->resolveEntity($topicKey, self::SUBJECTS);
            
            return [
                'isValid' => true,
                'pageType' => 'guide',
                'topic' => $tool ?? $subject ?? $this->formatName($topicKey),
                'topic_key' => $topicKey,
                'variables' => [
                    'topic' => $tool ?? $subject ?? $this->formatName($topicKey)
                ]
            ];
        }

        // 7. Future/Trends: future-of-ai-in-([a-z0-9\-]+)
        if (preg_match('/^future-of-ai-in-([a-z0-9\-]+)$/', $slug, $matches)) {
            $industryKey = $matches[1];
            $industry = $this->resolveEntity($industryKey, self::INDUSTRIES);
            $subject = $this->resolveEntity($industryKey, self::SUBJECTS);

            if ($industry || $subject) {
                return [
                    'isValid' => true,
                    'pageType' => 'workflow',
                    'industry' => $industry ?? $subject,
                    'variables' => [
                        'type' => 'Future Trend',
                        'target' => $industry ?? $subject
                    ]
                ];
            }
        }

        return ['isValid' => false];
    }

    /**
     * Resolve a slug key to its display name from a vocabulary list.
     */
    private function resolveEntity(string $key, array $vocab): ?string
    {
        return $vocab[$key] ?? $vocab[Str::slug($key)] ?? null;
    }

    /**
     * Fallback formatter for naming things.
     */
    private function formatName(string $key): string
    {
        return ucwords(str_replace('-', ' ', $key));
    }
}
