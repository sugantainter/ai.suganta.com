<template>
    <div class="min-h-screen bg-zinc-950 text-zinc-100">
        <header class="border-b border-zinc-800 bg-zinc-900/80 backdrop-blur">
            <div class="mx-auto flex max-w-5xl items-center px-4 py-4">
                <span class="text-lg font-semibold tracking-tight text-white">
                    {{ appName }}
                </span>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-4 py-10">
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { RouterView } from 'vue-router';

const appName = import.meta.env.VITE_APP_NAME ?? 'Laravel';
</script>
