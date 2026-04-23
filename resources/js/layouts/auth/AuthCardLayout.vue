<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import { Store } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
<div
    class="relative flex min-h-screen flex-col items-center justify-center bg-white p-6 font-['Plus_Jakarta_Sans',sans-serif] md:p-10 dark:bg-slate-950">
    <!-- Dot Grid -->
    <div
        class="absolute inset-0 z-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)]">
    </div>

    <div class="relative z-10 flex w-full max-w-md flex-col gap-8">
        <Link :href="home()"
            class="flex items-center gap-3 self-center transition-transform hover:scale-105 active:scale-95">
            <AppLogoIcon class="h-10 w-10 rounded-xl bg-blue-600 p-2 text-white shadow-none " />
            <span class="text-xl font-black tracking-tighter text-slate-900 dark:text-white">
                {{ $page.props.name }}
            </span>
        </Link>

        <div class="flex flex-col gap-6">
            <Card
                class="rounded-xl border-slate-200 bg-white shadow-none dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-none ">
                <CardHeader class="px-10 pt-10 pb-0 text-center">
                    <CardTitle class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ title }}
                    </CardTitle>
                    <CardDescription class="font-medium text-slate-500">
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-10 py-10">
                    <slot />
                </CardContent>
            </Card>
        </div>
    </div>
</div>
</template>
