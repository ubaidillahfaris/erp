<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { listAll, index } from '@/actions/App/Http/Controllers/CustomerPriceController';
import debounce from 'lodash/debounce';
import { Search, User, TrendingUp, ChevronRight, Tag } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout
defineOptions({ layout: AppLayout });

const props = defineProps<{
    customers: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customer Pricing', href: '/customer-prices' },
];

const search = ref(props.filters.search || '');

const columns = [
    { key: 'customer', label: 'Profil Pelanggan' },
    { key: 'active_prices_count', label: 'Harga Khusus Aktif', align: 'center' as const },
];

// Search watch
watch(search, debounce((newSearch) => {
    router.get(listAll().url, {
        search: newSearch || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
    <Head title="Indeks Harga Khusus" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <PageHeader 
            title="Indeks Harga Khusus" 
            description="Daftar pelanggan dengan pengaturan harga spesial." 
            back-href="/dashboard"
            :count="customers.total"
        />
        
        <div class="w-full max-w-7xl mx-auto">
            <DataTable 
                :data="customers" 
                :columns="columns" 
                v-model:search="search"
                search-placeholder="Cari nama pelanggan..."
                :show-selection="false"
            >
                <!-- CELL: Customer Profile -->
                <template #cell(customer)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 shrink-0 border border-slate-200">
                            <User class="h-5 w-5" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900">{{ row.name }}</span>
                            <span class="text-xs text-slate-500">{{ row.phone || 'Tanpa No. HP' }}</span>
                        </div>
                    </div>
                </template>

                <!-- CELL: Active Prices Count -->
                <template #cell(active_prices_count)="{ row }">
                    <div class="flex items-center justify-center">
                        <Badge 
                            v-if="row.active_prices_count > 0"
                            class="bg-emerald-50 text-emerald-700 hover:bg-emerald-50 border-emerald-100 px-3 py-1 rounded-full font-bold gap-1.5"
                        >
                            <Tag class="h-3 w-3" />
                            {{ row.active_prices_count }} Item
                        </Badge>
                        <Badge 
                            v-else
                            variant="secondary"
                            class="text-slate-400 border-none px-3 py-1 rounded-full italic font-medium"
                        >
                            Belum Ada
                        </Badge>
                    </div>
                </template>

                <!-- ACTIONS -->
                <template #actions="{ row }">
                    <Button 
                        as-child
                        variant="ghost" 
                        size="sm" 
                        class="h-9 px-4 rounded-xl font-bold uppercase tracking-widest text-[10px] gap-2 hover:bg-accent hover:text-white transition-all group"
                    >
                        <Link :href="index({ customer: row.id }).url">
                            Kelola Harga
                            <ChevronRight class="h-3 w-3 transition-transform group-hover:translate-x-0.5" />
                        </Link>
                    </Button>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 gap-2">
                        <TrendingUp class="h-12 w-12 text-slate-200 mb-2" />
                        <p class="text-lg font-bold text-slate-400">Tidak ada data pelanggan</p>
                        <p class="text-sm text-slate-400">Silakan tambahkan pelanggan terlebih dahulu di Master Customer.</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
.font-sans {
    font-family: 'Inter', sans-serif;
}
</style>
