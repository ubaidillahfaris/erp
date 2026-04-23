<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Store } from 'lucide-vue-next';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
<div
    class="relative flex min-h-screen flex-col items-center justify-center bg-white p-6 font-['Plus_Jakarta_Sans',sans-serif] selection:bg-blue-100 selection:text-blue-900 md:p-10 dark:bg-slate-950">
    <!-- Dot Grid Background -->
    <div
        class="absolute inset-0 z-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)]">
    </div>

    <div class="relative z-10 w-full max-w-sm">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-6">
                <!-- Logo Section -->
                <Link :href="home()"
                    class="group flex flex-col items-center gap-3 transition-transform hover:scale-105 active:scale-95">
                    <AppLogoIcon class="h-12 w-12 rounded-xl bg-blue-600 p-2.5 text-white shadow-none " />
                    <span class="text-2xl font-black tracking-tighter text-slate-900 dark:text-white">
                        {{ $page.props.name }}
                    </span>
                </Link>

                <!-- Header Content -->
                <div class="space-y-2 text-center">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ title }}</h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                        {{ description }}
                    </p>
                </div>
            </div>

            <!-- Form Area -->
            <div class="rounded-xl border border-slate-200 bg-white p-8 dark:border-slate-800 dark:bg-slate-900">
                <slot />
            </div>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="relative z-10 mt-12 text-center">
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">© 2026 Warung ERP</p>
    </div>
</div>
</template>
