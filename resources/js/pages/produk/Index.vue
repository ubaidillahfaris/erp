<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    Plus, Search, Edit2, Trash2, Package,
    MoreHorizontal, ShoppingCart, TestTube,
    History, Boxes, PackageOpen, ChevronRight,
    Pencil, Filter
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, edit, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/ProdukController';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import produk from '@/routes/produk';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    produks: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        jenis?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: index().url },
];

const search = ref(props.filters.search || '');
const jenis = ref(props.filters.jenis || 'all');
const perPage = ref(props.filters.per_page || String(props.produks.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'item', label: 'Item Details', sortKey: 'nama' },
    { key: 'identity', label: 'Identity', sortKey: 'sku' },
    { key: 'price', label: 'Price', align: 'right', sortable: false }, // Price sorting requires complex joins, disabling for now
    { key: 'stock', label: 'Stock', align: 'center', sortKey: 'stok' },
] as const;

const tabs = [
    { value: 'all', label: 'Semua Produk' },
    { value: 'raw_material', label: 'Bahan Baku' },
    { value: 'finished_good', label: 'Barang Jadi' },
];

watch([search, jenis, perPage, sort, direction], debounce(([newSearch, newJenis, newPerPage, newSort, newDirection]) => {
    router.get(index().url, {
        search: newSearch || undefined,
        jenis: newJenis === 'all' ? undefined : newJenis,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const deleteProduk = async (id: number) => {
    if (await confirmDialog('Apakah Anda yakin?', 'Apakah Anda yakin ingin menghapus produk ini? Semua data terkait mungkin ikut terhapus.')) {
        router.delete(destroy({ id }).url);
    }
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Hapus Produk Terpilih?', `Apakah Anda yakin ingin menghapus ${ids.length} produk yang dipilih?`)) {
        router.post(bulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        }, {
            onSuccess: () => {
                // Flash messages handled by server
            }
        });
    }
};

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
<Head title="Katalog Produk" />

<div class="px-6 py-8 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] font-sans">
    <PageHeader title="Katalog Produk" description="Manajemen Stok & Harga" back-href="/dashboard"
        :count="produks.total" />

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="produks" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            @bulk-delete="handleBulkDelete"
            search-placeholder="Cari nama, SKU..." 
            :title="'Katalog Produk'"
            :total-count="produks.total"
        >
            <template #toolbar-actions>
                <Select v-model="jenis">
                    <SelectTrigger class="h-9 w-[180px] rounded-md border-input bg-white text-[13px] font-medium shadow-none focus-visible:ring-accent/5 transition-all font-sans pl-3">
                        <div class="flex items-center gap-2">
                            <Filter class="h-3.5 w-3.5 text-muted-foreground" />
                            <SelectValue placeholder="Jenis Produk" />
                        </div>
                    </SelectTrigger>
                    <SelectContent class="rounded-xl shadow-none border-input font-sans">
                        <SelectItem value="all" class="text-[13px]">Semua Jenis</SelectItem>
                        <SelectItem value="raw_material" class="text-[13px]">Bahan Baku</SelectItem>
                        <SelectItem value="finished_good" class="text-[13px]">Barang Jadi</SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #header-actions>
                <Link :href="create().url">
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Tambah Produk
                    </Button>
                </Link>
            </template>
            <template #cell(item)="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <Package v-if="row.type === 'finished_good'" class="h-4 w-4" />
                        <Boxes v-else class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[13px] font-bold text-foreground truncate max-w-[200px]">{{ row.nama }}</p>
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-tighter mt-0.5">{{
                            row.type?.replace('_', ' ') }}</p>
                    </div>
                </div>
            </template>

            <template #cell(identity)="{ row }">
                <div class="flex flex-col gap-0.5">
                    <span class="text-xs font-mono font-bold text-foreground/70">#{{ row.sku || '--' }}</span>
                    <span class="text-xs font-bold text-muted-foreground uppercase tracking-widest italic">{{
                        (row.kategori || 'Inventory') }}</span>
                </div>
            </template>

            <template #cell(price)="{ row }">
                <span class="text-[13px] font-bold text-foreground tabular-nums">{{
                    formatCurrency(row.current_price?.retail_price || 0) }}</span>
            </template>

            <template #cell(stock)="{ row }">
                <div :class="[
                    'inline-flex flex-col items-center gap-0.5 px-2 py-0.5 rounded border transition-all min-w-[50px]',
                    row.stok <= (row.stok_minimal || 0)
                        ? 'bg-red-50 text-red-600 border-red-100'
                        : 'bg-emerald-50 text-emerald-600 border-emerald-100'
                ]">
                    <span class="text-[12px] font-bold tabular-nums leading-none">{{ row.stok }}</span>
                    <span class="text-xs font-bold uppercase opacity-60 leading-none">{{ row.unit?.simbol || 'pcs'
                    }}</span>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end"
                            class="rounded-xl p-1.5 w-44 shadow-none border-input font-sans">
                            <DropdownMenuItem @click="router.visit(produk.edit(row.id))">
                                <Pencil class="h-3.5 w-3.5" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="router.visit(`/stock/${row.id}`)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                <History class="h-3.5 w-3.5" /> Lihat Riwayat
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteProduk(row.id)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Produk
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <PackageOpen class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Katalog Kosong</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
