<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    History, 
    Package, 
    Warehouse as WarehouseIcon,
    AlertTriangle,
    CheckCircle2,
    XCircle,
    ArrowUpCircle,
    ArrowDownCircle,
    Archive
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
    batch: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock', href: '/stock' },
    { title: 'Batch Control', href: '/stock-batches' },
    { title: `Detail Batch ${props.batch.batch_number}`, href: '#' },
];

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
        month: 'long',
        day: 'numeric'
    });
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Detail Batch ${batch.batch_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/stock-batches">
                    <Button variant="outline" size="icon" class="h-9 w-9 rounded-xl border-slate-200 bg-white">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Batch #{{ batch.batch_number }}</h1>
                        <Badge 
                            :variant="getStatusBadge(batch.status).variant" 
                            :class="['rounded-lg px-2 py-1 flex items-center gap-1.5', getStatusBadge(batch.status).class]"
                        >
                            <component :is="getStatusBadge(batch.status).icon" class="h-3 w-3" />
                            {{ getStatusBadge(batch.status).label }}
                        </Badge>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">Histori pergerakan stok untuk batch spesifik ini.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info Card -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <Card class="border-slate-200 shadow-none bg-white rounded-2xl p-6">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                            <Package class="h-4 w-4" />
                            Informasi Produk & Batch
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Product Name</span>
                                    <span class="text-lg font-bold text-slate-900">{{ batch.product.name }}</span>
                                    <span class="text-sm text-slate-500 font-mono">{{ batch.product.sku }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Warehouse Location</span>
                                    <div class="flex items-center gap-2 text-slate-700">
                                        <WarehouseIcon class="h-4 w-4 text-slate-400" />
                                        <span class="font-medium">{{ batch.warehouse.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Current Balance</span>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-black text-slate-900">{{ formatCurrency(batch.quantity_on_hand) }}</span>
                                        <span class="text-sm font-bold text-slate-500 uppercase">{{ batch.unit.symbol }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-slate-400 uppercase font-bold tracking-wider text-orange-600">Expiry Date</span>
                                    <div class="flex items-center gap-2 text-orange-700">
                                        <Calendar class="h-4 w-4" />
                                        <span class="font-bold">{{ formatDate(batch.expiry_date) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Movement History -->
                    <Card class="border-slate-200 shadow-none bg-white rounded-2xl overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900 flex items-center gap-2">
                                <History class="h-4 w-4" />
                                Histori Mutasi Batch
                            </h3>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50/30">
                                    <TableHead>Tanggal & Waktu</TableHead>
                                    <TableHead>Referensi</TableHead>
                                    <TableHead class="text-right">Qty</TableHead>
                                    <TableHead>Keterangan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="mv in batch.stock_movements" :key="mv.id" class="hover:bg-slate-50/50">
                                    <TableCell class="text-xs font-medium text-slate-600">
                                        {{ formatDateTime(mv.created_at) }}
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <ArrowUpCircle v-if="mv.type === 'in'" class="h-4 w-4 text-emerald-500" />
                                            <ArrowDownCircle v-else class="h-4 w-4 text-rose-500" />
                                            <span class="text-xs font-bold uppercase tracking-tighter">{{ mv.reference_type }} #{{ mv.reference_id }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <span :class="mv.type === 'in' ? 'text-emerald-600' : 'text-rose-600'" class="font-bold tabular-nums">
                                            {{ mv.type === 'in' ? '+' : '-' }}{{ formatCurrency(mv.quantity) }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-xs text-slate-500 italic max-w-[200px] truncate">
                                        {{ mv.notes }}
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="!batch.stock_movements || batch.stock_movements.length === 0">
                                    <TableCell colspan="4" class="h-32 text-center text-slate-400 italic">
                                        Belum ada pergerakan stok untuk batch ini.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </Card>
                </div>

                <!-- Side Info / FEFO Rules -->
                <div class="flex flex-col gap-6">
                    <Card class="border-orange-100 shadow-none bg-orange-50/30 rounded-2xl p-6">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-orange-800 mb-4 flex items-center gap-2">
                            <AlertTriangle class="h-4 w-4" />
                            FEFO Insight
                        </h3>
                        <div class="space-y-3 text-xs leading-relaxed text-orange-900/70">
                            <p>Sistem menggunakan metode <strong>First Expired First Out</strong>. Batch ini akan diprioritaskan untuk keluar jika memiliki tanggal kadaluarsa paling awal dibanding batch lain untuk produk yang sama.</p>
                            <p class="font-bold">Status Batch Saat Ini:</p>
                            <div class="p-3 bg-white/50 rounded-xl border border-orange-100">
                                <template v-if="batch.status === 'expired'">
                                    <p class="text-red-700 font-bold">BATCH EXPIRED!</p>
                                    <p>Barang ini tidak boleh dijual atau digunakan. Segera lakukan penyesuaian stok atau write-off.</p>
                                </template>
                                <template v-else-if="batch.status === 'expiring_soon'">
                                    <p class="text-orange-700 font-bold">MENDEKATI KADALUARSA</p>
                                    <p>Tingkatkan upaya penjualan atau prioritas penggunaan produksi untuk batch ini.</p>
                                </template>
                                <template v-else>
                                    <p class="text-emerald-700 font-bold">KONDISI AMAN</p>
                                    <p>Masa kadaluarsa masih lama atau dalam batas toleransi aman.</p>
                                </template>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@reference "../../../css/app.css";

.badge-warning {
    @apply bg-orange-100 text-orange-700 hover:bg-orange-100 border-orange-200 shadow-none;
}
.badge-success {
    @apply bg-emerald-100 text-emerald-700 hover:bg-emerald-100 border-emerald-200 shadow-none;
}
</style>
