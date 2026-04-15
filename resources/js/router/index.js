import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'chat.home',
        component: () => import('../pages/ChatPage.vue'),
        meta: {
            title: 'AI Chat - SuGanta',
            description: 'Chat with multiple AI models from a single fast, secure interface.',
        },
    },
    {
        path: '/c/:conversationId',
        name: 'chat.conversation',
        component: () => import('../pages/ChatPage.vue'),
        meta: {
            title: 'Conversation - SuGanta AI Chat',
            description: 'Continue your AI conversation with full history and uploads.',
        },
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('../pages/SettingsPage.vue'),
        meta: {
            title: 'Settings - SuGanta AI',
            description: 'Manage profile, provider keys, security, and account usage settings.',
        },
    },
    {
        path: '/share/:shareToken',
        name: 'chat.shared',
        component: () => import('../pages/SharedChatPage.vue'),
        meta: {
            title: 'Shared Chat - SuGanta AI',
            description: 'View a shared SuGanta AI conversation without login.',
            keywords: 'shared ai chat, public ai conversation, suganta ai',
            robots: 'index, follow',
            ogType: 'article',
            ogImage: '/logo/favicon.png',
            twitterImage: '/logo/favicon.png',
            twitterCard: 'summary',
        },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

function setMetaTag(selector, attrName, attrValue, content) {
    const tag = document.querySelector(selector);
    if (tag) {
        tag.setAttribute('content', content);
        return;
    }

    const meta = document.createElement('meta');
    meta.setAttribute(attrName, attrValue);
    meta.setAttribute('content', content);
    document.head.appendChild(meta);
}

function setCanonical(url) {
    let canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) {
        canonical = document.createElement('link');
        canonical.setAttribute('rel', 'canonical');
        document.head.appendChild(canonical);
    }
    canonical.setAttribute('href', url);
}

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.afterEach((to) => {
    const title = String(to.meta?.title || 'SuGanta AI - Unified Multi-Model AI Chat');
    const description = String(
        to.meta?.description
            || 'Chat with top AI models in one place using SuGanta AI.'
    );
    const keywords = String(
        to.meta?.keywords
            || 'AI chat, unified AI API, OpenAI, Gemini, Anthropic, multi model chat, SaaS AI platform'
    );
    const robots = String(to.meta?.robots || 'index, follow');
    const ogType = String(to.meta?.ogType || 'website');
    const ogImage = String(to.meta?.ogImage || '/logo/favicon.png');
    const twitterImage = String(to.meta?.twitterImage || '/logo/favicon.png');
    const twitterCard = String(to.meta?.twitterCard || 'summary_large_image');
    const canonicalUrl = `${window.location.origin}${to.fullPath}`;
    const resolvedOgImage = ogImage.startsWith('http') ? ogImage : `${window.location.origin}${ogImage}`;
    const resolvedTwitterImage = twitterImage.startsWith('http') ? twitterImage : `${window.location.origin}${twitterImage}`;

    document.title = title;
    setMetaTag('meta[name="description"]', 'name', 'description', description);
    setMetaTag('meta[name="keywords"]', 'name', 'keywords', keywords);
    setMetaTag('meta[name="robots"]', 'name', 'robots', robots);
    setMetaTag('meta[property="og:type"]', 'property', 'og:type', ogType);
    setMetaTag('meta[property="og:title"]', 'property', 'og:title', title);
    setMetaTag('meta[property="og:description"]', 'property', 'og:description', description);
    setMetaTag('meta[property="og:url"]', 'property', 'og:url', canonicalUrl);
    setMetaTag('meta[property="og:image"]', 'property', 'og:image', resolvedOgImage);
    setMetaTag('meta[name="twitter:card"]', 'name', 'twitter:card', twitterCard);
    setMetaTag('meta[name="twitter:title"]', 'name', 'twitter:title', title);
    setMetaTag('meta[name="twitter:description"]', 'name', 'twitter:description', description);
    setMetaTag('meta[name="twitter:image"]', 'name', 'twitter:image', resolvedTwitterImage);
    setCanonical(canonicalUrl);
});

export default router;
