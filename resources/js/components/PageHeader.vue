<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    title: string;
    description?: string;
    backHref?: string;
    count?: number | string;
}

defineProps<Props>();
</script>

<template>
<div class="flex items-center justify-between max-w-7xl mx-auto w-full">
    <div class="flex items-center gap-3">
        <Button v-if="backHref" variant="outline" size="icon" as-child
            class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50 shrink-0">
            <Link :href="backHref">
                <ChevronLeft class="h-4 w-4" />
            </Link>
        </Button>
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold tracking-tight text-slate-900 leading-none">{{ title }}</h1>
                <div v-if="count !== undefined"
                    class="h-5 px-2 rounded-sm bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center">
                    {{ count }}
                </div>
            </div>
            <p v-if="description" class="text-xs text-slate-400 font-medium mt-1">
                {{ description }}
            </p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <slot name="actions"></slot>
    </div>
</div>
</template>
