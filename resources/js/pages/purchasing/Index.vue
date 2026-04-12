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
const status = ref(props.filters.status || 'semua');
const typeFilter = ref(props.filters.type || 'semua');

const columns = [
    { key: 'trx', label: 'Transaksi', sortKey: 'tanggal' },
    { key: 'vendor', label: 'Vendor & Tipe', sortKey: 'vendor_id' },
    { key: 'tanda', label: 'Nilai & Status', sortable: false },
];

watch([search, perPage, sort, direction, status, typeFilter], debounce(([s, p, st, d, stt, tp]) => {
    router.get(index().url, {
        search: s || undefined,
        per_page: p,
        sort: st || undefined,
        direction: st ? (d || 'asc') : undefined,
        status: stt !== 'semua' ? stt : undefined,
        type: tp !== 'semua' ? tp : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

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

    <div class="max-w-7xl mx-auto w-full flex flex-col gap-4">
        <!-- Filter Toolbar -->
        <div class="flex flex-col sm:flex-row items-center gap-3 bg-white p-4 rounded-xl border border-slate-200">
            <div class="flex-1 flex gap-4 w-full">
                <Select v-model="status">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Semua Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua Status</SelectItem>
                        <SelectItem value="draft">Draft (Belum Final)</SelectItem>
                        <SelectItem value="finalized">Finalized (Selesai)</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="typeFilter">
                    <SelectTrigger class="w-[250px]">
                        <SelectValue placeholder="Tipe Transaksi" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="semua">Semua Tipe Inbound</SelectItem>
                        <SelectItem value="purchase">Pembelian Stok</SelectItem>
                        <SelectItem value="gift">Pemberian/Bonus</SelectItem>
                        <SelectItem value="adjustment">Penyesuaian Naik</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            
            <div class="flex-shrink-0">
                <Link href="/purchasing/create">
                    <Button class="gap-2 bg-primary">
                        <Plus class="h-4 w-4" />
                        Tambah Inbound
                    </Button>
                </Link>
            </div>
        </div>

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
            @sort-change="handleSortChange"
            :total-count="purchases.total"
        >
            <template #cell(trx)="{ row }">
                <div class="flex items-center gap-4">
                    <div :class="[
                        'h-10 w-10 shrink-0 rounded-xl flex items-center justify-center font-bold',
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
                    <Badge variant="outline" :class="[typeThemes[row.transaction_type], 'text-xs rounded']">
                        {{ typeLabels[row.transaction_type] || row.transaction_type }}
                    </Badge>
                </div>
            </template>

            <template #cell(tanda)="{ row }">
                <div class="flex flex-col gap-1 items-start">
                    <span class="text-sm font-bold">{{ formatCurrency(row.total_biaya) }}</span>
                    <Badge v-if="row.status === 'finalized'" variant="secondary" class="bg-emerald-50 text-emerald-700 border-none">
                        Finalized
                    </Badge>
                    <Badge v-else variant="secondary" class="bg-slate-100 text-slate-700 border-none">
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
                        <DropdownMenuContent align="end" class="w-48 shadow-none ">
                            <DropdownMenuItem as-child>
                                <Link :href="`/purchasing/${row.id}`" class="flex w-full items-center gap-2">
                                    <Eye class="h-4 w-4" /> Lihat Detail
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child v-if="row.status === 'draft'">
                                <Link :href="`/purchasing/${row.id}/edit`" class="flex w-full items-center gap-2">
                                    <Edit2 class="h-4 w-4" /> Edit Pembelian
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator v-if="row.status === 'draft'" />
                            <DropdownMenuItem v-if="row.status === 'draft'" @click="handleDelete(row.id)" class="text-destructive flex w-full items-center gap-2 focus:bg-destructive/10 focus:text-destructive">
                                <Trash2 class="h-4 w-4" /> Hapus Transaksi
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
