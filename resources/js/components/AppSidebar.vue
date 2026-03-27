<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import { LayoutGrid, Package, Ruler, ShoppingCart, ShoppingBag, FileText, ReceiptText, PieChart, PackageOpen, Boxes, ClipboardList, Building2, Landmark } from 'lucide-vue-next';
import { computed } from 'vue';
import { index as bomIndex } from '@/actions/App/Http/Controllers/BOMController';
import { index as pengeluaranIndex } from '@/actions/App/Http/Controllers/PengeluaranController';
import { index as productionIndex } from '@/actions/App/Http/Controllers/ProductionController';
import { index as produkIndex } from '@/actions/App/Http/Controllers/ProdukController';
import { index as restockIndex } from '@/actions/App/Http/Controllers/RestockController';
import { index as satuanIndex } from '@/actions/App/Http/Controllers/SatuanController';
import { index as stockIndex } from '@/actions/App/Http/Controllers/StockController';
import { index as stockOpnameIndex } from '@/actions/App/Http/Controllers/StockOpnameController';
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

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Penjualan (POS)',
        href: '/pos',
        icon: ShoppingCart,
    },
];

const masterNavItems: NavItem[] = [
    {
        title: 'Produk (Barang)',
        href: produkIndex.url(),
        icon: Package,
    },
    {
        title: 'Stok Inventori',
        href: stockIndex.url(),
        icon: Boxes,
    },
    {
        title: 'Stock Opname',
        href: stockOpnameIndex.url(),
        icon: ClipboardList,
    },
    {
        title: 'BOM (Resep)',
        href: bomIndex.url(),
        icon: FileText,
    },
    {
        title: 'Master Vendor',
        href: '/vendors',
        icon: Building2,
    },
    {
        title: 'Produksi',
        href: productionIndex.url(),
        icon: PackageOpen,
    },
    {
        title: 'Satuan Barang',
        href: satuanIndex.url(),
        icon: Ruler,
    },
];

const transactionNavItems: NavItem[] = [

    {
        title: 'Restock',
        href: restockIndex.url(),
        icon: ShoppingBag,
    },
    {
        title: 'Jurnal Umum',
        href: '/journal',
        icon: Landmark,
    },
    {
        title: 'Laba Rugi',
        href: '/profit-loss',
        icon: PieChart,
    },
    {
        title: 'Biaya Operasional',
        href: pengeluaranIndex.url(),
        icon: ReceiptText,
    },
];

const footerNavItems: NavItem[] = [

];

const filteredMainNavItems = computed(() => {
    if (isCashier.value) {
        return mainNavItems.filter(item => item.title === 'Penjualan (POS)');
    }
    return mainNavItems;
});

const filteredTransactionNavItems = computed(() => isCashier.value ? [] : transactionNavItems);
const filteredMasterNavItems = computed(() => isCashier.value ? [] : masterNavItems);

</script>

<template>
<Sidebar collapsible="icon">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" as-child>
                    <Link :href="isCashier ? '/pos' : dashboard()">
                        <AppLogo />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain title="Platform" :items="filteredMainNavItems" />
        <NavMain v-if="filteredTransactionNavItems.length > 0" title="Transaksi" :items="filteredTransactionNavItems" />
        <NavMain v-if="filteredMasterNavItems.length > 0" title="Master Data" :items="filteredMasterNavItems" />
    </SidebarContent>

    <SidebarFooter>
        <NavFooter :items="footerNavItems" />
        <NavUser />
    </SidebarFooter>
</Sidebar>
<slot />
</template>
