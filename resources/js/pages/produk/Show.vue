<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Package, Boxes, AlertCircle, CheckCircle2, History as HistoryIcon, Tag, Pencil } from 'lucide-vue-next';
import { index, edit } from '@/actions/App/Http/Controllers/ProdukController';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    produk: any;
    overhead_rate: number | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: index().url },
    { title: props.produk.nama, href: '#' },
];

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
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <Head :title="`Detail Produk - ${produk.nama}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button variant="outline" size="icon" class="btn-secondary h-8 w-8">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ produk.nama }}</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Detail informasi SKU: <span class="font-mono font-bold">{{ produk.sku }}</span></p>
                    </div>
                </div>
                <Link :href="edit({ id: produk.id }).url">
                    <Button variant="outline" class="gap-2">
                        <Pencil class="h-4 w-4" />
                        Edit Produk
                    </Button>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info Card -->
                <Card class="lg:col-span-2 border-slate-200 shadow-none overflow-hidden h-fit">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-2">
                            <Tag class="w-4 h-4 text-primary" />
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-tight">Informasi Utama</h3>
                        </div>
                        <Badge :class="produk.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'">
                            <CheckCircle2 v-if="produk.is_active" class="w-3 h-3 mr-1" />
                            {{ produk.is_active ? 'Aktif' : 'Non-Aktif' }}
                        </Badge>
                    </div>
                    <div class="p-0">
                        <Table>
                            <TableBody>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 w-1/3 font-medium text-slate-500">ID Produk / SKU</TableCell>
                                    <TableCell class="font-mono font-bold font-semibold uppercase">{{ produk.sku || '-' }}</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500">Barcode</TableCell>
                                    <TableCell>{{ produk.barcode || '-' }}</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500">Kategori</TableCell>
                                    <TableCell><Badge variant="outline" class="font-bold py-0 h-5">{{ produk.kategori || 'General' }}</Badge></TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500">Tipe Barang</TableCell>
                                    <TableCell class="flex items-center gap-2">
                                        <Package v-if="produk.type === 'finished_good'" class="w-4 h-4 text-indigo-500" />
                                        <Boxes v-else class="w-4 h-4 text-amber-500" />
                                        <span class="font-bold capitalize">{{ produk.type?.replace('_', ' ') }}</span>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500">Satuan Dasar</TableCell>
                                    <TableCell class="font-bold text-primary">{{ produk.satuan?.nama }} ({{ produk.satuan?.simbol }})</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500">Saldo Stok</TableCell>
                                    <TableCell class="font-bold tabular-nums">
                                        <span class="text-xl" :class="produk.stok <= (produk.stok_minimal || 0) ? 'text-rose-600' : 'text-slate-900'">
                                            {{ produk.stok || 0 }}
                                        </span>
                                        <span class="ml-1 text-slate-400 text-sm font-normal">{{ produk.satuan?.simbol }}</span>
                                        <p v-if="produk.stok <= (produk.stok_minimal || 0)" class="text-[10px] text-rose-500 font-bold uppercase mt-1">Stok Menipis (Min: {{ produk.stok_minimal }})</p>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>

                <!-- Financial & Overhead Sidebar -->
                <div class="space-y-6">
                    <Card class="border-slate-200 shadow-none">
                        <div class="p-6 space-y-6">
                            <!-- Pricing Section -->
                            <div class="space-y-4">
                                <div class="flex items-center gap-2">
                                    <HistoryIcon class="w-4 h-4 text-emerald-500" />
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">Informasi Harga</h3>
                                </div>
                                <div class="space-y-2 pt-2 border-t border-slate-100">
                                    <p class="text-xs text-slate-500 uppercase font-bold">Harga Jual Retail</p>
                                    <p class="text-2xl font-black text-slate-900">{{ formatCurrency(produk.current_price?.retail_price) }}</p>
                                </div>
                                <div v-if="produk.current_price?.purchase_price" class="space-y-2 pt-2 border-t border-slate-100">
                                    <p class="text-xs text-slate-500 uppercase font-bold">Modal Terakhir</p>
                                    <p class="text-lg font-bold text-emerald-600">{{ formatCurrency(produk.current_price.purchase_price) }}</p>
                                    <p class="text-[10px] text-slate-400 italic">Diperbarui pada {{ formatDate(produk.current_price.updated_at) }}</p>
                                </div>
                            </div>

                            <!-- Phase 6A: Overhead Rate Display -->
                            <div v-if="produk.type === 'finished_good'" class="space-y-4 pt-6 border-t border-slate-100">
                                <div class="flex items-center gap-2">
                                    <AlertCircle class="w-4 h-4" :class="overhead_rate ? 'text-emerald-500' : 'text-amber-500'" />
                                    <h3 class="text-xs font-black uppercase tracking-widest" :class="overhead_rate ? 'text-slate-400' : 'text-amber-600'">
                                        Biaya Overhead
                                    </h3>
                                </div>
                                
                                <div v-if="overhead_rate" class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                    <p class="text-2xl font-black text-slate-900">{{ formatCurrency(overhead_rate) }}</p>
                                    <p class="text-xs text-slate-400 mt-1 italic">Diterapkan per unit barang jadi</p>
                                </div>
                                
                                <div v-else class="p-4 rounded-xl bg-amber-50 border border-amber-100 flex items-start gap-3">
                                    <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                                        <AlertCircle class="h-4 w-4 text-amber-600" />
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-amber-900">Belum diatur</p>
                                        <p class="text-[11px] text-amber-700 leading-tight mt-0.5">Biaya overhead diperlukan untuk kalkulasi HPP produksi yang akurat.</p>
                                        <Link :href="edit({ id: produk.id }).url" class="inline-block mt-2 text-[11px] font-bold text-amber-600 hover:underline">
                                           Atur Sekarang →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                    
                    <Card v-if="produk.deskripsi" class="border-slate-200 shadow-none bg-slate-50/50">
                        <div class="p-6">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Deskripsi Produk</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ produk.deskripsi }}</p>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
