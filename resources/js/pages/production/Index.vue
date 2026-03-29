<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Trash2, CheckCircle2, RotateCcw, Eye, MoreHorizontal, ChevronRight, Boxes, History } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, show, edit, destroy, bulkDestroy } from '@/actions/App/Http/Controllers/ProductionController';
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
    productions: {
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
    flash?: {
        success?: string;
        error?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Production Registry', href: '/production' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.productions.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'batch', label: 'Batch & Produk', sortKey: 'sku' },
    { key: 'yield', label: 'Yield (Target / Aktual)', align: 'center' },
    { key: 'cost', label: 'Total Biaya', align: 'right', sortKey: 'total_cost' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, perPage, sort, direction], debounce(([newSearch, newPerPage, newSort, newDirection]) => {
    router.get(index().url, {
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
    if (await confirmDialog('Hapus Produksi Terpilih?', `Apakah Anda yakin ingin menghapus ${ids.length} data produksi yang dipilih? (Produksi yang sudah selesai akan dilewati).`)) {
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

const deleteProduction = async (id: number) => {
    if (await confirmDialog('Hapus Catatan Produksi?', 'Apakah Anda yakin ingin menghapus catatan produksi ini? Tindakan ini tidak dapat dibatalkan.')) {
        router.delete(destroy({ production: id }).url);
    }
};

const handleClone = (id: number) => {
    router.get(create({ reproduce_from: id } as any).url);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(dateString));
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatStatus = (status: string) => {
    const statuses: Record<string, string> = {
        'draft': 'Draft',
        'in_progress': 'Diproses',
        'completed': 'Selesai',
        'cancelled': 'Batal'
    };
    return statuses[status] || status;
};

const getStatusStyles = (status: string) => {
    switch (status) {
        case 'draft':
            return 'bg-secondary/40 text-muted-foreground/60 border-transparent';
        case 'in_progress':
            return 'bg-blue-50 text-blue-600 border-blue-100';
        case 'completed':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'cancelled':
            return 'bg-destructive/5 text-destructive border-destructive/10';
        default:
            return 'bg-muted/50 text-muted-foreground/40 border-transparent';
    }
};
</script>

<template>
    <Head title="Production Registry" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
        <PageHeader 
            title="Production Logs" 
            description="Manajemen Batch & Hasil Manufaktur" 
            back-href="/dashboard"
            :count="productions.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="productions"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                @bulk-delete="handleBulkDelete"
                search-placeholder="Cari No. Referensi..."
                toolbar-title="Riwayat Batch"
                :title="'Production Logs'"
                :total-count="productions.total"
            >
                <template #header-actions>
                    <Link :href="create().url">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Mulai Produksi
                        </Button>
                    </Link>
                </template>
                <template #cell(batch)="{ row }">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                            <Boxes class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.bom?.nama }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] font-mono font-bold text-muted-foreground/40 uppercase tracking-widest">{{ row.sku }}</span>
                                <span class="text-[10px] text-muted-foreground/20 italic">•</span>
                                <span class="text-[11px] font-medium text-muted-foreground/50">{{ formatDate(row.tanggal) }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell(yield)="{ row }">
                    <div class="flex flex-col items-center">
                        <span class="text-[13px] font-bold text-foreground">
                            {{ parseFloat(row.actual_yield || 0).toLocaleString('id-ID') }}
                            <span class="text-muted-foreground/40 font-medium">/ {{ parseFloat(row.target_yield).toLocaleString('id-ID') }}</span>
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-tighter text-muted-foreground/30">Unit Yielded</span>
                    </div>
                </template>

                <template #cell(cost)="{ row }">
                    <div class="flex flex-col items-end gap-0.5">
                        <span class="text-[14px] font-bold text-foreground tabular-nums">
                            {{ formatCurrency(row.total_cost || 0) }}
                        </span>
                        <Badge v-if="row.is_estimated" class="h-4 px-1 rounded text-[8px] font-bold uppercase bg-orange-50 text-orange-500 border-none shadow-none">Estimasi</Badge>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-1.5 rounded-md text-[9px] font-bold uppercase tracking-widest transition-all" :class="getStatusStyles(row.status)">
                        {{ formatStatus(row.status) }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="show({ production: row.id }).url">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                <Eye class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40 font-sans">
                                <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5 text-center text-xs">Batch Ops</DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <DropdownMenuItem v-if="row.status === 'in_progress'" @click="router.get(edit({ production: row.id }).url)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-emerald-600 focus:text-emerald-600 focus:bg-emerald-50">
                                    <CheckCircle2 class="h-3.5 w-3.5" /> Selesaikan
                                </DropdownMenuItem>

                                <DropdownMenuItem @click="handleClone(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <RotateCcw class="h-3.5 w-3.5 text-muted-foreground/60" /> Duplikat Produksi
                                </DropdownMenuItem>
                                
                                <DropdownMenuItem v-if="row.status === 'draft'" @click="deleteProduction(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-destructive focus:text-destructive focus:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Draft
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <History class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Log produksi kosong</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
