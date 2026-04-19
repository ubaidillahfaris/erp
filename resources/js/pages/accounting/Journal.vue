<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Calendar, X, BookOpen, Hash, ArrowLeft
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    journals: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        date_start?: string;
        date_end?: string;
        type?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Journal Ledger', href: '/accounting/journal' },
];

const search = ref(props.filters.search || '');
const date_start = ref(props.filters.date_start || '');
const date_end = ref(props.filters.date_end || '');
const type = ref(props.filters.type || 'all');
const perPage = ref(props.filters.per_page || String(props.journals.per_page));
const sort = ref(props.filters.sort || 'tanggal');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'ref_date', label: 'Ref / Date', sortKey: 'ref_number' },
    { key: 'description', label: 'Description', sortable: true },
    { key: 'type_badge', label: 'Type', sortable: false },
    { key: 'total_debit', label: 'Debit', sortKey: 'items_sum_debit', align: 'right' },
    { key: 'total_credit', label: 'Credit', sortKey: 'items_sum_credit', align: 'right' },
];

watch([search, date_start, date_end, type, perPage, sort, direction], debounce(() => {
    router.get('/accounting/journal', {
        search: search.value || undefined,
        date_start: date_start.value || undefined,
        date_end: date_end.value || undefined,
        type: type.value === 'all' ? undefined : type.value,
        per_page: perPage.value,
        sort: sort.value || undefined,
        direction: sort.value ? (direction.value || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatCurrency = (cents: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(cents / 100);
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const resetFilters = () => {
    search.value = '';
    date_start.value = '';
    date_end.value = '';
    type.value = 'all';
};

const hasActiveFilters = computed(() => {
    return search.value || date_start.value || date_end.value || type.value !== 'all';
});

const getTypeLabel = (morphType: string) => {
    if (morphType.includes('Sale')) return 'SALE';
    if (morphType.includes('Production')) return 'PRD';
    if (morphType.includes('Purchase')) return 'PUR';
    return 'GEN';
};

const getTypeVariant = (morphType: string) => {
    const label = getTypeLabel(morphType);
    if (label === 'SALE') return 'bg-blue-50 text-blue-700 border-blue-100';
    if (label === 'PRD') return 'bg-amber-50 text-amber-700 border-amber-100';
    if (label === 'PUR') return 'bg-purple-50 text-purple-700 border-purple-100';
    return 'bg-slate-50 text-slate-700 border-slate-100';
};
</script>

<template>
<Head title="Journal Ledger" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <div class="flex items-center gap-4">
            <Link href="/dashboard">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Journal Entry Ledger</h1>
                <p class="text-sm text-slate-400 mt-0.5">Audit seluruh transaksi finansial (Double-Entry Ledger)</p>
            </div>
        </div>
        
        <div class="w-full">
            <DataTable 
                :data="journals" 
                :columns="columns" 
                v-model:search="search" 
                v-model:perPage="perPage"
                search-placeholder="Cari Ref / Deskripsi..." 
                toolbar-title="Financial Ledger" 
                :title="'Journal Entries'"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                :total-count="journals.total"
                :expandable="true"
            >
                <template #toolbar-prefix>
                    <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                        <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2 h-9">
                            <Calendar class="h-3.5 w-3.5 text-muted-foreground mr-2" />
                            <input type="date" v-model="date_start" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                            <span class="text-xs text-muted-foreground mx-1">-</span>
                            <input type="date" v-model="date_end" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                        </div>

                        <Select v-model="type">
                            <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                                <SelectValue placeholder="Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Type</SelectItem>
                                <SelectItem value="SALE">Penjualan</SelectItem>
                                <SelectItem value="PRD">Produksi</SelectItem>
                                <SelectItem value="PUR">Pembelian</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                            <X class="h-3 w-3 mr-1" /> Reset
                        </Button>
                    </div>
                </template>

                <template #cell(ref_date)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                            <Hash class="h-4 w-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-slate-900 font-mono tracking-tight">{{ row.ref_number }}</p>
                            <p class="text-xs text-slate-500">{{ formatDate(row.tanggal) }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(type_badge)="{ row }">
                    <Badge :class="['text-[10px] uppercase font-bold px-1.5 h-5 rounded-md border shadow-none', getTypeVariant(row.journalable_type)]">
                        {{ getTypeLabel(row.journalable_type) }}
                    </Badge>
                </template>

                <template #cell(description)="{ row }">
                    <span class="text-[12px] font-medium text-slate-600 truncate max-w-[300px] block">
                        {{ row.description }}
                    </span>
                </template>

                <template #cell(total_debit)="{ row }">
                    <span class="text-[13px] font-bold text-emerald-600 tabular-nums">
                        {{ formatCurrency(row.items_sum_debit) }}
                    </span>
                </template>

                <template #cell(total_credit)="{ row }">
                    <span class="text-[13px] font-bold text-rose-600 tabular-nums">
                        {{ formatCurrency(row.items_sum_credit) }}
                    </span>
                </template>

                <template #expanded="{ row }">
                    <div class="bg-white border rounded-lg overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 border-b">
                                <tr>
                                    <th class="px-4 py-2 text-[10px] font-bold uppercase text-slate-500">Account</th>
                                    <th class="px-4 py-2 text-[10px] font-bold uppercase text-slate-500 text-right">Debit</th>
                                    <th class="px-4 py-2 text-[10px] font-bold uppercase text-slate-500 text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in row.items" :key="item.id" class="hover:bg-slate-50/50">
                                    <td class="px-4 py-2">
                                        <div class="flex flex-col">
                                            <span class="text-[12px] font-bold text-slate-700">{{ item.account?.name }}</span>
                                            <span class="text-[10px] font-mono text-slate-400">{{ item.account?.code }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <span v-if="item.debit > 0" class="text-[12px] font-bold text-emerald-600 font-mono">
                                            {{ formatCurrency(item.debit) }}
                                        </span>
                                        <span v-else class="text-[11px] text-slate-300">-</span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <span v-if="item.credit > 0" class="text-[12px] font-bold text-rose-600 font-mono">
                                            {{ formatCurrency(item.credit) }}
                                        </span>
                                        <span v-else class="text-[11px] text-slate-300">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="bg-slate-50 px-4 py-2 border-t flex justify-between items-center text-[10px] text-slate-500 uppercase font-bold tracking-tight">
                            <span>Created By: {{ row.creator?.name || 'System' }}</span>
                            <div class="flex gap-4">
                                <span class="text-emerald-700 font-bold">Total Dr: {{ formatCurrency(row.items_sum_debit) }}</span>
                                <span class="text-rose-700 font-bold">Total Cr: {{ formatCurrency(row.items_sum_credit) }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                        <BookOpen class="h-12 w-12 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada transaksi</p>
                            <p class="text-xs text-muted-foreground mt-1">Gunakan modul Kasir, Produksi, atau Pembelian untuk menjurnal</p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
