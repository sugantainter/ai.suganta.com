<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <header class="sticky top-0 z-40 border-b border-zinc-800/80 bg-zinc-950/80 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-center gap-3">
                    <a
                        href="https://www.suganta.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-xl border border-zinc-800 bg-zinc-900/80 p-1.5 shadow-lg shadow-black/30"
                    >
                        <img
                            src="/logo/Su250.png"
                            alt="SuGanta"
                            class="h-7 w-auto rounded-md"
                        >
                    </a>
                </div>
                <nav class="flex items-center rounded-xl border border-zinc-800 bg-zinc-900/70 p-1">
                    <RouterLink
                        to="/"
                        class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-zinc-300 transition hover:text-white"
                        active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                    >
                        Chat
                    </RouterLink>
                    <RouterLink
                        to="/settings"
                        class="rounded-lg px-3.5 py-1.5 text-sm font-medium text-zinc-300 transition hover:text-white"
                        active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                    >
                        Settings
                    </RouterLink>
                </nav>
            </div>
        </header>
        <main :class="isChatRoute ? 'h-[calc(100dvh-73px)]' : 'mx-auto max-w-6xl px-4 py-8'">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { RouterLink, RouterView } from 'vue-router';

const route = useRoute();
const isChatRoute = computed(() => {
    return route.name === 'chat.home' || route.name === 'chat.conversation';
});
</script>
