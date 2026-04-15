<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <main :class="isChatRoute ? 'h-dvh' : 'mx-auto min-h-dvh max-w-6xl px-4 py-8'">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { RouterView } from 'vue-router';

const route = useRoute();
const isChatRoute = computed(() => {
    return route.name === 'chat.home' || route.name === 'chat.conversation';
});
</script>
