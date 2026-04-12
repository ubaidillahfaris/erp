<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, Calendar, Landmark, ArrowUpCircle, ArrowDownCircle, Info, Receipt, History } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

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
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Jurnal Umum (Ledger)', href: '/journal' },
];

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const perPage = ref(String(props.filters.per_page || props.journals.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'tanggal', label: 'Tanggal', sortKey: 'tanggal' },
    { key: 'category', label: 'Kategori' },
    { key: 'description', label: 'Keterangan' },
    { key: 'via', label: 'Via', align: 'center' },
    { key: 'debit', label: 'Debit (Masuk)', align: 'right' },
    { key: 'kredit', label: 'Kredit (Keluar)', align: 'right' },
    { key: 'balance', label: 'Saldo', align: 'right', sortKey: 'balance' },
] as const;

watch([startDate, endDate, perPage, sort, direction], debounce(([newStart, newEnd, newPerPage, newSort, newDirection]) => {
    router.get('/journal', {
        start_date: newStart,
        end_date: newEnd,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

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

<div class="px-8 py-10 flex flex-col gap-10 font-sans bg-slate-50 min-h-[calc(100vh-64px)]">
    <PageHeader 
        title="Jurnal Umum" 
        description="Laporan Arus Kas Masuk (Debit) & Keluar (Kredit)" 
        back-href="/dashboard"
        :count="journals.total"
    >
        <template #actions>
            <div class="flex items-center gap-2 bg-white p-1 border border-input rounded-md shadow-none ">
                <Input type="date" v-model="startDate" class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs font-semibold" />
                <span class="text-xs text-muted-foreground font-bold opacity-30 px-1">s/d</span>
                <Input type="date" v-model="endDate" class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs font-semibold" />
            </div>
        </template>
    </PageHeader>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-7xl mx-auto w-full">
        <Card class="rounded-xl bg-white shadow-sm border border-input">
            <CardHeader class="pb-2 px-6 pt-6">
                <CardTitle class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center">
                    <ArrowUpCircle class="mr-2 h-3.5 w-3.5 text-green-500" />
                    Total Pemasukan (Debit)
                </CardTitle>
            </CardHeader>
            <CardContent class="px-6 pb-6">
                <div class="text-2xl font-black text-green-600 tracking-tighter">{{ formatCurrency(totals.debit) }}</div>
            </CardContent>
        </Card>

        <Card class="rounded-xl bg-white shadow-sm border border-input">
            <CardHeader class="pb-2 px-6 pt-6">
                <CardTitle class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center">
                    <ArrowDownCircle class="mr-2 h-3.5 w-3.5 text-red-500" />
                    Total Pengeluaran (Kredit)
                </CardTitle>
            </CardHeader>
            <CardContent class="px-6 pb-6">
                <div class="text-2xl font-black text-red-600 tracking-tighter">{{ formatCurrency(totals.kredit) }}</div>
            </CardContent>
        </Card>

        <Card class="rounded-xl bg-accent text-white shadow-none shadow-accent/20 border-none">
            <CardHeader class="pb-2 px-6 pt-6">
                <CardTitle class="text-xs font-bold uppercase tracking-widest text-white/60 flex items-center">
                    <Landmark class="mr-2 h-3.5 w-3.5" />
                    Saldo Akhir Mutasi
                </CardTitle>
            </CardHeader>
            <CardContent class="px-6 pb-6">
                <div class="text-2xl font-black tracking-tighter">
                    {{ formatCurrency(finalBalance) }}
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Main Ledger -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable
            :data="journals"
            :columns="columns"
            v-model:perPage="perPage"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            search-placeholder="Cari transaksi..."
            toolbar-title="Entry Transaksi Real-time"
            :title="'Jurnal Umum'"
            :total-count="journals.total"
        >
            <template #cell(tanggal)="{ row }">
                <span class="font-mono text-xs font-bold text-muted-foreground">{{ formatDate(row.tanggal) }}</span>
            </template>

            <template #cell(category)="{ row }">
                <Badge variant="outline" class="rounded-md text-xs px-2 py-0.5 font-bold uppercase tracking-tighter border-input text-muted-foreground shadow-none ">
                    {{ row.category }}
                </Badge>
            </template>

            <template #cell(description)="{ row }">
                <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-bold text-foreground">{{ row.description }}</span>
                    <span v-if="row.reference" class="text-xs text-muted-foreground uppercase opacity-50 flex items-center font-bold tracking-tight">
                        <History class="mr-1 h-3 w-3" />
                        Ref: {{ row.reference_type.split('\\').pop() }} #{{ row.reference_id }}
                    </span>
                </div>
            </template>

            <template #cell(via)="{ row }">
                <span class="text-xs font-black uppercase tracking-widest text-muted-foreground">{{ row.payment_method }}</span>
            </template>

            <template #cell(debit)="{ row }">
                <span v-if="row.type === 'debit'" class="text-sm font-black text-green-600 tabular-nums">
                    {{ formatCurrency(row.amount) }}
                </span>
                <span v-else class="text-sm text-muted-foreground font-bold italic text-xs">--</span>
            </template>

            <template #cell(kredit)="{ row }">
                <span v-if="row.type === 'kredit'" class="text-sm font-black text-red-600 tabular-nums">
                    {{ formatCurrency(row.amount) }}
                </span>
                <span v-else class="text-sm text-muted-foreground font-bold italic text-xs">--</span>
            </template>

            <template #cell(balance)="{ row }">
                <span class="text-sm font-black tabular-nums" :class="Number(row.balance) >= 0 ? 'text-foreground' : 'text-destructive'">
                    {{ formatCurrency(row.balance) }}
                </span>
            </template>

            <template #empty>
                <div class="h-48 flex flex-col items-center justify-center gap-3 opacity-20">
                    <Receipt class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Tidak ada catatan transaksi</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
