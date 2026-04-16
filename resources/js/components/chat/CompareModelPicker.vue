<template>
    <div class="rounded-2xl border border-zinc-800 bg-linear-to-br from-zinc-900/70 to-zinc-950/70 p-3 shadow-inner shadow-black/20">
        <div class="mb-3 flex flex-wrap items-start gap-2">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-zinc-100">Compare Models</p>
                <p class="text-[11px] text-zinc-400">Select at least 2 models for side-by-side responses.</p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-2 py-0.5 text-[11px] font-medium text-blue-200">
                    {{ selectedCompareModels.length }} selected
                </span>
                <input
                    v-model="compareSearch"
                    type="search"
                    placeholder="Search models..."
                    class="h-8 min-w-[150px] rounded-lg border border-zinc-700 bg-zinc-900/90 px-2.5 text-xs text-zinc-200 placeholder:text-zinc-500 focus:border-blue-500/70 focus:outline-none"
                >
                <button
                    v-if="compareSearch"
                    type="button"
                    class="h-8 rounded-lg border border-zinc-700 px-2 text-[11px] text-zinc-300 hover:bg-zinc-800"
                    @click="compareSearch = ''"
                >
                    Clear search
                </button>
            </div>
        </div>

        <div class="mb-2 flex flex-wrap gap-1.5">
            <button
                type="button"
                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                :disabled="filteredCompareOptions.length === 0"
                @click="selectTopModels(3)"
            >
                Top 3
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                :disabled="reasoningOptions.length === 0"
                @click="selectPreset('reasoning')"
            >
                Reasoning
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                :disabled="visionOptions.length === 0"
                @click="selectPreset('vision')"
            >
                Vision
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                :disabled="filteredCompareOptions.length === 0"
                @click="selectAllModels"
            >
                Select all
            </button>
            <button
                type="button"
                class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                :disabled="selectedCompareModels.length === 0"
                @click="emit('update:compareModels', [])"
            >
                Clear selected
            </button>
        </div>

        <div class="mb-2 flex items-center justify-between text-[11px] text-zinc-400">
            <span>{{ filteredCompareOptions.length }} results</span>
            <span v-if="selectedCompareModels.length < 2" class="text-amber-300">Choose 2+ models to compare</span>
            <span v-else class="text-emerald-300">Ready for comparison</span>
        </div>

        <div class="max-h-52 space-y-1 overflow-y-auto pr-1">
            <label
                v-for="item in sortedCompareOptions"
                :key="`compare-picker-${item.model}`"
                class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-zinc-300 transition hover:bg-zinc-800/80"
            >
                <input
                    type="checkbox"
                    class="h-3.5 w-3.5 rounded border-zinc-600 bg-zinc-900 text-blue-500 focus:ring-0"
                    :checked="selectedCompareModels.includes(item.model)"
                    @change="toggleCompareModel(item.model)"
                >
                <span class="truncate">{{ item.display_name }}</span>
                <span
                    v-if="item.supports_reasoning"
                    class="rounded border border-violet-500/40 bg-violet-500/10 px-1 py-0.5 text-[10px] text-violet-200"
                >
                    reasoning
                </span>
                <span
                    v-if="item.provider"
                    class="ml-auto rounded border border-zinc-700 bg-zinc-800/70 px-1.5 py-0.5 text-[10px] text-zinc-400"
                >
                    {{ item.provider }}
                </span>
            </label>
            <p v-if="filteredCompareOptions.length === 0" class="px-2 py-1 text-xs text-zinc-500">
                No models match your filter.
            </p>
        </div>

        <div class="mt-3 flex flex-wrap gap-1.5">
            <span
                v-for="item in selectedCompareDisplayModels"
                :key="`selected-compare-${item.model}`"
                class="inline-flex items-center gap-1 rounded-full border border-blue-500/40 bg-blue-500/10 px-2 py-0.5 text-[11px] text-blue-200"
            >
                <span class="max-w-[140px] truncate">{{ item.display_name }}</span>
                <button
                    type="button"
                    class="rounded-full px-1 text-blue-200 hover:bg-blue-500/20"
                    @click="removeCompareModel(item.model)"
                >
                    x
                </button>
            </span>
            <span v-if="selectedCompareDisplayModels.length === 0" class="text-[11px] text-zinc-500">
                No models selected yet.
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    compareModels: { type: Array, default: () => [] },
    modelOptions: { type: Array, default: () => [] },
});

const emit = defineEmits([
    'update:compareModels',
]);

const compareSearch = ref('');

const selectedCompareModels = computed(() => (
    Array.isArray(props.compareModels)
        ? props.compareModels.map((item) => String(item || '')).filter((item) => item !== '')
        : []
));

const selectedCompareDisplayModels = computed(() => {
    const optionMap = new Map(
        (Array.isArray(props.modelOptions) ? props.modelOptions : []).map((item) => [String(item.model || ''), item])
    );

    return selectedCompareModels.value
        .map((modelKey) => optionMap.get(modelKey))
        .filter((item) => item && String(item.model || '') !== '');
});

const filteredCompareOptions = computed(() => {
    const all = Array.isArray(props.modelOptions) ? props.modelOptions : [];
    const term = compareSearch.value.trim().toLowerCase();
    if (term === '') {
        return all;
    }
    return all.filter((item) => {
        const name = String(item.display_name || item.model || '').toLowerCase();
        return name.includes(term);
    });
});

const reasoningOptions = computed(() => filteredCompareOptions.value.filter((item) => item?.supports_reasoning === true));
const visionOptions = computed(() => filteredCompareOptions.value.filter((item) => item?.supports_vision === true));
const sortedCompareOptions = computed(() => {
    return [...filteredCompareOptions.value].sort((a, b) => {
        const aSelected = selectedCompareModels.value.includes(String(a?.model || '')) ? 1 : 0;
        const bSelected = selectedCompareModels.value.includes(String(b?.model || '')) ? 1 : 0;
        if (aSelected !== bSelected) {
            return bSelected - aSelected;
        }
        return String(a?.display_name || a?.model || '').localeCompare(String(b?.display_name || b?.model || ''));
    });
});

function toggleCompareModel(modelKey) {
    const normalized = String(modelKey || '');
    if (normalized === '') {
        return;
    }

    const next = [...selectedCompareModels.value];
    const existingIndex = next.indexOf(normalized);
    if (existingIndex >= 0) {
        next.splice(existingIndex, 1);
    } else {
        next.push(normalized);
    }

    emit('update:compareModels', next);
}

function removeCompareModel(modelKey) {
    const normalized = String(modelKey || '');
    if (normalized === '') {
        return;
    }

    emit('update:compareModels', selectedCompareModels.value.filter((item) => item !== normalized));
}

function selectAllModels() {
    const all = filteredCompareOptions.value.map((item) => String(item.model || '')).filter((key) => key !== '');
    emit('update:compareModels', Array.from(new Set(all)));
}

function selectTopModels(limit) {
    const max = Number.isFinite(limit) && limit > 0 ? limit : 3;
    const all = filteredCompareOptions.value
        .slice(0, max)
        .map((item) => String(item.model || ''))
        .filter((key) => key !== '');

    if (all.length === 0) {
        return;
    }

    emit('update:compareModels', Array.from(new Set(all)));
}

function selectPreset(type) {
    const source = type === 'reasoning' ? reasoningOptions.value : visionOptions.value;
    const all = source.map((item) => String(item.model || '')).filter((key) => key !== '');
    if (all.length === 0) {
        return;
    }
    emit('update:compareModels', Array.from(new Set(all)));
}
</script>
