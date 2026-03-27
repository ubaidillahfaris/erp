<script setup lang="ts">
import { Head, Link, router, WhenVisible } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, FileText } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

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
    { title: 'Pengeluaran', href: '#' },
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
    }).format(value);
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

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Pengeluaran Lain-lain</h1>
                <p class="text-sm text-muted-foreground mt-1">Catat transaksi pengeluaran operasional warung (listrik, wifi, dll).</p>
            </div>
            <Link href="/pengeluaran/create">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Catat Pengeluaran
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Riwayat Pengeluaran</h3>
                <div class="flex items-center gap-4 w-full max-w-sm">
                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari keterangan / jenis..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="pengeluarans" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tanggal</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Jenis Beban</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama Pengeluaran</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Keterangan</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nominal</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in pengeluarans.data" :key="item.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 text-sm">{{ formatDate(item.tanggal) }}</TableCell>
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">{{ item.jenis_pengeluaran }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ item.nama_pengeluaran }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm text-muted-foreground">{{ item.keterangan || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-bold text-destructive">
                                {{ formatCurrency(item.nominal) }}
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="pengeluarans.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Belum ada data pengeluaran.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="pengeluarans" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
