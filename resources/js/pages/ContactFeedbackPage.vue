<template>
    <div class="mx-auto min-h-dvh w-full max-w-4xl px-4 py-8">
        <div class="mb-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-5">
            <p class="text-xl font-semibold text-zinc-100">Contact & Feedback</p>
            <p class="mt-2 text-sm text-zinc-400">
                Send an inquiry or share product feedback. This form submits directly to the public API.
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    class="rounded-lg border px-3 py-2 text-sm transition"
                    :class="formType === 'contact'
                        ? 'border-emerald-500/70 bg-emerald-500/15 text-emerald-200'
                        : 'border-zinc-700 bg-zinc-900 text-zinc-300 hover:bg-zinc-800'"
                    @click="switchType('contact')"
                >
                    Contact
                </button>
                <button
                    type="button"
                    class="rounded-lg border px-3 py-2 text-sm transition"
                    :class="formType === 'feedback'
                        ? 'border-emerald-500/70 bg-emerald-500/15 text-emerald-200'
                        : 'border-zinc-700 bg-zinc-900 text-zinc-300 hover:bg-zinc-800'"
                    @click="switchType('feedback')"
                >
                    Feedback
                </button>
            </div>

            <form class="space-y-4" @submit.prevent="submitForm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">First name *</span>
                        <input
                            v-model.trim="form.first_name"
                            type="text"
                            maxlength="100"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500 disabled:cursor-not-allowed disabled:opacity-70"
                            placeholder="First name"
                            :disabled="profileLocked"
                            required
                        >
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">Last name *</span>
                        <input
                            v-model.trim="form.last_name"
                            type="text"
                            maxlength="100"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500 disabled:cursor-not-allowed disabled:opacity-70"
                            placeholder="Last name"
                            :disabled="profileLocked"
                            required
                        >
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">Email *</span>
                        <input
                            v-model.trim="form.email"
                            type="email"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500 disabled:cursor-not-allowed disabled:opacity-70"
                            placeholder="you@example.com"
                            :disabled="profileLocked"
                            required
                        >
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">Phone</span>
                        <input
                            v-model.trim="form.phone"
                            type="text"
                            maxlength="30"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500 disabled:cursor-not-allowed disabled:opacity-70"
                            placeholder="+91..."
                            :disabled="profileLocked"
                        >
                    </label>
                </div>
                <p v-if="profileLocked" class="text-xs text-zinc-500">
                    Profile info is auto-filled from your account and cannot be edited here.
                </p>

                <div v-if="formType === 'feedback'" class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">Feedback type</span>
                        <select
                            v-model="feedbackCategory"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                        >
                            <option value="General">General</option>
                            <option value="Bug Report">Bug Report</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="UX Feedback">UX Feedback</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-zinc-400">Rating</span>
                        <select
                            v-model="feedbackRating"
                            class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                        >
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Good</option>
                            <option value="3">3 - Average</option>
                            <option value="2">2 - Poor</option>
                            <option value="1">1 - Very poor</option>
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-400">Subject *</span>
                    <input
                        v-model.trim="form.subject"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                        :placeholder="formType === 'feedback' ? 'Feedback subject' : 'Inquiry subject'"
                        required
                    >
                </label>

                <label class="block">
                    <span class="mb-1 block text-xs text-zinc-400">Message *</span>
                    <textarea
                        v-model.trim="form.message"
                        rows="6"
                        maxlength="5000"
                        class="w-full rounded-lg border border-zinc-700 bg-zinc-900 px-3 py-2 text-sm text-zinc-100 outline-none focus:border-zinc-500"
                        :placeholder="formType === 'feedback'
                            ? 'Share what worked, what did not, and what we should improve.'
                            : 'Tell us how we can help you.'"
                        required
                    ></textarea>
                </label>

                <div v-if="errorText" class="rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300">
                    {{ errorText }}
                </div>
                <div v-if="successText" class="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-200">
                    {{ successText }}
                </div>

                <div class="flex items-center justify-end">
                    <button
                        type="submit"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-medium text-zinc-900 hover:bg-zinc-100 disabled:opacity-60"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Submitting...' : (formType === 'feedback' ? 'Submit feedback' : 'Send message') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { showErrorAlert } from '../utils/alerts';

const CONTACT_API_URL = 'https://api.suganta.com/api/v1/contacts';

const formType = ref('contact');
const feedbackCategory = ref('General');
const feedbackRating = ref('5');
const submitting = ref(false);
const profileLocked = ref(false);
const successText = ref('');
const errorText = ref('');

const form = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
});

function resolveAuthProfile(overviewData) {
    const direct = overviewData?.auth_user_display ?? {};
    const authUser = overviewData?.auth_user ?? {};
    const profileRoot = overviewData?.profile ?? {};
    const profileUser = profileRoot?.user ?? {};
    const profileDetails = profileRoot?.profile ?? {};

    const firstName = String(authUser.first_name ?? profileDetails.first_name ?? '').trim();
    const lastName = String(authUser.last_name ?? profileDetails.last_name ?? '').trim();

    return {
        first_name: firstName || String(direct.name ?? profileUser.name ?? '').split(' ').slice(0, 1).join(' ').trim(),
        last_name: lastName || String(direct.name ?? profileUser.name ?? '').split(' ').slice(1).join(' ').trim(),
        email: String(direct.email ?? authUser.email ?? profileUser.email ?? '').trim(),
        phone: String(
            direct.phone
            ?? authUser.phone
            ?? profileDetails.phone_primary
            ?? profileDetails.principal_phone
            ?? profileDetails.parent_phone
            ?? profileDetails.phone_secondary
            ?? ''
        ).trim(),
    };
}

async function preloadAuthUserProfile() {
    try {
        const response = await fetch('/api/v1/settings/overview', {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
            },
        });
        if (!response.ok) {
            return;
        }
        const data = await response.json().catch(() => ({}));
        const profile = resolveAuthProfile(data ?? {});
        if (!profile.first_name || !profile.last_name || !profile.email) {
            return;
        }
        form.value.first_name = profile.first_name;
        form.value.last_name = profile.last_name;
        form.value.email = profile.email;
        form.value.phone = profile.phone;
        profileLocked.value = true;
    } catch {
        // If profile prefill fails, keep editable fallback form behavior.
    }
}

function switchType(type) {
    formType.value = type === 'feedback' ? 'feedback' : 'contact';
    successText.value = '';
    errorText.value = '';
    if (formType.value === 'feedback' && form.value.subject.trim() === '') {
        form.value.subject = `${feedbackCategory.value} feedback`;
    }
}

function normalizeErrorMessage(data, fallback = 'Unable to submit form right now.') {
    if (data?.message && typeof data.message === 'string') {
        return data.message;
    }
    const entries = Object.entries(data?.errors ?? {});
    if (entries.length > 0) {
        const [, messages] = entries[0];
        if (Array.isArray(messages) && messages[0]) {
            return String(messages[0]);
        }
    }
    return fallback;
}

async function submitForm() {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    successText.value = '';
    errorText.value = '';

    const subject = form.value.subject.trim() || (formType.value === 'feedback' ? `${feedbackCategory.value} feedback` : '');
    const baseMessage = form.value.message.trim();
    const finalMessage = formType.value === 'feedback'
        ? `${baseMessage}\n\n[Feedback Category: ${feedbackCategory.value}] [Rating: ${feedbackRating.value}/5]`
        : baseMessage;

    const payload = {
        first_name: form.value.first_name.trim(),
        last_name: form.value.last_name.trim(),
        email: form.value.email.trim(),
        phone: form.value.phone.trim() || undefined,
        subject,
        message: finalMessage,
    };

    try {
        const response = await fetch(CONTACT_API_URL, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = normalizeErrorMessage(data);
            errorText.value = message;
            await showErrorAlert(message, 'Submission failed');
            return;
        }

        successText.value = String(data?.message || 'Submitted successfully.');
        const lockedProfile = {
            first_name: form.value.first_name,
            last_name: form.value.last_name,
            email: form.value.email,
            phone: form.value.phone,
        };
        form.value = {
            first_name: lockedProfile.first_name,
            last_name: lockedProfile.last_name,
            email: lockedProfile.email,
            phone: lockedProfile.phone,
            subject: formType.value === 'feedback' ? `${feedbackCategory.value} feedback` : '',
            message: '',
        };
    } catch (error) {
        const message = String(error?.message || 'Network error. Please try again.');
        errorText.value = message;
        await showErrorAlert(message, 'Submission failed');
    } finally {
        submitting.value = false;
    }
}

onMounted(() => {
    preloadAuthUserProfile();
});
</script>
