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
            <div v-if="isLoading" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-background/80 backdrop-blur-xl">
                <div class="relative flex flex-col items-center gap-4 animate-in fade-in zoom-in duration-500">
                    <div class="bg-primary/10 p-4 rounded-2xl ring-1 ring-primary/20 shadow-2xl shadow-primary/10">
                        <AppLogo class="h-12 w-12 text-primary" />
                    </div>
                    <div class="flex items-center gap-2 text-muted-foreground font-medium animate-pulse">
                        <Loader2 class="h-4 w-4 animate-spin" />
                        <span>Menyiapkan Dashboard...</span>
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
