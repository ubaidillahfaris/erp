<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { ArrowLeft, ArrowUpCircle, ArrowDownCircle, Info } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import PageHeader from '@/components/PageHeader.vue';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    product: any;
    movements: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    filters: {
    per_page?: string;
        warehouse_id?: string;
    };
    warehouses: any[];
    currentWarehouseId: number;
    batches?: any[];
}>();

const perPage = ref(props.filters.per_page || String(props.movements.per_page));
const warehouseId = ref(props.filters.warehouse_id || String(props.currentWarehouseId));

watch([perPage, warehouseId], debounce(([newPerPage, newWarehouseId]) => {
    router.get(`/stock/${props.product.id}`, {
        per_page: newPerPage,
        warehouse_id: newWarehouseId
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stok Inventori', href: '/stock' },
    { title: props.product.name, href: `/stock/${props.product.id}` },
];

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit' }).format(new Date(dateString));
};

const getMovementDetails = (movement: any) => {
    switch (movement.reference_type) {
        case 'restock':
            return { label: 'Restock', color: 'bg-blue-100 text-blue-700', refHref: `/restock/${movement.reference_id}/edit` };
        case 'production_usage':
            return { label: 'Pemakaian Productsi', color: 'bg-orange-100 text-orange-700', refHref: `/production` };
        case 'production_yield':
            return { label: 'Hasil Productsi', color: 'bg-green-100 text-green-700', refHref: `/production` };
        case 'adjustment':
            return { label: 'Penyesuaian Manual', color: 'bg-purple-100 text-purple-700', refHref: null };
        default:
            return { label: movement.reference_type || 'Mutasi', color: 'bg-gray-100 text-gray-700', refHref: null };
    }
};
</script>

<template>
<Head :title="`Histori Stok - ${product.name}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6">
        <PageHeader 
            title="Histori Mutasi Stok" 
            :description="`${product.name} (${product.sku})`"
            back-href="/stock"
        >
            <template #actions>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl h-full">
                        <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pilih Lokasi:</Label>
                        <Select v-model="warehouseId">
                            <SelectTrigger class="h-8 w-44 border-none shadow-none text-xs font-bold bg-transparent">
                                <SelectValue placeholder="Pilih Gudang" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Gudang (Konsolidasi)</SelectItem>
                                <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                    {{ w.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex items-center gap-6 bg-white border border-slate-200 rounded-xl px-6 py-3 shadow-none">
                        <div class="text-center border-r border-slate-200 pr-6">
                            <p class="text-xs text-muted-foreground uppercase font-black tracking-widest">{{ warehouseId === 'all' ? 'Saldo Total' : 'Saldo Lokasi' }}</p>
                            <p class="text-2xl font-bold text-foreground">
                                {{ parseFloat(warehouseId === 'all' ? product.total_balance : (product.stock?.balance || 0)).toLocaleString('id-ID') }}
                            </p>
                        </div>
                        <div class="text-left">
                            <p class="text-xs text-muted-foreground uppercase font-black tracking-widest">Unit</p>
                            <p class="text-sm font-bold text-muted-foreground">{{ product.unit?.name }}</p>
                        </div>
                    </div>
                </div>
            </template>
        </PageHeader>

        <!-- Batch Breakdown Section -->
        <Card v-if="product.is_batch_tracked" class="border-orange-200 shadow-none bg-orange-50/20 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-orange-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-orange-900 leading-none">Rincian Per Batch (FEFO)</h3>
                    <p class="text-xs text-orange-600 mt-1">Daftar batch yang tersedia di lokasi terpilih.</p>
                </div>
                <Badge variant="outline" class="bg-white text-orange-700 border-orange-200">
                    {{ batches?.length || 0 }} Batch Aktif
                </Badge>
            </div>
            <div class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-orange-100">
                            <TableHead class="text-orange-900 font-bold">Nomor Batch</TableHead>
                            <TableHead class="text-orange-900 font-bold text-center">Gudang</TableHead>
                            <TableHead class="text-orange-900 font-bold text-center">Tgl Kadaluarsa</TableHead>
                            <TableHead class="text-orange-900 font-bold text-right">Stok</TableHead>
                            <TableHead class="text-orange-900 font-bold text-center">Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="b in batches" :key="b.id" class="border-orange-50 hover:bg-orange-50/50">
                            <TableCell class="font-mono font-medium text-slate-700">{{ b.batch_number }}</TableCell>
                            <TableCell class="text-center">
                                <span class="text-xs px-2 py-0.5 bg-white border rounded-full text-slate-600">{{ b.warehouse?.name }}</span>
                            </TableCell>
                            <TableCell class="text-center font-medium">
                                <span :class="b.status === 'expired' ? 'text-red-600' : (b.status === 'expiring_soon' ? 'text-orange-600' : 'text-slate-600')">
                                    {{ b.expiry_date ? new Date(b.expiry_date).toLocaleDateString('id-ID') : 'Tanpa Expiry' }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <span class="font-bold text-slate-900">{{ parseFloat(b.quantity_on_hand).toLocaleString('id-ID') }}</span>
                                <span class="text-[10px] ml-1 text-slate-400">{{ b.unit?.symbol }}</span>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge v-if="b.status === 'expired'" variant="destructive" class="text-[10px] h-5">EXPIRED</Badge>
                                <Badge v-else-if="b.status === 'expiring_soon'" variant="warning" class="text-[10px] h-5">WARNING</Badge>
                                <Badge v-else variant="success" class="text-[10px] h-5">OK</Badge>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="batches?.length === 0">
                            <TableCell colspan="5" class="h-20 text-center text-orange-400 text-sm italic">
                                Tidak ada batch tersedia di lokasi ini.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </Card>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Histori Pergerakan</h3>
                <p class="text-xs text-slate-400 mt-1">Daftar lengkap transaksi masuk dan keluar untuk product ini.</p>
            </div>
            <div class="p-6">
                <div class="rounded-xl border border-slate-200 bg-white shadow-none shadow-none overflow-hidden">
                    <!-- Top Pagination -->
                    <DataTablePagination :paginator="movements" v-model:perPage="perPage"
                        class="border-b bg-white px-4 pt-4" />

                    <Table>
                        <TableHeader>
                            <TableRow class="bg-muted/50">
                                <TableHead class="w-[200px]">Tanggal & Waktu</TableHead>
                                <TableHead>Tipe Mutasi</TableHead>
                                <TableHead class="text-right">Masuk / Keluar</TableHead>
                                <TableHead>Referensi / Keterangan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="m in movements.data" :key="m.id"
                                class="hover:bg-muted/20 transition-colors">
                                <TableCell class="text-sm font-medium">
                                    {{ formatDate(m.created_at) }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <Badge variant="secondary"
                                            :class="['w-fit font-bold uppercase text-xs tracking-tight px-1.5 py-0', getMovementDetails(m).color]">
                                            {{ getMovementDetails(m).label }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right font-mono text-base">
                                    <div class="flex items-center justify-end gap-2"
                                        :class="m.type === 'in' ? 'text-green-600' : 'text-destructive'">
                                        <ArrowUpCircle v-if="m.type === 'in'" class="h-4 w-4" />
                                        <ArrowDownCircle v-else class="h-4 w-4" />
                                        <span class="font-bold">
                                            {{ (m.type === 'in' ? '+' : '-') }} {{
                                                parseFloat(m.quantity).toLocaleString('id-ID') }}
                                        </span>
                                        <span class="text-xs text-muted-foreground font-sans">{{ m.unit?.name
                                        }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-0.5">
                                        <p class="text-sm">{{ m.notes || '-' }}</p>
                                        <p v-if="m.reference_type && m.reference_id"
                                            class="text-xs text-muted-foreground italic flex items-center gap-1">
                                            <Info class="h-3 w-3" />
                                            Source: {{ m.reference_type }} #{{ m.reference_id }}
                                        </p>
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="movements.data.length === 0">
                                <TableCell colspan="4" class="h-32 text-center text-muted-foreground">
                                    Belum ada histori pergerakan stok.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Bottom Pagination -->
                <DataTablePagination :paginator="movements" v-model:perPage="perPage" class="mt-4" />
            </div>
        </Card>
    </div>
</AppLayout>
</template>
