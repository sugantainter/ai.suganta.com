<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4">
                <div class="flex items-center gap-3">
                <img
                    src="/logo/Su250.png"
                    alt="SuGanta"
                    class="h-10 w-auto rounded-sm"
                >
                <span class="text-lg font-semibold tracking-tight text-white">{{ appName }}</span>
                </div>
                <nav class="flex items-center gap-2">
                    <RouterLink
                        to="/"
                        class="rounded-md px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        active-class="bg-zinc-800 text-white"
                    >
                        Chat
                    </RouterLink>
                    <RouterLink
                        to="/settings"
                        class="rounded-md px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white"
                        active-class="bg-zinc-800 text-white"
                    >
                        Settings
                    </RouterLink>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router';

const appName = import.meta.env.VITE_APP_NAME ?? 'Laravel';
</script>
