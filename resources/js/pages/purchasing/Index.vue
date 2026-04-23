<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { index, destroy } from '@/actions/App/Http/Controllers/PurchaseController';
import debounce from 'lodash/debounce';
import { Plus, CheckCircle2, ChevronRight, FileText, Search, Settings2, Trash2, Edit2, FileIcon, Eye, Info, MoreHorizontal } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { useConfirm } from '@/composables/useConfirm';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    purchases: any;
    filters: any;
}>();

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.purchases.per_page));
const sort = ref(props.filters.sort || 'tanggal');
const direction = ref(props.filters.direction || 'desc');
const activeFilters = ref(props.filters.active_filters || {});

const columns = [
    { key: 'trx', label: 'Transaksi', sortKey: 'tanggal' },
    { key: 'vendor', label: 'Vendor & Tipe', sortKey: 'vendor_id' },
    { key: 'tanda', label: 'Nilai & Status', sortable: false },
];

const filterOptions = [
    {
        key: 'status',
        label: 'Status',
        options: [
            { value: 'draft', label: 'Draft (Belum Final)' },
            { value: 'finalized', label: 'Finalized (Selesai)' },
        ]
    },
    {
        key: 'transaction_type',
        label: 'Tipe Inbound',
        options: [
            { value: 'purchase', label: 'Pembelian Stok' },
            { value: 'gift', label: 'Pemberian/Bonus' },
            { value: 'adjustment', label: 'Penyesuaian Naik' },
        ]
    }
];

watch([search, perPage, sort, direction, activeFilters], debounce(([s, p, st, d, f]) => {
    router.get(index().url, {
        search: s || undefined,
        per_page: p,
        sort: st || undefined,
        direction: st ? (d || 'asc') : undefined,
        active_filters: Object.keys(f).length > 0 ? f : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300), { deep: true });

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const { confirmDialog } = useConfirm();

const handleDelete = async (id: number) => {
    if (await confirmDialog('Hapus Draft?', 'Transaksi yang dihapus tidak bisa dikembalikan.')) {
        router.delete(destroy({ purchase: id }).url);
    }
};

const typeLabels: Record<string, string> = {
    purchase: 'Pembelian (Hutang/Tunai)',
    gift: 'Pemberian (Bonus/Hadiah)',
    adjustment: 'Penyesuaian (Selisih Lebih)'
};

const typeThemes: Record<string, string> = {
    purchase: 'bg-blue-100 text-blue-800 border-blue-200',
    gift: 'bg-purple-100 text-purple-800 border-purple-200',
    adjustment: 'bg-orange-100 text-orange-800 border-orange-200'
};
</script>

<template>
<Head title="Purchasing Inbound" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <PageHeader 
        title="Pembelian & Inbound" 
        description="Kelola riwayat pembelian barang, penerimaan bonus, dan penyesuaian stok." 
        back-href="/dashboard" 
        :count="purchases.total" 
    />

    <div class="max-w-7xl mx-auto w-full">
        <!-- Data Table -->
        <DataTable 
            :data="purchases" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari no invoice, vendor..." 
            toolbar-title="Dokumen Inbound" 
            :sort="sort"
            :direction="direction as any"
            :filter-options="filterOptions"
            v-model:active-filters="activeFilters"
            @sort-change="handleSortChange"
            :total-count="purchases.total"
        >
            <template #header-actions>
                <Link href="/purchasing/create">
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Tambah Inbound
                    </Button>
                </Link>
            </template>

            <template #cell(trx)="{ row }">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'h-10 w-10 shrink-0 rounded-xl flex items-center justify-center font-bold transition-colors group-hover:bg-accent group-hover:text-white',
                        row.status === 'finalized' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                    ]">
                        <CheckCircle2 v-if="row.status === 'finalized'" class="h-5 w-5" />
                        <FileIcon v-else class="h-5 w-5" />
                    </div>
                    <div>
                        <Link :href="`/purchasing/${row.id}`" class="text-sm font-bold text-foreground hover:underline flex items-center gap-2">
                            {{ row.no_invoice || `TRX-${String(row.id).padStart(5, '0')}` }}
                        </Link>
                        <p class="text-xs text-muted-foreground mt-0.5 whitespace-nowrap">{{ formatDate(row.tanggal) }}</p>
                    </div>
                </div>
            </template>

            <template #cell(vendor)="{ row }">
                <div class="flex flex-col gap-1.5 items-start">
                    <span class="text-sm font-medium">{{ row.vendor?.nama || 'Tanpa Vendor (Internal)' }}</span>
                    <Badge variant="outline" :class="[typeThemes[row.transaction_type], 'text-xs rounded font-bold uppercase tracking-wider h-5']">
                        {{ typeLabels[row.transaction_type]?.split(' (')[0] || row.transaction_type }}
                    </Badge>
                </div>
            </template>

            <template #cell(tanda)="{ row }">
                <div class="flex flex-col gap-1 items-start">
                    <span class="text-sm font-bold tabular-nums">{{ formatCurrency(row.total_biaya) }}</span>
                    <Badge v-if="row.status === 'finalized'" variant="secondary" class="bg-emerald-50 text-emerald-700 border-none h-5 font-bold uppercase tracking-widest text-[10px]">
                        Finalized
                    </Badge>
                    <Badge v-else variant="secondary" class="bg-slate-100 text-slate-700 border-none h-5 font-bold uppercase tracking-widest text-[10px]">
                        Draft
                    </Badge>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex justify-end pr-2">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-colors">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48 shadow-none rounded-xl p-1.5 border-slate-200">
                            <DropdownMenuItem @click="router.visit(`/purchasing/${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                <Eye class="h-3.5 w-3.5" /> Lihat Detail
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="row.status === 'draft'" @click="router.visit(`/purchasing/${row.id}/edit`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                <Edit2 class="h-3.5 w-3.5" /> Edit Pembelian
                            </DropdownMenuItem>
                            <DropdownMenuSeparator v-if="row.status === 'draft'" />
                            <DropdownMenuItem v-if="row.status === 'draft'" @click="handleDelete(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Transaksi
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="py-12 flex flex-col items-center opacity-30">
                    <FileText class="h-10 w-10 text-muted-foreground mb-4" />
                    <p class="text-sm font-bold uppercase tracking-widest">Belum ada transaksi</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
