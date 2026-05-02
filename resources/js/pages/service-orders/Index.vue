<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, FileText, User as UserIcon, 
    ShoppingCart, CreditCard, ChevronRight,
    Calendar, CircleCheck, CircleAlert, Filter,
    X, ClipboardList, Clock, History
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
    orders: {
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
        status?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
    steps: Array<{ code: string, name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Riwayat Servis', href: '/service-orders' },
];

const search = ref(props.filters.search || '');
const date_start = ref(props.filters.date_start || '');
const date_end = ref(props.filters.date_end || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page || String(props.orders.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'order', label: 'Order / Invoice', sortKey: 'order_number' },
    { key: 'customer', label: 'Pelanggan', sortable: false },
    { key: 'items', label: 'Layanan', sortable: false },
    { key: 'total', label: 'Total', sortKey: 'total_amount' },
    { key: 'status_badge', label: 'Progress Status', sortKey: 'current_status_code' },
];

watch([search, date_start, date_end, status, perPage, sort, direction], debounce(() => {
    router.get('/service-orders', {
        search: search.value || undefined,
        date_start: date_start.value || undefined,
        date_end: date_end.value || undefined,
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
    }).format((value || 0) / 100);
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
    status.value = 'all';
};

const hasActiveFilters = computed(() => {
    return search.value || date_start.value || date_end.value || status.value !== 'all';
});
</script>

<template>
<Head title="Riwayat Servis" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Riwayat Servis" 
        description="Daftar seluruh order jasa & layanan" 
        back-href="/dashboard" 
        :count="orders.total" 
    />
    
    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="orders" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari nomor order..." 
            toolbar-title="Service Ledger" 
            :title="'Riwayat Servis'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            :total-count="orders.total"
        >
            <template #header-actions>
                <Link href="/service-orders/create">
                    <Button primary>
                        <Plus class="h-4 w-4 mr-1" />
                        Order Baru
                    </Button>
                </Link>
            </template>

            <template #toolbar-prefix>
                <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                    <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2 h-9">
                        <Calendar class="h-3.5 w-3.5 text-muted-foreground mr-2" />
                        <input type="date" v-model="date_start" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                        <span class="text-xs text-muted-foreground mx-1">-</span>
                        <input type="date" v-model="date_end" class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                    </div>

                    <Select v-model="status">
                        <SelectTrigger class="h-9 w-[160px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Status Progress" />
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem v-for="s in steps" :key="s.code" :value="s.code">
                                {{ s.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground font-semibold uppercase tracking-tight" @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(order)="{ row }">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <ClipboardList class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-foreground font-mono leading-none">#{{ row.order_number }}</p>
                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-tighter mt-1.5">{{ formatDate(row.created_at) }}</p>
                    </div>
                </div>
            </template>

            <template #cell(customer)="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200/50">
                        <UserIcon class="h-3 w-3 text-slate-400" />
                    </div>
                    <span class="text-[12px] font-semibold text-foreground/80 leading-none" :class="!row.party && 'italic opacity-50'">
                        {{ row.party?.name || 'Walk-in' }}
                    </span>
                </div>
            </template>

            <template #cell(items)="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="text-[12px] font-semibold text-foreground leading-none">{{ row.service?.name }}</span>
                    <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground opacity-60">{{ row.items_count || 0 }} Tipe Layanan</span>
                </div>
            </template>

            <template #cell(total)="{ row }">
                <span class="text-[13px] font-semibold text-foreground tabular-nums">
                    {{ formatCurrency(row.total_amount) }}
                </span>
            </template>

            <template #cell(status_badge)="{ row }">
                <Badge 
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-semibold px-1.5 h-5 whitespace-nowrap"
                    :class="{
                        'bg-amber-50 text-amber-600 border-amber-100': !row.production_step?.is_final,
                        'bg-slate-100 text-slate-500 border-slate-200': !row.production_step
                    }"
                >
                    <Clock v-if="!row.production_step?.is_final" class="h-3 w-3 mr-1" />
                    <CircleCheck v-else class="h-3 w-3 mr-1" />
                    {{ row.production_step?.name || 'PENDING' }}
                </Badge>
            </template>

            <template #actions="{ row }">
                <Link :href="`/service-orders/${row.id}`" class="flex items-center justify-center h-8 w-8 rounded-lg hover:bg-secondary transition-all">
                    <ChevronRight class="h-4 w-4 text-muted-foreground" />
                </Link>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                    <History class="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Belum ada riwayat servis</p>
                        <p class="text-xs text-muted-foreground mt-1">Gunakan POS Servis untuk membuat order baru</p>
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
