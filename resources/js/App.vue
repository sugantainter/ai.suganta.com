<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <header class="border-b border-zinc-800 bg-[#171717]">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <div class="flex items-center gap-3">
                <img
                    src="/logo/Su250.png"
                    alt="SuGanta"
                    class="h-8 w-auto rounded-sm"
                >
                </div>
                <nav class="flex items-center gap-2">
                    <RouterLink
                        to="/"
                        class="rounded-lg px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        active-class="bg-zinc-800 text-white"
                    >
                        Chat
                    </RouterLink>
                    <RouterLink
                        to="/settings"
                        class="rounded-lg px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        active-class="bg-zinc-800 text-white"
                    >
                        Settings
                    </RouterLink>
                </nav>
            </div>
        </header>
        <main :class="isChatRoute ? 'h-[calc(100dvh-57px)]' : 'mx-auto max-w-6xl px-4 py-8'">
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
