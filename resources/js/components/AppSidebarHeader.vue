<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import {
    Search, Bell, HelpCircle, ChevronDown,
    LayoutGrid, Menu, Zap, Users
} from 'lucide-vue-next';
import { computed } from 'vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
</script>

<template>
<header
    class="flex h-16 shrink-0 items-center justify-between gap-4 bg-white px-6 sticky top-0 z-50 border-b border-input transition-all duration-300">
    <div class="flex items-center gap-4 flex-1 max-w-xl">
        <!-- Chicago Style Top Search -->
        <div class="relative flex-1 group">
            <Search
                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground group-focus-within:text-accent transition-colors" />
            <input type="text" placeholder="Search or type a command"
                class="w-full h-10 pl-10 pr-4 text-[13px] bg-secondary/50 border-transparent rounded-lg focus:bg-white focus:border-accent/20 focus:ring-4 focus:ring-accent/5 transition-all duration-200 placeholder:text-muted-foreground font-medium" />
        </div>
    </div>

    <div class="flex items-center gap-2">
        <!-- Quick Actions -->
        <div class="flex items-center gap-1 pr-4 border-r border-input">
            <button
                class="h-9 w-9 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                <Zap class="h-4 w-4" />
            </button>
            <button
                class="h-9 w-9 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                <Users class="h-4 w-4" />
            </button>
            <button
                class="h-9 w-9 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all relative">
                <Bell class="h-4 w-4" />
                <span class="absolute top-2 right-2 h-1.5 w-1.5 bg-accent rounded-full border border-white" />
            </button>
        </div>

        <!-- User Info -->
        <div
            class="flex items-center gap-3 pl-2 group cursor-pointer hover:bg-secondary/50 py-1 px-2 rounded-lg transition-all">
            <div class="h-8 w-8 shrink-0 rounded-full border border-input overflow-hidden shadow-sm">
                <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" :alt="user.name"
                    class="h-full w-full object-cover" />
                <div v-else
                    class="h-full w-full bg-accent flex items-center justify-center text-white font-bold text-xs uppercase">
                    {{ user?.name?.charAt(0) }}
                </div>
            </div>
            <ChevronDown class="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
        </div>
    </div>
</header>
</template>
