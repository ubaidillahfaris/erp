<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, FileText, User as UserIcon, 
    ShoppingCart, CreditCard, ChevronRight,
    Calendar, CircleCheck, CircleAlert, Filter,
    X
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    sales: {
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
        payment_method?: string;
        status?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Riwayat Penjualan', href: '/sales' },
];

const search = ref(props.filters.search || '');
const date_start = ref(props.filters.date_start || '');
const date_end = ref(props.filters.date_end || '');
const payment_method = ref(props.filters.payment_method || 'all');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page || String(props.sales.per_page));
const sort = ref(props.filters.sort || 'tanggal');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'invoice', label: 'Invoice', sortKey: 'invoice_number' },
    { key: 'customer', label: 'Pelanggan', sortable: false },
    { key: 'items_summary', label: 'Item & Qty', sortable: false },
    { key: 'payment', label: 'Pembayaran', sortKey: 'payment_method' },
    { key: 'total', label: 'Total', sortKey: 'total_amount' },
    { key: 'status_badge', label: 'Status', sortKey: 'status' },
];

watch([search, date_start, date_end, payment_method, status, perPage, sort, direction], debounce(() => {
    router.get('/sales', {
        search: search.value || undefined,
        date_start: date_start.value || undefined,
        date_end: date_end.value || undefined,
        payment_method: payment_method.value === 'all' ? undefined : payment_method.value,
        status: status.value === 'all' ? undefined : status.value,
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
    date_start.value = '';
    date_end.value = '';
    payment_method.value = 'all';
    status.value = 'all';
};

const hasActiveFilters = computed(() => {
    return search.value || date_start.value || date_end.value || payment_method.value !== 'all' || status.value !== 'all';
});
</script>

<template>
<Head title="Riwayat Penjualan" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Riwayat Penjualan" 
        description="Daftar seluruh transaksi kasir (POS)" 
        back-href="/dashboard" 
        :count="sales.total" 
    />
    
    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="sales" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari nomor invoice..." 
            toolbar-title="Sales Ledger" 
            :title="'Riwayat Penjualan'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            :total-count="sales.total"
        >
            <template #toolbar-prefix>
                <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                    <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2 h-9">
                        <Calendar class="h-3.5 w-3.5 text-muted-foreground mr-2" />
                        <input type="date" v-model="date_start" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                        <span class="text-xs text-muted-foreground mx-1">-</span>
                        <input type="date" v-model="date_end" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                    </div>

                    <Select v-model="payment_method">
                        <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Metode" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Metode</SelectItem>
                            <SelectItem value="cash">Tunai</SelectItem>
                            <SelectItem value="qris">QRIS</SelectItem>
                            <SelectItem value="transfer">Transfer</SelectItem>
                            <SelectItem value="credit">Piutang</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="status">
                        <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="voided">Voided</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(invoice)="{ row }">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <FileText class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-foreground font-mono leading-none">#{{ row.invoice_number }}</p>
                        <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-tighter mt-1.5">{{ formatDate(row.tanggal) }}</p>
                    </div>
                </div>
            </template>

            <template #cell(customer)="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200/50">
                        <UserIcon class="h-3 w-3 text-slate-400" />
                    </div>
                    <span class="text-[12px] font-bold text-foreground/80 leading-none" :class="!row.sale_customer && 'italic opacity-50'">
                        {{ row.sale_customer?.customer?.name || 'Walk-in' }}
                    </span>
                </div>
            </template>

            <template #cell(items_summary)="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="text-[12px] font-bold text-foreground leading-none">{{ row.items?.length || 0 }} Items</span>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground opacity-60">{{ row.items?.reduce((acc: number, i: any) => acc + Number(i.qty), 0) }} Total Qty</span>
                </div>
            </template>

            <template #cell(payment)="{ row }">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5">
                        <CreditCard class="h-3 w-3 text-muted-foreground/60" />
                        <span class="text-[11px] font-black uppercase tracking-[0.1em] text-foreground leading-none">{{ row.payment_method }}</span>
                    </div>
                    <span v-if="row.payment_method === 'cash'" class="text-[9px] font-bold text-muted-foreground uppercase tracking-tighter">
                        Rec: {{ formatCurrency(row.received_amount) }}
                    </span>
                </div>
            </template>

            <template #cell(total)="{ row }">
                <span class="text-[13px] font-bold text-foreground tabular-nums">
                    {{ formatCurrency(row.total_amount) }}
                </span>
            </template>

            <template #cell(status_badge)="{ row }">
                <!-- Priority 1: Voided -->
                <Badge 
                    v-if="row.status === 'voided'" 
                    class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[10px] uppercase font-bold px-1.5 h-5"
                >
                    <CircleAlert class="h-3 w-3 mr-1" /> Voided
                </Badge>

                <!-- Priority 2: Credit/Payable Status -->
                <template v-else-if="row.payment_method === 'credit' && row.payable">
                    <Badge 
                        v-if="row.payable.status === 'paid'"
                        class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-bold px-1.5 h-5 whitespace-nowrap"
                    >
                        <CircleCheck class="h-3 w-3 mr-1" /> Lunas
                    </Badge>
                    <Badge 
                        v-else-if="row.payable.status === 'partial'"
                        class="bg-amber-50 text-amber-600 border-amber-100 hover:bg-amber-50 text-[10px] uppercase font-bold px-1.5 h-5 whitespace-nowrap"
                    >
                        <CircleAlert class="h-3 w-3 mr-1" /> Cicilan
                    </Badge>
                    <Badge 
                        v-else
                        class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[10px] uppercase font-bold px-1.5 h-5 whitespace-nowrap"
                    >
                        <CircleAlert class="h-3 w-3 mr-1" /> Belum Bayar
                    </Badge>
                </template>

                <!-- Priority 3: Completed Normal -->
                <Badge 
                    v-else
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-bold px-1.5 h-5"
                >
                    <CircleCheck class="h-3 w-3 mr-1" /> Selesai
                </Badge>
            </template>

            <template #actions="{ row }">
                <Link :href="`/sales/${row.id}`" class="flex items-center justify-center h-8 w-8 rounded-lg hover:bg-secondary transition-all">
                    <ChevronRight class="h-4 w-4 text-muted-foreground" />
                </Link>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                    <ShoppingCart class="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada riwayat</p>
                        <p class="text-xs text-muted-foreground mt-1">Gunakan POS untuk membuat transaksi baru</p>
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
