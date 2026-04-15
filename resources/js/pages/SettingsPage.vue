<template>
    <div class="grid min-h-[80vh] gap-4 lg:grid-cols-[1fr_1fr]">
        <section class="rounded-2xl border border-zinc-800/80 bg-linear-to-b from-zinc-900/90 to-zinc-950/90 p-5 shadow-xl shadow-black/30">
            <h2 class="text-lg font-semibold text-white">Account Settings</h2>
            <p class="mt-1 text-sm text-zinc-400">Manage your API credentials and account usage.</p>

            <div class="mt-5 space-y-3 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="text-sm font-semibold text-zinc-200">Authenticated User</h3>
                <div v-if="overview.auth_user_display?.avatar" class="pb-1">
                    <img
                        :src="overview.auth_user_display.avatar"
                        alt="Profile"
                        class="h-14 w-14 rounded-full border border-zinc-700 object-cover"
                    >
                </div>
                <div class="text-sm text-zinc-300">
                    <p><span class="text-zinc-500">Tenant ID:</span> {{ overview.tenant_id ?? '-' }}</p>
                    <p><span class="text-zinc-500">Name:</span> {{ overview.auth_user_display?.name ?? '-' }}</p>
                    <p><span class="text-zinc-500">Email:</span> {{ overview.auth_user_display?.email ?? '-' }}</p>
                    <p><span class="text-zinc-500">Phone:</span> {{ overview.auth_user_display?.phone ?? '-' }}</p>
                    <p><span class="text-zinc-500">Role:</span> {{ overview.auth_user_display?.role ?? '-' }}</p>
                    <p><span class="text-zinc-500">Profile Completion:</span> {{ overview.auth_user_display?.completion_percentage ?? 0 }}%</p>
                </div>
            </div>

            <div class="mt-4 space-y-3 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="text-sm font-semibold text-zinc-200">Usage</h3>
                <p class="text-sm text-zinc-400">Total Tokens</p>
                <p class="text-2xl font-semibold text-white">{{ overview.usage?.total_tokens ?? 0 }}</p>
                <p class="text-xs text-zinc-500">Limit: {{ overview.usage?.token_limit ?? 10000 }}</p>
                <p class="text-xs text-zinc-500">Remaining: {{ overview.usage?.remaining_tokens ?? 10000 }}</p>
                <p class="text-xs text-zinc-500">Active Models: {{ overview.active_models_count ?? 0 }}</p>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-800/80 bg-linear-to-b from-zinc-900/90 to-zinc-950/90 p-5 shadow-xl shadow-black/30">
            <h2 class="text-lg font-semibold text-white">Provider API Keys</h2>
            <p class="mt-1 text-sm text-zinc-400">Save your own provider keys securely (encrypted at rest).</p>

            <div class="mt-4 space-y-3 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                <label class="text-xs text-zinc-400">Provider</label>
                <select
                    v-model="provider"
                    class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                >
                    <option v-for="providerName in providerOptions" :key="providerName" :value="providerName">
                        {{ providerName }}
                    </option>
                </select>

                <label class="text-xs text-zinc-400">API Key</label>
                <input
                    v-model="providerApiKey"
                    type="password"
                    class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    placeholder="Paste provider API key"
                />

                <button
                    class="w-full rounded-md bg-zinc-100 px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-white disabled:opacity-60"
                    :disabled="saving || !provider || !providerApiKey.trim()"
                    @click="saveProviderKey"
                >
                    {{ saving ? 'Saving...' : 'Save Key' }}
                </button>
                <p class="text-xs text-zinc-500">{{ statusText }}</p>
            </div>

            <div class="mt-4 space-y-2 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="text-sm font-semibold text-zinc-200">Saved Key Status</h3>
                <div
                    v-for="item in providerKeys"
                    :key="item.provider"
                    class="flex items-center justify-between rounded-md border border-zinc-800 bg-zinc-900 px-2 py-2 text-xs"
                >
                    <span class="text-zinc-300">{{ item.provider }}</span>
                    <span :class="item.has_custom_key ? 'text-emerald-400' : 'text-zinc-500'">
                        {{ item.has_custom_key ? 'Saved' : 'Not set' }}
                    </span>
                </div>
            </div>

            <div class="mt-4 space-y-3 rounded-xl border border-zinc-800 bg-zinc-900/60 p-4">
                <h3 class="text-sm font-semibold text-zinc-200">Update Password</h3>
                <input
                    v-model="passwordForm.current_password"
                    type="password"
                    class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    placeholder="Current password"
                />
                <input
                    v-model="passwordForm.password"
                    type="password"
                    class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    placeholder="New password"
                />
                <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    class="w-full rounded-md border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                    placeholder="Confirm new password"
                />
                <button
                    class="w-full rounded-md bg-zinc-100 px-4 py-2 text-sm font-semibold text-zinc-900 hover:bg-white disabled:opacity-60"
                    :disabled="passwordSaving || !canUpdatePassword"
                    @click="updatePassword"
                >
                    {{ passwordSaving ? 'Updating...' : 'Update Password' }}
                </button>
                <p class="text-xs text-zinc-500">{{ passwordStatusText }}</p>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';

const overview = ref({});
const providerKeys = ref([]);
const provider = ref('');
const providerApiKey = ref('');
const saving = ref(false);
const statusText = ref('Ready');
const passwordSaving = ref(false);
const passwordStatusText = ref('Password strength: min 8 chars, include mix of character types.');
const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const providerOptions = computed(() => {
    return (providerKeys.value ?? []).map((item) => item.provider);
});

const canUpdatePassword = computed(() => {
    return Boolean(
        passwordForm.value.current_password &&
        passwordForm.value.password &&
        passwordForm.value.password_confirmation
    );
});

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

    if (!response.ok) {
        const data = await response.json().catch(() => ({}));
        throw new Error(data.message || `Request failed: ${response.status}`);
    }

    return response.json();
}

async function loadOverview() {
    const data = await apiRequest('/api/v1/settings/overview');
    overview.value = data ?? {};
    providerKeys.value = data.provider_keys ?? [];
    if (!provider.value) {
        provider.value = providerOptions.value[0] ?? '';
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
        await loadOverview();
        statusText.value = `Saved key for ${provider.value}`;
    } catch (error) {
        statusText.value = error.message || 'Failed to save provider key';
    } finally {
        saving.value = false;
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
    } finally {
        passwordSaving.value = false;
    }
}

onMounted(async () => {
    statusText.value = 'Loading...';
    try {
        await loadOverview();
        statusText.value = 'Ready';
    } catch (error) {
        statusText.value = error.message || 'Failed to load settings';
    }
});
</script>
