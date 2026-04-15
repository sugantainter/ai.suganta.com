import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'chat',
        component: () => import('../pages/SpaPage.vue'),
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
