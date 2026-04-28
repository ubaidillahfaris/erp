<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Building2, ChevronRight, Calculator, Trash2, Box } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal, FileText, Pencil } from 'lucide-vue-next';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    assets: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Aset Tetap', href: '/fixed-assets' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.assets.per_page));
const sort = ref(props.filters.sort || 'acquisition_date');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'asset', label: 'Informasi Aset', sortKey: 'name' },
    { key: 'acquisition', label: 'Perolehan', sortKey: 'acquisition_date' },
    { key: 'rate', label: 'Rate (%)', align: 'center' },
    { key: 'value', label: 'Nilai Buku', align: 'right', sortKey: 'current_book_value' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, perPage, sort, direction], debounce(([newSearch, newPerPage, newSort, newDirection]) => {
    router.get('/fixed-assets', {
        search: newSearch || undefined,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value / 100 || 0); // Convert cents to IDR
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'active': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'disposed': return 'bg-slate-50 text-slate-600 border-slate-100';
        case 'fully_depreciated': return 'bg-orange-50 text-orange-600 border-orange-100';
        default: return 'bg-secondary text-secondary-foreground';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'active': return 'AKTIF';
        case 'disposed': return 'DIHENTIKAN';
        case 'fully_depreciated': return 'PENYUSUTAN PENUH';
        default: return status.toUpperCase();
    }
};
</script>

<template>
<Head title="Daftar Aset Tetap" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader title="Fixed Assets" description="Manajemen & Penyusutan Aset Tetap" back-href="/dashboard"
        :count="assets.total" />

    <div class="w-full max-w-7xl mx-auto">
        <DataTable :data="assets" :columns="columns" v-model:search="search" v-model:perPage="perPage" :sort="sort"
            :direction="direction as any" @sort-change="handleSortChange"
            search-placeholder="Cari nama aset, kode, atau kategori..." toolbar-title="Inventaris Aset"
            :title="'Aset Tetap'" :total-count="assets.total">
            <template #header-actions>
                <div class="flex gap-2">
                    <Link href="/accounting/depreciation">
                        <Button variant="outline">
                            <Calculator class="h-4 w-4" />
                            Kelola Penyusutan
                        </Button>
                    </Link>
                    <Link href="/fixed-assets/create">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Tambah Aset
                        </Button>
                    </Link>
                </div>
            </template>

            <template #cell(asset)="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 shrink-0 rounded-lg bg-peach-50 flex items-center justify-center text-peach-500 border border-peach-100/50">
                        <Building2 class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[13px] font-bold text-foreground capitalize truncate leading-none">{{ row.name }}
                        </p>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span
                                class="text-[9px] font-black font-mono px-1 py-0.5 bg-slate-100 text-slate-500 rounded-sm">{{
                                    row.asset_code }}</span>
                            <span class="text-[9px] font-bold text-muted-foreground italic uppercase tracking-wider">{{
                                row.category }}</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #cell(acquisition)="{ row }">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[12px] font-bold text-foreground">{{ formatCurrency(row.acquisition_cost)
                        }}</span>
                    <span class="text-[10px] text-muted-foreground font-medium uppercase tracking-tight">{{
                        formatDate(row.acquisition_date) }}</span>
                </div>
            </template>

            <template #cell(rate)="{ row }">
                <div class="flex flex-col items-center">
                    <span class="text-[12px] font-black text-slate-700">{{ ((12 / row.useful_life_months) * 100).toFixed(1) }}%</span>
                    <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-tighter">Per Tahun</span>
                </div>
            </template>

            <template #cell(value)="{ row }">
                <div class="flex flex-col items-end gap-0.5">
                    <span class="text-[14px] font-black text-foreground tabular-nums">{{
                        formatCurrency(row.current_book_value) }}</span>
                    <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-tighter">{{
                        row.useful_life_months }} BULAN MASA MANFAAT</span>
                </div>
            </template>

            <template #cell(status)="{ row }">
                <Badge :class="['text-[9px] font-black px-2 py-0.5 rounded-full border', getStatusColor(row.status)]">
                    {{ getStatusLabel(row.status) }}
                </Badge>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-foreground transition-all">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end"
                            class="rounded-xl p-1.5 w-48 shadow-none border-slate-200 font-sans">
                            <DropdownMenuLabel
                                class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest px-2.5 py-2">
                                Opsi Aset</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem @click="router.visit(`/fixed-assets/${row.id}`)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-bold">
                                <Search class="h-3.5 w-3.5" /> Lihat Detail Aset
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="router.visit(`/fixed-assets/${row.id}/edit`)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-bold">
                                <Pencil class="h-3.5 w-3.5" /> Edit Aset
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-bold text-destructive focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Aset
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <Box class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada aset tetap
                        terdaftar</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>

<style scoped>
.bg-peach-50 {
    background-color: #fff5f0;
}

.text-peach-500 {
    color: #ff6b35;
}

.border-peach-100\/50 {
    border-color: rgba(255, 107, 53, 0.1);
}
</style>
