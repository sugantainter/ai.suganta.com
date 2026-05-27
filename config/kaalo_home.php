<?php

$www = 'https://www.suganta.com';

return [

    'seo' => [
        'title' => 'Kaalo AI by SuGanta — All Popular Models in One Learning Workspace',
        'description' => 'Kaalo AI is a learning-first workspace for students, teachers, and institutes. Switch between ChatGPT, Gemini, Claude, DeepSeek and more—one sign-in, study-ready answers, and transparent pricing.',
        'keywords' => 'Kaalo AI, SuGanta, AI for students, AI for teachers, multi-model AI chat, ChatGPT Gemini Claude, study assistant India',
        'robots' => 'index, follow',
    ],

    'hero' => [
        'badge' => 'Kaalo AI · Learning-first workspace',
        'title' => 'Kaalo AI — all popular models in one place',
        'subtitle' => 'Move from “stuck on a problem” to a clear explanation in minutes. Ask in plain language, switch models when you want a second opinion, and keep everything focused on studying and teaching—not scattered across five different apps.',
        'models' => ['GPT', 'Gemini', 'Claude', 'DeepSeek', '+More models over time'],
        'cta_primary' => ['label' => 'Open Kaalo AI', 'href' => $www.'/register'],
        'cta_secondary' => ['label' => 'Back to SuGanta Home', 'href' => $www],
    ],

    'highlights' => [
        ['label' => 'Access', 'value' => 'One sign-in flow'],
        ['label' => 'Models', 'value' => 'Switch in seconds'],
        ['label' => 'Built for', 'value' => 'Study & teaching'],
        ['label' => 'Roadmap', 'value' => 'Regular updates'],
    ],

    'trust_stats' => [
        ['value' => '4+', 'label' => 'Premium models unified'],
        ['value' => '1', 'label' => 'Workspace for study & class'],
        ['value' => '₹499', 'label' => 'Starting plan / month'],
        ['value' => '24/7', 'label' => 'Doubt-solving on demand'],
    ],

    'pricing' => [
        'eyebrow' => 'Limited-time value',
        'title' => 'Get premium AI access in one simple plan',
        'subtitle' => 'No need to manage separate model subscriptions. Kaalo AI gives one workspace for students, teachers, and institutes with transparent pricing.',
        'comparison_title' => 'Individual AI subscriptions',
        'comparison_intro' => 'Managing different tools separately can become expensive and hard to track.',
        'competitor_costs' => [
            ['name' => 'ChatGPT Plus', 'price' => '$20/mo'],
            ['name' => 'Gemini Advanced', 'price' => '$20/mo'],
            ['name' => 'Claude Pro', 'price' => '$20/mo'],
            ['name' => 'Perplexity Pro', 'price' => '$20/mo'],
        ],
        'comparison_note' => 'Multiple logins, multiple bills, and no unified education-focused flow.',
        'plans' => [
            [
                'name' => 'Advance',
                'price' => '₹499',
                'period' => '/month',
                'featured' => false,
            ],
            [
                'name' => 'Pro',
                'price' => '₹999',
                'period' => '/month',
                'featured' => true,
                'badge' => 'Most popular',
            ],
            [
                'name' => 'Alrata Pro',
                'price' => '₹1999',
                'period' => '/month',
                'featured' => false,
                'badge' => 'Full power access',
            ],
        ],
        'benefits' => [
            'Access top premium AI models in one place',
            'Side-by-side comparisons for better answers',
            'Built for study, doubt solving, and lesson prep',
            'Regular feature upgrades for education workflows',
        ],
        'cta' => ['label' => 'Get Started with Kaalo AI', 'href' => $www.'/register'],
    ],

    'workflow' => [
        'title' => 'How Kaalo AI fits your day',
        'subtitle' => 'You stay on one page. Pick a model when you need a different style of explanation, then turn answers into notes, steps, or lesson ideas you can actually use in class or at the desk.',
        'steps' => [
            [
                'number' => '1',
                'title' => 'Ask in your own words',
                'body' => 'Paste a question, upload context if the tool allows, or describe where you are stuck—homework, board prep, or a concept you need to teach tomorrow.',
            ],
            [
                'number' => '2',
                'title' => 'Choose the right model',
                'body' => 'Some models shine at step-by-step math, others at concise summaries or long-form reasoning. Switch models to compare explanations and build confidence in the answer.',
            ],
            [
                'number' => '3',
                'title' => 'Turn output into action',
                'body' => 'Copy revision bullets, draft worksheet questions, or outline a lesson flow. Pair AI answers with your teacher on SuGanta when you need human verification.',
            ],
        ],
    ],

    'audiences' => [
        'title' => 'Built for everyone in the learning loop',
        'subtitle' => 'The same workspace adapts to how you learn or how you teach—without losing the thread between subjects and terms.',
        'cards' => [
            [
                'title' => 'Students',
                'items' => [
                    'Break down tough chapters into simpler steps',
                    'Practice questions with hints—not just final answers',
                    'Revision cards before tests and boards',
                ],
            ],
            [
                'title' => 'Teachers & tutors',
                'items' => [
                    'Lesson outlines and explanation variants for mixed batches',
                    'Quick worksheet and quiz drafts you can edit',
                    'Compare model outputs before sharing with students',
                ],
            ],
            [
                'title' => 'Institutes',
                'items' => [
                    'Support staff workflows: FAQs, parent communication drafts',
                    'Content planning across subjects and grades',
                    'Consistent tone when many team members contribute',
                ],
            ],
        ],
    ],

    'features' => [
        'title' => 'Highlighted features',
        'subtitle' => 'Every panel and shortcut is aimed at one outcome: get from doubt to clarity quickly, with answers you can trace, compare, and reuse.',
        'items' => [
            ['title' => 'Multi-model access', 'body' => 'Major models live behind one workflow so you spend time learning—not managing tabs, logins, and disconnected histories.'],
            ['title' => 'Faster doubt solving', 'body' => 'Step-by-step breakdowns for formulas, word problems, and conceptual “why” questions—ideal when you need structure, not a wall of text.'],
            ['title' => 'Study-ready responses', 'body' => 'Summaries, revision bullets, mnemonic ideas, and practice prompts you can drop straight into notes or slides after a quick edit.'],
            ['title' => 'Better decision support', 'body' => 'When answers disagree, you see different reasoning paths—useful for validating tricky physics, chemistry, or proof-style math.'],
            ['title' => 'Every learner', 'body' => 'From grade-school fundamentals to competitive exam crunch time—tone and depth adapt to what you ask for in the prompt.'],
            ['title' => 'Continuous improvements', 'body' => 'Model line-ups, classroom-oriented shortcuts, and safety guardrails evolve over time as we learn from real teaching and study use-cases.'],
        ],
    ],

    'prompts' => [
        'title' => 'Example prompts to try',
        'subtitle' => 'Copy, tweak for your board or syllabus, and run. Add “explain like I’m 14” or “give me only bullet steps” when you want a different format.',
        'examples' => [
            [
                'category' => 'Mathematics',
                'text' => 'I understand quadratic equations in theory but I keep making sign errors when completing the square. Show one full worked example, then give me two similar problems with hints only.',
            ],
            [
                'category' => 'Science',
                'text' => 'Compare photosynthesis and respiration in a table: inputs, outputs, where it happens in the cell, and one real-life analogy for each.',
            ],
            [
                'category' => 'Exam prep',
                'text' => 'I have 45 minutes for English literature revision on three poems. Give a timed plan, key themes per poem, and five short answer questions.',
            ],
            [
                'category' => 'Teaching',
                'text' => 'Draft a 40-minute lesson on fractions for mixed ability: starter, main activity with two difficulty tracks, and a 5-question exit ticket.',
            ],
        ],
    ],

    'trust' => [
        'title' => 'Why learners and educators trust SuGanta',
        'items' => [
            ['title' => 'Built by SuGanta', 'body' => 'Kaalo AI is part of SuGanta’s edtech platform—connecting students, teachers, and institutes on www.suganta.com with verified human support when you need it.'],
            ['title' => 'Education-first design', 'body' => 'Workflows prioritize study, lesson prep, and doubt solving—not generic chat. Prompts and UI patterns are shaped for classrooms and exam seasons.'],
            ['title' => 'Transparent pricing in INR', 'body' => 'Plans are listed clearly in rupees with no hidden per-model billing. Compare what you would pay for separate subscriptions versus one Kaalo AI plan.'],
            ['title' => 'You stay in control', 'body' => 'AI can be wrong. Kaalo AI encourages comparing models, editing outputs, and pairing with a human teacher on SuGanta for high-stakes work.'],
            ['title' => 'Privacy-aware', 'body' => 'Use your SuGanta account with standard platform policies. Review our privacy and safety pages before sharing sensitive student data in prompts.'],
        ],
        'links' => [
            ['label' => 'Privacy Policy', 'href' => $www.'/privacy-and-policies'],
            ['label' => 'Terms of Service', 'href' => $www.'/terms-and-conditions'],
            ['label' => 'Safety Policy', 'href' => $www.'/safety'],
        ],
    ],

    'faqs' => [
        [
            'question' => 'Where does Kaalo AI open?',
            'answer' => 'After you sign in with your SuGanta account, Kaalo AI opens at ai.suganta.com—the same workspace whether you are on laptop or mobile browser. Logged-in users go straight to the chat dashboard.',
        ],
        [
            'question' => 'Is Kaalo AI a replacement for teachers?',
            'answer' => 'No. Kaalo AI helps you think faster, compare explanations, and draft study material. For verified teaching, doubt clearing with a human, and institute workflows, SuGanta connects you to real educators.',
        ],
        [
            'question' => 'Why offer multiple models?',
            'answer' => 'Different models excel at different tasks—logic, concise summaries, long context, or creative phrasing. Switching in seconds lets you cross-check answers instead of trusting a single response.',
        ],
        [
            'question' => 'Can AI be wrong?',
            'answer' => 'Yes. Always review critical steps, especially for exams and graded work. Use side-by-side comparisons in Kaalo AI and consult your teacher when stakes are high.',
        ],
        [
            'question' => 'Do I need a SuGanta account?',
            'answer' => 'Yes. One SuGanta sign-in unlocks Kaalo AI and the broader platform—find teachers, institutes, and learning resources on www.suganta.com.',
        ],
        [
            'question' => 'What models are included today?',
            'answer' => 'Kaalo AI supports leading models including ChatGPT, Gemini, Claude, and DeepSeek, with more education-focused integrations on the roadmap.',
        ],
    ],

];
