<script setup lang="ts">
import { cn } from '@/lib/utils';

interface Props {
    title: string;
    description?: string;
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div :class="cn('flex flex-col gap-1', $props.class)">
        <h3 class="text-sm font-bold text-foreground leading-tight">{{ title }}</h3>
        <p v-if="description" class="text-xs text-muted-foreground font-medium leading-relaxed italic">
            {{ description }}
        </p>
    </div>
</template>
