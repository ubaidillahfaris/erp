<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Store } from 'lucide-vue-next';

const page = usePage();
const name = "Warung.ERP";

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-screen flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0 font-['Plus_Jakarta_Sans',sans-serif] selection:bg-blue-100 selection:text-blue-900"
    >
        <div
            class="relative hidden h-full flex-col bg-slate-100 p-10 text-slate-900 lg:flex dark:bg-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-800"
        >
            <!-- Dot Grid for split sidebar too -->
            <div class="absolute inset-0 z-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:20px_20px] dark:bg-[radial-gradient(#1e293b_1px,transparent_1px)] opacity-50"></div>
            
            <Link
                :href="home()"
                class="relative z-20 flex items-center gap-3 text-lg font-black tracking-tighter transition-transform hover:scale-105 active:scale-95"
            >
                <AppLogoIcon class="h-9 w-9 rounded-lg bg-blue-600 p-1.5 text-white shadow-none " />
                <span>Warung<span class="text-blue-600">.ERP</span></span>
            </Link>
            
            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <p class="text-lg font-medium leading-relaxed italic text-slate-600 dark:text-slate-400">
                        &ldquo;Transformasi digital bukan lagi pilihan, tapi keharusan untuk UMKM yang ingin naik kelas.&rdquo;
                    </p>
                    <footer class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">— Tim Warung ERP</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8 relative bg-white dark:bg-slate-950 h-full flex items-center justify-center">
            <div class="absolute inset-0 z-0 lg:hidden bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:24px_24px]"></div>
            
            <div
                class="relative z-10 mx-auto flex w-full flex-col justify-center space-y-8 sm:w-[400px]"
            >
                <div class="flex flex-col space-y-3 text-center">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-none dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-none ">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
