<template>
    <div class="h-dvh overflow-hidden bg-[#0f0f0f] text-zinc-100">
        <div class="grid h-full min-h-0" :class="isSharedView ? 'grid-cols-1' : 'md:grid-cols-[minmax(260px,280px)_1fr]'">
            <aside v-if="!isSharedView" class="hidden h-full min-h-0 flex-col border-r border-zinc-800/90 bg-[#0d0d0d] md:flex">
                <div class="shrink-0 space-y-3 border-b border-zinc-800/80 p-3">
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
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white px-3 py-2.5 text-sm font-semibold text-zinc-900 shadow-md shadow-black/20 transition hover:bg-zinc-100 active:scale-[0.99]"
                        @click="startNewChat"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                        </svg>
                        New chat
                    </button>
                    <button
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-zinc-400 transition hover:bg-zinc-800/80 hover:text-zinc-200"
                        type="button"
                        @click="openSearchModal"
                    >
                        <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="M21 21l-4.3-4.3" stroke-linecap="round" />
                        </svg>
                        Search chats
                    </button>
                </div>

                <div class="hide-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-2" @scroll="handleHistoryScroll">
                    <p class="px-2 py-1 text-[11px] uppercase tracking-wide text-zinc-500">Recent chats</p>

                    <div
                        v-for="conversation in conversations"
                        :key="conversation.id"
                        class="mb-0.5 flex items-start gap-0.5 rounded-xl px-1 py-0.5 text-sm transition"
                        :class="currentConversationId === conversation.id
                            ? 'bg-zinc-800/95 ring-1 ring-white/10 shadow-sm shadow-black/30'
                            : 'hover:bg-zinc-800/60'"
                    >
                        <button
                            class="min-w-0 flex-1 rounded-lg px-2.5 py-2 text-left outline-none transition focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                            :class="currentConversationId === conversation.id ? 'text-white' : 'text-zinc-300'"
                            type="button"
                            @click="openConversation(conversation.id)"
                        >
                            <p class="truncate font-medium">{{ conversation.subject || 'Untitled chat' }}</p>
                            <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                        </button>
                        <button
                            class="mt-0.5 rounded-md px-2 py-1 text-xs text-zinc-500 transition hover:bg-red-500/10 hover:text-red-300 disabled:opacity-60"
                            :disabled="deletingConversationId === conversation.id"
                            type="button"
                            @click.stop="deleteConversation(conversation.id)"
                        >
                            {{ deletingConversationId === conversation.id ? '...' : 'Delete' }}
                        </button>
                    </div>

                    <p v-if="!conversations.length" class="px-3 py-2 text-xs text-zinc-500">No conversations yet.</p>
                    <p v-if="historyLoading" class="px-3 py-2 text-xs text-zinc-500">Loading history...</p>
                    <p v-else-if="!historyHasMore && conversations.length" class="px-3 py-2 text-xs text-zinc-600">
                        No more chats
                    </p>
                </div>

                <div class="shrink-0 border-t border-zinc-800 px-3 py-3 text-xs text-zinc-500">
                    {{ usagePercentLabel }}
                </div>
            </aside>

            <section
                class="flex h-full min-h-0 flex-col overflow-hidden bg-[#212121]"
                aria-label="Chat conversation"
            >
                <div class="sticky top-0 z-30 bg-[#212121]">
                    <div class="shrink-0 border-b border-zinc-800 px-3 py-2 md:hidden">
                        <div class="flex items-center justify-between gap-2">
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
                            <button
                                class="rounded-lg border border-zinc-700 bg-zinc-900 px-2.5 py-1.5 text-[11px] text-zinc-200 hover:bg-zinc-800"
                                type="button"
                                @click="mobileHistoryOpen = true"
                            >
                                History
                            </button>
                        </div>
                    </div>
                    <ChatTopBar
                        v-model="model"
                        :compare-mode="compareMode"
                        :compare-models="compareModels"
                        :capability-filter="capabilityFilter"
                        :response-style="responseStyle"
                        :is-shared-view="isSharedView"
                        :model-options="modelOptions"
                        :status-text="statusText"
                        :model-error-message="modelErrorMessage"
                        :share-loading="shareLoading"
                        :can-share="Boolean(currentConversationId)"
                        @update:capability-filter="capabilityFilter = $event"
                        @update:response-style="responseStyle = $event"
                        @update:compare-mode="compareMode = $event"
                        @update:compare-models="compareModels = $event"
                        @open-search="openSearchModal"
                        @share="shareConversation"
                    />
                </div>

                <div
                    ref="messageContainerRef"
                    class="chat-scroll min-h-0 flex-1 scroll-smooth overflow-y-auto overscroll-contain"
                    role="log"
                    aria-live="polite"
                    aria-relevant="additions text"
                >
                    <div v-if="messages.length">
                        <article
                            v-for="(message, index) in messages"
                            :key="`${message.role}-${index}-${message.content?.slice(0, 16)}`"
                            class="group/msg border-b border-zinc-800/35 transition-colors duration-200 last:border-b-0"
                            :class="message.role === 'user' ? 'bg-[#2f2f2f]' : 'bg-[#212121]'"
                        >
                            <div class="mx-auto flex max-w-3xl gap-3 px-4 py-5 sm:gap-5 sm:px-6 sm:py-6">
                                <div class="flex w-7 shrink-0 justify-center sm:w-8" aria-hidden="true">
                                    <div
                                        v-if="message.role === 'user'"
                                        class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-md bg-zinc-600 text-[9px] font-bold uppercase tracking-wide text-white sm:h-8 sm:w-8 sm:text-[10px]"
                                    >
                                        You
                                    </div>
                                    <div
                                        v-else
                                        class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-md bg-emerald-950/80 text-[10px] font-bold text-emerald-200 ring-1 ring-emerald-800/60 sm:h-8 sm:w-8"
                                    >
                                        AI
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div v-if="message.processing" class="flex items-center gap-3 py-0.5 text-zinc-400">
                                        <span class="flex gap-1.5" aria-hidden="true">
                                            <span class="chat-thinking-dot inline-block h-2 w-2 rounded-full bg-zinc-400 [animation-delay:0ms]"></span>
                                            <span class="chat-thinking-dot inline-block h-2 w-2 rounded-full bg-zinc-400 [animation-delay:160ms]"></span>
                                            <span class="chat-thinking-dot inline-block h-2 w-2 rounded-full bg-zinc-400 [animation-delay:320ms]"></span>
                                        </span>
                                        <span class="text-sm font-medium tracking-tight text-zinc-500">Generating</span>
                                    </div>
                                    <template v-else>
                                        <div
                                            v-if="message.role === 'user'"
                                            class="wrap-break-word whitespace-pre-wrap text-[15px] leading-7 text-zinc-100"
                                        >
                                            {{ message.content }}
                                        </div>
                                        <div
                                            v-else
                                            :class="assistantMarkdownHtmlClass"
                                            v-html="formatMessageContent(message.content)"
                                            @click.capture="handleMarkdownCodeCopyClick"
                                        ></div>
                                        <p
                                            v-if="message.role === 'assistant' && (message.model || message.provider)"
                                            class="mt-3 text-[11px] text-zinc-500"
                                        >
                                            {{ message.model || 'Assistant' }}<span v-if="message.provider"> · {{ message.provider }}</span>
                                        </p>
                                    </template>
                                    <div
                                        v-if="message.attachments?.length"
                                        class="mt-3 flex flex-wrap gap-2"
                                    >
                                        <div
                                            v-for="attachment in message.attachments"
                                            :key="`${attachment.name}-${attachment.size || 0}`"
                                            class="flex items-center gap-2 rounded-lg border border-zinc-600/60 bg-zinc-900/50 px-2 py-1 text-xs text-zinc-300"
                                        >
                                            <img
                                                v-if="attachment.type?.startsWith('image/') && attachment.dataUrl"
                                                :src="attachment.dataUrl"
                                                :alt="attachment.name"
                                                class="h-8 w-8 rounded object-cover"
                                            >
                                            <div
                                                v-else
                                                class="flex h-8 w-8 items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold text-zinc-200"
                                            >
                                                FILE
                                            </div>
                                            <span class="max-w-44 truncate">{{ attachment.name }}</span>
                                        </div>
                                    </div>
                                    <div
                                        v-if="!isSharedView && message.role === 'assistant' && !message.processing"
                                        class="mt-3 flex flex-wrap items-center gap-1.5 opacity-100 transition-opacity md:opacity-0 md:group-hover/msg:opacity-100"
                                    >
                                        <button
                                            class="rounded-lg border border-zinc-600/70 bg-zinc-900/60 px-2.5 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                            type="button"
                                            :disabled="sending"
                                            @click="copyAssistantReply(index)"
                                        >
                                            {{ copiedAssistantIndex === index ? 'Copied' : 'Copy' }}
                                        </button>
                                        <button
                                            class="rounded-lg border px-2.5 py-1 text-[11px] disabled:opacity-60"
                                            :class="message.feedback === 'up'
                                                ? 'border-emerald-500/70 bg-emerald-500/20 text-emerald-200'
                                                : 'border-zinc-600/70 bg-zinc-900/60 text-zinc-300 hover:bg-zinc-800'"
                                            type="button"
                                            :disabled="sending"
                                            @click="setAssistantFeedback(index, 'up')"
                                        >
                                            👍
                                        </button>
                                        <button
                                            class="rounded-lg border px-2.5 py-1 text-[11px] disabled:opacity-60"
                                            :class="message.feedback === 'down'
                                                ? 'border-rose-500/70 bg-rose-500/20 text-rose-200'
                                                : 'border-zinc-600/70 bg-zinc-900/60 text-zinc-300 hover:bg-zinc-800'"
                                            type="button"
                                            :disabled="sending"
                                            @click="setAssistantFeedback(index, 'down')"
                                        >
                                            👎
                                        </button>
                                        <button
                                            class="rounded-lg border border-zinc-600/70 bg-zinc-900/60 px-2.5 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                            type="button"
                                            :disabled="sending"
                                            @click="regenerateAssistantReply(index)"
                                        >
                                            Regenerate
                                        </button>
                                        <button
                                            class="rounded-lg border border-zinc-600/70 bg-zinc-900/60 px-2.5 py-1 text-[11px] text-zinc-300 hover:bg-zinc-800 disabled:opacity-60"
                                            type="button"
                                            :disabled="sending"
                                            @click="continueAssistantReply(index)"
                                        >
                                            Continue
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <div v-if="isSharedView" class="mx-auto max-w-3xl px-4 pb-8 pt-2 sm:px-6">
                            <div class="rounded-xl border border-zinc-700 bg-zinc-900/60 px-4 py-3 text-sm text-zinc-300">
                                <p>Login to access more features and continue this chat.</p>
                                <a href="/" class="mt-2 inline-block text-xs text-emerald-400 hover:text-emerald-300">Login to continue</a>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex min-h-[calc(100dvh-22rem)] flex-col items-center justify-center px-4 py-12 sm:px-6">
                        <div class="w-full max-w-2xl text-center">
                            <p class="bg-linear-to-b from-zinc-50 to-zinc-400 bg-clip-text text-3xl font-semibold tracking-tight text-transparent sm:text-[2.35rem] sm:leading-tight">
                                What can I help with?
                            </p>
                            <p class="mx-auto mt-4 max-w-md text-sm leading-relaxed text-zinc-500">
                                Enterprise-grade multi-model chat. Ask anything, upload files, or start from a suggestion.
                            </p>
                            <div class="mx-auto mt-10 grid max-w-xl gap-2.5 sm:grid-cols-2">
                                <button
                                    v-for="(prompt, idx) in starterPrompts"
                                    :key="idx"
                                    type="button"
                                    class="group flex items-start gap-3 rounded-2xl border border-zinc-700/70 bg-zinc-800/35 px-4 py-3.5 text-left text-sm text-zinc-200 shadow-sm shadow-black/10 transition hover:border-zinc-600 hover:bg-zinc-800/65 hover:shadow-md"
                                    @click="applyStarterPrompt(prompt)"
                                >
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-700/80 text-zinc-400 transition group-hover:bg-emerald-950/50 group-hover:text-emerald-300">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 leading-snug">{{ prompt }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-zinc-800/90 bg-[#212121] px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-3 shadow-[0_-12px_40px_rgba(0,0,0,0.45)]">
                    <div class="mx-auto w-full max-w-3xl">
                        <ConversationUploadsModal
                            :open="uploadsModalOpen"
                            :can-open="Boolean(currentConversationId)"
                            :loading="assetsLoading"
                            :assets="conversationAssets"
                            :asset-action-loading-id="assetActionLoadingId"
                            @open="uploadsModalOpen = true"
                            @close="uploadsModalOpen = false"
                            @preview="openAsset($event, false)"
                            @download="openAsset($event, true)"
                        />
                        <div
                            v-if="chatErrorMessage"
                            class="mb-3 rounded-lg border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-300"
                        >
                            {{ chatErrorMessage }}
                        </div>
                        <div
                            v-if="rateLimitHint"
                            class="mb-3 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs text-amber-200"
                        >
                            {{ rateLimitHint }}
                        </div>
                        <div
                            v-else-if="sending"
                            class="mb-3 flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-900/70 px-3 py-2 text-xs text-zinc-300"
                        >
                            <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                            <span>Generating response...</span>
                            <span
                                v-if="asyncModeActive"
                                class="ml-1 rounded-full border border-emerald-500/50 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-emerald-300"
                            >
                                Async mode
                            </span>
                        </div>
                        <div class="rounded-[1.75rem] border border-zinc-600/50 bg-[#303030] px-3 py-3 shadow-inner shadow-black/20 transition-[border-color,box-shadow] duration-200 focus-within:border-zinc-500/70 focus-within:shadow-[0_0_0_1px_rgba(16,185,129,0.12),inset_0_1px_0_rgba(255,255,255,0.04)] sm:rounded-full sm:py-2">
                            <div v-if="attachments.length && !isSharedView" class="mb-3 flex flex-wrap gap-2">
                                <div
                                    v-for="item in attachments"
                                    :key="`${item.name}-${item.size}`"
                                    class="flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800/80 px-2 py-1 text-xs text-zinc-300"
                                >
                                    <img
                                        v-if="item.type.startsWith('image/') && item.dataUrl"
                                        :src="item.dataUrl"
                                        :alt="item.name"
                                        class="h-8 w-8 rounded object-cover"
                                    >
                                    <div
                                        v-else
                                        class="flex h-8 w-8 items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold text-zinc-200"
                                    >
                                        FILE
                                    </div>
                                    <span class="max-w-44 truncate">{{ item.name }}</span>
                                    <button
                                        class="text-zinc-400 hover:text-zinc-200"
                                        type="button"
                                        @click="removeAttachment(item)"
                                    >
                                        x
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                <input
                                    ref="fileInputRef"
                                    type="file"
                                    class="hidden"
                                    accept="image/*,.txt,.md,.csv,.json,.log"
                                    multiple
                                    @change="onFilePicked"
                                >
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300 hover:bg-zinc-800 hover:text-zinc-100 sm:h-9 sm:w-9"
                                    type="button"
                                    :disabled="isSharedView"
                                    @click="openFilePicker"
                                    title="Upload files"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z"></path>
                                    </svg>
                                </button>
                                <textarea
                                    ref="composerInputRef"
                                    v-model="inputMessage"
                                    rows="1"
                                    :disabled="isSharedView"
                                    class="max-h-52 min-h-11 w-full resize-none overflow-y-auto bg-transparent py-2.5 text-[15px] leading-6 text-zinc-100 outline-none placeholder:text-zinc-500 sm:max-h-44 sm:min-h-10 sm:py-2.5"
                                    :placeholder="isSharedView ? 'Login to continue this chat' : 'Message SuGanta…'"
                                    @input="adjustComposerHeight"
                                    @keydown="onComposerKeydown"
                                />
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full text-zinc-300 transition disabled:opacity-60 sm:h-9 sm:w-9"
                                    :class="listening
                                        ? 'animate-pulse bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/50'
                                        : 'hover:bg-zinc-800 hover:text-zinc-100'"
                                    type="button"
                                    :disabled="isSharedView || !speechSupported"
                                    @click="toggleMic"
                                    :title="listening ? 'Stop microphone' : 'Use microphone'"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M12 15a3 3 0 0 0 3-3V7a3 3 0 1 0-6 0v5a3 3 0 0 0 3 3zm5-3a5 5 0 1 1-10 0H5a7 7 0 0 0 6 6.93V21h2v-2.07A7 7 0 0 0 19 12z"></path>
                                    </svg>
                                </button>
                                <button
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-zinc-900 hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-60 sm:h-9 sm:w-9"
                                    :disabled="isSharedView || sending || (!inputMessage.trim() && attachments.length === 0)"
                                    @click="sendMessage"
                                    :title="sending ? 'Sending' : 'Send message'"
                                >
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 fill-current" aria-hidden="true">
                                        <path d="M3 11.5 21 4l-7.5 18-1.9-7.6L3 11.5zm9 1.6 1.1 4.3L17.8 6l-8.6 7.1z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div
                                v-if="listening && !isSharedView"
                                class="mt-2 flex items-center gap-2 pl-1 text-[11px] text-emerald-300"
                            >
                                <span class="inline-flex h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                                <span>Listening... speak now</span>
                            </div>
                            <p v-if="!isSharedView" class="mt-2 px-1 text-center text-[11px] text-zinc-500">
                                Enter to send · Shift + Enter for new line · Esc to leave the text box
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <ShareChatModal
            :open="shareModalOpen"
            :title="shareModalTitle"
            :url="sharedUrlText"
            :copied-text="shareCopiedText"
            @close="closeShareModal"
            @copy="copyShareLink"
            @share-platform="shareOnPlatform"
        />

        <ChatSearchModal
            :open="searchModalOpen"
            :query="searchQuery"
            :loading="searchLoading"
            :error="searchError"
            :conversations="filteredConversations"
            @close="closeSearchModal"
            @update:query="searchQuery = $event"
            @open-conversation="openConversationFromSearch"
        />

        <div
            v-if="!isSharedView && mobileHistoryOpen"
            class="fixed inset-0 z-70 bg-black/70 md:hidden"
            @click.self="mobileHistoryOpen = false"
        >
            <div class="absolute left-0 top-0 flex h-full w-[86%] max-w-sm flex-col border-r border-zinc-800/90 bg-[#0d0d0d] shadow-2xl shadow-black/60">
                <div class="shrink-0 space-y-2 border-b border-zinc-800/80 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs uppercase tracking-wide text-zinc-500">Recent chats</p>
                        <button
                            class="rounded-md border border-zinc-700 px-2 py-1 text-xs text-zinc-300 hover:bg-zinc-800"
                            type="button"
                            @click="mobileHistoryOpen = false"
                        >
                            Close
                        </button>
                    </div>
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white px-3 py-2.5 text-sm font-semibold text-zinc-900 shadow-md transition hover:bg-zinc-100 active:scale-[0.99]"
                        @click="startNewChat"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                        </svg>
                        New chat
                    </button>
                </div>
                <div class="hide-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain px-2 py-2" @scroll="handleHistoryScroll">
                    <div
                        v-for="conversation in conversations"
                        :key="`mobile-${conversation.id}`"
                        class="mb-0.5 flex items-start gap-0.5 rounded-xl px-1 py-0.5 text-sm transition"
                        :class="currentConversationId === conversation.id
                            ? 'bg-zinc-800/95 ring-1 ring-white/10'
                            : 'hover:bg-zinc-800/60'"
                    >
                        <button
                            class="min-w-0 flex-1 rounded-lg px-2.5 py-2 text-left outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40"
                            :class="currentConversationId === conversation.id ? 'text-white' : 'text-zinc-300'"
                            type="button"
                            @click="openConversation(conversation.id)"
                        >
                            <p class="truncate font-medium">{{ conversation.subject || 'Untitled chat' }}</p>
                            <p class="mt-1 truncate text-xs text-zinc-500">{{ conversation.last_assistant_message || 'No reply yet' }}</p>
                        </button>
                        <button
                            class="mt-0.5 rounded-md px-2 py-1 text-xs text-zinc-500 transition hover:bg-red-500/10 hover:text-red-300 disabled:opacity-60"
                            :disabled="deletingConversationId === conversation.id"
                            type="button"
                            @click.stop="deleteConversation(conversation.id)"
                        >
                            {{ deletingConversationId === conversation.id ? '...' : 'Delete' }}
                        </button>
                    </div>
                    <p v-if="!conversations.length" class="px-3 py-2 text-xs text-zinc-500">No conversations yet.</p>
                    <p v-if="historyLoading" class="px-3 py-2 text-xs text-zinc-500">Loading history...</p>
                    <p v-else-if="!historyHasMore && conversations.length" class="px-3 py-2 text-xs text-zinc-600">No more chats</p>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { showConfirmAlert, showErrorAlert } from '../utils/alerts';
import { loginGatewayRedirectIfNeeded } from '../utils/authRedirect';
import { formatMessageContent, handleMarkdownCodeCopyClick } from '../utils/messageFormat';
import { runAssistantTypewriter } from '../utils/assistantTypewriter';
import { fetchChatSseJson } from '../utils/chatStreamRequest';
import { executeModelRequests } from './chat/compareRunner';
import ChatTopBar from '../components/chat/ChatTopBar.vue';
import ShareChatModal from '../components/chat/ShareChatModal.vue';
import ChatSearchModal from '../components/chat/ChatSearchModal.vue';
import ConversationUploadsModal from '../components/chat/ConversationUploadsModal.vue';

const route = useRoute();
const router = useRouter();
const messageContainerRef = ref(null);
const fileInputRef = ref(null);
const composerInputRef = ref(null);

const assistantMarkdownHtmlClass =
    'markdown-body space-y-2 wrap-break-word text-[15px] leading-relaxed text-zinc-100 [&_a]:text-sky-400 [&_a]:underline [&_blockquote]:my-2 [&_blockquote]:border-l-2 [&_blockquote]:border-zinc-600 [&_blockquote]:pl-3 [&_blockquote]:text-zinc-300 [&_code]:rounded [&_code]:bg-zinc-800 [&_code]:px-1 [&_code]:py-0.5 [&_code]:text-[13px] [&_h1]:mb-2 [&_h1]:mt-3 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:mb-2 [&_h2]:mt-3 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:mb-1.5 [&_h3]:mt-2 [&_h3]:text-base [&_h3]:font-semibold [&_h4]:mb-1 [&_h4]:mt-2 [&_h4]:text-sm [&_h4]:font-semibold [&_hr]:my-4 [&_hr]:border-zinc-600 [&_img]:my-3 [&_img]:max-w-full [&_img]:rounded-lg [&_img]:border [&_img]:border-zinc-700/80 [&_li]:my-0.5 [&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1.5 [&_pre]:my-2 [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-zinc-950 [&_pre]:p-3 [&_pre]:text-xs [&_table]:my-2 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-zinc-700 [&_td]:px-2 [&_td]:py-1 [&_th]:border [&_th]:border-zinc-700 [&_th]:px-2 [&_th]:py-1 [&_th]:text-left [&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5';

const starterPrompts = [
    'Summarize a complex topic for a non-expert audience',
    'Review my code for bugs, edge cases, and clearer structure',
    'Draft a professional email or Slack update for my team',
    'Brainstorm product or marketing ideas with pros and cons',
];

const conversations = ref([]);
const messages = ref([]);
const usage = ref({ total_tokens: 0, recent_requests: [] });
const models = ref([]);

const usagePercentLabel = computed(() => {
    const total = Number(usage.value?.total_tokens ?? 0);
    const limit = Math.max(Number(usage.value?.token_limit ?? 10000), 1);
    const percent = (total / limit) * 100;
    const formattedTotal = new Intl.NumberFormat().format(total);
    const formattedLimit = new Intl.NumberFormat().format(limit);
    return `${percent.toFixed(2)}% used (${formattedTotal} / ${formattedLimit})`;
});

const searchQuery = ref('');
const searchModalOpen = ref(false);
const searchResults = ref([]);
const searchLoading = ref(false);
const searchError = ref('');
let searchDebounceTimer = null;
let activeSearchRequestId = 0;

const currentConversationId = ref(null);
const model = ref('gemini-2.5-flash-lite');
const compareMode = ref(false);
const compareModels = ref([]);
const temperature = ref(0.7);
const capabilityFilter = ref('all');
const responseStyle = ref('balanced');
const inputMessage = ref('');
const attachments = ref([]);
const sending = ref(false);
const statusText = ref('Ready');
const modelErrorMessage = ref('');
const chatErrorMessage = ref('');
const rateLimitHint = ref('');
const asyncModeActive = ref(false);
const copiedAssistantIndex = ref(null);
const conversationAssets = ref([]);
const assetsLoading = ref(false);
const assetActionLoadingId = ref(null);
const uploadsModalOpen = ref(false);
const deletingConversationId = ref(null);
const historyLoading = ref(false);
const historyPage = ref(1);
const historyHasMore = ref(true);
const shareLoading = ref(false);
const sharedUrlText = ref('');
const shareModalOpen = ref(false);
const shareCopiedText = ref('Copy');
const shareModalTitle = ref('Share conversation');
const mobileHistoryOpen = ref(false);
/** Bumped on each new send/regenerate/continue so in-flight typing reveals stop cleanly. */
const revealSessionId = ref(0);
const SpeechRecognitionCtor = typeof window !== 'undefined'
    ? (window.SpeechRecognition || window.webkitSpeechRecognition || null)
    : null;
const PLAN_UPGRADE_URL = 'https://app.suganta.com/subscriptions?s_type=3';
const speechSupported = Boolean(SpeechRecognitionCtor);
const listening = ref(false);
let recognition = null;
let chatPollCancelled = false;
let copiedAssistantTimer = null;
const ASYNC_MODE_MAX_MS = 300000;
const CHAT_SYNC_REQUEST_TIMEOUT_MS = 300000;
const COMPARE_MAX_PARALLEL_REQUESTS = 8;
/** Align with server `provider_context_message_limit` (defaults to 24) so follow-ups keep enough prior turns. */
const MAX_CONTEXT_MESSAGES = 24;

const MULTI_TURN_SYSTEM_HINT =
    'This is a multi-turn chat. When the latest user message is short, a follow-up, or refers to "that", "it", "the above", "your answer", or similar, resolve it using the earlier messages in this thread.';
const isSharedView = computed(() => route.name === 'chat.shared');
const shareTokenFromRoute = computed(() => String(route.params.shareToken ?? '').trim());

const modelOptions = computed(() => {
    if (capabilityFilter.value === 'all') {
        return models.value;
    }
    if (capabilityFilter.value === 'vision') {
        return models.value.filter((item) => item.supports_vision === true);
    }
    if (capabilityFilter.value === 'reasoning') {
        return models.value.filter((item) => item.supports_reasoning === true);
    }
    if (capabilityFilter.value === 'web_search') {
        return models.value.filter((item) => item.supports_web_search === true);
    }
    if (capabilityFilter.value === 'tools') {
        return models.value.filter((item) => item.supports_tools === true);
    }
    return models.value;
});

const activeModelKeys = computed(() => {
    if (!compareMode.value) {
        return model.value ? [model.value] : [];
    }
    const available = new Set(modelOptions.value.map((item) => String(item.model || '')));
    const selected = compareModels.value
        .map((item) => String(item || '').trim())
        .filter((item) => item !== '' && available.has(item));
    return Array.from(new Set(selected));
});

const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) {
        return conversations.value;
    }
    return searchResults.value;
});

const currentConversationSubject = computed(() => {
    const conversationId = currentConversationId.value;
    if (!conversationId) {
        return '';
    }
    const match = conversations.value.find((item) => Number(item?.id) === Number(conversationId));
    if (!match) {
        return '';
    }
    return String(match.subject || '').trim();
});

watch([capabilityFilter, models], () => {
    if (isSharedView.value) {
        modelErrorMessage.value = '';
        return;
    }

    if (!modelOptions.value.some((item) => item.model === model.value)) {
        model.value = modelOptions.value[0]?.model ?? '';
        if (models.value.length > 0 && model.value === '') {
            modelErrorMessage.value = 'No models match the selected capability filter.';
        } else if (models.value.length === 0) {
            modelErrorMessage.value = 'No models are available for your account right now.';
        } else {
            modelErrorMessage.value = '';
        }
    } else {
        modelErrorMessage.value = '';
    }

    const available = new Set(modelOptions.value.map((item) => String(item.model || '')));
    compareModels.value = compareModels.value.filter((item) => available.has(String(item || '')));
    if (compareMode.value && compareModels.value.length === 0 && model.value) {
        compareModels.value = [model.value];
    }
});

watch(compareMode, (enabled) => {
    if (!enabled) {
        return;
    }
    if (compareModels.value.length === 0 && model.value) {
        compareModels.value = [model.value];
    }
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
    const timeoutMs = Number(options.timeoutMs ?? 0);
    const hasCustomSignal = Boolean(options.signal);
    const controller = !hasCustomSignal && timeoutMs > 0 ? new AbortController() : null;
    const timeoutId = controller
        ? setTimeout(() => {
            controller.abort();
        }, timeoutMs)
        : null;

    let response;
    try {
        response = await fetch(path, {
            credentials: 'include',
            ...options,
            signal: options.signal ?? controller?.signal,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(options.headers ?? {}),
            },
        });
    } catch (cause) {
        const aborted = controller?.signal?.aborted || String(cause?.name || '').toLowerCase() === 'aborterror';
        const error = new Error(
            aborted
                ? 'Request timed out. Switching to background processing...'
                : (String(cause?.message || '') || 'Network request failed.')
        );
        error.code = aborted ? 'request_timeout' : 'network_error';
        error.status = 0;
        throw error;
    } finally {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
    }

    const data = await parseApiResponse(response);
    if (!response.ok) {
        if (loginGatewayRedirectIfNeeded(response, data)) {
            return new Promise(() => {});
        }
        const error = new Error(data?.message || `Request failed: ${response.status}`);
        error.code = String(data?.code || '');
        error.status = Number(response.status || 0);
        error.rateLimit = {
            retryAfter: Number.parseInt(String(response.headers.get('retry-after') ?? ''), 10),
            limit: Number.parseInt(String(response.headers.get('x-ratelimit-limit') ?? ''), 10),
            remaining: Number.parseInt(String(response.headers.get('x-ratelimit-remaining') ?? ''), 10),
            reset: Number.parseInt(String(response.headers.get('x-ratelimit-reset') ?? ''), 10),
        };
        throw error;
    }

    return data;
}

function shouldFallbackToAsync(error) {
    const code = String(error?.code || '').toLowerCase();
    const status = Number(error?.status || 0);
    if (code.startsWith('provider_')) {
        return false;
    }
    return code === 'chat_concurrency_limited'
        || code === 'chat_provider_timeout'
        || code === 'request_timeout'
        || status === 504;
}

function toUserFriendlyChatError(error) {
    const code = String(error?.code || '').toLowerCase();
    const status = Number(error?.status || 0);
    const rawMessage = String(error?.message || '').trim();

    if (code === 'token_limit_exceeded' && rawMessage) {
        return rawMessage;
    }

    if (status === 429) {
        if (rawMessage) {
            return rawMessage;
        }
        return 'Too many requests right now. Please wait a few seconds and try again.';
    }
    if (code === 'chat_concurrency_limited') {
        return 'The server is busy with many chat requests. Please retry shortly.';
    }
    if (code === 'chat_provider_timeout' || code === 'request_timeout') {
        return 'The response timed out. Please try again.';
    }

    return rawMessage || 'Unable to process chat request at this time.';
}

function isTokenLimitExceededError(error) {
    const code = String(error?.code || '').toLowerCase();
    return code === 'token_limit_exceeded';
}

function buildRateLimitHint(error) {
    const status = Number(error?.status || 0);
    if (status !== 429) {
        return '';
    }

    const retryAfter = Number(error?.rateLimit?.retryAfter);
    if (Number.isFinite(retryAfter) && retryAfter > 0) {
        return `Try again in about ${retryAfter} second${retryAfter === 1 ? '' : 's'}.`;
    }

    const reset = Number(error?.rateLimit?.reset);
    if (Number.isFinite(reset) && reset > 0) {
        const nowSeconds = Math.floor(Date.now() / 1000);
        const remainingSeconds = Math.max(0, reset - nowSeconds);
        if (remainingSeconds > 0) {
            return `Rate limit resets in about ${remainingSeconds} second${remainingSeconds === 1 ? '' : 's'}.`;
        }
    }

    const remaining = Number(error?.rateLimit?.remaining);
    const limit = Number(error?.rateLimit?.limit);
    if (Number.isFinite(remaining) && Number.isFinite(limit) && limit > 0) {
        return `Rate limit status: ${remaining}/${limit} requests remaining.`;
    }

    return 'Please wait briefly before sending the next message.';
}

function sleep(ms) {
    return new Promise((resolve) => {
        setTimeout(resolve, ms);
    });
}

async function pollAsyncChatJob(jobId, timeoutMs = ASYNC_MODE_MAX_MS) {
    const startedAt = Date.now();
    let waitMs = 800;
    let transientFailureCount = 0;

    while (Date.now() - startedAt < timeoutMs) {
        if (chatPollCancelled) {
            throw new Error('Request cancelled.');
        }

        let data;
        try {
            data = await apiRequest(`/api/v1/chat/jobs/${encodeURIComponent(jobId)}`, { timeoutMs: 8000 });
            transientFailureCount = 0;
        } catch (error) {
            transientFailureCount += 1;
            if (transientFailureCount >= 4) {
                throw error;
            }
            await sleep(waitMs);
            waitMs = Math.min(3500, waitMs + 600);
            continue;
        }

        const status = String(data?.status || '').toLowerCase();
        if (status === 'completed') {
            return data?.result ?? {};
        }
        if (status === 'failed') {
            throw new Error(String(data?.error || 'Async chat processing failed.'));
        }

        if (status === 'queued') {
            statusText.value = 'Queued... preparing your response';
        } else if (status === 'processing') {
            statusText.value = 'Generating response...';
        }

        const suggestedWait = Number(data?.poll_after_ms || 0);
        if (Number.isFinite(suggestedWait) && suggestedWait >= 300 && suggestedWait <= 5000) {
            waitMs = suggestedWait;
        }
        await sleep(waitMs);
        waitMs = Math.min(3000, waitMs + 250);
    }

    const timeoutError = new Error('Async response exceeded 300 seconds. Switching to sync mode...');
    timeoutError.code = 'async_timeout_sync_retry';
    throw timeoutError;
}

async function sendChatAsAsync(payload) {
    asyncModeActive.value = true;
    statusText.value = 'Server busy. Switching to async mode...';
    const started = await apiRequest('/api/v1/chat/async', {
        method: 'POST',
        body: JSON.stringify({ ...payload, stream: false }),
        timeoutMs: 10000,
    });
    const jobId = String(started?.job_id || '');
    if (!jobId) {
        throw new Error('Unable to create async chat job.');
    }

    statusText.value = 'Generating response...';
    return await pollAsyncChatJob(jobId, ASYNC_MODE_MAX_MS);
}

function parseConversationId(value) {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return null;
    }
    return parsed;
}

function extractAssistantMessage(data) {
    if (!data || typeof data !== 'object') {
        return '';
    }

    const candidates = [
        data.message,
        data.content,
        data.result?.message,
        data.result?.content,
        data.response?.message,
        data.response?.content,
    ];

    const firstText = candidates.find((item) => typeof item === 'string' && item.trim() !== '');
    return firstText ? String(firstText) : '';
}

function normalizeMessagesForApi(messages) {
    return (messages ?? [])
        .filter((message) => message && typeof message === 'object')
        .map((message) => ({
            role: String(message.role || 'user'),
            content: String(message.content || ''),
        }))
        .filter((message) => message.content.trim() !== '');
}

function trimConversationContext(messages) {
    const normalized = normalizeMessagesForApi(messages);
    const maxBody = MAX_CONTEXT_MESSAGES;
    let body = normalized.length <= maxBody ? [...normalized] : normalized.slice(-maxBody);

    const userCount = body.filter((m) => m.role === 'user').length;
    const assistantCount = body.filter((m) => m.role === 'assistant').length;
    const looksLikeFollowUpThread = body.length >= 3 && (userCount >= 2 || (userCount >= 1 && assistantCount >= 1));

    if (looksLikeFollowUpThread && body[0]?.role !== 'system') {
        const hint = { role: 'system', content: MULTI_TURN_SYSTEM_HINT };
        const tailBudget = maxBody - 1;
        const tail = body.length > tailBudget ? body.slice(-tailBudget) : body;
        body = [hint, ...tail];
    }

    return body;
}

function buildPayloadMessages(nextMessages) {
    return trimConversationContext(nextMessages);
}

async function syncConversationRoute(conversationId) {
    if (isSharedView.value) {
        return;
    }

    const parsed = parseConversationId(conversationId);
    if (parsed === null) {
        if (route.name !== 'chat.home') {
            await router.push({ name: 'chat.home' });
        }
        return;
    }

    if (route.name === 'chat.conversation' && parseConversationId(route.params.conversationId) === parsed) {
        return;
    }

    await router.push({ name: 'chat.conversation', params: { conversationId: String(parsed) } });
}

async function scrollMessagesToBottom() {
    await nextTick();
    const el = messageContainerRef.value;
    if (!el) {
        return;
    }
    el.scrollTop = el.scrollHeight;
}

async function focusComposer() {
    await nextTick();
    composerInputRef.value?.focus();
    adjustComposerHeight();
}

function adjustComposerHeight() {
    const el = composerInputRef.value;
    if (!el) {
        return;
    }
    el.style.height = 'auto';
    el.style.height = `${Math.min(el.scrollHeight, 200)}px`;
}

function onComposerKeydown(event) {
    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
        return;
    }
    event.preventDefault();
    sendMessage();
}

async function applyStarterPrompt(text) {
    if (isSharedView.value) {
        return;
    }
    inputMessage.value = text;
    await nextTick();
    adjustComposerHeight();
    composerInputRef.value?.focus();
}

async function loadBootstrapData() {
    if (isSharedView.value) {
        return;
    }

    statusText.value = 'Loading...';
    modelErrorMessage.value = '';
    try {
        const [usageData, modelData] = await Promise.all([
            apiRequest('/api/v1/usage'),
            apiRequest('/api/v1/models'),
        ]);

        usage.value = usageData ?? { total_tokens: 0, recent_requests: [] };
        models.value = modelData.models ?? [];
        if (models.value.length === 0) {
            modelErrorMessage.value = 'No models are available right now. Please try again later.';
        }
        await loadConversationList(true);
        const first = modelOptions.value[0];
        if (first) {
            model.value = first.model;
            modelErrorMessage.value = '';
        }
        statusText.value = 'Ready';
    } catch (error) {
        modelErrorMessage.value = 'Unable to load models. Please refresh or try again shortly.';
        statusText.value = error.message || 'Failed to load data';
        showErrorAlert(statusText.value, 'Failed to load chat data');
    }
}

async function openConversation(conversationId, syncRoute = true) {
    if (isSharedView.value) {
        return;
    }

    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null) {
        return;
    }

    currentConversationId.value = parsedConversationId;
    mobileHistoryOpen.value = false;
    chatErrorMessage.value = '';
    statusText.value = 'Loading conversation...';
    try {
        const data = await apiRequest(`/api/v1/chat/history/${parsedConversationId}?limit=200`);
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
            attachments: [],
            provider: '',
            model: '',
            feedback: null,
            processing: false,
        }));
        if (syncRoute) {
            await syncConversationRoute(parsedConversationId);
        }
        await loadConversationAssets(parsedConversationId);
        statusText.value = 'Conversation loaded';
        await scrollMessagesToBottom();
        await focusComposer();
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation';
        showErrorAlert(statusText.value, 'Conversation load failed');
    }
}

async function startNewChat() {
    if (isSharedView.value) {
        return;
    }

    currentConversationId.value = null;
    mobileHistoryOpen.value = false;
    messages.value = [];
    conversationAssets.value = [];
    uploadsModalOpen.value = false;
    inputMessage.value = '';
    chatErrorMessage.value = '';
    await syncConversationRoute(null);
    statusText.value = 'New chat started';
    await focusComposer();
}

function openSearchModal() {
    if (isSharedView.value) {
        return;
    }

    searchQuery.value = '';
    searchResults.value = [];
    searchError.value = '';
    searchModalOpen.value = true;
}

function closeSearchModal() {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
    searchModalOpen.value = false;
}

async function openConversationFromSearch(conversationId) {
    await openConversation(conversationId);
    closeSearchModal();
}

async function deleteConversation(conversationId) {
    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null || isSharedView.value || deletingConversationId.value) {
        return;
    }

    const targetConversation = conversations.value.find((item) => Number(item?.id) === parsedConversationId);
    const targetTitle = String(targetConversation?.subject || 'this chat');
    const didDelete = await showConfirmAlert({
        title: 'Delete chat?',
        text: `Delete "${targetTitle}"? This cannot be undone.`,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Keep',
        confirmButtonColor: '#dc2626',
        onConfirm: async () => {
            deletingConversationId.value = parsedConversationId;
            try {
                await apiRequest(`/api/v1/chat/history/${parsedConversationId}`, {
                    method: 'DELETE',
                });

                conversations.value = conversations.value.filter((item) => Number(item?.id) !== parsedConversationId);
                searchResults.value = searchResults.value.filter((item) => Number(item?.id) !== parsedConversationId);

                if (currentConversationId.value === parsedConversationId) {
                    await startNewChat();
                } else {
                    statusText.value = 'Conversation deleted';
                }
            } finally {
                deletingConversationId.value = null;
            }
        },
    });
    if (!didDelete) {
        statusText.value = 'Delete cancelled';
    }
}

async function runSearchFromDatabase(query) {
    const raw = String(query ?? '').trim();
    const trimmed = raw.slice(0, 120);
    if (raw.length > 120) {
        searchError.value = 'Search is limited to first 120 characters.';
    }
    if (trimmed === '') {
        searchResults.value = conversations.value;
        searchLoading.value = false;
        if (raw.length <= 120) {
            searchError.value = '';
        }
        return;
    }

    const requestId = ++activeSearchRequestId;
    searchLoading.value = true;
    if (raw.length <= 120) {
        searchError.value = '';
    }
    try {
        const data = await apiRequest(`/api/v1/chat/histories/search?q=${encodeURIComponent(trimmed)}&limit=30&page=1`);
        if (requestId !== activeSearchRequestId) {
            return;
        }
        searchResults.value = data.conversations ?? [];
    } catch (error) {
        if (requestId !== activeSearchRequestId) {
            return;
        }
        searchResults.value = [];
        searchError.value = error.message || 'Failed to search chat history';
        showErrorAlert(searchError.value, 'Search failed');
    } finally {
        if (requestId === activeSearchRequestId) {
            searchLoading.value = false;
        }
    }
}

function setAssistantMessageAt(baseIndex, modelIndex, message) {
    messages.value[baseIndex + modelIndex] = message;
    messages.value = [...messages.value];
}

async function revealAssistantMessage(index, finalMessage, session, revealOptions = {}) {
    const current = messages.value[index];
    if (!current) {
        return;
    }
    const fullText = String(finalMessage.content ?? '');
    if (revealOptions.skipTypewriter) {
        messages.value.splice(index, 1, {
            ...current,
            ...finalMessage,
            content: fullText,
            processing: false,
        });
        await scrollMessagesToBottom();
        return;
    }
    messages.value.splice(index, 1, {
        ...current,
        ...finalMessage,
        content: '',
        processing: false,
    });
    await scrollMessagesToBottom();
    await runAssistantTypewriter(
        (slice) => {
            if (revealSessionId.value !== session) {
                return;
            }
            const latest = messages.value[index];
            if (!latest) {
                return;
            }
            messages.value.splice(index, 1, {
                ...latest,
                ...finalMessage,
                content: slice,
                processing: false,
            });
        },
        fullText,
        {
            shouldContinue: () => revealSessionId.value === session,
            onScroll: () => scrollMessagesToBottom(),
        },
    );
    if (revealSessionId.value !== session) {
        return;
    }
    const last = messages.value[index];
    if (last) {
        messages.value.splice(index, 1, {
            ...last,
            ...finalMessage,
            content: fullText,
            processing: false,
        });
    }
}

function buildAssistantSuccessMessage(data, selectedModel) {
    return {
        role: 'assistant',
        content: extractAssistantMessage(data),
        attachments: [],
        provider: String(data?.provider || ''),
        model: String(data?.model || selectedModel || ''),
        feedback: null,
        processing: false,
    };
}

function buildAssistantErrorMessage(selectedModel, error) {
    return {
        role: 'assistant',
        content: `Error from ${selectedModel}: ${toUserFriendlyChatError(error)}`,
        attachments: [],
        provider: '',
        model: selectedModel,
        feedback: null,
        processing: false,
    };
}

function updateModelProgressStatus(isCompareMode, completedCount, totalModels, currentModelIndex = null) {
    if (isCompareMode) {
        if (currentModelIndex !== null) {
            statusText.value = `Generating response (${currentModelIndex + 1}/${totalModels})...`;
            return;
        }
        statusText.value = `Completed ${completedCount}/${totalModels} model responses...`;
        return;
    }

    statusText.value = 'Generating response...';
}

async function sendMessage() {
    if (isSharedView.value) {
        await showErrorAlert('Please login to continue this conversation with full features.', 'Login required');
        return;
    }

    const text = inputMessage.value.trim();
    if ((!text && attachments.value.length === 0) || sending.value) {
        return;
    }

    if (activeModelKeys.value.length === 0) {
        modelErrorMessage.value = compareMode.value
            ? 'Please select one or more models for comparison.'
            : 'Please select a model before sending your message.';
        chatErrorMessage.value = '';
        statusText.value = 'Model selection required';
        return;
    }

    if (compareMode.value && activeModelKeys.value.length < 2) {
        modelErrorMessage.value = 'Please select at least 2 models for comparison.';
        chatErrorMessage.value = '';
        statusText.value = 'Select at least 2 models';
        return;
    }

    const currentAttachments = [...attachments.value];
    const attachmentSummary = currentAttachments.length > 0
        ? `\n\nAttachments: ${currentAttachments.map((item) => item.name).join(', ')}`
        : '';
    const composedUserText = `${text}${attachmentSummary}`.trim();
    const nextMessages = [...messages.value, {
        role: 'user',
        content: composedUserText,
        attachments: currentAttachments,
        processing: false,
    }];
    const placeholderReplies = activeModelKeys.value.map((modelKey) => ({
        role: 'assistant',
        content: '',
        attachments: [],
        provider: '',
        model: modelKey,
        feedback: null,
        processing: true,
    }));
    messages.value = [...nextMessages, ...placeholderReplies];
    inputMessage.value = '';
    attachments.value = [];
    await nextTick();
    adjustComposerHeight();
    sending.value = true;
    asyncModeActive.value = false;
    chatErrorMessage.value = '';
    rateLimitHint.value = '';
    statusText.value = 'Sending...';
    await scrollMessagesToBottom();

    const payloadBase = {
        conversation_id: currentConversationId.value ?? undefined,
        save_history: true,
        stream: !compareMode.value,
        temperature: Number(temperature.value),
        response_style: String(responseStyle.value || 'balanced'),
        messages: buildPayloadMessages(nextMessages),
        use_full_context: true,
        attachments: currentAttachments.map((item) => ({
            name: String(item.name || 'attachment'),
            type: item.type?.startsWith('image/') ? 'image' : (item.textContent ? 'text' : 'file'),
            mime_type: String(item.type || 'application/octet-stream'),
            content_text: item.textContent ? String(item.textContent) : undefined,
            content_base64: item.dataUrl ? String(item.dataUrl) : undefined,
        })),
    };

    try {
        revealSessionId.value += 1;
        const typingSession = revealSessionId.value;
        const baseIndex = nextMessages.length;
        const selectedModelKeys = [...activeModelKeys.value];
        const totalModels = selectedModelKeys.length;
        let completedCount = 0;
        const streamPerform = (payload) => performChatRequest(payload, !compareMode.value
            ? {
                onStreamDelta: (delta) => {
                    const idx = baseIndex;
                    const m = messages.value[idx];
                    if (!m || m.role !== 'assistant') {
                        return;
                    }
                    messages.value.splice(idx, 1, {
                        ...m,
                        content: String(m.content || '') + delta,
                        processing: true,
                    });
                },
            }
            : {});

        const {
            workingConversationId,
            firstError,
            successCount,
        } = await executeModelRequests({
            compareMode: compareMode.value,
            selectedModelKeys,
            payloadBase,
            initialConversationId: currentConversationId.value ?? null,
            maxParallelRequests: COMPARE_MAX_PARALLEL_REQUESTS,
            performChatRequest: streamPerform,
            onModelStart: ({ modelIndex }) => {
                updateModelProgressStatus(compareMode.value, completedCount, totalModels, modelIndex);
            },
            onModelSuccess: async ({ modelIndex, selectedModel, data }) => {
                await revealAssistantMessage(
                    baseIndex + modelIndex,
                    buildAssistantSuccessMessage(data, selectedModel),
                    typingSession,
                    { skipTypewriter: Boolean(data?._streamed) },
                );
                completedCount += 1;
                updateModelProgressStatus(compareMode.value, completedCount, totalModels);
            },
            onModelError: ({ modelIndex, selectedModel, error }) => {
                setAssistantMessageAt(baseIndex, modelIndex, buildAssistantErrorMessage(selectedModel, error));
                completedCount += 1;
                updateModelProgressStatus(compareMode.value, completedCount, totalModels);
            },
            onParallelStart: ({ concurrency }) => {
                statusText.value = `Running compare requests in parallel (max ${concurrency} at once)...`;
            },
        });

        if (workingConversationId) {
            currentConversationId.value = workingConversationId;
            await syncConversationRoute(workingConversationId);
            await loadConversationAssets(workingConversationId);
        }
        if (successCount === 0 && firstError) {
            throw firstError;
        }
        await Promise.all([loadConversationList(), loadUsage()]);
        statusText.value = compareMode.value ? 'Comparison completed' : 'Response received';
        chatErrorMessage.value = '';
        rateLimitHint.value = '';
        await scrollMessagesToBottom();
    } catch (error) {
        messages.value = nextMessages;
        const userFriendlyMessage = toUserFriendlyChatError(error);
        chatErrorMessage.value = userFriendlyMessage;
        rateLimitHint.value = buildRateLimitHint(error);
        modelErrorMessage.value = userFriendlyMessage.toLowerCase().includes('model')
            ? (userFriendlyMessage || 'Model error. Please choose another model and try again.')
            : modelErrorMessage.value;
        statusText.value = userFriendlyMessage || 'Failed to send message';
        if (isTokenLimitExceededError(error)) {
            showErrorAlert(chatErrorMessage.value, 'Chat request failed', {
                secondaryButtonText: 'Upgrade Your Plan',
                onSecondaryClick: () => {
                    if (typeof window !== 'undefined') {
                        window.location.href = PLAN_UPGRADE_URL;
                    }
                },
            });
        } else {
            showErrorAlert(chatErrorMessage.value, 'Chat request failed');
        }
    } finally {
        asyncModeActive.value = false;
        sending.value = false;
    }
}

async function performChatRequest(payload, streamOptions = {}) {
    const useSse = Boolean(payload.stream) && typeof streamOptions.onStreamDelta === 'function';

    if (useSse) {
        try {
            let data = await fetchChatSseJson('/api/v1/chat', payload, {
                onDelta: streamOptions.onStreamDelta,
                timeoutMs: CHAT_SYNC_REQUEST_TIMEOUT_MS,
            });
            if (String(data?.status || '').toLowerCase() === 'queued' && String(data?.job_id || '') !== '') {
                asyncModeActive.value = true;
                statusText.value = 'Generating response...';
                data = await pollAsyncChatJob(String(data.job_id));
            }
            return data;
        } catch (error) {
            if (String(error?.code || '') === 'chat_async_queued' && String(error?.job_id || '') !== '') {
                asyncModeActive.value = true;
                statusText.value = 'Generating response...';
                return await pollAsyncChatJob(String(error.job_id));
            }
            if (!shouldFallbackToAsync(error)) {
                throw error;
            }
            try {
                return await sendChatAsAsync({ ...payload, stream: false });
            } catch (asyncError) {
                if (String(asyncError?.code || '') !== 'async_timeout_sync_retry') {
                    throw asyncError;
                }
                asyncModeActive.value = false;
                statusText.value = 'Async timed out. Retrying in sync mode...';
                return await apiRequest('/api/v1/chat', {
                    method: 'POST',
                    body: JSON.stringify({ ...payload, stream: false }),
                    timeoutMs: CHAT_SYNC_REQUEST_TIMEOUT_MS,
                });
            }
        }
    }

    try {
        let data = await apiRequest('/api/v1/chat', {
            method: 'POST',
            body: JSON.stringify(payload),
            timeoutMs: CHAT_SYNC_REQUEST_TIMEOUT_MS,
        });
        if (String(data?.status || '').toLowerCase() === 'queued' && String(data?.job_id || '') !== '') {
            asyncModeActive.value = true;
            statusText.value = 'Generating response...';
            data = await pollAsyncChatJob(String(data.job_id));
        }
        return data;
    } catch (error) {
        if (!shouldFallbackToAsync(error)) {
            throw error;
        }
        try {
            return await sendChatAsAsync({ ...payload, stream: false });
        } catch (asyncError) {
            if (String(asyncError?.code || '') !== 'async_timeout_sync_retry') {
                throw asyncError;
            }
            asyncModeActive.value = false;
            statusText.value = 'Async timed out. Retrying in sync mode...';
            return await apiRequest('/api/v1/chat', {
                method: 'POST',
                body: JSON.stringify({ ...payload, stream: false }),
                timeoutMs: CHAT_SYNC_REQUEST_TIMEOUT_MS,
            });
        }
    }
}

async function regenerateAssistantReply(assistantIndex) {
    if (isSharedView.value || sending.value || assistantIndex < 0 || assistantIndex >= messages.value.length) {
        return;
    }
    const targetMessage = messages.value[assistantIndex];
    if (!targetMessage || targetMessage.role !== 'assistant') {
        return;
    }

    const baseMessages = messages.value.slice(0, assistantIndex).map((message) => ({
        role: message.role,
        content: message.content,
    }));
    if (!baseMessages.some((message) => message.role === 'user' && String(message.content || '').trim() !== '')) {
        return;
    }

    const snapshot = [...messages.value];
    messages.value = [
        ...snapshot.slice(0, assistantIndex),
        { ...targetMessage, content: '', processing: true, attachments: [], feedback: null },
        ...snapshot.slice(assistantIndex + 1),
    ];
    sending.value = true;
    asyncModeActive.value = false;
    chatErrorMessage.value = '';
    rateLimitHint.value = '';
    statusText.value = 'Regenerating response...';
    await scrollMessagesToBottom();

    revealSessionId.value += 1;
    const typingSession = revealSessionId.value;

    const payload = {
        model: model.value,
        conversation_id: currentConversationId.value ?? undefined,
        save_history: true,
        stream: true,
        temperature: Number(temperature.value),
        response_style: String(responseStyle.value || 'balanced'),
        messages: trimConversationContext(baseMessages),
        use_full_context: true,
        attachments: [],
    };

    try {
        const data = await performChatRequest(payload, {
            onStreamDelta: (delta) => {
                const m = messages.value[assistantIndex];
                if (!m || m.role !== 'assistant') {
                    return;
                }
                messages.value.splice(assistantIndex, 1, {
                    ...m,
                    content: String(m.content || '') + delta,
                    processing: true,
                });
            },
        });
        if (data.conversation_id) {
            currentConversationId.value = data.conversation_id;
            await syncConversationRoute(data.conversation_id);
            await loadConversationAssets(data.conversation_id);
        }
        await revealAssistantMessage(assistantIndex, {
            role: 'assistant',
            content: extractAssistantMessage(data),
            attachments: [],
            provider: String(data?.provider || ''),
            model: String(data?.model || model.value || ''),
            feedback: null,
            processing: false,
        }, typingSession, { skipTypewriter: Boolean(data?._streamed) });
        await Promise.all([loadConversationList(), loadUsage()]);
        chatErrorMessage.value = '';
        rateLimitHint.value = '';
        statusText.value = 'Response regenerated';
    } catch (error) {
        messages.value = snapshot;
        const userFriendlyMessage = toUserFriendlyChatError(error);
        chatErrorMessage.value = userFriendlyMessage;
        rateLimitHint.value = buildRateLimitHint(error);
        statusText.value = userFriendlyMessage || 'Failed to regenerate response';
        showErrorAlert(chatErrorMessage.value, 'Regenerate failed');
    } finally {
        asyncModeActive.value = false;
        sending.value = false;
    }
}

async function continueAssistantReply(assistantIndex) {
    if (isSharedView.value || sending.value || assistantIndex < 0 || assistantIndex >= messages.value.length) {
        return;
    }
    const targetMessage = messages.value[assistantIndex];
    if (!targetMessage || targetMessage.role !== 'assistant') {
        return;
    }

    const continuationPrompt = 'Continue from your previous answer and include only the missing important points.';
    const baseMessages = messages.value.slice(0, assistantIndex + 1).map((message) => ({
        role: message.role,
        content: message.content,
    }));
    const continuationMessages = [...baseMessages, { role: 'user', content: continuationPrompt }];

    const snapshot = [...messages.value];
    messages.value = [
        ...snapshot,
        { role: 'assistant', content: '', attachments: [], provider: '', model: '', feedback: null, processing: true },
    ];
    sending.value = true;
    asyncModeActive.value = false;
    chatErrorMessage.value = '';
    rateLimitHint.value = '';
    statusText.value = 'Continuing response...';
    await scrollMessagesToBottom();

    revealSessionId.value += 1;
    const continueTypingSession = revealSessionId.value;

    const payload = {
        model: model.value,
        conversation_id: currentConversationId.value ?? undefined,
        save_history: true,
        stream: true,
        temperature: Number(temperature.value),
        response_style: String(responseStyle.value || 'balanced'),
        messages: trimConversationContext(continuationMessages),
        use_full_context: true,
        attachments: [],
    };

    try {
        const continueIdx = snapshot.length;
        const data = await performChatRequest(payload, {
            onStreamDelta: (delta) => {
                const m = messages.value[continueIdx];
                if (!m || m.role !== 'assistant') {
                    return;
                }
                messages.value.splice(continueIdx, 1, {
                    ...m,
                    content: String(m.content || '') + delta,
                    processing: true,
                });
            },
        });
        if (data.conversation_id) {
            currentConversationId.value = data.conversation_id;
            await syncConversationRoute(data.conversation_id);
            await loadConversationAssets(data.conversation_id);
        }
        await revealAssistantMessage(continueIdx, {
            role: 'assistant',
            content: extractAssistantMessage(data),
            attachments: [],
            provider: String(data?.provider || ''),
            model: String(data?.model || model.value || ''),
            feedback: null,
            processing: false,
        }, continueTypingSession, { skipTypewriter: Boolean(data?._streamed) });
        await Promise.all([loadConversationList(), loadUsage()]);
        chatErrorMessage.value = '';
        rateLimitHint.value = '';
        statusText.value = 'Response continued';
        await scrollMessagesToBottom();
    } catch (error) {
        messages.value = snapshot;
        const userFriendlyMessage = toUserFriendlyChatError(error);
        chatErrorMessage.value = userFriendlyMessage;
        rateLimitHint.value = buildRateLimitHint(error);
        statusText.value = userFriendlyMessage || 'Failed to continue response';
        showErrorAlert(chatErrorMessage.value, 'Continue failed');
    } finally {
        asyncModeActive.value = false;
        sending.value = false;
    }
}

async function copyAssistantReply(index) {
    const message = messages.value[index];
    const text = String(message?.content || '').trim();
    if (!text) {
        return;
    }
    try {
        if (!navigator?.clipboard?.writeText) {
            throw new Error('Clipboard unavailable');
        }
        await navigator.clipboard.writeText(text);
        copiedAssistantIndex.value = index;
        if (copiedAssistantTimer) {
            clearTimeout(copiedAssistantTimer);
        }
        copiedAssistantTimer = setTimeout(() => {
            copiedAssistantIndex.value = null;
            copiedAssistantTimer = null;
        }, 1800);
    } catch {
        showErrorAlert('Unable to copy automatically. Please copy manually.', 'Copy failed');
    }
}

async function setAssistantFeedback(index, type) {
    const message = messages.value[index];
    if (!message || message.role !== 'assistant') {
        return;
    }

    const nextType = message.feedback === type ? null : type;
    const previousFeedback = message.feedback ?? null;
    messages.value[index] = {
        ...message,
        feedback: nextType,
    };

    if (nextType === null) {
        return;
    }

    try {
        await apiRequest('/api/v1/chat/feedback', {
            method: 'POST',
            body: JSON.stringify({
                conversation_id: currentConversationId.value ?? undefined,
                feedback: nextType,
                assistant_message: String(message.content || ''),
                provider: String(message.provider || ''),
                model: String(message.model || model.value || ''),
                response_style: String(responseStyle.value || 'balanced'),
            }),
            timeoutMs: 8000,
        });
    } catch (error) {
        messages.value[index] = {
            ...messages.value[index],
            feedback: previousFeedback,
        };
        showErrorAlert(error?.message || 'Unable to save feedback right now.', 'Feedback failed');
    }
}

function openFilePicker() {
    if (isSharedView.value) {
        return;
    }

    fileInputRef.value?.click();
}

async function onFilePicked(event) {
    const input = event.target;
    const files = Array.from(input?.files ?? []);
    if (files.length === 0) {
        return;
    }

    for (const file of files) {
        const item = {
            name: file.name,
            size: file.size,
            type: file.type || 'application/octet-stream',
            textContent: '',
            dataUrl: '',
        };

        try {
            if (item.type.startsWith('image/')) {
                item.dataUrl = await readFileAsDataUrl(file);
            } else if (
                item.type.startsWith('text/') ||
                file.name.endsWith('.md') ||
                file.name.endsWith('.csv') ||
                file.name.endsWith('.json') ||
                file.name.endsWith('.log')
            ) {
                item.textContent = (await file.text()).slice(0, 15000);
            }
            attachments.value.push(item);
        } catch {
            chatErrorMessage.value = `Unable to read file: ${file.name}`;
            showErrorAlert(chatErrorMessage.value, 'File read failed');
        }
    }

    input.value = '';
}

function readFileAsDataUrl(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('Failed to read file.'));
        reader.readAsDataURL(file);
    });
}

function removeAttachment(item) {
    attachments.value = attachments.value.filter((entry) => !(entry.name === item.name && entry.size === item.size));
}

async function loadConversationAssets(conversationId) {
    const parsedConversationId = parseConversationId(conversationId);
    if (parsedConversationId === null) {
        conversationAssets.value = [];
        return;
    }

    assetsLoading.value = true;
    try {
        const data = await apiRequest(`/api/v1/chat/history/${parsedConversationId}/assets?limit=100`);
        conversationAssets.value = data.assets ?? [];
    } catch {
        conversationAssets.value = [];
    } finally {
        assetsLoading.value = false;
    }
}

async function openAsset(asset, forceDownload = false) {
    const conversationId = currentConversationId.value;
    if (!conversationId || !asset?.id) {
        return;
    }

    assetActionLoadingId.value = asset.id;
    try {
        const data = await apiRequest(
            `/api/v1/chat/history/${conversationId}/assets/${asset.id}/signed-url?expires_minutes=10`
        );
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
        chatErrorMessage.value = error.message || 'Failed to open uploaded asset.';
        showErrorAlert(chatErrorMessage.value, 'Asset open failed');
    } finally {
        assetActionLoadingId.value = null;
    }
}

function toggleMic() {
    if (isSharedView.value) {
        return;
    }

    if (!speechSupported) {
        chatErrorMessage.value = 'Voice input is not supported in this browser.';
        showErrorAlert(chatErrorMessage.value, 'Microphone unavailable');
        return;
    }

    if (listening.value) {
        recognition?.stop();
        return;
    }

    if (!recognition) {
        recognition = new SpeechRecognitionCtor();
        recognition.lang = 'en-US';
        recognition.continuous = true;
        recognition.interimResults = true;

        recognition.onresult = (event) => {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            inputMessage.value = transcript.trim();
        };

        recognition.onerror = (event) => {
            const code = String(event?.error || '').toLowerCase();
            if (code === 'not-allowed' || code === 'service-not-allowed') {
                chatErrorMessage.value = 'Microphone permission is blocked. Please allow mic access in your browser/site settings and try again.';
            } else if (code === 'audio-capture') {
                chatErrorMessage.value = 'No microphone was found. Please connect a microphone and try again.';
            } else {
                chatErrorMessage.value = 'Microphone error. Please check mic permission.';
            }
            showErrorAlert(chatErrorMessage.value, 'Microphone error');
            listening.value = false;
        };
        recognition.onend = () => {
            listening.value = false;
        };
    }

    chatErrorMessage.value = '';
    try {
        recognition.start();
        listening.value = true;
    } catch {
        chatErrorMessage.value = 'Unable to start microphone. Please allow mic permission and try again.';
        showErrorAlert(chatErrorMessage.value, 'Microphone start failed');
        listening.value = false;
    }
}

async function shareConversation() {
    if (!currentConversationId.value || shareLoading.value || isSharedView.value) {
        return;
    }

    shareLoading.value = true;
    try {
        const data = await apiRequest(`/api/v1/chat/history/${currentConversationId.value}/share`, {
            method: 'POST',
        });
        const shareUrl = String(data.share_url ?? '');
        sharedUrlText.value = shareUrl;
        shareCopiedText.value = 'Copy';
        shareModalTitle.value = String(
            data.conversation?.subject
            || currentConversationSubject.value
            || 'Shared conversation'
        );
        if (shareUrl !== '') {
            shareModalOpen.value = true;
            statusText.value = 'Share link created';
        }
    } catch (error) {
        const message = error?.message || 'Failed to create share link.';
        statusText.value = message;
        showErrorAlert(message, 'Share failed');
    } finally {
        shareLoading.value = false;
    }
}

function closeShareModal() {
    shareModalOpen.value = false;
}

async function copyShareLink() {
    const shareUrl = String(sharedUrlText.value || '').trim();
    if (shareUrl === '') {
        return;
    }

    try {
        if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(shareUrl);
        } else {
            throw new Error('Clipboard unavailable');
        }
        shareCopiedText.value = 'Copied';
        statusText.value = 'Share link copied';
    } catch {
        showErrorAlert('Unable to copy automatically. Please copy the link manually.', 'Copy failed');
    }
}

function shareOnPlatform(platform) {
    const shareUrl = encodeURIComponent(String(sharedUrlText.value || '').trim());
    const shareTitle = encodeURIComponent(String(shareModalTitle.value || 'Shared conversation'));
    if (shareUrl === '') {
        return;
    }

    const target = platform === 'x'
        ? `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`
        : platform === 'linkedin'
            ? `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`
            : `https://www.reddit.com/submit?url=${shareUrl}&title=${shareTitle}`;

    window.open(target, '_blank', 'noopener,noreferrer');
}

async function loadSharedConversation(shareToken) {
    const token = String(shareToken ?? '').trim();
    if (token === '') {
        messages.value = [];
        statusText.value = 'Shared conversation not found';
        return;
    }

    sending.value = false;
    searchModalOpen.value = false;
    currentConversationId.value = null;
    conversations.value = [];
    conversationAssets.value = [];
    uploadsModalOpen.value = false;
    attachments.value = [];
    statusText.value = 'Loading shared conversation...';
    chatErrorMessage.value = '';
    modelErrorMessage.value = '';
    try {
        const data = await apiRequest(`/api/v1/public/chat/share/${encodeURIComponent(token)}?limit=200`, {
            credentials: 'omit',
            headers: {
                Accept: 'application/json',
            },
        });
        messages.value = (data.messages ?? []).map((item) => ({
            role: item.role,
            content: item.content,
            attachments: [],
            provider: '',
            model: String(data?.conversation?.model || ''),
            feedback: null,
            processing: false,
        }));
        if (data.conversation?.model) {
            model.value = String(data.conversation.model);
        }
        statusText.value = 'Shared conversation loaded';
        await scrollMessagesToBottom();
    } catch (error) {
        messages.value = [];
        statusText.value = error?.message || 'Unable to load shared conversation.';
        showErrorAlert(statusText.value, 'Shared chat unavailable');
    }
}

async function loadConversationList() {
    await loadConversationListInternal(true);
}

async function loadUsage() {
    usage.value = await apiRequest('/api/v1/usage');
}

async function loadConversationListInternal(reset = false) {
    if (historyLoading.value) {
        return;
    }

    if (reset) {
        historyPage.value = 1;
        historyHasMore.value = true;
    }

    if (!historyHasMore.value && !reset) {
        return;
    }

    historyLoading.value = true;
    try {
        const pageToLoad = historyPage.value;
        const data = await apiRequest(`/api/v1/chat/histories?limit=30&page=${pageToLoad}`);
        const incoming = data.conversations ?? [];

        if (reset) {
            conversations.value = incoming;
        } else {
            const existingIds = new Set(conversations.value.map((item) => item.id));
            const uniqueIncoming = incoming.filter((item) => !existingIds.has(item.id));
            conversations.value = [...conversations.value, ...uniqueIncoming];
        }

        const total = Number(data.total ?? 0);
        historyHasMore.value = conversations.value.length < total;
        historyPage.value = pageToLoad + 1;
    } catch (error) {
        statusText.value = error.message || 'Failed to load conversation history';
        showErrorAlert(statusText.value, 'Conversation history failed');
    } finally {
        historyLoading.value = false;
    }
}

function handleHistoryScroll(event) {
    const target = event.target;
    if (!target || historyLoading.value || !historyHasMore.value) {
        return;
    }

    const threshold = 80;
    const distanceFromBottom = target.scrollHeight - target.scrollTop - target.clientHeight;
    if (distanceFromBottom <= threshold) {
        loadConversationListInternal(false);
    }
}

function handleChatGlobalKeydown(event) {
    if (isSharedView.value) {
        return;
    }
    if (event.key === 'Escape' && composerInputRef.value === document.activeElement) {
        composerInputRef.value.blur();
        event.preventDefault();
    }
}

watch(inputMessage, () => {
    void nextTick(() => {
        adjustComposerHeight();
    });
});

watch(searchQuery, (value) => {
    if (isSharedView.value) {
        return;
    }

    if (!searchModalOpen.value) {
        return;
    }

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    const trimmed = String(value ?? '').trim();
    if (trimmed === '') {
        searchResults.value = conversations.value;
        searchLoading.value = false;
        searchError.value = '';
        return;
    }

    searchDebounceTimer = setTimeout(() => {
        runSearchFromDatabase(trimmed);
    }, 250);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', handleChatGlobalKeydown);
    chatPollCancelled = true;
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
    if (copiedAssistantTimer) {
        clearTimeout(copiedAssistantTimer);
    }
    if (recognition && listening.value) {
        recognition.stop();
    }
});

onMounted(() => {
    if (isSharedView.value) {
        loadSharedConversation(shareTokenFromRoute.value);
        return;
    }

    document.addEventListener('keydown', handleChatGlobalKeydown);

    loadBootstrapData().then(async () => {
        const conversationIdFromRoute = parseConversationId(route.params.conversationId);
        if (conversationIdFromRoute !== null) {
            await openConversation(conversationIdFromRoute, false);
        }
    });
});

watch(
    () => route.params.conversationId,
    async (value) => {
        if (isSharedView.value) {
            return;
        }

        const conversationIdFromRoute = parseConversationId(value);
        if (conversationIdFromRoute === null) {
            if (currentConversationId.value !== null || messages.value.length > 0) {
                currentConversationId.value = null;
                messages.value = [];
                conversationAssets.value = [];
                statusText.value = 'Ready';
            }
            return;
        }

        if (currentConversationId.value === conversationIdFromRoute) {
            return;
        }

        await openConversation(conversationIdFromRoute, false);
    }
);

watch(
    () => route.params.shareToken,
    async (value) => {
        if (!isSharedView.value) {
            return;
        }
        await loadSharedConversation(value);
    }
);
</script>
