<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Eye, Search, Filter, Trash2, Edit2, MoreHorizontal, Check } from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

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
    { title: 'Restock / Belanja', href: '#' },
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

const settleRestock = (id: number) => {
    if (confirm('Apakah Anda yakin ingin melunasi pembayaran restock ini?')) {
        router.post(`/restock/${id}/settle`, {}, {
            preserveScroll: true
        });
    }
};

const deleteRestock = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus data restock ini? (Akan mengurangi stok dan membatalkan laporan pengeluaran).')) {
        router.delete(`/restock/${id}`);
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'lunas': return 'default';
        case 'hutang': return 'destructive';
        case 'bayar_berkala': return 'outline';
        default: return 'secondary';
    }
};

const formatStatus = (status: string) => {
    return status.replace('_', ' ').toUpperCase();
};
</script>

<template>
<Head title="Restock / Belanja" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Restock / Belanja</h1>
                <p class="text-sm text-muted-foreground mt-1">Catat transaksi belanja barang (kulakan) dari vendor.</p>
            </div>
            <Link href="/restock/create">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Catat Belanja Baru
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Riwayat Restock</h3>
                <div class="flex items-center gap-4 w-full max-w-2xl">
                    <div class="w-[180px]">
                        <Select v-model="status">
                            <SelectTrigger class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent class="rounded-none">
                                <SelectItem value="semua">Semua Status</SelectItem>
                                <SelectItem value="lunas">Lunas</SelectItem>
                                <SelectItem value="hutang">Hutang</SelectItem>
                                <SelectItem value="bayar_berkala">Bertahap</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="relative flex-1">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari nota..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                    
                    <div class="relative flex-1">
                        <Filter class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="vendor" 
                            placeholder="Filter vendor..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="restocks" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tanggal</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Vendor / Supplier</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Keterangan</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Jenis Item</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Total Biaya</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="restock in restocks.data" :key="restock.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-medium text-sm">{{ formatDate(restock.tanggal) }}</TableCell>
                            <TableCell class="px-0 py-4">
                                <span v-if="restock.vendor" class="font-medium text-primary text-sm">{{ restock.vendor.nama }}</span>
                                <span v-else class="text-muted-foreground italic text-xs">-</span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ restock.keterangan || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <Badge :variant="getStatusVariant(restock.status_pembayaran)" class="rounded-none text-[10px] px-2 py-0">
                                    {{ formatStatus(restock.status_pembayaran) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center text-sm font-medium">{{ restock.items_count }} <span class="text-[10px] text-muted-foreground uppercase">JENIS</span></TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-bold">{{ formatCurrency(restock.total_biaya) }}</TableCell>
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

                                        <Link :href="`/restock/${restock.id}/edit`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Edit2 class="mr-2 h-4 w-4" />
                                                <span>Edit Belanja</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem
                                            v-if="restock.status_pembayaran !== 'lunas'"
                                            class="cursor-pointer text-primary focus:bg-primary/10 focus:text-primary rounded-none"
                                            @click="settleRestock(restock.id)">
                                            <Check class="mr-2 h-4 w-4" />
                                            <span>Pelunasan</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="deleteRestock(restock.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Data</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="restocks.data.length === 0">
                            <TableCell colspan="6" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Belum ada data restock.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="restocks" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
