<template>
    <div
        v-if="open"
        class="fixed inset-0 z-60 flex items-center justify-center bg-black/70 px-4 py-6"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-3xl rounded-[24px] border border-zinc-800 bg-[#171717] p-3 shadow-2xl sm:rounded-[28px] sm:p-6">
            <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-4">
                <div>
                    <p class="text-xl font-semibold text-zinc-100 sm:text-3xl">{{ title }}</p>
                    <p class="mt-1 text-sm text-zinc-500">Share this conversation</p>
                </div>
                <button
                    class="rounded-lg p-2 text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                    type="button"
                    @click="$emit('close')"
                    aria-label="Close share modal"
                >
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                        <path d="m13.41 12 5.29-5.29a1 1 0 1 0-1.41-1.41L12 10.59 6.71 5.3A1 1 0 0 0 5.3 6.71L10.59 12 5.3 17.29a1 1 0 1 0 1.41 1.41L12 13.41l5.29 5.29a1 1 0 0 0 1.41-1.41z"></path>
                    </svg>
                </button>
            </div>

            <div class="mt-4 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-4">
                <p class="text-sm text-zinc-300">Anyone with this link can open and read this chat.</p>
                <div class="mt-3 flex items-center gap-2 rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2">
                    <p class="min-w-0 flex-1 truncate text-xs text-zinc-300">{{ url }}</p>
                    <button
                        class="rounded-md border border-zinc-700 px-2 py-1 text-xs text-zinc-200 hover:bg-zinc-800"
                        type="button"
                        @click="$emit('copy')"
                    >
                        {{ copiedText }}
                    </button>
                </div>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-2 sm:mt-6 sm:gap-3 sm:grid-cols-4">
                <button
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-zinc-800 bg-zinc-900/60 px-3 py-4 text-zinc-200 hover:bg-zinc-800"
                    type="button"
                    @click="$emit('copy')"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-zinc-900">
                        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                            <path d="M3.9 12a5 5 0 0 1 1.46-3.54l3.1-3.1a5 5 0 0 1 7.07 7.07l-1.3 1.3a1 1 0 0 1-1.42-1.41l1.3-1.3a3 3 0 0 0-4.24-4.24l-3.1 3.1a3 3 0 0 0 4.24 4.24l.76-.76a1 1 0 1 1 1.41 1.42l-.76.75A5 5 0 0 1 3.9 12zm6.96 5.66 1.3-1.3a1 1 0 1 1 1.42 1.41l-1.3 1.3a5 5 0 0 1-7.07-7.07l.75-.75a1 1 0 0 1 1.42 1.41l-.76.76a3 3 0 1 0 4.24 4.24z"></path>
                        </svg>
                    </span>
                    <span class="text-sm">Copy link</span>
                </button>

                <button
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-zinc-800 bg-zinc-900/60 px-3 py-4 text-zinc-200 hover:bg-zinc-800"
                    type="button"
                    @click="$emit('share-platform', 'x')"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-xl font-semibold text-zinc-900">X</span>
                    <span class="text-sm">X</span>
                </button>

                <button
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-zinc-800 bg-zinc-900/60 px-3 py-4 text-zinc-200 hover:bg-zinc-800"
                    type="button"
                    @click="$emit('share-platform', 'linkedin')"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-xl font-semibold text-zinc-900">in</span>
                    <span class="text-sm">LinkedIn</span>
                </button>

                <button
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-zinc-800 bg-zinc-900/60 px-3 py-4 text-zinc-200 hover:bg-zinc-800"
                    type="button"
                    @click="$emit('share-platform', 'reddit')"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 text-xl font-semibold text-zinc-900">r/</span>
                    <span class="text-sm">Reddit</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Shared conversation' },
    url: { type: String, default: '' },
    copiedText: { type: String, default: 'Copy' },
});

defineEmits(['close', 'copy', 'share-platform']);
</script>
