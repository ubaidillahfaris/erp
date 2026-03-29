<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Trash2, CheckCircle2, RotateCcw, Eye, MoreHorizontal, ChevronRight, Boxes, History } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, show, edit, destroy } from '@/actions/App/Http/Controllers/ProductionController';
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

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get(index().url, {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Manufacturing</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Production History</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Production Logs</h1>
                <Link :href="create().url">
                    <Button class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                        <Plus class="h-4 w-4" />
                        Mulai Produksi
                    </Button>
                </Link>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar -->
            <div class="flex items-center justify-between border-b border-border/40 pb-px h-12">
                <div class="flex items-center gap-8 h-full">
                     <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/40 px-1">Riwayat Batch</h3>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari No. Referensi..." 
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
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Batch & Produk</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-center">Yield (Target / Aktual)</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-right">Total Biaya</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-center">Status</TableHead>
                            <TableHead class="h-11 px-6 w-[80px] text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="production in productions.data" :key="production.id" class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10">
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                                        <Boxes class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ production.bom?.nama }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] font-mono font-bold text-muted-foreground/40 uppercase tracking-widest">{{ production.sku }}</span>
                                            <span class="text-[10px] text-muted-foreground/20 italic">•</span>
                                            <span class="text-[11px] font-medium text-muted-foreground/50">{{ formatDate(production.tanggal) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-[13px] font-bold text-foreground">
                                        {{ parseFloat(production.actual_yield || 0).toLocaleString('id-ID') }}
                                        <span class="text-muted-foreground/40 font-medium">/ {{ parseFloat(production.target_yield).toLocaleString('id-ID') }}</span>
                                    </span>
                                    <span class="text-[9px] font-bold uppercase tracking-tighter text-muted-foreground/30">Unit Yielded</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right">
                                <div class="flex flex-col items-end gap-0.5">
                                    <span class="text-[14px] font-bold text-foreground tabular-nums">
                                        {{ formatCurrency(production.total_cost || 0) }}
                                    </span>
                                    <Badge v-if="production.is_estimated" class="h-4 px-1 rounded text-[8px] font-bold uppercase bg-orange-50 text-orange-500 border-none">Estimasi</Badge>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center">
                                <Badge variant="secondary" class="h-5 px-1.5 rounded-md text-[9px] font-bold uppercase tracking-widest transition-all" :class="getStatusStyles(production.status)">
                                    {{ formatStatus(production.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="show({ production: production.id }).url">
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
                                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40">
                                            <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5 text-center">Batch Options</DropdownMenuLabel>
                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem v-if="production.status === 'in_progress'" @click="router.get(edit({ production: production.id }).url)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-emerald-600 focus:text-emerald-600 focus:bg-emerald-50">
                                                <CheckCircle2 class="h-3.5 w-3.5" /> Selesaikan
                                            </DropdownMenuItem>

                                            <DropdownMenuItem @click="handleClone(production.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                                <RotateCcw class="h-3.5 w-3.5 text-muted-foreground/60" /> Duplikat Produksi
                                            </DropdownMenuItem>
                                            
                                            <DropdownMenuItem v-if="production.status === 'draft'" @click="deleteProduction(production.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-destructive focus:text-destructive focus:bg-destructive/5">
                                                <Trash2 class="h-3.5 w-3.5" /> Hapus Draft
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="productions.data.length === 0">
                            <TableCell colspan="5" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <History class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Log produksi kosong</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="productions" v-model:perPage="perPage" />
            </div>
        </div>
    </div>
</template>
