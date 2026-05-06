<script setup>
import { AlertCircle } from 'lucide-vue-next';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

defineProps({
    error: { type: [Error, Object, String, null], default: null },
});
</script>

<template>
    <Alert v-if="error" variant="destructive">
        <AlertCircle />
        <AlertTitle>Unable to load workspace data</AlertTitle>
        <AlertDescription>
            {{ typeof error === 'string' ? error : error.message }}
        </AlertDescription>
    </Alert>
</template>
