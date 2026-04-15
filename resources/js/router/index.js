import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'chat.home',
        component: () => import('../pages/ChatPage.vue'),
    },
    {
        path: '/c/:conversationId',
        name: 'chat.conversation',
        component: () => import('../pages/ChatPage.vue'),
    },
    {
        path: '/settings',
        name: 'settings',
        component: () => import('../pages/SettingsPage.vue'),
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
