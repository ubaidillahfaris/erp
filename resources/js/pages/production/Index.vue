<script setup lang="ts">
import { Head, Link, router, WhenVisible } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, Loader2, PlayCircle, CheckCircle2, RotateCcw, Eye, MoreHorizontal } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index, create, show, edit, destroy } from '@/actions/App/Http/Controllers/ProductionController';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    { title: 'Produksi', href: '#' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.productions.per_page));

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get(index().url, {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const deleteProduction = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus catatan produksi ini?')) {
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
    }).format(value);
};

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('id-ID').format(value);
};

const formatStatus = (status: string) => {
    const statuses: Record<string, string> = {
        'draft': 'Draft',
        'in_progress': 'Diproses',
        'completed': 'Selesai',
        'cancelled': 'Dibatalkan'
    };
    return statuses[status] || status;
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'draft':
            return 'outline';
        case 'in_progress':
            return 'secondary';
        case 'completed':
            return 'default';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
<Head title="Modul Produksi" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Modul Produksi</h1>
                <p class="text-sm text-muted-foreground mt-1">Catat proses konversi bahan baku menjadi barang setengah jadi/jadi.</p>
            </div>
            <Link :href="create().url">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Mulai Produksi
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Riwayat Produksi</h3>
                <div class="flex items-center gap-4 w-full max-w-sm">
                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari SKU Produksi..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="productions" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">No. Referensi</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tanggal</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Hasil Produksi</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Target</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aktual</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Total Biaya</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="production in productions.data" :key="production.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-mono text-xs">{{ production.sku }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ formatDate(production.tanggal) }}</TableCell>
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">{{ production.bom?.nama }}</TableCell>
                            <TableCell class="px-0 py-4 text-center text-sm font-medium">{{ parseFloat(production.target_yield).toLocaleString('id-ID') }}</TableCell>
                            <TableCell class="px-0 py-4 text-center text-sm font-bold">{{ parseFloat(production.actual_yield || 0).toLocaleString('id-ID') }}</TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-sm font-bold tracking-tight text-foreground">
                                        {{ formatCurrency(production.total_cost || 0) }}
                                    </span>
                                    <Badge v-if="production.is_estimated" variant="outline" class="rounded-none text-[9px] px-1.5 py-0 h-4 border-orange-200 dark:border-orange-800 text-orange-600 dark:text-orange-400 font-medium uppercase leading-none">
                                        Estimasi
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <Badge :variant="getStatusVariant(production.status)" class="rounded-none text-[10px] px-2 py-0 uppercase">
                                    {{ formatStatus(production.status) }}
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

                                        <Link :href="show({ production: production.id }).url">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Eye class="mr-2 h-4 w-4" />
                                                <span>Lihat Detail</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <Link v-if="production.status === 'in_progress'"
                                            :href="edit({ production: production.id }).url">
                                            <DropdownMenuItem
                                                class="cursor-pointer font-medium text-emerald-600 dark:text-emerald-500 rounded-none">
                                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                                <span>Selesaikan Produksi</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem v-if="production.status !== 'draft'" class="cursor-pointer rounded-none"
                                            @click="handleClone(production.id)">
                                            <RotateCcw class="mr-2 h-4 w-4" />
                                            <span>Produksi Ulang (Duplikat)</span>
                                        </DropdownMenuItem>

                                        <template v-if="production.status === 'draft'">
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                                @click="deleteProduction(production.id)">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                <span>Hapus Draft</span>
                                            </DropdownMenuItem>
                                        </template>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="productions.data.length === 0">
                            <TableCell colspan="8" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Belum ada data produksi.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="productions" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
