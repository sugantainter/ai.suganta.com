<template>
    <div class="shrink-0 border-b border-zinc-800/90 bg-[#212121] px-3 py-2">
        <div v-if="isSharedView" class="flex items-center justify-between gap-2">
            <div>
                <p class="text-sm font-semibold text-zinc-100">Shared conversation</p>
                <p class="text-xs text-zinc-500">View only mode</p>
            </div>
            <a
                href="/"
                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
            >
                Login
            </a>
        </div>

        <div v-else class="space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <select
                    :value="modelValue"
                    :disabled="modelOptions.length === 0 || compareMode"
                    class="min-h-9 min-w-0 flex-1 rounded-xl border border-zinc-700/90 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-zinc-500 sm:max-w-md"
                    @change="$emit('update:modelValue', $event.target.value)"
                >
                    <option v-for="item in modelOptions" :key="item.model" :value="item.model">
                        {{ item.display_name }}
                    </option>
                </select>
                <p class="order-last hidden w-full text-[11px] text-zinc-500 sm:order-0 sm:ml-auto sm:block sm:w-auto sm:truncate sm:max-w-40 md:max-w-56">
                    {{ statusText }}
                </p>
                <button
                    type="button"
                    class="inline-flex min-h-9 items-center justify-center rounded-xl border border-zinc-700/90 bg-zinc-900/80 px-3 text-xs font-medium text-zinc-200 hover:bg-zinc-800 disabled:opacity-50"
                    :disabled="shareLoading || !canShare"
                    @click="$emit('share')"
                >
                    {{ shareLoading ? '…' : 'Share' }}
                </button>
                <button
                    type="button"
                    class="inline-flex min-h-9 items-center justify-center rounded-xl border border-zinc-700/90 bg-zinc-900/80 px-3 text-xs font-medium text-zinc-200 hover:bg-zinc-800"
                    :aria-expanded="advancedOpen"
                    aria-controls="chat-advanced-controls"
                    @click="advancedOpen = !advancedOpen"
                >
                    {{ advancedOpen ? 'Hide' : 'Options' }}
                </button>
            </div>
            <p class="w-full text-[11px] leading-tight text-zinc-500 sm:hidden">
                {{ statusText }}
            </p>

            <div
                v-show="advancedOpen"
                id="chat-advanced-controls"
                class="flex flex-wrap items-center gap-2 rounded-xl border border-zinc-800/80 bg-zinc-900/30 px-2 py-2"
            >
                <button
                    class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 md:hidden"
                    type="button"
                    @click="$emit('open-search')"
                >
                    Search
                </button>
                <button
                    class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                    type="button"
                    @click="$emit('update:compareMode', !compareMode)"
                >
                    {{ compareMode ? 'Compare on' : 'Compare off' }}
                </button>
                <select
                    :value="capabilityFilter"
                    class="min-h-9 rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500"
                    @change="$emit('update:capabilityFilter', $event.target.value)"
                >
                    <option value="all">Models: all</option>
                    <option value="vision">Vision</option>
                    <option value="reasoning">Reasoning</option>
                    <option value="web_search">Web search</option>
                    <option value="tools">Tools</option>
                </select>
                <select
                    :value="responseStyle"
                    class="min-h-9 rounded-lg border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-xs text-zinc-200 outline-none focus:border-zinc-500"
                    @change="$emit('update:responseStyle', $event.target.value)"
                >
                    <option value="concise">Tone: concise</option>
                    <option value="balanced">Tone: balanced</option>
                    <option value="detailed">Tone: detailed</option>
                </select>
            </div>

            <div
                v-if="compareMode"
                ref="comparePanelRef"
                class="space-y-2"
            >
                <div
                    class="cursor-pointer rounded-lg border border-zinc-800 bg-zinc-900/30 px-3 py-2 transition hover:border-zinc-700"
                    @click="openComparePicker"
                >
                    <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-medium text-zinc-300">
                        Compare mode active
                    </p>
                    <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-2 py-0.5 text-[11px] text-blue-200">
                        {{ compareModelsCount }} model{{ compareModelsCount === 1 ? '' : 's' }} selected
                    </span>
                    <span
                        v-if="compareModelsCount < 2"
                        class="rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[11px] text-amber-200"
                    >
                        Select at least 2 models
                    </span>
                    <button
                        type="button"
                        class="ml-auto rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1 text-[11px] text-zinc-200 hover:bg-zinc-800"
                        @click.stop="comparePickerExpanded = !comparePickerExpanded"
                    >
                        {{ comparePickerExpanded ? 'Hide settings' : 'Configure models' }}
                    </button>
                </div>
                    <p class="mt-2 text-[11px] text-zinc-500">
                        Click here to {{ comparePickerExpanded ? 'keep it open and edit models' : 'open model settings' }}.
                    </p>
                </div>
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-1 scale-[0.99] opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 scale-100 opacity-100"
                    leave-to-class="translate-y-1 scale-[0.99] opacity-0"
                >
                    <CompareModelPicker
                        v-if="comparePickerExpanded"
                        :compare-models="compareModels"
                        :model-options="modelOptions"
                        @update:compare-models="$emit('update:compareModels', $event)"
                    />
                </Transition>
            </div>
        </div>

        <p v-if="modelErrorMessage" class="mt-2 text-xs text-red-400">
            {{ modelErrorMessage }}
        </p>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CompareModelPicker from './CompareModelPicker.vue';

const props = defineProps({
    isSharedView: { type: Boolean, default: false },
    modelValue: { type: String, default: '' },
    compareMode: { type: Boolean, default: false },
    compareModels: { type: Array, default: () => [] },
    capabilityFilter: { type: String, default: 'all' },
    responseStyle: { type: String, default: 'balanced' },
    modelOptions: { type: Array, default: () => [] },
    statusText: { type: String, default: 'Ready' },
    modelErrorMessage: { type: String, default: '' },
    shareLoading: { type: Boolean, default: false },
    canShare: { type: Boolean, default: false },
});

const emit = defineEmits([
    'update:modelValue',
    'update:compareMode',
    'update:compareModels',
    'update:capabilityFilter',
    'update:responseStyle',
    'open-search',
    'share',
]);

const comparePickerExpanded = ref(false);
const comparePanelRef = ref(null);
const advancedOpen = ref(false);
const compareModelsCount = computed(() => (
    Array.isArray(props.compareModels)
        ? props.compareModels.filter((item) => String(item || '').trim() !== '').length
        : 0
));

watch(() => props.compareMode, (enabled) => {
    if (!enabled) {
        comparePickerExpanded.value = false;
        return;
    }

    // Auto-open config when compare mode has less than 2 models selected.
    comparePickerExpanded.value = compareModelsCount.value < 2;
}, { immediate: true });

function openComparePicker() {
    comparePickerExpanded.value = true;
}

function handleClickOutsideComparePanel(event) {
    if (!comparePickerExpanded.value) {
        return;
    }

    const panelElement = comparePanelRef.value;
    if (!panelElement) {
        return;
    }

    const target = event.target;
    if (target instanceof Node && panelElement.contains(target)) {
        return;
    }

    comparePickerExpanded.value = false;
}

onMounted(() => {
    if (typeof document !== 'undefined') {
        document.addEventListener('mousedown', handleClickOutsideComparePanel);
    }
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.removeEventListener('mousedown', handleClickOutsideComparePanel);
    }
});
</script>
