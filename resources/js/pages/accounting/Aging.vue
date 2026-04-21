<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Calendar, X, AlertTriangle, ArrowLeft, Download, ExternalLink
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    aging_lines: any[];
    summary: {
        payable: Record<string, number>;
        receivable: Record<string, number>;
    };
    buckets: Record<string, string>;
    filters: {
        type: string;
        party_type: string;
        bucket: string;
        as_of_date: string;
    };
    as_of_date: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Aging Report', href: '/accounting/aging' },
];

const type = ref(props.filters.type || 'all');
const partyType = ref(props.filters.party_type || 'all');
const bucketFilter = ref(props.filters.bucket || 'all');
const asOfDate = ref(props.filters.as_of_date || '');
const search = ref('');

const columns = [
    { key: 'party_name', label: 'Pihak (Party)', sortable: true },
    { key: 'reference', label: 'Referensi', sortable: true },
    { key: 'due_date', label: 'Jatuh Tempo', sortable: true },
    { key: 'days_overdue', label: 'Hari Lewat', sortable: true, align: 'right' },
    { key: 'bucket', label: 'Status Aging' },
    { key: 'amount', label: 'Jumlah (Amount)', sortable: true, align: 'right' },
] as const;

watch([type, partyType, bucketFilter, asOfDate], debounce(() => {
    router.get('/accounting/aging', {
        type: type.value === 'all' ? undefined : type.value,
        party_type: partyType.value === 'all' ? undefined : partyType.value,
        bucket: bucketFilter.value === 'all' ? undefined : bucketFilter.value,
        as_of_date: asOfDate.value || undefined,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getBucketLabel = (bucket: string) => {
    return props.buckets[bucket] || bucket;
};

const getBucketVariant = (bucket: string) => {
    if (bucket === 'current') return 'bg-slate-100 text-slate-700 border-slate-200';
    if (bucket === 'days_30') return 'bg-amber-100 text-amber-700 border-amber-200 uppercase';
    if (bucket === 'days_60') return 'bg-orange-100 text-orange-700 border-orange-200 uppercase';
    if (bucket === 'days_90') return 'bg-rose-100 text-rose-700 border-rose-200 uppercase';
    return 'bg-red-600 text-white border-red-700 uppercase font-black';
};

const getBucketCardClass = (bucket: string) => {
    if (bucket === 'current') return 'bg-white border-slate-200';
    if (bucket === 'days_30') return 'bg-amber-50/50 border-amber-200';
    if (bucket === 'days_60') return 'bg-orange-50/50 border-orange-200';
    if (bucket === 'days_90') return 'bg-rose-50/50 border-rose-200';
    return 'bg-red-50 border-red-200 ring-1 ring-red-500/20';
};

const resetFilters = () => {
    type.value = 'all';
    partyType.value = 'all';
    bucketFilter.value = 'all';
    asOfDate.value = new Date().toISOString().split('T')[0];
};

const hasActiveFilters = computed(() => {
    return type.value !== 'all' || partyType.value !== 'all' || bucketFilter.value !== 'all';
});

const handleRowClick = (row: any) => {
    router.get(`/payables/${row.payable_id}`);
};
</script>

<template>
<Head title="Aging Report" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/dashboard">
                    <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                        Aging Report <AlertTriangle class="h-5 w-5 text-amber-500" />
                    </h1>
                    <p class="text-sm text-slate-400 mt-0.5">Laporan Umur Hutang & Piutang per tanggal: {{ formatDate(as_of_date) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" class="h-9 gap-2 text-slate-600 border-slate-200">
                    <Download class="h-4 w-4" /> Export Excel
                </Button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="flex flex-col gap-6">
            <!-- Hutang (Payables) -->
            <div v-if="filters.type === 'all' || filters.type === 'payable'" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        Ringkasan Hutang (Payables)
                    </h3>
                    <span class="text-lg font-black text-rose-600 tabular-nums">{{ formatCurrency(summary.payable.total) }}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <Card v-for="(label, key) in buckets" :key="'p-'+key" :class="['shadow-sm border transition-all hover:shadow-md cursor-pointer', getBucketCardClass(key as string)]" @click="bucketFilter = key as string; type = 'payable'">
                        <CardHeader class="p-4 pb-2">
                            <CardTitle class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ label }}</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 pt-0">
                            <p :class="['text-lg font-black tabular-nums', key === 'current' ? 'text-slate-900' : 'text-rose-600']">
                                {{ formatCurrency(summary.payable[key as string]) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Piutang (Receivables) -->
            <div v-if="filters.type === 'all' || filters.type === 'receivable'" class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        Ringkasan Piutang (Receivables)
                    </h3>
                    <span class="text-lg font-black text-emerald-600 tabular-nums">{{ formatCurrency(summary.receivable.total) }}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <Card v-for="(label, key) in buckets" :key="'r-'+key" :class="['shadow-sm border transition-all hover:shadow-md cursor-pointer', getBucketCardClass(key as string)]" @click="bucketFilter = key as string; type = 'receivable'">
                        <CardHeader class="p-4 pb-2">
                            <CardTitle class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ label }}</CardTitle>
                        </CardHeader>
                        <CardContent class="p-4 pt-0">
                            <p :class="['text-lg font-black tabular-nums', key === 'current' ? 'text-slate-900' : 'text-emerald-600']">
                                {{ formatCurrency(summary.receivable[key as string]) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <div class="w-full">
            <DataTable 
                :data="{ 
                    data: aging_lines,
                    links: [],
                    current_page: 1,
                    last_page: 1,
                    per_page: aging_lines.length,
                    total: aging_lines.length
                }" 
                :columns="columns" 
                v-model:search="search" 
                search-placeholder="Cari Pihak / Referensi..." 
                toolbar-title="Aging Details" 
                :title="'Detail Posisi Saldo'"
                :total-count="aging_lines.length"
                @row-click="handleRowClick"
                class="bg-white rounded-xl border-slate-200 shadow-sm"
            >
                <template #toolbar-prefix>
                    <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                        <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2 h-9 shrink-0">
                            <Calendar class="h-3.5 w-3.5 text-muted-foreground mr-2" />
                            <input type="date" v-model="asOfDate" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                        </div>

                        <Select v-model="type">
                            <SelectTrigger class="h-9 w-[120px] text-xs font-medium bg-white rounded-lg border-slate-200 shrink-0">
                                <SelectValue placeholder="Tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Tipe</SelectItem>
                                <SelectItem value="payable">Hutang</SelectItem>
                                <SelectItem value="receivable">Piutang</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="partyType">
                            <SelectTrigger class="h-9 w-[120px] text-xs font-medium bg-white rounded-lg border-slate-200 shrink-0">
                                <SelectValue placeholder="Pihak" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Pihak</SelectItem>
                                <SelectItem value="vendor">Vendor</SelectItem>
                                <SelectItem value="customer">Customer</SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="bucketFilter">
                            <SelectTrigger class="h-9 w-[140px] text-xs font-medium bg-white rounded-lg border-slate-200 shrink-0">
                                <SelectValue placeholder="Bucket Aging" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Bucket</SelectItem>
                                <SelectItem v-for="(label, key) in buckets" :key="key" :value="key as string">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground shrink-0" @click="resetFilters">
                            <X class="h-3 w-3 mr-1" /> Reset
                        </Button>
                    </div>
                </template>

                <template #cell(party_name)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-slate-900 truncate">{{ row.party_name }}</p>
                            <p class="text-[10px] text-slate-400 uppercase font-medium tracking-wider">{{ row.party_type }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(reference)="{ row }">
                    <div class="flex items-center gap-1.5 opacity-80 decoration-slate-300">
                        <span class="text-[11px] font-mono text-slate-500 uppercase">{{ row.reference }}</span>
                        <ExternalLink class="h-3 w-3 text-slate-300" />
                    </div>
                </template>

                <template #cell(due_date)="{ row }">
                    <span class="text-xs font-medium text-slate-600">
                        {{ formatDate(row.due_date) }}
                    </span>
                </template>

                <template #cell(days_overdue)="{ row }">
                    <div class="flex items-center justify-end gap-2">
                        <span :class="['text-[13px] font-bold tabular-nums', row.days_overdue > 0 ? 'text-rose-600' : 'text-slate-400']">
                            {{ row.days_overdue > 0 ? row.days_overdue : 0 }} Hr
                        </span>
                        <AlertTriangle v-if="row.days_overdue > 60" class="h-3.5 w-3.5 text-rose-500" />
                    </div>
                </template>

                <template #cell(bucket)="{ row }">
                    <Badge :class="['text-[9px] uppercase font-bold px-1.5 h-5 rounded-md border shadow-none tracking-tighter', getBucketVariant(row.bucket)]">
                        {{ getBucketLabel(row.bucket) }}
                    </Badge>
                </template>

                <template #cell(amount)="{ row }">
                    <span :class="['text-[13px] font-black tabular-nums', row.type === 'payable' ? 'text-rose-600' : 'text-emerald-600 underline underline-offset-4 decoration-emerald-200/50']">
                        {{ formatCurrency(row.amount) }}
                    </span>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                        <AlertTriangle class="h-12 w-12 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Tidak Ada Tagihan</p>
                            <p class="text-xs text-muted-foreground mt-1">Semua kewajiban telah lunas pada filter ini</p>
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
