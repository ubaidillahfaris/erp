<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    Plus, Search, Edit2, Trash2, Package,
    MoreHorizontal, ShoppingCart, TestTube,
    History as HistoryIcon, Boxes, PackageOpen, ChevronRight,
    Pencil, Filter
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, edit, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/ProductController';
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
import product from '@/routes/product';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    products: {
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
        active_filters?: Record<string, any>;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Product', href: index().url },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.products.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');
const activeFilters = ref(props.filters.active_filters || {});

const columns = [
    { key: 'item', label: 'Item Details', sortKey: 'name' },
    { key: 'identity', label: 'Identity', sortKey: 'sku' },
    { key: 'price', label: 'Price', align: 'right', sortable: false }, // Price sorting requires complex joins, disabling for now
    { key: 'stock', label: 'Stock', align: 'center', sortKey: 'stok' },
] as const;

const tabs = [
    { value: 'all', label: 'All Products' },
    { value: 'raw_material', label: 'Raw Materials' },
    { value: 'finished_good', label: 'Finished Goods' },
];

const filterOptions = [
    {
        key: 'jenis',
        label: 'Product Type',
        options: [
            { value: 'raw_material', label: 'Raw Materials' },
            { value: 'finished_good', label: 'Finished Goods' },
        ]
    }
];

watch([search, perPage, sort, direction, activeFilters], debounce(([newSearch, newPerPage, newSort, newDirection, newFilters]) => {
    router.get(index().url, {
        search: newSearch || undefined,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined,
        active_filters: Object.keys(newFilters).length > 0 ? newFilters : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300), { deep: true });

const { confirmDialog } = useConfirm();

const deleteProduct = async (id: number) => {
    if (await confirmDialog('Apakah Anda yakin?', 'Are you sure you want to delete product ini? Semua data terkait mungkin ikut terhapus.')) {
        router.delete(destroy({ id }).url);
    }
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Delete Product Terpilih?', `Are you sure you want to delete ${ids.length} product yang dipilih?`)) {
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
<Head title="Product Catalog" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader title="Product Catalog" description="Stock & Price Management" back-href="/dashboard"
        :count="products.total" />

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="products" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            :sort="sort"
            :direction="direction as any"
            :filter-options="filterOptions"
            v-model:active-filters="activeFilters"
            @sort-change="handleSortChange"
            @bulk-delete="handleBulkDelete"
            search-placeholder="Search name, SKU..." 
            :title="'Product Catalog'"
            :total-count="products.total"
        >
            <template #toolbar-actions>
                <!-- Add other toolbar actions here if needed -->
            </template>
            <template #header-actions>
                <Link :href="create().url">
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Add Product
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
                        <p class="text-[13px] font-bold text-foreground truncate max-w-[200px] leading-none">{{ row.name }}</p>
                        <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest mt-1.5 opacity-70">{{
                            row.type?.replace('_', ' ') }}</p>
                    </div>
                </div>
            </template>

            <template #cell(identity)="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="text-[11px] font-mono font-bold text-foreground/80 tracking-tight bg-slate-100 px-1.5 py-0.5 rounded w-fit">#{{ row.sku || '--' }}</span>
                    <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest pl-0.5">{{
                        (row.category?.name || 'Inventory') }}</span>
                </div>
            </template>

            <template #cell(price)="{ row }">
                <div class="flex flex-col items-end">
                    <span class="text-[13px] font-bold text-foreground tabular-nums">{{
                        formatCurrency(row.current_price?.retail_price || 0) }}</span>
                    <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-tighter">Harga Retail</span>
                </div>
            </template>

            <template #cell(stock)="{ row }">
                <div :class="[
                    'inline-flex flex-col items-center gap-1 px-2.5 py-1 rounded-lg border transition-all min-w-[60px]',
                    row.stok <= (row.min_stock || 0)
                        ? 'bg-rose-50 text-rose-600 border-rose-100 shadow-sm shadow-rose-100/50'
                        : 'bg-emerald-50 text-emerald-600 border-emerald-100 shadow-sm shadow-emerald-100/50'
                ]">
                    <span class="text-[13px] font-bold tabular-nums leading-none">{{ Number(row.stok || 0) }}</span>
                    <span class="text-[10px] font-bold uppercase opacity-60 leading-none tracking-widest">{{ row.unit?.symbol || 'pcs'
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
                            class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                            <DropdownMenuItem @click="router.visit(product.edit(row.id))">
                                <Pencil class="h-3.5 w-3.5" /> Edit
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="router.visit(`/stock/${row.id}`)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                <HistoryIcon class="h-3.5 w-3.5" /> View History
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteProduct(row.id)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Delete Product
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <PackageOpen class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Catalog Empty</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
