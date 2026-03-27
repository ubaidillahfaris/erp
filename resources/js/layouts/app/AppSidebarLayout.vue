<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { useToasts } from '@/composables/useToasts';
import type { BreadcrumbItem } from '@/types';
import { onMounted, ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { Loader2 } from 'lucide-vue-next';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

useToasts();

const isLoading = ref(true);

onMounted(() => {
    // Brief delay for the "WOW" effect and to ensure Inertia props are fully ready
    setTimeout(() => {
        isLoading.value = false;
    }, 800);
});
</script>

<template>
    <div class="relative min-h-screen">
        <!-- Premium Splash Screen -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-500 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isLoading" class="fixed inset-0 z-[100] flex items-center justify-center bg-background/80 backdrop-blur-2xl">
                <div class="flex flex-col items-center gap-8 animate-in fade-in zoom-in duration-700">
                    <!-- Premium Logo Container -->
                    <div class="group relative">
                        <!-- Orbiting Ring Animation -->
                        <div class="absolute -inset-4 rounded-full border border-primary/20 animate-[spin_4s_linear_infinite]" />
                        <div class="absolute -inset-4 rounded-full border border-primary/10 animate-[spin_6s_linear_reverse_infinite]" />
                        
                        <div class="relative flex h-20 w-20 items-center justify-center rounded-3xl bg-primary shadow-2xl shadow-primary/20 ring-1 ring-white/20 transition-transform duration-500 group-hover:scale-110">
                            <AppLogo :is-icon-only="true" class="h-10 w-10 text-primary-foreground" />
                        </div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex items-center gap-3 px-4 py-2 rounded-full bg-primary/5 border border-primary/10 shadow-sm animate-pulse">
                            <Loader2 class="h-4 w-4 text-primary animate-spin" />
                            <span class="text-sm font-semibold tracking-wide text-primary uppercase">Initializing Systems</span>
                        </div>
                        <p class="text-xs text-muted-foreground font-medium opacity-50">Menyiapkan Dashboard Martous...</p>
                    </div>
                </div>
            </div>
        </Transition>

        <Toaster />
        
        <Transition
            enter-active-class="transition duration-700 ease-out delay-300"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <AppShell v-if="!isLoading" variant="sidebar">
                <AppSidebar />
                <AppContent variant="sidebar" class="overflow-x-hidden">
                    <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                    <slot />
                </AppContent>
            </AppShell>
        </Transition>
    </div>
</template>
