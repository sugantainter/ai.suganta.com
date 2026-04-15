<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <main :class="isFullWidthRoute ? 'h-dvh' : 'mx-auto min-h-dvh max-w-6xl px-4 py-8'">
            <RouterView />
        </main>
        <RouterLink
            to="/contact"
            class="fixed bottom-4 right-4 z-50 rounded-full border border-zinc-700 bg-zinc-900 px-4 py-2 text-xs font-medium text-zinc-100 shadow-lg shadow-black/40 transition hover:bg-zinc-800"
        >
            Contact & Feedback
        </RouterLink>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { RouterLink, RouterView } from 'vue-router';

const route = useRoute();
const isFullWidthRoute = computed(() => {
    return ['chat.home', 'chat.conversation', 'chat.shared', 'settings', 'contact.feedback'].includes(String(route.name ?? ''));
});
</script>
