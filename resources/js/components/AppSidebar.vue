<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { 
    LayoutGrid, Package, Ruler, ShoppingCart, ShoppingBag, 
    FileText, ReceiptText, PieChart, PackageOpen, Boxes, 
    ClipboardList, Building2, Landmark, ChevronRight,
    Users, ShieldCheck
} from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
const isCashier = computed(() => user.value?.roles?.includes('cashier'));
const menus = computed(() => (page.props.menus as any[]) || []);

const iconMap: Record<string, any> = {
    LayoutGrid, Package, Ruler, ShoppingCart, ShoppingBag,
    FileText, ReceiptText, PieChart, PackageOpen, Boxes,
    ClipboardList, Building2, Landmark, Users, ShieldCheck
};

const getIcon = (name: string) => iconMap[name] || Package;

const footerNavItems: any[] = [];

// Group menus by group_name
const groupedMenus = computed(() => {
    const groups: Record<string, any[]> = {};
    menus.value.forEach(menu => {
        const groupName = menu.group_name || 'Lainnya';
        if (!groups[groupName]) {
            groups[groupName] = [];
        }
        groups[groupName].push({
            title: menu.name,
            href: menu.path || '#',
            icon: getIcon(menu.icon),
            children: menu.children?.map((child: any) => ({
                title: child.name,
                href: child.path || '#',
                icon: getIcon(child.icon),
            }))
        });
    });

    return groups;
});

</script>

<template>
<Sidebar collapsible="icon">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" as-child>
                    <Link :href="isCashier ? '/pos' : '/dashboard'">
                        <AppLogo />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <template v-for="(items, groupName) in groupedMenus" :key="groupName">
            <NavMain :title="groupName" :items="items" />
        </template>
    </SidebarContent>

    <SidebarFooter>
        <NavFooter :items="footerNavItems" />
        <NavUser />
    </SidebarFooter>
</Sidebar>
<slot />
</template>
