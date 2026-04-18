<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Filter, Trash2, Edit2, MoreHorizontal, Check, ChevronRight, ShoppingCart, History as HistoryIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as restockIndex, bulkDestroy as restockBulkDestroy } from '@/actions/App/Http/Controllers/RestockController';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    restocks: {
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
        vendor?: string;
        status?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Restock Registry', href: '/restock' },
];

const search = ref(props.filters.search || '');
const vendor = ref(props.filters.vendor || '');
const status = ref(props.filters.status || 'semua');
const perPage = ref(props.filters.per_page || String(props.restocks.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'vendor', label: 'Timestamp & Vendor', sortKey: 'tanggal' },
    { key: 'info', label: 'Keterangan' },
    { key: 'items', label: 'Items', align: 'center' },
    { key: 'cost', label: 'Total Biaya', align: 'right', sortKey: 'total_biaya' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, vendor, status, perPage, sort, direction], debounce(([newSearch, newVendor, newStatus, newPerPage, newSort, newDirection]) => {
    router.get(restockIndex().url, {
        search: newSearch || undefined,
        vendor: newVendor || undefined,
        status: newStatus === 'semua' ? undefined : newStatus,
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
    if (await confirmDialog('Hapus Restock Terpilih?', `Apakah Anda yakin ingin menghapus ${ids.length} data restock yang dipilih?`)) {
        router.post(restockBulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        }, {
            onSuccess: () => {
                // Flash messages handled by server
            }
        });
    }
};

const settleRestock = async (id: number) => {
    if (await confirmDialog('Lunasi Pembayaran?', 'Apakah Anda yakin ingin melunasi pembayaran restock ini? Sisa hutang akan dibayarkan sepenuhnya.')) {
        router.post(`/restock/${id}/settle`, {}, {
            preserveScroll: true
        });
    }
};

const deleteRestock = async (id: number) => {
    if (await confirmDialog('Hapus Data Restock?', 'Apakah Anda yakin ingin menghapus data restock ini? (Akan mengurangi stok produk dan membatalkan laporan pengeluaran keuangan).')) {
        router.delete(`/restock/${id}`);
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusStyles = (status: string) => {
    switch (status) {
        case 'lunas':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'hutang':
            return 'bg-destructive/5 text-destructive border-destructive/10';
        case 'bayar_berkala':
            return 'bg-orange-50 text-orange-600 border-orange-100';
        default:
            return 'bg-secondary/40 text-muted-foreground border-transparent';
    }
};

const formatStatus = (status: string) => {
    if (status === 'bayar_berkala') return 'Bertahap';
    return status.toUpperCase();
};
</script>

<template>
    <Head title="Restock & Inventory" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Restock Registry" 
            description="Manajemen Stok & Pembelian" 
            back-href="/dashboard"
            :count="restocks.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="restocks"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                @bulk-delete="handleBulkDelete"
                search-placeholder="Cari nota..."
                toolbar-title="Inventory Replenishment"
                :title="'Restock Logs'"
                :total-count="restocks.total"
            >
                <template #header-actions>
                    <Link href="/restock/create">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Catat Belanja Baru
                        </Button>
                    </Link>
                </template>
                <template #toolbar-actions>
                    <div class="flex items-center gap-3">
                        <div class="relative group">
                            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground transition-colors group-focus-within:text-accent" />
                            <Input 
                                v-model="vendor" 
                                placeholder="Filter vendor..." 
                                class="pl-9 h-9 rounded-xl border-slate-200 bg-white text-[13px] font-medium shadow-none focus-visible:ring-accent/5 transition-all font-sans" 
                            />
                        </div>
                        <Select v-model="status">
                            <SelectTrigger class="h-9 w-[140px] rounded-xl border-slate-200 bg-white text-[13px] font-medium shadow-none focus-visible:ring-accent/5 transition-all font-sans">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-none border-slate-200 font-sans">
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="lunas">Lunas</SelectItem>
                                <SelectItem value="hutang">Hutang</SelectItem>
                                <SelectItem value="bayar_berkala">Bertahap</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </template>

                <template #cell(vendor)="{ row }">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                            <HistoryIcon class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.vendor?.nama || 'Direct Purchase' }}</p>
                            <p class="text-xs font-medium text-muted-foreground mt-0.5">{{ formatDate(row.tanggal) }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(info)="{ row }">
                    <p class="text-[13px] text-muted-foreground max-w-[200px] line-clamp-2 leading-relaxed tracking-tight">{{ row.keterangan || 'No internal notes' }}</p>
                </template>

                <template #cell(items)="{ row }">
                    <span class="text-xs font-bold font-mono px-2 py-0.5 bg-muted rounded uppercase tracking-tighter text-muted-foreground">{{ row.items_count }} Types</span>
                </template>

                <template #cell(cost)="{ row }">
                    <span class="text-[15px] font-bold text-foreground tabular-nums tracking-tight">
                        {{ formatCurrency(row.total_biaya) }}
                    </span>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-1.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all" :class="getStatusStyles(row.status_pembayaran)">
                        {{ formatStatus(row.status_pembayaran) }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/restock/${row.id}/edit`">
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
                                <DropdownMenuLabel class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-2 py-1.5 text-center text-xs">Procurement Ops</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="router.get(`/restock/${row.id}/edit`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <Edit2 class="h-3.5 w-3.5 text-muted-foreground" /> Edit Detail
                                </DropdownMenuItem>
                                
                                <DropdownMenuItem v-if="row.status_pembayaran !== 'lunas'" @click="settleRestock(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-emerald-600 focus:text-emerald-600 focus:bg-emerald-50">
                                    <Check class="h-3.5 w-3.5" /> Pelunasan
                                </DropdownMenuItem>
                                
                                <DropdownMenuItem @click="deleteRestock(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 font-medium">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Data
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <ShoppingCart class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada riwayat belanja</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
