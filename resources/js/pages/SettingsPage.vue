<template>
    <div class="h-dvh overflow-hidden bg-[#0f0f0f] text-zinc-100">
        <div class="grid h-full min-h-0 md:grid-cols-[260px_1fr]">
            <aside class="hidden h-full min-h-0 flex-col border-r border-zinc-800 bg-[#171717] md:flex">
                <div class="shrink-0 space-y-3 border-b border-zinc-800 p-3">
                    <a
                        href="https://www.suganta.com"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-center justify-center rounded-xl border border-zinc-800 bg-zinc-900/80 p-2 shadow-lg shadow-black/30"
                    >
                        <img
                            src="/logo/Su250.png"
                            alt="SuGanta"
                            class="h-8 w-auto rounded-md"
                        >
                    </a>
                    <div class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900/70 p-1">
                        <RouterLink
                            to="/"
                            class="w-full rounded-md px-3 py-1.5 text-center text-xs font-medium text-zinc-300 transition hover:text-white"
                            active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                        >
                            Chat
                        </RouterLink>
                        <RouterLink
                            to="/settings"
                            class="w-full rounded-md px-3 py-1.5 text-center text-xs font-medium text-zinc-300 transition hover:text-white"
                            active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                        >
                            Settings
                        </RouterLink>
                        <RouterLink
                            to="/contact"
                            class="w-full rounded-md px-3 py-1.5 text-center text-xs font-medium text-zinc-300 transition hover:text-white"
                            active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                        >
                            Contact
                        </RouterLink>
                    </div>
                </div>
                <div class="hide-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-2">
                    <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-500">Settings</p>
                    <nav class="space-y-1">
                    <button
                        v-for="item in menuItems"
                        :key="item.id"
                        class="w-full rounded-lg px-3 py-2 text-left text-sm transition"
                        :class="activeSection === item.id
                            ? 'bg-zinc-800 text-white'
                            : 'text-zinc-300 hover:bg-zinc-800/70'"
                        type="button"
                        @click="scrollToSection(item.id)"
                    >
                        {{ item.label }}
                    </button>
                    </nav>
                    <p class="mt-4 px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-500">Quick links</p>
                    <div class="space-y-1">
                        <a
                            href="https://app.suganta.com/profile"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-zinc-800/70 hover:text-white"
                        >
                            Profile
                        </a>
                        <a
                            href="https://app.suganta.com/subscriptions?s_type=3"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-zinc-800/70 hover:text-white"
                        >
                            Plan & Subscription
                        </a>
                        <a
                            href="https://app.suganta.com/support-tickets"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-zinc-800/70 hover:text-white"
                        >
                            Support
                        </a>
                        <RouterLink
                            to="/contact"
                            class="block rounded-lg px-3 py-2 text-sm text-zinc-300 transition hover:bg-zinc-800/70 hover:text-white"
                        >
                            Contact & Feedback
                        </RouterLink>
                    </div>
                </div>
                <div class="shrink-0 border-t border-zinc-800 px-3 py-3 text-xs text-zinc-500">
                    {{ overview.usage?.total_tokens ?? 0 }} / {{ overview.usage?.token_limit ?? 10000 }} tokens
                </div>
            </aside>

            <section class="flex h-full min-h-0 flex-col overflow-hidden bg-[#212121]">
                <div class="sticky top-0 z-30 shrink-0 border-b border-zinc-800 bg-[#212121] px-4 py-3">
                    <div class="mb-2 flex items-center justify-between gap-2 md:hidden">
                        <a
                            href="https://www.suganta.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-lg border border-zinc-800 bg-zinc-900/80 p-1.5"
                        >
                            <img
                                src="/logo/Su250.png"
                                alt="SuGanta"
                                class="h-6 w-auto rounded-md"
                            >
                        </a>
                        <div class="flex items-center rounded-lg border border-zinc-800 bg-zinc-900/70 p-1">
                            <RouterLink
                                to="/"
                                class="rounded-md px-3 py-1 text-xs font-medium text-zinc-300"
                                active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                            >
                                Chat
                            </RouterLink>
                            <RouterLink
                                to="/settings"
                                class="rounded-md px-3 py-1 text-xs font-medium text-zinc-300"
                                active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                            >
                                Settings
                            </RouterLink>
                            <RouterLink
                                to="/contact"
                                class="rounded-md px-3 py-1 text-xs font-medium text-zinc-300"
                                active-class="bg-zinc-800 text-white shadow-sm shadow-black/40"
                            >
                                Contact
                            </RouterLink>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-medium text-zinc-200">Settings</p>
                        <p class="hidden text-xs text-zinc-500 md:block">{{ statusText }}</p>
                        <div class="flex items-center gap-2">
                            <a
                                href="https://app.suganta.com/profile"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-zinc-600 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                            >
                                Profile
                            </a>
                            <a
                                href="https://app.suganta.com/subscriptions?s_type=3"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-200 hover:bg-emerald-500/20"
                            >
                                Plan & Subscription
                            </a>
                            <a
                                href="https://app.suganta.com/support-tickets"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-lg border border-zinc-600 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                            >
                                Support
                            </a>
                            <RouterLink
                                to="/"
                                class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 md:hidden"
                            >
                                Back to chat
                            </RouterLink>
                        </div>
                    </div>
                </div>
                <div ref="settingsScrollRef" class="hide-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-3 md:px-5 md:py-5">
                    <div class="space-y-4">
                <div class="hide-scrollbar -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 md:hidden">
                    <button
                        v-for="item in menuItems"
                        :key="`mobile-${item.id}`"
                        class="whitespace-nowrap rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200"
                        type="button"
                        @click="scrollToSection(item.id)"
                    >
                        {{ item.label }}
                    </button>
                </div>
                <div id="settings-section-general" class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <img
                            v-if="displayUser.avatar"
                            :src="displayUser.avatar"
                            alt="Profile"
                            class="h-14 w-14 rounded-full border border-zinc-700 object-cover"
                        >
                        <div>
                            <p class="text-lg font-semibold text-white">{{ displayUser.name || 'User' }}</p>
                            <p class="text-sm text-zinc-400">{{ displayUser.email || 'No email available' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 text-sm text-zinc-300 sm:grid-cols-2">
                        <p><span class="text-zinc-500">Tenant ID:</span> {{ overview.tenant_id ?? '-' }}</p>
                        <p><span class="text-zinc-500">Role:</span> {{ displayUser.role ?? '-' }}</p>
                        <p><span class="text-zinc-500">Phone:</span> {{ displayUser.phone ?? '-' }}</p>
                        <p><span class="text-zinc-500">Profile completion:</span> {{ displayUser.completion_percentage ?? 0 }}%</p>
                    </div>
                    <div class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-3">
                        <p class="text-sm text-zinc-300">Need help or want to share product feedback?</p>
                        <RouterLink
                            to="/contact"
                            class="mt-2 inline-flex rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                        >
                            Open Contact & Feedback
                        </RouterLink>
                    </div>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <div id="settings-section-usage" class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                        <h2 class="text-base font-semibold text-white">Usage</h2>
                        <p class="mt-1 text-sm text-zinc-400">Token usage and model access overview.</p>
                        <div class="mt-4 rounded-xl border border-zinc-800 bg-zinc-900/50 p-4">
                            <p class="text-sm text-zinc-400">Total tokens</p>
                            <p class="mt-1 text-3xl font-semibold text-white">{{ overview.usage?.total_tokens ?? 0 }}</p>
                            <div class="mt-3 space-y-1 text-xs text-zinc-500">
                                <p>Limit: {{ overview.usage?.token_limit ?? 10000 }}</p>
                                <p>Remaining: {{ overview.usage?.remaining_tokens ?? 10000 }}</p>
                                <p>Active models: {{ overview.active_models_count ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <div id="settings-section-api-keys" class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                        <h2 class="text-base font-semibold text-white">Provider API keys</h2>
                        <p class="mt-1 text-sm text-zinc-400">Store your own provider keys securely.</p>

                        <div class="mt-4 space-y-3">
                            <label class="text-xs text-zinc-400">Provider</label>
                            <select
                                v-model="provider"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                            >
                                <option v-for="providerName in providerOptions" :key="providerName" :value="providerName">
                                    {{ providerName }}
                                </option>
                            </select>

                            <label class="text-xs text-zinc-400">API key</label>
                            <input
                                v-model="providerApiKey"
                                type="password"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                                placeholder="Paste provider API key"
                            />

                            <button
                                class="w-full rounded-lg bg-white px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 disabled:opacity-60"
                                :disabled="saving || !provider || !providerApiKey.trim()"
                                @click="saveProviderKey"
                            >
                                {{ saving ? 'Saving...' : 'Save key' }}
                            </button>
                            <p class="text-xs text-zinc-500">{{ statusText }}</p>
                        </div>
                    </div>
                </div>

                <div id="settings-section-uploads" class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-white">Uploads</h2>
                            <p class="mt-1 text-sm text-zinc-400">All files and images uploaded in your conversations.</p>
                        </div>
                        <button
                            class="rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-xs text-zinc-200 hover:bg-zinc-800"
                            type="button"
                            :disabled="uploadsLoading"
                            @click="loadUploads"
                        >
                            {{ uploadsLoading ? 'Refreshing...' : 'Refresh' }}
                        </button>
                    </div>

                    <div class="mb-3 grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input
                            v-model.trim="uploadsSearch"
                            type="text"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                            placeholder="Search uploads by file name or type..."
                        />
                        <select
                            v-model="uploadsTypeFilter"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500 sm:w-44"
                        >
                            <option value="all">All types</option>
                            <option value="image">Images</option>
                            <option value="doc">Documents</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>

                    <div class="mb-3 flex flex-wrap gap-2">
                        <button
                            v-for="chip in uploadTypeChips"
                            :key="`upload-chip-${chip.value}`"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs transition"
                            :class="uploadsTypeFilter === chip.value
                                ? 'border-zinc-500 bg-zinc-800 text-white'
                                : 'border-zinc-700 bg-zinc-900 text-zinc-300 hover:bg-zinc-800'"
                            @click="uploadsTypeFilter = chip.value"
                        >
                            {{ chip.label }} ({{ chip.count }})
                        </button>
                    </div>

                    <div class="space-y-2">
                        <p v-if="uploadsLoading" class="py-6 text-center text-sm text-zinc-500">Loading uploads...</p>
                        <p v-else-if="!filteredUploads.length" class="py-6 text-center text-sm text-zinc-500">
                            {{ userUploads.length ? 'No uploads match your filters.' : 'No uploads found yet.' }}
                        </p>
                        <div
                            v-for="asset in filteredUploads"
                            :key="`settings-upload-${asset.id}`"
                            class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-200">{{ asset.name }}</p>
                                <p class="truncate text-xs text-zinc-500">
                                    {{ asset.mime_type || asset.attachment_type }} · Conversation #{{ asset.conversation_id }}
                                </p>
                            </div>
                            <div class="ml-2 flex items-center gap-1">
                                <button
                                    class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                    :disabled="uploadAssetActionLoadingId === asset.id"
                                    @click="openUploadAsset(asset, false)"
                                >
                                    Preview
                                </button>
                                <button
                                    class="rounded-md border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                    :disabled="uploadAssetActionLoadingId === asset.id"
                                    @click="openUploadAsset(asset, true)"
                                >
                                    Download
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="settings-section-security" class="grid gap-4 xl:grid-cols-2">
                    <div class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                        <h2 class="text-base font-semibold text-white">Saved key status</h2>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="item in providerKeys"
                                :key="item.provider"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-800 bg-zinc-900/50 px-3 py-2 text-sm"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-zinc-300">{{ item.provider }}</span>
                                    <span :class="item.has_custom_key ? 'text-emerald-400' : 'text-zinc-500'">
                                        {{ item.has_custom_key ? 'Saved' : 'Not set' }}
                                    </span>
                                </div>
                                <button
                                    v-if="item.has_custom_key"
                                    class="rounded-md border border-red-500/40 px-2 py-1 text-xs text-red-300 hover:bg-red-500/10 disabled:opacity-60"
                                    :disabled="removingProvider === item.provider"
                                    @click="removeProviderKey(item.provider)"
                                >
                                    {{ removingProvider === item.provider ? 'Removing...' : 'Remove' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-zinc-800 bg-[#171717] p-4 sm:p-5">
                        <h2 class="text-base font-semibold text-white">Update password</h2>
                        <p class="mt-1 text-sm text-zinc-400">Use a strong password with at least 8 characters.</p>
                        <div class="mt-4 space-y-3">
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                                placeholder="Current password"
                            />
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                                placeholder="New password"
                            />
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                                placeholder="Confirm new password"
                            />
                            <button
                                class="w-full rounded-lg bg-white px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-zinc-100 disabled:opacity-60"
                                :disabled="passwordSaving || !canUpdatePassword"
                                @click="updatePassword"
                            >
                                {{ passwordSaving ? 'Updating...' : 'Update password' }}
                            </button>
                            <p class="text-xs text-zinc-500">{{ passwordStatusText }}</p>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { showErrorAlert } from '../utils/alerts';

const overview = ref({});
const providerKeys = ref([]);
const provider = ref('');
const providerApiKey = ref('');
const saving = ref(false);
const removingProvider = ref('');
const statusText = ref('Ready');
const activeSection = ref('general');
const passwordSaving = ref(false);
const passwordStatusText = ref('Password strength: min 8 chars, include mix of character types.');
const uploadsLoading = ref(false);
const userUploads = ref([]);
const uploadAssetActionLoadingId = ref(null);
const uploadsSearch = ref('');
const uploadsTypeFilter = ref('all');
const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const settingsScrollRef = ref(null);

const menuItems = [
    { id: 'general', label: 'General' },
    { id: 'usage', label: 'Usage' },
    { id: 'uploads', label: 'Uploads' },
    { id: 'api-keys', label: 'API Keys' },
    { id: 'security', label: 'Security' },
];

const displayUser = computed(() => {
    const direct = overview.value?.auth_user_display ?? {};
    const authUser = overview.value?.auth_user ?? {};
    const profileRoot = overview.value?.profile ?? {};
    const profileUser = profileRoot?.user ?? {};
    const profileDetails = profileRoot?.profile ?? {};

    const firstName = (authUser.first_name ?? profileDetails.first_name ?? '').toString().trim();
    const lastName = (authUser.last_name ?? profileDetails.last_name ?? '').toString().trim();
    const derivedName = `${firstName} ${lastName}`.trim();

    return {
        id: direct.id ?? authUser.id ?? authUser.user_id ?? profileUser.id ?? null,
        name: direct.name ?? authUser.name ?? profileUser.name ?? (derivedName || null),
        email: direct.email ?? authUser.email ?? profileUser.email ?? null,
        phone: direct.phone
            ?? authUser.phone
            ?? profileDetails.phone_primary
            ?? profileDetails.principal_phone
            ?? profileDetails.parent_phone
            ?? profileDetails.phone_secondary
            ?? null,
        role: direct.role ?? authUser.role ?? profileUser.role ?? null,
        avatar: direct.avatar ?? profileRoot.profile_image_url ?? authUser.avatar ?? authUser.profile_image ?? null,
        completion_percentage: Number(direct.completion_percentage ?? profileRoot.completion_percentage ?? 0),
    };
});

const providerOptions = computed(() => (providerKeys.value ?? []).map((item) => item.provider));

const canUpdatePassword = computed(() => {
    return Boolean(passwordForm.value.current_password && passwordForm.value.password && passwordForm.value.password_confirmation);
});

function isAssetOfType(asset, type) {
    const mimeType = String(asset?.mime_type ?? '').toLowerCase();
    const attachmentType = String(asset?.attachment_type ?? '').toLowerCase();
    const fileName = String(asset?.name ?? '').toLowerCase();
    const typeDescriptor = `${mimeType} ${attachmentType}`;

    if (type === 'image') {
        return typeDescriptor.includes('image');
    }

    if (type === 'doc') {
        return attachmentType.includes('document')
            || mimeType.includes('text/')
            || mimeType.includes('word')
            || mimeType.includes('officedocument')
            || fileName.endsWith('.doc')
            || fileName.endsWith('.docx')
            || fileName.endsWith('.txt')
            || fileName.endsWith('.md')
            || fileName.endsWith('.csv')
            || fileName.endsWith('.json');
    }

    if (type === 'pdf') {
        return mimeType.includes('pdf') || fileName.endsWith('.pdf');
    }

    return true;
}

const uploadsMatchingSearch = computed(() => {
    const normalizedSearch = uploadsSearch.value.toLowerCase().trim();

    if (!normalizedSearch) {
        return userUploads.value ?? [];
    }

    return (userUploads.value ?? []).filter((asset) => {
        const mimeType = String(asset?.mime_type ?? '').toLowerCase();
        const attachmentType = String(asset?.attachment_type ?? '').toLowerCase();
        const fileName = String(asset?.name ?? '').toLowerCase();
        const typeDescriptor = `${mimeType} ${attachmentType}`;

        return fileName.includes(normalizedSearch) || typeDescriptor.includes(normalizedSearch);
    });
});

const uploadTypeChips = computed(() => {
    const uploads = uploadsMatchingSearch.value ?? [];
    const images = uploads.filter((asset) => isAssetOfType(asset, 'image')).length;
    const docs = uploads.filter((asset) => isAssetOfType(asset, 'doc')).length;
    const pdf = uploads.filter((asset) => isAssetOfType(asset, 'pdf')).length;

    return [
        { value: 'all', label: 'All', count: uploads.length },
        { value: 'image', label: 'Images', count: images },
        { value: 'doc', label: 'Documents', count: docs },
        { value: 'pdf', label: 'PDF', count: pdf },
    ];
});

const filteredUploads = computed(() => {
    return (uploadsMatchingSearch.value ?? []).filter((asset) => isAssetOfType(asset, uploadsTypeFilter.value));
});

async function parseApiResponse(response) {
    const rawText = await response.text();
    if (rawText.trim() === '') {
        return {};
    }

    try {
        return JSON.parse(rawText);
    } catch {
        if (response.ok) {
            return { message: rawText };
        }
        throw new Error(rawText || `Request failed: ${response.status}`);
    }
}

async function apiRequest(path, options = {}) {
    const response = await fetch(path, {
        credentials: 'include',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(options.headers ?? {}),
        },
    });

    const data = await parseApiResponse(response);
    if (!response.ok) {
        throw new Error(data?.message || `Request failed: ${response.status}`);
    }

    return data;
}

async function loadOverview() {
    const data = await apiRequest('/api/v1/settings/overview');
    overview.value = data ?? {};
    providerKeys.value = data.provider_keys ?? [];
    if (!provider.value) {
        provider.value = providerOptions.value[0] ?? '';
    }
}

async function loadProviderKeys() {
    const data = await apiRequest('/api/v1/provider-keys');
    providerKeys.value = data.provider_keys ?? [];

    if (!providerOptions.value.includes(provider.value)) {
        provider.value = providerOptions.value[0] ?? '';
    }
}

async function loadUploads() {
    uploadsLoading.value = true;
    try {
        const data = await apiRequest('/api/v1/settings/uploads?limit=200&page=1');
        userUploads.value = data.uploads ?? [];
    } catch (error) {
        userUploads.value = [];
        showErrorAlert(error.message || 'Failed to load uploads', 'Uploads load failed');
    } finally {
        uploadsLoading.value = false;
    }
}

async function openUploadAsset(asset, forceDownload = false) {
    const conversationId = Number(asset?.conversation_id ?? 0);
    const assetId = Number(asset?.id ?? 0);
    if (!conversationId || !assetId) {
        return;
    }

    uploadAssetActionLoadingId.value = assetId;
    try {
        const data = await apiRequest(`/api/v1/chat/history/${conversationId}/assets/${assetId}/signed-url?expires_minutes=10`);
        const url = String(data.signed_url ?? '');
        if (!url) {
            throw new Error('Unable to generate secure file URL.');
        }

        if (forceDownload) {
            const link = document.createElement('a');
            link.href = url;
            link.download = asset.name || 'download';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    } catch (error) {
        showErrorAlert(error.message || 'Failed to open upload asset.', 'Upload access failed');
    } finally {
        uploadAssetActionLoadingId.value = null;
    }
}

async function saveProviderKey() {
    if (!provider.value || !providerApiKey.value.trim()) {
        return;
    }

    saving.value = true;
    try {
        await apiRequest('/api/v1/provider-keys', {
            method: 'POST',
            body: JSON.stringify({
                provider: provider.value,
                api_key: providerApiKey.value.trim(),
                is_active: true,
            }),
        });
        providerApiKey.value = '';
        await loadProviderKeys();
        statusText.value = `Saved key for ${provider.value}`;
    } catch (error) {
        statusText.value = error.message || 'Failed to save provider key';
        showErrorAlert(statusText.value, 'Provider key save failed');
    } finally {
        saving.value = false;
    }
}

async function removeProviderKey(providerName) {
    if (!providerName) {
        return;
    }

    removingProvider.value = providerName;
    try {
        const data = await apiRequest(`/api/v1/provider-keys/${encodeURIComponent(providerName)}`, {
            method: 'DELETE',
        });
        statusText.value = data.message || `Removed key for ${providerName}`;
        await loadProviderKeys();
    } catch (error) {
        statusText.value = error.message || `Failed to remove key for ${providerName}`;
        showErrorAlert(statusText.value, 'Provider key remove failed');
    } finally {
        removingProvider.value = '';
    }
}

async function updatePassword() {
    if (!canUpdatePassword.value) {
        return;
    }

    passwordSaving.value = true;
    try {
        const data = await apiRequest('/api/v1/settings/password', {
            method: 'PUT',
            body: JSON.stringify(passwordForm.value),
        });

        passwordForm.value = {
            current_password: '',
            password: '',
            password_confirmation: '',
        };
        passwordStatusText.value = data.message || 'Password updated successfully.';
    } catch (error) {
        passwordStatusText.value = error.message || 'Failed to update password';
        showErrorAlert(passwordStatusText.value, 'Password update failed');
    } finally {
        passwordSaving.value = false;
    }
}

function scrollToSection(sectionId) {
    const element = document.getElementById(`settings-section-${sectionId}`);
    if (!element) {
        return;
    }

    activeSection.value = sectionId;
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
}

function updateActiveSectionFromViewport() {
    let bestSectionId = activeSection.value;
    let bestDistance = Number.POSITIVE_INFINITY;

    for (const item of menuItems) {
        const element = document.getElementById(`settings-section-${item.id}`);
        if (!element) {
            continue;
        }

        const rect = element.getBoundingClientRect();
        const distance = Math.abs(rect.top - 120);
        if (distance < bestDistance) {
            bestDistance = distance;
            bestSectionId = item.id;
        }
    }

    activeSection.value = bestSectionId;
}

onMounted(async () => {
    statusText.value = 'Loading...';
    try {
        await loadOverview();
        await loadUploads();
        statusText.value = 'Ready';
    } catch (error) {
        statusText.value = error.message || 'Failed to load settings';
        showErrorAlert(statusText.value, 'Settings load failed');
    }

    settingsScrollRef.value?.addEventListener('scroll', updateActiveSectionFromViewport, { passive: true });
    updateActiveSectionFromViewport();
});

onBeforeUnmount(() => {
    settingsScrollRef.value?.removeEventListener('scroll', updateActiveSectionFromViewport);
});
</script>
