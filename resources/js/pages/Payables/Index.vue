<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, FileText, User as UserIcon, 
    CreditCard, ChevronRight, Calendar, 
    CircleCheck, CircleAlert, Filter, X,
    Wallet, HandCoins, Activity, ArrowRight,
    Landmark, Clock, AlertTriangle, Info
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent } from '@/components/ui/card';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    payables: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        type?: string;
        status?: string;
        search?: string;
        date_start?: string;
        date_end?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
    summary: {
        total_payable: number;
        total_receivable: number;
        overdue_count: number;
    };
    chartData: {
        payable_total: number;
        payable_paid: number;
        payable_remaining: number;
        receivable_total: number;
        receivable_paid: number;
        receivable_remaining: number;
        open_count: number;
        partial_count: number;
        paid_count: number;
        overdue_count: number;
    };
}>();

// DONUT CHART CALCULATIONS
// radius = 70, cx=90, cy=90
const radius = 70;
const circumference = 2 * Math.PI * radius; // ~439.8

const donutData = computed(() => {
    const totalCurrent = props.chartData.payable_remaining + props.chartData.receivable_remaining + (props.chartData.payable_paid + props.chartData.receivable_paid);
    
    if (totalCurrent === 0) return [];

    const segments = [
        { label: 'Hutang', value: props.chartData.payable_remaining, color: 'rgb(225, 29, 72)' }, // rose-600
        { label: 'Piutang', value: props.chartData.receivable_remaining, color: 'rgb(16, 185, 129)' }, // emerald-500
        { label: 'Terbayar', value: props.chartData.payable_paid + props.chartData.receivable_paid, color: 'rgb(100, 116, 139)' }, // slate-500
    ];

    let currentOffset = 0;
    return segments.map(s => {
        const percentage = (s.value / totalCurrent) * 100;
        const dash = (s.value / totalCurrent) * circumference;
        const offset = currentOffset;
        currentOffset -= dash; // Subtract because SVG strokes go clockwise from top if rotated correctly
        
        return {
            ...s,
            percentage,
            dasharray: `${dash} ${circumference}`,
            dashoffset: offset
        };
    });
});

const donutTotal = computed(() => {
    return props.chartData.payable_remaining + props.chartData.receivable_remaining;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Hutang & Piutang', href: '/payables' },
];

const search = ref(props.filters.search || '');
// ... (rest of search/filters code)
const type = ref(props.filters.type || 'all');
const status = ref(props.filters.status || 'all');
const date_start = ref(props.filters.date_start || '');
const date_end = ref(props.filters.date_end || '');
const perPage = ref(props.filters.per_page || String(props.payables.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'reference', label: 'Referensi', sortable: false },
    { key: 'party', label: 'Pihak', sortable: false },
    { key: 'amounts', label: 'Pokok + Bunga', sortKey: 'total_amount' },
    { key: 'balance', label: 'Terbayar / Sisa', sortKey: 'remaining_amount' },
    { key: 'due_date', label: 'Jatuh Tempo', sortKey: 'due_date' },
    { key: 'status_badge', label: 'Status', sortKey: 'status' },
];

watch([search, type, status, date_start, date_end, perPage, sort, direction], debounce(() => {
    router.get('/payables', {
        search: search.value || undefined,
        type: type.value === 'all' ? undefined : type.value,
        status: status.value === 'all' ? undefined : status.value,
        date_start: date_start.value || undefined,
        date_end: date_end.value || undefined,
        per_page: perPage.value,
        sort: sort.value || undefined,
        direction: sort.value ? (direction.value || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
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
    type.value = 'all';
    status.value = 'all';
    date_start.value = '';
    date_end.value = '';
};

const hasActiveFilters = computed(() => {
    return search.value || type.value !== 'all' || status.value !== 'all' || date_start.value || date_end.value;
});

const getStatusConfig = (status: string) => {
    switch (status) {
        case 'open':
            return { label: 'Open', class: 'bg-blue-50 text-blue-600 border-blue-100' };
        case 'partial':
            return { label: 'Parsial', class: 'bg-amber-50 text-amber-600 border-amber-100' };
        case 'paid':
            return { label: 'Lunas', class: 'bg-emerald-50 text-emerald-600 border-emerald-100' };
        case 'overdue':
            return { label: 'Overdue', class: 'bg-rose-50 text-rose-600 border-rose-100' };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-600 border-slate-100' };
    }
};

const getTypeLabel = (type: string) => {
    return type === 'payable' ? 'Hutang' : 'Piutang';
};
</script>

<template>
<Head title="Hutang & Piutang" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Hutang & Piutang" 
        description="Kelola kewajiban hutang dan piutang tagihan pelanggan" 
        back-href="/dashboard" 
        :count="payables.total" 
    />
    
    <!-- ====== DASHBOARD OVERVIEW ====== -->
    <div class="w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-5 gap-6">
        
        <!-- LEFT COLUMN: METRICS & STATUS (3/5) -->
        <div class="lg:col-span-3 flex flex-col gap-6">
            
            <!-- ROW 1: LARGE CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Hutang Aktif Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4 group hover:shadow-md transition-all duration-300">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Total Hutang Aktif</span>
                            <h3 class="text-xl font-black text-rose-600 tabular-nums">{{ formatCurrency(chartData.payable_remaining) }}</h3>
                            <span class="text-[10px] text-muted-foreground">dari {{ formatCurrency(chartData.payable_total) }} total</span>
                        </div>
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-rose-50 flex items-center justify-center text-rose-500">
                            <Wallet class="h-5 w-5" />
                        </div>
                    </div>
                    <!-- Progress Section -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-tighter">
                            <span class="text-emerald-600">Terbayar: {{ formatCurrency(chartData.payable_paid) }}</span>
                            <span class="text-muted-foreground">{{ Math.round((chartData.payable_paid / chartData.payable_total) * 100) || 0 }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-rose-500 rounded-full transition-all duration-1000" 
                                :style="{ width: `${(chartData.payable_paid / chartData.payable_total) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Piutang Aktif Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-col gap-4 group hover:shadow-md transition-all duration-300">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Total Piutang Aktif</span>
                            <h3 class="text-xl font-black text-emerald-600 tabular-nums">{{ formatCurrency(chartData.receivable_remaining) }}</h3>
                            <span class="text-[10px] text-muted-foreground">dari {{ formatCurrency(chartData.receivable_total) }} total</span>
                        </div>
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-500">
                            <HandCoins class="h-5 w-5" />
                        </div>
                    </div>
                    <!-- Progress Section -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-tighter">
                            <span class="text-emerald-600">Terbayar: {{ formatCurrency(chartData.receivable_paid) }}</span>
                            <span class="text-muted-foreground">{{ Math.round((chartData.receivable_paid / chartData.receivable_total) * 100) || 0 }}%</span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-emerald-500 rounded-full transition-all duration-1000" 
                                :style="{ width: `${(chartData.receivable_paid / chartData.receivable_total) * 100}%` }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: STATUS COUNTERS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 items-center text-center">
                    <span class="text-lg font-black text-blue-600">{{ chartData.open_count }}</span>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">Open</span>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 items-center text-center">
                    <span class="text-lg font-black text-amber-600">{{ chartData.partial_count }}</span>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">Cicilan</span>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 items-center text-center">
                    <span class="text-lg font-black text-emerald-600">{{ chartData.paid_count }}</span>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">Lunas</span>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 p-3 flex flex-col gap-1 items-center text-center relative overflow-hidden group">
                    <div v-if="chartData.overdue_count > 0" class="absolute inset-0 bg-rose-500/5 animate-pulse"></div>
                    <span class="text-lg font-black text-rose-600 relative z-10">{{ chartData.overdue_count }}</span>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-rose-400 relative z-10">Overdue</span>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: DONUT CHART (2/5) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 flex flex-col items-center gap-6">
            <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground w-full text-left">Komposisi Kewajiban</h3>
            
            <div class="relative flex items-center justify-center">
                <!-- SVG DONUT -->
                <svg width="180" height="180" viewBox="0 0 180 180" class="transform -rotate-90">
                    <!-- Background Circle -->
                    <circle cx="90" cy="90" :r="radius" stroke="currentColor" stroke-width="20" fill="transparent" class="text-slate-50" />
                    
                    <!-- Data Segments -->
                    <circle 
                        v-for="(seg, idx) in donutData" 
                        :key="idx"
                        cx="90" 
                        cy="90" 
                        :r="radius" 
                        :stroke="seg.color" 
                        stroke-width="20" 
                        fill="transparent" 
                        :stroke-dasharray="seg.dasharray"
                        :stroke-dashoffset="seg.dashoffset"
                        stroke-linecap="round"
                        class="transition-all duration-1000 ease-out"
                    />
                </svg>
                
                <!-- Center Text -->
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span class="text-[10px] font-bold uppercase tracking-tighter text-muted-foreground">Aktif</span>
                    <span class="text-sm font-black text-slate-900 leading-none">{{ formatCurrency(donutTotal) }}</span>
                </div>
            </div>

            <!-- Legend -->
            <div class="grid grid-cols-3 w-full gap-2">
                <div v-for="(seg, idx) in donutData" :key="idx" class="flex flex-col gap-1 items-center">
                    <div class="flex items-center gap-1.5">
                        <div class="h-2 w-2 rounded-full" :style="{ backgroundColor: seg.color }"></div>
                        <span class="text-[9px] font-bold uppercase text-muted-foreground">{{ seg.label }}</span>
                    </div>
                    <span class="text-[10px] font-black text-slate-700">{{ Math.round(seg.percentage) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="payables" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari referensi atau pihak..." 
            toolbar-title="Payable Ledger" 
            :title="'Daftar Hutang Piutang'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            :total-count="payables.total"
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
                            <SelectValue placeholder="Jenis" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Jenis</SelectItem>
                            <SelectItem value="payable">Hutang</SelectItem>
                            <SelectItem value="receivable">Piutang</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="status">
                        <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="open">Open</SelectItem>
                            <SelectItem value="partial">Partial</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(reference)="{ row }">
                <div class="flex items-center gap-3">
                    <div :class="['h-9 w-9 shrink-0 rounded-lg flex items-center justify-center transition-all group-hover:shadow-sm', 
                        row.type === 'payable' ? 'bg-rose-50 text-rose-500' : 'bg-emerald-50 text-emerald-500']">
                        <FileText v-if="row.reference_type === 'sale' || row.reference_type === 'restock'" class="h-4 w-4" />
                        <Activity v-else class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-[13px] font-bold text-foreground font-mono uppercase leading-none">
                                {{ row.reference_number || `${row.reference_type}#${row.reference_id}` }}
                            </p>
                            <Badge variant="outline" class="text-[9px] px-1 h-3.5 uppercase font-black tracking-tighter leading-none border-slate-200">
                                {{ row.reference_type }}
                            </Badge>
                        </div>
                        <p class="text-[11px] text-muted-foreground flex items-center gap-1 mt-1.5">
                            <span class="font-bold text-slate-500 uppercase tracking-tighter">{{ formatDate(row.created_at) }}</span>
                            <span class="h-0.5 w-0.5 rounded-full bg-slate-300 mx-0.5"></span>
                            <span :class="row.type === 'payable' ? 'text-rose-600 font-black uppercase' : 'text-emerald-600 font-black uppercase'">
                                {{ getTypeLabel(row.type) }}
                            </span>
                        </p>
                    </div>
                </div>
            </template>

            <template #cell(party)="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center">
                        <UserIcon class="h-3 w-3 text-slate-400" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[12px] font-bold text-foreground leading-none">
                            {{ row.party?.nama || row.party?.name || 'External Party' }}
                        </span>
                        <span class="text-[10px] text-muted-foreground uppercase tracking-widest font-medium">
                            {{ row.party_type }}
                        </span>
                    </div>
                </div>
            </template>

            <template #cell(amounts)="{ row }">
                <div class="flex flex-col">
                    <span class="text-[12px] font-bold text-foreground">
                        {{ formatCurrency(row.principal_amount) }}
                    </span>
                    <span v-if="row.total_interest > 0" class="text-[10px] text-rose-600 font-medium">
                        + {{ formatCurrency(row.total_interest) }} (Bunga)
                    </span>
                    <span v-else class="text-[10px] text-muted-foreground">
                        Tanpa Bunga
                    </span>
                </div>
            </template>

            <template #cell(balance)="{ row }">
                <div class="flex flex-col gap-0.5">
                    <div class="flex items-center justify-between w-full max-w-[140px]">
                        <span class="text-[11px] font-medium text-muted-foreground">Paid:</span>
                        <span class="text-[11px] font-bold text-emerald-600 tabular-nums">{{ formatCurrency(row.paid_amount) }}</span>
                    </div>
                    <div class="flex items-center justify-between w-full max-w-[140px]">
                        <span class="text-[11px] font-medium text-muted-foreground">Rem:</span>
                        <span class="text-[11px] font-bold text-rose-600 tabular-nums">{{ formatCurrency(row.remaining_amount) }}</span>
                    </div>
                    <!-- simple progress bar -->
                    <div class="w-full max-w-[140px] h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                        <div 
                            class="h-full bg-emerald-500 rounded-full" 
                            :style="{ width: `${(row.paid_amount / row.total_amount) * 100}%` }"
                        ></div>
                    </div>
                </div>
            </template>

            <template #cell(due_date)="{ row }">
                <div class="flex flex-col">
                    <span :class="['text-[12px] font-bold', row.status === 'overdue' ? 'text-rose-600' : 'text-foreground']">
                        {{ formatDate(row.due_date) }}
                    </span>
                    <span class="text-[10px] text-muted-foreground">
                        {{ row.installment_count }} Cicilan
                    </span>
                </div>
            </template>

            <template #cell(status_badge)="{ row }">
                <Badge 
                    :class="[getStatusConfig(row.status).class, 'text-[10px] uppercase font-black tracking-widest px-2 h-5 rounded-lg border-none shadow-none ']"
                >
                    <CircleAlert v-if="row.status === 'overdue'" class="h-3 w-3 mr-1" />
                    <CircleCheck v-else-if="row.status === 'paid'" class="h-3 w-3 mr-1" />
                    <Clock v-else class="h-3 w-3 mr-1" />
                    {{ getStatusConfig(row.status).label }}
                </Badge>
            </template>

            <template #actions="{ row }">
                <Link :href="`/payables/${row.id}`" class="flex items-center justify-center h-8 w-8 rounded-lg hover:bg-secondary transition-all">
                    <ArrowRight class="h-4 w-4 text-muted-foreground" />
                </Link>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                    <Landmark class="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada catatan</p>
                        <p class="text-xs text-muted-foreground mt-1">Hutang atau piutang akan muncul otomatis sesuai transaksi</p>
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</div>
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
