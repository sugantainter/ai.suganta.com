import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/:pathMatch(.*)*',
        name: 'app',
        component: () => import('../pages/SpaPage.vue'),
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
