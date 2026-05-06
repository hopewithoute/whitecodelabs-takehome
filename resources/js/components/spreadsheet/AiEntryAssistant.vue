<script setup>
import { computed, ref } from 'vue';
import { Sparkles, Wand2 } from 'lucide-vue-next';
import { draftTimeEntriesWithAi } from '@/api/options';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps({
    selectedCompanyId: { type: [String, null], default: null },
});

const emit = defineEmits(['drafted']);

const prompt = ref('');
const textareaRef = ref(null);
const state = ref('idle');
const error = ref(null);
const warningCount = ref(0);

const canSubmit = computed(() => prompt.value.trim().length >= 3 && state.value !== 'submitting');

async function submitPrompt() {
    if (!canSubmit.value) {
        return;
    }

    state.value = 'submitting';
    error.value = null;

    try {
        const result = await draftTimeEntriesWithAi(prompt.value, props.selectedCompanyId);
        const entries = result.entries ?? [];

        warningCount.value = entries.reduce((total, entry) => total + (entry.warnings?.length ? 1 : 0), 0);
        emit('drafted', entries);
        prompt.value = '';
        if (textareaRef.value) {
            textareaRef.value.$el.style.height = 'auto';
        }
        state.value = 'submitted';
    } catch (exception) {
        error.value = exception.message;
        state.value = 'idle';
    }
}

function handleKeydown(event) {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        submitPrompt();
    }
}

function autoResize(event) {
    const textarea = event.target;
    textarea.style.height = 'auto';
    textarea.style.height = `${Math.min(textarea.scrollHeight, 160)}px`;
}
</script>

<template>
    <div data-ai-entry-assistant class="border-b border-border bg-card/80 px-3 py-3">
        <div class="space-y-2">
            <div class="flex items-center gap-2">
                <Sparkles class="size-4 text-primary" />
                <span class="text-sm font-medium text-foreground">AI-assisted draft</span>
                <Badge variant="outline">Draft only</Badge>
            </div>
            <Textarea
                ref="textareaRef"
                v-model="prompt"
                class="min-h-16 max-h-36 resize-none overflow-y-auto"
                rows="2"
                placeholder="Example: Ava worked 2h on Implementation and 1.5h on Review for Client Portal on Jan 15."
                :disabled="state === 'submitting'"
                @input="autoResize"
                @keydown="handleKeydown"
            />
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">Ctrl+Enter to submit</p>
                <Button
                    class="shrink-0"
                    :disabled="!canSubmit"
                    @click="submitPrompt"
                >
                    <Wand2 />
                    {{ state === 'submitting' ? 'Drafting' : 'Draft rows' }}
                </Button>
            </div>
        </div>

        <Alert v-if="error" variant="destructive" class="mt-3">
            <AlertTitle>AI draft failed</AlertTitle>
            <AlertDescription>{{ error }}</AlertDescription>
        </Alert>

        <p v-else-if="state === 'submitted'" class="mt-2 text-xs text-muted-foreground">
            Draft rows added to the spreadsheet. Review them before submitting.
            <span v-if="warningCount"> {{ warningCount }} {{ warningCount === 1 ? 'row needs' : 'rows need' }} attention.</span>
        </p>
    </div>
</template>
