<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Package, Search, Plus, Trash2, Edit, MoreHorizontal, BookOpen, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, edit, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/BOMController';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    boms: {
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
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Production BOM', href: '/bom' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.boms.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'reference', label: 'Reference & Product', sortKey: 'sku' },
    { key: 'info', label: 'Recipe Info', sortKey: 'name' },
    { key: 'estimation', label: 'Estimation', align: 'center' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, perPage, sort, direction], debounce(([newSearch, newPerPage, newSort, newDirection]) => {
    router.get('/bom', {
        search: newSearch || undefined,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Hapus Resep Terpilih?', `Are you sure you want to delete ${ids.length} resep yang dipilih?`)) {
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

const deleteBom = async (id: number) => {
    if (await confirmDialog('Hapus Resep BOM?', 'Are you sure you want to delete resep productsi ini? Data yang terkait mungkin ikut terhapus.')) {
        router.delete(destroy.url({ bom: id }));
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};
</script>

<template>
    <Head title="Bill of Materials" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Recipes & BOM" 
            description="Master Formula & Komposisi Productsi" 
            back-href="/dashboard"
            :count="boms.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="boms"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                @bulk-delete="handleBulkDelete"
                search-placeholder="Cari resep atau product..."
                toolbar-title="Master Formula"
                :title="'Recipes & BOM'"
                :total-count="boms.total"
            >
                <template #header-actions>
                    <Link :href="create.url()">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Buat Resep Baru
                        </Button>
                    </Link>
                </template>
                <template #cell(reference)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                            <BookOpen class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-bold text-foreground capitalize truncate leading-none">{{ row.product.name }}</p>
                            <p class="text-[11px] font-mono font-bold text-muted-foreground uppercase tracking-widest mt-1.5">Ref: {{ row.sku }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(info)="{ row }">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[13px] font-bold text-foreground">{{ row.name || 'Unnamed recipe' }}</span>
                        <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground italic tracking-tight opacity-70">
                            <span class="font-bold text-foreground/70">{{ row.expected_yield }} {{ row.yield_unit?.name || row.product?.unit?.name }}</span>
                            <span>•</span>
                            <span>SKU: {{ row.product.sku }}</span>
                        </div>
                    </div>
                </template>

                <template #cell(estimation)="{ row }">
                    <span class="text-[13px] font-bold text-foreground tabular-nums">
                        {{ formatCurrency(row.product?.current_price?.purchase_price || 0) }}
                    </span>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-2 rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all" :class="row.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-muted/50 text-muted-foreground border-transparent'">
                        {{ row.is_active ? 'Active' : 'Draft' }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="edit.url({ bom: row.id.toString() })">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-none border-slate-200 font-sans">
                                <DropdownMenuLabel class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-2 py-1.5 text-center text-xs">BOM Ops</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="deleteBom(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 font-medium">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Resep
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <Package class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada resep productsi</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
