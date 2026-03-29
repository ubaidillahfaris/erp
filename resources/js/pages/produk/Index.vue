<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Plus, Search, Edit2, Trash2, Package, 
    MoreHorizontal, ShoppingCart, TestTube, 
    History, Boxes, PackageOpen, ChevronRight 
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/ProdukController';
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
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: index().url },
];

const search = ref(props.filters.search || '');
const jenis = ref(props.filters.jenis || 'all');
const perPage = ref(props.filters.per_page || String(props.produks.per_page));

watch([search, jenis, perPage], debounce(([newSearch, newJenis, newPerPage]) => {
    router.get(index().url, {
        search: newSearch,
        jenis: newJenis === 'all' ? undefined : newJenis,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const deleteProduk = async (id: number) => {
    if (await confirmDialog('Apakah Anda yakin?', 'Apakah Anda yakin ingin menghapus produk ini? Semua data terkait mungkin ikut terhapus.')) {
        router.delete(destroy({ id }).url);
    }
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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Inventory</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">{{ (jenis || 'All').replace('_', ' ') }} Items</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Katalog Produk</h1>
                <Link :href="create().url">
                    <Button class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                        <Plus class="h-4 w-4" />
                        Tambah Produk
                    </Button>
                </Link>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar & Filter Tabs -->
            <div class="flex items-center justify-between border-b border-border/40 pb-px">
                <div class="flex items-center gap-8 h-12">
                    <button 
                        @click="jenis = 'all'"
                        :class="['h-full border-b-2 text-[13px] font-bold transition-all px-1', jenis === 'all' ? 'border-accent text-accent' : 'border-transparent text-muted-foreground/40 hover:text-foreground']"
                    >
                        Semua Produk
                    </button>
                    <button 
                        @click="jenis = 'raw_material'"
                        :class="['h-full border-b-2 text-[13px] font-bold transition-all px-1', jenis === 'raw_material' ? 'border-accent text-accent' : 'border-transparent text-muted-foreground/40 hover:text-foreground']"
                    >
                        Bahan Baku
                    </button>
                    <button 
                        @click="jenis = 'finished_good'"
                        :class="['h-full border-b-2 text-[13px] font-bold transition-all px-1', jenis === 'finished_good' ? 'border-accent text-accent' : 'border-transparent text-muted-foreground/40 hover:text-foreground']"
                    >
                        Barang Jadi
                    </button>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari nama, SKU..." 
                            class="pl-9 h-9 rounded-lg w-[240px] border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all" 
                        />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-border/40 overflow-hidden">
                <Table>
                    <TableHeader class="bg-muted/5">
                        <TableRow class="hover:bg-transparent border-none">
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Item Details</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Identity</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-right">Price</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 text-center">Stock</TableHead>
                            <TableHead class="h-11 px-6 w-[60px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(produk, idx) in produks.data" :key="produk.id" 
                            class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10"
                        >
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                                        <Package v-if="produk.type === 'finished_good'" class="h-4 w-4" />
                                        <Boxes v-else class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[13px] font-bold text-foreground truncate max-w-[200px]">{{ produk.nama }}</p>
                                        <p class="text-[10px] font-medium text-muted-foreground/40 uppercase tracking-tighter mt-0.5">{{ produk.type?.replace('_', ' ') }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[11px] font-mono font-bold text-foreground/70">#{{ produk.sku || '--' }}</span>
                                    <span class="text-[9px] font-bold text-muted-foreground/30 uppercase tracking-widest italic">{{ (produk.kategori || 'Inventory') }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right">
                                <span class="text-[13px] font-bold text-foreground tabular-nums">{{ formatCurrency(produk.current_price?.retail_price || 0) }}</span>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-center">
                                <div :class="[
                                    'inline-flex flex-col items-center gap-0.5 px-2 py-0.5 rounded border transition-all',
                                    produk.stok <= (produk.stok_minimal || 0)
                                        ? 'bg-red-50 text-red-600 border-red-100'
                                        : 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                ]">
                                    <span class="text-[12px] font-bold tabular-nums leading-none">{{ produk.stok }}</span>
                                    <span class="text-[8px] font-bold uppercase opacity-60 leading-none">{{ produk.unit?.simbol || 'pcs' }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="edit({ id: produk.id }).url">
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
                                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-lg border-border/40">
                                            <DropdownMenuItem @click="router.visit(`/stock/${produk.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                                <History class="h-3.5 w-3.5" /> Lihat Riwayat
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteProduk(produk.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                                <Trash2 class="h-3.5 w-3.5" /> Hapus Produk
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="produks.data.length === 0">
                            <TableCell colspan="5" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <PackageOpen class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Katalog Kosong</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="produks" v-model:perPage="perPage" />
            </div>
        </div>
    </div>
</template>
