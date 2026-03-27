<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Calendar, Landmark, ArrowUpCircle, ArrowDownCircle, Info, Receipt, History } from 'lucide-vue-next';
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

const props = defineProps<{
    journals: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    summaries: any[];
    filters: {
        start_date: string;
        end_date: string;
        per_page: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Jurnal Umum (Ledger)', href: '/journal' },
];

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const perPage = ref(props.filters.per_page);

watch([startDate, endDate, perPage], debounce(([newStart, newEnd, newPerPage]) => {
    router.get('/journal', {
        start_date: newStart,
        end_date: newEnd,
        per_page: newPerPage,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(date));
};

const totals = computed(() => {
    return props.summaries.reduce((acc, curr) => {
        acc.debit += Number(curr.total_debit);
        acc.kredit += Number(curr.total_kredit);
        return acc;
    }, { debit: 0, kredit: 0 });
});

const finalBalance = computed(() => totals.value.debit - totals.value.kredit);
</script>

<template>
<Head title="Jurnal Umum" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Jurnal Umum</h1>
                <p class="text-sm text-muted-foreground mt-1">Laporan arus kas masuk (Debit) dan keluar (Kredit).</p>
            </div>
            <div class="flex items-center gap-2">
                 <div class="flex items-center gap-2 bg-muted/20 p-1 border border-muted">
                    <Input type="date" v-model="startDate" class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs" />
                    <span class="text-xs text-muted-foreground">s/d</span>
                    <Input type="date" v-model="endDate" class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs" />
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="rounded-none border-muted bg-transparent shadow-none">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60 flex items-center">
                        <ArrowUpCircle class="mr-2 h-3 w-3 text-green-500" />
                        Total Pemasukan (Debit)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totals.debit) }}</div>
                </CardContent>
            </Card>

            <Card class="rounded-none border-muted bg-transparent shadow-none">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60 flex items-center">
                        <ArrowDownCircle class="mr-2 h-3 w-3 text-red-500" />
                        Total Pengeluaran (Kredit)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ formatCurrency(totals.kredit) }}</div>
                </CardContent>
            </Card>

            <Card class="rounded-none border-primary/20 bg-primary/5 shadow-none border">
                <CardHeader class="pb-2">
                    <CardTitle class="text-xs font-bold uppercase tracking-widest text-primary/60 flex items-center">
                        <Landmark class="mr-2 h-3 w-3" />
                        Saldo Akhir Mutasi
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="finalBalance >= 0 ? 'text-primary' : 'text-destructive'">
                        {{ formatCurrency(finalBalance) }}
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Main content -->
        <div class="flex flex-col gap-6">
            <div class="flex items-center justify-between border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60 italic">Entry Transaksi Real-time</h3>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="journals" @update:per-page="perPage = $event" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tanggal</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Kategori</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Keterangan</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50 text-center">Via</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Debit (Masuk)</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Kredit (Keluar)</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Saldo</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="journal in journals.data" :key="journal.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-mono text-xs">{{ formatDate(journal.tanggal) }}</TableCell>
                            <TableCell class="px-0 py-4">
                                <Badge variant="outline" class="rounded-none text-[10px] px-2 py-0 font-bold uppercase tracking-tighter opacity-70">
                                    {{ journal.category }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-medium">{{ journal.description }}</span>
                                    <span v-if="journal.reference" class="text-[10px] text-muted-foreground uppercase opacity-50 flex items-center">
                                        <History class="mr-1 h-3 w-3" />
                                        Ref: {{ journal.reference_type.split('\\').pop() }} #{{ journal.reference_id }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60">{{ journal.payment_method }}</span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <span v-if="journal.type === 'debit'" class="text-sm font-bold text-green-600">
                                    {{ formatCurrency(journal.amount) }}
                                </span>
                                <span v-else class="text-sm text-muted-foreground/20">-</span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <span v-if="journal.type === 'kredit'" class="text-sm font-bold text-red-600">
                                    {{ formatCurrency(journal.amount) }}
                                </span>
                                <span v-else class="text-sm text-muted-foreground/20">-</span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <span class="text-sm font-bold" :class="Number(journal.balance) >= 0 ? 'text-primary' : 'text-destructive'">
                                    {{ formatCurrency(journal.balance) }}
                                </span>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="journals.data.length === 0">
                            <TableCell colspan="7" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest italic">
                                Tidak ada catatan transaksi pada periode ini.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="journals" @update:per-page="perPage = $event" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
