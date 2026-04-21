<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Save, CheckCircle, ArrowLeft, Search, AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    produks: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
    { title: 'Mulai Baru', href: '#' },
];

const searchTerm = ref(props.filters.search || '');
const isSearching = ref(false);

const form = useForm({
    tanggal: new Date().toISOString().split('T')[0],
    keterangan: '',
    status: 'draft',
    // Key: produk_id, Value: item details
    items_map: {} as Record<number, any>
});

// Sync current page products to items_map
watch(() => props.produks.data, (newProduks) => {
    newProduks.forEach(p => {
        if (!form.items_map[p.id]) {
            form.items_map[p.id] = {
                produk_id: p.id,
                nama: p.nama,
                sku: p.sku,
                satuan_id: p.satuan_id,
                satuan_nama: p.satuan?.nama,
                satuan_simbol: p.satuan?.simbol,
                system_qty: parseFloat(p.stock?.balance || 0),
                physical_qty: parseFloat(p.stock?.balance || 0)
            };
        }
    });
}, { immediate: true });

// Server-side search
watch(searchTerm, debounce((value) => {
    isSearching.value = true;
    router.get('/stock-opname/create',
        { search: value },
        { preserveState: true, preserveScroll: true, onFinish: () => isSearching.value = false }
    );
}, 300));

import { useConfirm } from '@/composables/useConfirm';

const { confirmDialog } = useConfirm();

const calculateDiff = (item: any) => {
    return (item.physical_qty || 0) - (item.system_qty || 0);
};

const submit = async (status: 'draft' | 'completed') => {
    form.status = status;
    const msgTitle = status === 'completed' ? 'Selesaikan Opname?' : 'Simpan Draft?';
    const msg = status === 'completed'
        ? 'Apakah Anda yakin ingin menyelesaikan opname ini? Stok sistem akan disesuaikan otomatis tanpa dapat dibatalkan.'
        : 'Simpan pekerjaan ini sebagai draft untuk dilanjutkan nanti?';

    if (await confirmDialog(msgTitle, msg)) {
        // Transform items_map to items array for backend
        form.transform((data) => ({
            ...data,
            items: Object.values(data.items_map)
        })).post('/stock-opname');
    }
};

const totalDiscrepancies = computed(() => {
    return Object.values(form.items_map).filter(item => Math.abs(calculateDiff(item)) > 0.000001).length;
});
</script>

<template>
<Head title="Mulai Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link href="/stock-opname">
                    <Button variant="outline" size="icon"
                        class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Mulai Stock Opname</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Input stok fisik untuk penyesuaian inventori.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="submit('draft')" :disabled="form.processing"
                    class="btn-secondary">
                    <Save class="mr-2 h-4 w-4" />
                    Simpan Draft
                </Button>
                <Button @click="submit('completed')" :disabled="form.processing"
                    class="btn-primary">
                    <CheckCircle class="mr-2 h-4 w-4" />
                    Selesaikan Opname
                </Button>
            </div>
        </div>

        <!-- 1. Informasi Umum -->
        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Opname</h3>
                <p class="text-xs text-slate-400 mt-1">Tanggal dan keterangan sesi stock opname ini.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <Label for="tanggal">Tanggal Opname</Label>
                        <Input id="tanggal" v-model="form.tanggal" type="date" class="h-10" />
                    </div>
                    <div class="space-y-2">
                        <Label for="keterangan">Keterangan / Catatan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" placeholder="Misal: Opname Bulanan"
                            class="resize-none min-h-[40px]" />
                    </div>
                </div>

                <div v-if="totalDiscrepancies > 0"
                    class="mt-4 flex items-center gap-3 py-3 px-4 rounded-lg border border-yellow-200 bg-yellow-50">
                    <AlertCircle class="h-4 w-4 text-yellow-600 shrink-0" />
                    <span class="text-xs font-semibold text-yellow-700">
                        {{ totalDiscrepancies }} barang terdeteksi memiliki selisih stok.
                    </span>
                </div>
            </div>
        </Card>

        <!-- 2. Daftar Barang -->
        <Card class="border border-slate-200 rounded-xl bg-white shadow-none overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Daftar Barang</h3>
                        <p class="text-xs text-slate-400 mt-1">Masukkan jumlah stok fisik aktual untuk setiap barang.
                        </p>
                    </div>
                    <!-- Search -->
                    <div class="relative w-64">
                        <Search v-if="!isSearching"
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Loader2 v-else
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 animate-spin" />
                        <Input v-model="searchTerm" placeholder="Cari barang..."
                            class="pl-9 h-9 bg-slate-50 border-slate-200" />
                    </div>
                </div>
            </div>
            <div class="p-0">
                <Table>
                    <TableHeader class="bg-slate-50 border-b border-slate-200">
                        <TableRow class="hover:bg-slate-50">
                            <TableHead class="h-10 px-6 text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                Barang</TableHead>
                            <TableHead
                                class="h-10 px-4 text-right text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                Stok Sistem</TableHead>
                            <TableHead
                                class="h-10 px-4 w-[200px] text-right text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                Stok Fisik</TableHead>
                            <TableHead
                                class="h-10 px-6 text-right text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                Selisih</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="produk in produks.data" :key="produk.id"
                            class="hover:bg-slate-50/60 border-b border-slate-100 transition-colors">
                            <TableCell class="px-6 py-4">
                                <div class="font-semibold text-sm text-slate-900 capitalize">{{ produk.nama }}</div>
                                <div class="text-xs font-mono text-slate-400 mt-0.5">{{ produk.sku }}</div>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right text-sm font-medium text-slate-700">
                                {{ form.items_map[produk.id]?.system_qty }}
                                <span class="text-xs text-slate-400 ml-1">{{ produk.satuan?.simbol }}</span>
                            </TableCell>
                            <TableCell class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Input v-if="form.items_map[produk.id]"
                                        v-model="form.items_map[produk.id].physical_qty" type="number" step="any"
                                        class="w-28 text-right h-9 border-slate-200 bg-white text-sm" />
                                    <span class="text-xs font-bold text-slate-400 w-8 text-left uppercase">{{
                                        produk.satuan?.simbol }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div v-if="form.items_map[produk.id]" class="text-sm font-bold"
                                    :class="calculateDiff(form.items_map[produk.id]) === 0
                                        ? 'text-slate-400'
                                        : (calculateDiff(form.items_map[produk.id]) > 0 ? 'text-emerald-600' : 'text-red-500')">
                                    <span v-if="calculateDiff(form.items_map[produk.id]) > 0">+</span>
                                    {{ calculateDiff(form.items_map[produk.id]) }}
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="produks.data.length === 0">
                            <TableCell colspan="4" class="h-32 text-center text-sm text-slate-400">
                                Barang tidak ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    <DataTablePagination :paginator="produks" />
                </div>
            </div>
        </Card>

    </div>
</AppLayout>
</template>
