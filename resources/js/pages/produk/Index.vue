<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router, WhenVisible } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, Loader2, Package, MoreHorizontal, ShoppingCart, TestTube, History } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/ProdukController';

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

const deleteProduk = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
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
<Head title="Daftar Produk" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Daftar Produk</h1>
                <p class="text-sm text-muted-foreground mt-1">Kelola data barang dagangan, stok, dan kategori.</p>
            </div>
            <Link :href="create().url">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Produk
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Katalog Produk</h3>
                <div class="flex items-center gap-4 w-full max-w-2xl">
                    <Select v-model="jenis">
                        <SelectTrigger class="w-[200px] rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10">
                            <SelectValue placeholder="Semua Jenis" />
                        </SelectTrigger>
                        <SelectContent class="rounded-none">
                            <SelectGroup>
                                <SelectItem value="all">Semua Jenis</SelectItem>
                                <SelectItem value="raw_material">Raw Material</SelectItem>
                                <SelectItem value="intermediate_good">Bahan Setengah Jadi</SelectItem>
                                <SelectItem value="finished_good">Finished Good</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari produk (Nama, SKU, Barcode)..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="produks" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">SKU</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Kategori</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Barcode</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Stok Min</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Harga Beli</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Harga Jual</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="produk in produks.data" :key="produk.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-mono text-xs">{{ produk.sku }}</TableCell>
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">{{ produk.nama }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ produk.kategori || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm text-muted-foreground">{{ produk.barcode || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-center text-sm font-medium">{{ produk.stok_minimal }}</TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-medium text-muted-foreground">
                                {{ formatCurrency(produk.current_price?.purchase_price || 0) }}
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-bold text-primary">
                                {{ formatCurrency(produk.current_price?.retail_price || 0) }}
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <Badge :variant="produk.is_active ? 'default' : 'secondary'" class="rounded-none text-[10px] px-2 py-0">
                                    {{ produk.is_active ? 'AKTIF' : 'NON-AKTIF' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="rounded-none">
                                        <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Aksi Cepat</DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <Link :href="`/restock/create?produk_id=${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <ShoppingCart class="mr-2 h-4 w-4" />
                                                <span>Belanja (Restock)</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <Link v-if="produk.type !== 'finished_good'"
                                            :href="`/production/create?produk_id=${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <TestTube class="mr-2 h-4 w-4" />
                                                <span>Gunakan Produksi</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <Link :href="`/stock/${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <History class="mr-2 h-4 w-4" />
                                                <span>History Mutasi</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuSeparator />

                                        <Link :href="edit({ id: produk.id }).url">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Edit2 class="mr-2 h-4 w-4" />
                                                <span>Edit Produk</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="deleteProduk(produk.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Produk</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="produks.data.length === 0">
                            <TableCell colspan="9" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Tidak ada produk ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="produks" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
