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
<div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-1.5">
            <Button v-if="backHref" variant="ghost" size="icon-sm" as-child
                class="text-muted-foreground hover:text-foreground hover:bg-transparent -ml-2.5">
                <Link :href="backHref">
                    <ChevronLeft class="h-4 w-4" />
                </Link>
            </Button>
            <div class="flex flex-col">
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground leading-none">{{ title }}</h1>
                    <div v-if="count !== undefined"
                        class="h-6 px-2 rounded-sm bg-primary/10 border border-primary/20 text-primary font-bold text-[11px] flex items-center justify-center transition-all animate-in zoom-in-50 duration-300">
                        {{ count }}
                    </div>
                </div>
                <p v-if="description" class="text-[11px]  text-muted-foreground/40 mt-1 tracking-[0.1em]">
                    {{ description }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <slot name="actions"></slot>
        </div>
    </div>
</div>
</template>
