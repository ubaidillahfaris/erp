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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
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
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar -->
            <div class="flex items-center justify-between border-b border-border/40 pb-px h-12">
                <div class="flex items-center gap-8 h-full">
                     <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/40 px-1">Riwayat Pengeluaran</h3>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari keterangan / jenis..." 
                            class="pl-9 h-9 rounded-lg w-[280px] border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all" 
                        />
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-border/40 overflow-hidden">
                <Table>
                    <TableHeader class="bg-muted/5">
                        <TableRow class="hover:bg-transparent border-none">
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Detail Pengeluaran</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Kategori & Tanggal</TableHead>
                            <TableHead class="h-11 px-6 text-right text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Nominal</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in pengeluarans.data" :key="item.id" class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10">
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 shrink-0 rounded-xl bg-orange-50 flex items-center justify-center text-orange-400 border border-orange-100/50 transition-colors group-hover:bg-orange-500 group-hover:text-white group-hover:border-transparent">
                                        <ReceiptText class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ item.nama_pengeluaran }}</p>
                                        <p class="text-[11px] text-muted-foreground/60 line-clamp-1 mt-0.5">{{ item.keterangan || 'No internal notes' }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold font-mono px-1.5 py-0.5 bg-secondary text-muted-foreground rounded uppercase tracking-tighter">{{ item.jenis_pengeluaran }}</span>
                                    </div>
                                    <span class="text-[11px] font-medium text-muted-foreground/50">{{ formatDate(item.tanggal) }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <span class="text-[15px] font-bold text-destructive tabular-nums">
                                    -{{ formatCurrency(item.nominal) }}
                                </span>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="pengeluarans.data.length === 0">
                            <TableCell colspan="3" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <FileText class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada catatan pengeluaran</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="pengeluarans" v-model:perPage="perPage" />
            </div>
        </div>
    </div>
</template>
