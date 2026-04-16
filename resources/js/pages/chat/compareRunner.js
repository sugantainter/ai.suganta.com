export async function runTasksWithConcurrencyLimit(tasks, concurrency) {
    const safeConcurrency = Math.max(1, Math.min(concurrency, tasks.length || 1));
    let cursor = 0;

    const workers = Array.from({ length: safeConcurrency }, async () => {
        while (cursor < tasks.length) {
            const currentIndex = cursor;
            cursor += 1;
            await tasks[currentIndex]();
        }
    });

    await Promise.all(workers);
}

/**
 * Execute selected model requests with sequential or throttled-parallel flow.
 * First compare request is used to establish conversation id, remaining compare
 * requests run in parallel with a configurable concurrency cap.
 */
export async function executeModelRequests({
    compareMode,
    selectedModelKeys,
    payloadBase,
    initialConversationId,
    maxParallelRequests,
    performChatRequest,
    onModelStart,
    onModelSuccess,
    onModelError,
    onParallelStart,
}) {
    let workingConversationId = initialConversationId ?? null;
    let firstError = null;
    let successCount = 0;
    const totalModels = selectedModelKeys.length;

    const processModelRequest = async (modelIndex, selectedModel, forcedConversationId = null) => {
        onModelStart?.({ modelIndex, totalModels, selectedModel });
        try {
            const data = await performChatRequest({
                ...payloadBase,
                model: selectedModel,
                conversation_id: forcedConversationId ?? workingConversationId ?? payloadBase.conversation_id,
            });
            if (data?.conversation_id) {
                workingConversationId = data.conversation_id;
            }
            successCount += 1;
            onModelSuccess?.({ modelIndex, totalModels, selectedModel, data });
            return;
        } catch (error) {
            if (!firstError) {
                firstError = error;
            }
            onModelError?.({ modelIndex, totalModels, selectedModel, error });
        }
    };

    if (compareMode && totalModels > 1) {
        await processModelRequest(0, selectedModelKeys[0]);

        const remainingModels = selectedModelKeys.slice(1).map((selectedModel, offset) => ({
            selectedModel,
            modelIndex: offset + 1,
        }));

        if (remainingModels.length > 0) {
            const concurrency = Math.min(maxParallelRequests, remainingModels.length);
            onParallelStart?.({ concurrency, totalModels });
            const tasks = remainingModels.map(({ modelIndex, selectedModel }) => (
                () => processModelRequest(modelIndex, selectedModel, workingConversationId)
            ));
            await runTasksWithConcurrencyLimit(tasks, concurrency);
        }
    } else {
        for (let modelIndex = 0; modelIndex < totalModels; modelIndex += 1) {
            await processModelRequest(modelIndex, selectedModelKeys[modelIndex]);
        }
    }

    return {
        workingConversationId,
        firstError,
        successCount,
    };
}
