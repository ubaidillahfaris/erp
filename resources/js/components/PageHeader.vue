<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

interface Props {
    title: string;
    description?: string;
    backHref?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
        <div class="flex items-center gap-1.5">
            <Button v-if="backHref" variant="ghost" size="icon-sm" as-child class="text-muted-foreground hover:text-foreground hover:bg-transparent -ml-2.5">
                <Link :href="backHref">
                    <ChevronLeft class="h-4 w-4" />
                </Link>
            </Button>
            <div class="flex flex-col">
                <h1 class="text-xl font-bold tracking-tight text-foreground leading-none">{{ title }}</h1>
                <p v-if="description" class="text-[11px] font-medium text-muted-foreground mt-1 underline decoration-primary/30 underline-offset-4 decoration-2">
                    {{ description }}
                </p>
            </div>
        </div>
    </div>
</template>
