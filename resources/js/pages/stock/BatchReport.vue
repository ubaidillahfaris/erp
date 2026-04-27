<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Calendar, 
    Filter, 
    Search, 
    ChevronRight, 
    AlertTriangle, 
    CheckCircle2, 
    XCircle,
    Archive,
    Warehouse as WarehouseIcon,
    ArrowLeft
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

interface Batch {
    id: number;
    batch_number: string;
    lot_number?: string;
    expiry_date?: string;
    quantity_on_hand: number;
    status: 'ok' | 'expiring_soon' | 'expired';
    product: {
        name: string;
        sku: string;
    };
    warehouse: {
        name: string;
    };
    unit: {
        symbol: string;
    };
}

const props = defineProps<{
    batches: {
        data: Batch[];
        from: number;
        to: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: {
        warehouse_id?: string;
        status?: string;
        search?: string;
    };
    warehouses: any[];
}>();

const expiringSoonCount = computed(() => props.batches.data.filter((b: Batch) => b.status === 'expiring_soon').length);
const expiredCount = computed(() => props.batches.data.filter((b: Batch) => b.status === 'expired').length);
const safeCount = computed(() => props.batches.data.filter((b: Batch) => b.status === 'ok').length);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock', href: '/stock' },
    { title: 'Laporan Batch & Expiry', href: '#' },
];

const search = ref(props.filters.search || '');
const warehouse_id = ref(props.filters.warehouse_id || 'all');
const status = ref(props.filters.status || 'all');

const updateFilters = debounce(() => {
    router.get('/stock-batches', {
        search: search.value,
        warehouse_id: warehouse_id.value === 'all' ? undefined : warehouse_id.value,
        status: status.value === 'all' ? undefined : status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, warehouse_id, status], () => {
    updateFilters();
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'expired':
            return { label: 'Expired', variant: 'destructive' as const, icon: XCircle, class: '' };
        case 'expiring_soon':
            return { label: 'Akan Habis', variant: 'outline' as const, icon: AlertTriangle, class: 'badge-warning' };
        case 'ok':
            return { label: 'Aman', variant: 'outline' as const, icon: CheckCircle2, class: 'badge-success' };
        default:
            return { label: status, variant: 'secondary' as const, icon: Archive, class: '' };
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID').format(value);
};

const formatDate = (date: string | undefined) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getExpiryColor = (date: string | undefined, status: string) => {
    if (!date) return 'text-slate-400';
    if (status === 'expired') return 'text-destructive font-bold';
    if (status === 'expiring_soon') return 'text-orange-500 font-medium';
    return 'text-slate-700';
};
</script>

<template>
    <Head title="Laporan Batch & Expiry" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/stock">
                        <Button variant="outline" size="icon" class="h-9 w-9 rounded-xl border-slate-200 bg-white">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Batch & Expiry Control</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Monitor masa kadaluarsa dan stok per batch (FEFO).</p>
                    </div>
                </div>
            </div>

            <!-- Stats/Summary Cards (Optional but adds premium feel) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="p-4 border-slate-200 shadow-none bg-white flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-600">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Mendekati Kadaluarsa</p>
                        <p class="text-2xl font-bold text-slate-900">{{ expiringSoonCount }} Batch</p>
                    </div>
                </Card>
                <Card class="p-4 border-slate-200 shadow-none bg-white flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-red-50 flex items-center justify-center text-red-600">
                        <XCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Sudah Kadaluarsa</p>
                        <p class="text-2xl font-bold text-slate-900">{{ expiredCount }} Batch</p>
                    </div>
                </Card>
                <Card class="p-4 border-slate-200 shadow-none bg-white flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-green-50 flex items-center justify-center text-green-600">
                        <CheckCircle2 class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Kondisi Aman</p>
                        <p class="text-2xl font-bold text-slate-900">{{ safeCount }} Batch</p>
                    </div>
                </Card>
            </div>

            <!-- Filter & Table Section -->
            <Card class="border-slate-200 shadow-none bg-white rounded-2xl overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row gap-4 items-center justify-between bg-slate-50/50">
                    <div class="relative w-full md:w-96">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari Product atau No. Batch..." 
                            class="pl-10 h-10 border-slate-200 bg-white rounded-xl focus:ring-primary/20"
                        />
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="flex items-center gap-2">
                            <WarehouseIcon class="h-4 w-4 text-slate-400" />
                            <Select v-model="warehouse_id">
                                <SelectTrigger class="w-[180px] h-10 rounded-xl bg-white">
                                    <SelectValue placeholder="Semua Gudang" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Gudang</SelectItem>
                                    <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                        {{ w.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-center gap-2">
                            <Filter class="h-4 w-4 text-slate-400" />
                            <Select v-model="status">
                                <SelectTrigger class="w-[180px] h-10 rounded-xl bg-white">
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="ok">Aman</SelectItem>
                                    <SelectItem value="expiring_soon">Akan Habis</SelectItem>
                                    <SelectItem value="expired">Expired</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/50 hover:bg-slate-50/50">
                                <TableHead class="font-bold text-slate-900">Product & SKU</TableHead>
                                <TableHead class="font-bold text-slate-900 text-center">Nomor Batch</TableHead>
                                <TableHead class="font-bold text-slate-900 text-center">Gudang</TableHead>
                                <TableHead class="font-bold text-slate-900 text-center">Tanggal Kadaluarsa</TableHead>
                                <TableHead class="font-bold text-slate-900 text-right">Stok Batch</TableHead>
                                <TableHead class="font-bold text-slate-900 text-center">Status</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="batch in batches.data" :key="batch.id" class="group hover:bg-slate-50 transition-colors">
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900 group-hover:text-primary transition-colors">
                                            {{ batch.product.name }}
                                        </span>
                                        <span class="text-xs text-slate-400 font-mono tracking-tight">{{ batch.product.sku }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center font-medium font-mono text-slate-600">
                                    {{ batch.batch_number }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge variant="outline" class="bg-slate-50 font-normal">
                                        {{ batch.warehouse.name }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-center font-medium">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span :class="getExpiryColor(batch.expiry_date, batch.status)">
                                            {{ formatDate(batch.expiry_date) }}
                                        </span>
                                        <span v-if="batch.status === 'expired'" class="text-[10px] text-red-500 uppercase font-bold">
                                            Sudah Melewati Batas
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="font-bold text-slate-900">{{ formatCurrency(batch.quantity_on_hand) }}</span>
                                        <span class="text-xs text-slate-400">{{ batch.unit.symbol }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge 
                                        :variant="getStatusBadge(batch.status).variant" 
                                        :class="[
                                            'rounded-lg px-2 py-1 flex items-center gap-1.5 w-fit mx-auto',
                                            getStatusBadge(batch.status).class
                                        ]"
                                    >
                                        <component :is="getStatusBadge(batch.status).icon" class="h-3 w-3" />
                                        {{ getStatusBadge(batch.status).label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Link :href="`/stock-batches/${batch.id}`">
                                        <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 group-hover:text-primary">
                                            <ChevronRight class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="batches.data.length === 0">
                                <TableCell colspan="7" class="h-64 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <Archive class="h-12 w-12 text-slate-200" />
                                        <p class="text-slate-500 font-medium">Tidak ada data batch ditemukan.</p>
                                        <p class="text-xs text-slate-400">Coba ubah filter atau pencarian Anda.</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between">
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-medium text-slate-900">{{ batches.from || 0 }}</span> - <span class="font-medium text-slate-900">{{ batches.to || 0 }}</span> dari <span class="font-medium text-slate-900">{{ batches.total }}</span> batch.
                    </p>
                    <div class="flex gap-2">
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="!batches.prev_page_url"
                            @click="batches.prev_page_url && router.get(batches.prev_page_url)"
                            class="h-8 rounded-lg bg-white"
                        >
                            Sebelumnya
                        </Button>
                        <Button 
                            variant="outline" 
                            size="sm" 
                            :disabled="!batches.next_page_url"
                            @click="batches.next_page_url && router.get(batches.next_page_url)"
                            class="h-8 rounded-lg bg-white"
                        >
                            Berikutnya
                        </Button>
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

/* Custom status variants for badges if not defined in shadcn/ui */
.badge-warning {
    @apply bg-orange-100 text-orange-700 hover:bg-orange-100 border-orange-200 shadow-none;
}
.badge-success {
    @apply bg-emerald-100 text-emerald-700 hover:bg-emerald-100 border-emerald-200 shadow-none;
}
</style>
