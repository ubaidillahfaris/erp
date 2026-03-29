<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, FileText, ChevronRight, ReceiptText } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengeluaran', href: '/pengeluaran' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.pengeluarans.per_page));

const columns = [
    { key: 'detail', label: 'Detail Pengeluaran' },
    { key: 'kategori', label: 'Kategori & Tanggal' },
    { key: 'nominal', label: 'Nominal', align: 'right' },
] as const;

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/pengeluaran', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Operational</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Pengeluaran Lain</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Cash Outflow</h1>
                <Link href="/pengeluaran/create">
                    <Button class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                        <Plus class="h-4 w-4" />
                        Catat Expense
                    </Button>
                </Link>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="pengeluarans"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari keterangan / jenis..."
                toolbar-title="Riwayat Pengeluaran"
            >
                <template #cell(detail)="{ row }">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-orange-50 flex items-center justify-center text-orange-400 border border-orange-100/50 transition-colors group-hover:bg-orange-500 group-hover:text-white group-hover:border-transparent">
                            <ReceiptText class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.nama_pengeluaran }}</p>
                            <p class="text-[11px] text-muted-foreground/60 line-clamp-1 mt-0.5">{{ row.keterangan || 'No internal notes' }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(kategori)="{ row }">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 bg-secondary text-muted-foreground rounded uppercase tracking-tighter">{{ row.jenis_pengeluaran }}</span>
                        </div>
                        <span class="text-[11px] font-medium text-muted-foreground/50">{{ formatDate(row.tanggal) }}</span>
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
