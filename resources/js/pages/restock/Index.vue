<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Filter, Trash2, Edit2, MoreHorizontal, Check, ChevronRight, ShoppingCart, History } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

watch([search, vendor, status, perPage], debounce(([newSearch, newVendor, newStatus, newPerPage]) => {
    router.get('/restock', {
        search: newSearch,
        vendor: newVendor,
        status: newStatus,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

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
            return 'bg-secondary/40 text-muted-foreground/60 border-transparent';
    }
};

const formatStatus = (status: string) => {
    if (status === 'bayar_berkala') return 'Bertahap';
    return status.toUpperCase();
};
</script>

<template>
    <Head title="Restock & Inventory" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Supply Chain</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Restock History</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Restock Logs</h1>
                <Link href="/restock/create">
                    <Button class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                        <Plus class="h-4 w-4" />
                        Catat Belanja Baru
                    </Button>
                </Link>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar & Filters -->
            <div class="flex flex-col gap-4 border-b border-border/40 pb-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/40 px-1">Inventory Replenishment</h3>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                            <Input 
                                v-model="search" 
                                placeholder="Cari nota..." 
                                class="pl-9 h-9 rounded-lg w-[200px] border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all" 
                            />
                        </div>
                        <div class="relative">
                            <Filter class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                            <Input 
                                v-model="vendor" 
                                placeholder="Filter vendor..." 
                                class="pl-9 h-9 rounded-lg w-[200px] border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all" 
                            />
                        </div>
                        <Select v-model="status">
                            <SelectTrigger class="h-9 w-[160px] rounded-lg border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-xl border-border/40">
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="lunas">Lunas</SelectItem>
                                <SelectItem value="hutang">Hutang</SelectItem>
                                <SelectItem value="bayar_berkala">Bertahap</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-border/40 overflow-hidden">
                <Table>
                    <TableHeader class="bg-muted/5">
                        <TableRow class="hover:bg-transparent border-none">
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Timestamp & Vendor</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Keterangan</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-center">Items</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-right">Total Biaya</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-center">Status</TableHead>
                            <TableHead class="h-11 px-6 w-[80px] text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="restock in restocks.data" :key="restock.id" class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10">
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                                        <History class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ restock.vendor?.nama || 'Direct Purchase' }}</p>
                                        <p class="text-[11px] font-medium text-muted-foreground/50 mt-0.5">{{ formatDate(restock.tanggal) }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <p class="text-[13px] text-muted-foreground/70 max-w-[200px] line-clamp-2 leading-relaxed">{{ restock.keterangan || 'No internal notes' }}</p>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center">
                                <span class="text-[11px] font-bold font-mono px-2 py-0.5 bg-muted rounded uppercase tracking-tighter">{{ restock.items_count }} Types</span>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right">
                                <span class="text-[15px] font-bold text-foreground tabular-nums">
                                    {{ formatCurrency(restock.total_biaya) }}
                                </span>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center">
                                <Badge variant="secondary" class="h-5 px-1.5 rounded-md text-[9px] font-bold uppercase tracking-widest transition-all" :class="getStatusStyles(restock.status_pembayaran)">
                                    {{ formatStatus(restock.status_pembayaran) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/restock/${restock.id}/edit`">
                                        <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                            <ChevronRight class="h-4 w-4" />
                                        </button>
                                    </Link>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40">
                                            <DropdownMenuItem @click="router.get(`/restock/${restock.id}/edit`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                                <Edit2 class="h-3.5 w-3.5 text-muted-foreground/60" /> Edit Detail
                                            </DropdownMenuItem>
                                            
                                            <DropdownMenuItem v-if="restock.status_pembayaran !== 'lunas'" @click="settleRestock(restock.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-emerald-600 focus:text-emerald-600 focus:bg-emerald-50">
                                                <Check class="h-3.5 w-3.5" /> Pelunasan
                                            </DropdownMenuItem>
                                            
                                            <DropdownMenuItem @click="deleteRestock(restock.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 font-medium">
                                                <Trash2 class="h-3.5 w-3.5" /> Hapus Data
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="restocks.data.length === 0">
                            <TableCell colspan="6" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <ShoppingCart class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada riwayat belanja</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="restocks" v-model:perPage="perPage" />
            </div>
        </div>
    </div>
</template>
