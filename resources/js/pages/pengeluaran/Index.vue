<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, FileText, ChevronRight, ReceiptText, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { index as pengeluaranIndex, bulkDestroy as pengeluaranBulkDestroy, create as pengeluaranCreate } from '@/actions/App/Http/Controllers/PengeluaranController';
import { useConfirm } from '@/composables/useConfirm';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

import DataTable from '@/components/DataTable.vue';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    pengeluarans: {
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
    { title: 'Pengeluaran', href: '/pengeluaran' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.pengeluarans.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'detail', label: 'Detail Pengeluaran', sortKey: 'nama_pengeluaran' },
    { key: 'kategori', label: 'Kategori & Tanggal', sortKey: 'tanggal' },
    { key: 'nominal', label: 'Nominal', align: 'right', sortKey: 'nominal' },
] as const;

watch([search, perPage, sort, direction], debounce(([newSearch, newPerPage, newSort, newDirection]) => {
    router.get(pengeluaranIndex().url, {
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
    if (await confirmDialog('Hapus Pengeluaran Terpilih?', `Apakah Anda yakin ingin menghapus ${ids.length} catatan pengeluaran yang dipilih?`)) {
        router.post(pengeluaranBulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        }, {
            onSuccess: () => {
                // Flash messages handled by server
            }
        });
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
</script>

<template>
    <Head title="Pengeluaran Lain-lain" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <PageHeader 
            title="Cash Outflow" 
            description="Manajemen Pengeluaran Operasional" 
            back-href="/dashboard"
            :count="pengeluarans.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="pengeluarans"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                @bulk-delete="handleBulkDelete"
                search-placeholder="Cari keterangan / jenis..."
                toolbar-title="Riwayat Pengeluaran"
                :title="'Cash Outflow'"
                :total-count="pengeluarans.total"
            >
                <template #header-actions>
                    <Link :href="pengeluaranCreate().url">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Tambah Pengeluaran
                        </Button>
                    </Link>
                </template>
                <template #cell(detail)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-orange-50 flex items-center justify-center text-orange-400 border border-orange-100/50 transition-colors group-hover:bg-orange-500 group-hover:text-white group-hover:border-transparent">
                            <ReceiptText class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-bold text-foreground capitalize truncate leading-none">{{ row.nama_pengeluaran }}</p>
                            <p class="text-[10px] text-muted-foreground line-clamp-1 mt-1.5 italic">{{ row.keterangan || 'No internal notes' }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(kategori)="{ row }">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black font-mono px-1.5 py-0.5 bg-secondary text-muted-foreground rounded-sm uppercase tracking-widest">{{ row.jenis_pengeluaran }}</span>
                            <span v-if="row.account" class="text-[9px] font-black px-1.5 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded-sm uppercase line-clamp-1 tracking-tighter">
                                {{ row.account.code }}
                            </span>
                        </div>
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-tight">{{ formatDate(row.tanggal) }}</span>
                    </div>
                </template>

                <template #cell(nominal)="{ row }">
                    <span class="text-[15px] font-bold text-destructive tabular-nums">
                        -{{ formatCurrency(row.nominal) }}
                    </span>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <FileText class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada catatan pengeluaran</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
