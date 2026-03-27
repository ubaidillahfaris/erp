<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Eye, Search, Trash2, Edit2, MoreHorizontal, ClipboardList } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    opnames: {
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
    { title: 'Stock Opname', href: '#' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.opnames.per_page));

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/stock-opname', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const deleteOpname = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus data opname ini?')) {
        router.delete(`/stock-opname/${id}`);
    }
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
        case 'completed': return 'default';
        case 'draft': return 'secondary';
        default: return 'outline';
    }
};

const formatStatus = (status: string) => {
    return status.toUpperCase();
};
</script>

<template>
<Head title="Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Stock Opname</h1>
                <p class="text-sm text-muted-foreground mt-1">Sesuaikan stok fisik dengan stok sistem secara berkala.</p>
            </div>
            <Link href="/stock-opname/create">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Mulai Opname Baru
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Riwayat Opname</h3>
                <div class="flex items-center gap-4">
                     <div class="relative w-full max-w-sm">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari keterangan..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="opnames" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tanggal</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Keterangan</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Jumlah Barang</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="opname in opnames.data" :key="opname.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-medium text-sm">{{ formatDate(opname.tanggal) }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ opname.keterangan || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <Badge :variant="getStatusVariant(opname.status)" class="rounded-none text-[10px] px-2 py-0">
                                    {{ formatStatus(opname.status) }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center text-sm font-medium">{{ opname.items_count }} <span class="text-[10px] text-muted-foreground uppercase">BARANG</span></TableCell>
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

                                        <Link :href="`/stock-opname/${opname.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Eye class="mr-2 h-4 w-4" />
                                                <span>Lihat Detail</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <Link v-if="opname.status === 'draft'" :href="`/stock-opname/${opname.id}/edit`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Edit2 class="mr-2 h-4 w-4" />
                                                <span>Lanjutkan Draft</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem
                                            v-if="opname.status === 'draft'"
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="deleteOpname(opname.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Draft</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="opnames.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Belum ada data stock opname.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="opnames" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
