<template>
    <div v-if="canOpen" class="mb-3">
        <button
            class="rounded-lg border border-zinc-700 bg-zinc-900/70 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800 disabled:opacity-60"
            :disabled="loading"
            type="button"
            @click="$emit('open')"
        >
            {{ loading ? 'Loading uploads...' : 'See uploads files' }}
        </button>
    </div>

    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6"
        @click.self="$emit('close')"
    >
        <div class="w-full max-w-2xl rounded-2xl border border-zinc-800 bg-[#171717] p-4 shadow-2xl sm:p-5">
            <div class="mb-3 flex items-center justify-between border-b border-zinc-800 pb-3">
                <p class="text-sm font-semibold uppercase tracking-wide text-zinc-300">Conversation uploads</p>
                <button
                    class="rounded-md px-2 py-1 text-zinc-400 hover:bg-zinc-800 hover:text-zinc-200"
                    type="button"
                    @click="$emit('close')"
                >
                    x
                </button>
            </div>

            <div class="max-h-[60vh] overflow-y-auto pr-1">
                <p v-if="loading" class="py-6 text-center text-sm text-zinc-500">Loading uploads...</p>
                <p v-else-if="!assets.length" class="py-6 text-center text-sm text-zinc-500">No uploads found for this conversation.</p>
                <div v-else class="space-y-2">
                    <div
                        v-for="asset in assets"
                        :key="asset.id"
                        class="flex items-center justify-between rounded-lg border border-zinc-800 bg-zinc-900 px-2 py-1.5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-zinc-200">{{ asset.name }}</p>
                            <p class="truncate text-[11px] text-zinc-500">{{ asset.mime_type || asset.attachment_type }}</p>
                        </div>
                        <div class="ml-3 flex items-center gap-1">
                            <button
                                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                :disabled="assetActionLoadingId === asset.id"
                                @click="$emit('preview', asset)"
                            >
                                Preview
                            </button>
                            <button
                                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                :disabled="assetActionLoadingId === asset.id"
                                @click="$emit('download', asset)"
                            >
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    canOpen: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    assets: { type: Array, default: () => [] },
    assetActionLoadingId: { type: [Number, String, null], default: null },
});

defineEmits(['open', 'close', 'preview', 'download']);
</script>
