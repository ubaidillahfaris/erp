<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import {
    LayoutGrid, Package, Ruler, ShoppingCart, ShoppingBag,
    FileText, ReceiptText, PieChart, PackageOpen, Boxes,
    ClipboardList, Building2, Landmark, Users, ShieldCheck,
    Search, Zap, ChevronsUpDown, Settings, LifeBuoy, Bell
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
const menus = computed(() => (page.props.menus as any[]) || []);

const iconMap: Record<string, any> = {
    LayoutGrid, Package, Ruler, ShoppingCart, ShoppingBag,
    FileText, ReceiptText, PieChart, PackageOpen, Boxes,
    ClipboardList, Building2, Landmark, Users, ShieldCheck,
};

const getIcon = (name: string) => iconMap[name] || Package;

const groupedMenus = computed(() => {
    const groups: Record<string, any[]> = {};
    menus.value.forEach((menu: any) => {
        const groupName = menu.group_name || 'Menu';
        if (!groups[groupName]) groups[groupName] = [];
        groups[groupName].push({
            title: menu.name,
            href: menu.path || '#',
            icon: getIcon(menu.icon),
            children: menu.children?.map((child: any) => ({
                title: child.name,
                href: child.path || '#',
                icon: getIcon(child.icon),
            })),
        });
    });
    return groups;
});
</script>

<template>
<Sidebar collapsible="icon" class="border-r border-sidebar-border bg-white shadow-none">

    <!-- ── HEADER: Logo ── -->
    <SidebarHeader class="px-5 pt-8 pb-6">
        <Link href="/dashboard" class="flex items-center gap-3 group">
            <div class="h-8 w-8 shrink-0 rounded-lg bg-foreground flex items-center justify-center text-white shadow-md">
                <Zap class="h-4 w-4 fill-current" />
            </div>
            <span class="text-xl font-black tracking-tight text-foreground group-data-[collapsible=icon]:hidden">Plan</span>
        </Link>
    </SidebarHeader>

    <!-- ── NAV ITEMS ── -->
    <SidebarContent class="py-2 gap-0 overflow-x-hidden">
        <template v-for="(items, groupName) in groupedMenus" :key="groupName">
            <NavMain :title="String(groupName)" :items="items" />
        </template>
    </SidebarContent>

    <!-- ── FOOTER: Settings & User ── -->
    <SidebarFooter class="px-3 pb-6 pt-4 space-y-1">
        <!-- System Nav -->
        <SidebarMenu class="px-2 mb-4">
            <SidebarMenuItem>
                <SidebarMenuButton as-child class="h-9 px-3 rounded-lg hover:bg-muted text-muted-foreground transition-all">
                    <Link href="/settings" class="flex items-center gap-3">
                        <Settings class="h-4 w-4 shrink-0" />
                        <span class="text-[13px] font-medium group-data-[collapsible=icon]:hidden">Settings</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <SidebarMenuItem>
                <SidebarMenuButton as-child class="h-9 px-3 rounded-lg hover:bg-muted text-muted-foreground transition-all">
                    <Link href="/support" class="flex items-center gap-3">
                        <LifeBuoy class="h-4 w-4 shrink-0" />
                        <span class="text-[13px] font-medium group-data-[collapsible=icon]:hidden">Help & Support</span>
                        <div class="ml-auto flex h-5 w-5 items-center justify-center rounded-md bg-accent text-[10px] font-black text-white group-data-[collapsible=icon]:hidden">8</div>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>

        <NavUser />
    </SidebarFooter>
</Sidebar>

<slot />
</template>
