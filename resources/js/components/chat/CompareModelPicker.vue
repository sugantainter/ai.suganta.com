<template>
    <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-3">
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <p class="text-xs font-medium text-zinc-200">Select models to compare</p>
            <div class="ml-auto flex flex-wrap gap-1">
                <input
                    v-model="compareSearch"
                    type="search"
                    placeholder="Filter models..."
                    class="h-7 rounded-md border border-zinc-700 bg-zinc-900 px-2 text-[11px] text-zinc-200 placeholder:text-zinc-500 focus:border-zinc-500 focus:outline-none"
                >
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
                    Clear
                </button>
            </div>
        </div>

        <div class="max-h-36 space-y-1 overflow-y-auto pr-1">
            <label
                v-for="item in filteredCompareOptions"
                :key="`compare-picker-${item.model}`"
                class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-xs text-zinc-300 hover:bg-zinc-800/80"
            >
                <input
                    type="checkbox"
                    class="h-3.5 w-3.5 rounded border-zinc-600 bg-zinc-900 text-blue-500 focus:ring-0"
                    :checked="selectedCompareModels.includes(item.model)"
                    @change="toggleCompareModel(item.model)"
                >
                <span class="truncate">{{ item.display_name }}</span>
            </label>
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
</script>
