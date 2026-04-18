<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    title?: string;
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
<SidebarGroup class="px-3 py-1">
    <SidebarGroupLabel v-if="title"
        class="px-2 mb-1.5 text-xs font-bold uppercase tracking-widest text-muted-foreground select-none">
        {{ title }}
    </SidebarGroupLabel>
    <SidebarMenu class="gap-0.5">
        <SidebarMenuItem v-for="item in items" :key="item.title">
            <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title"
                class="h-8 px-2 rounded-md transition-all duration-200 data-[active=true]:bg-secondary data-[active=true]:text-foreground hover:bg-muted/50 group">
                <Link preserve-scroll :href="item.href" class="flex items-center gap-2.5 w-full">
                    <component :is="item.icon"
                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground transition-colors group-data-[active=true]:text-foreground" />
                    <span
                        class="text-[13px] font-medium text-muted-foreground group-data-[active=true]:text-foreground group-data-[active=true]:font-bold">
                        {{ item.title }}
                    </span>
                    <!-- Active bar indicator removed for cleaner look -->
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</SidebarGroup>
</template>
