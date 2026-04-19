<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, ChevronRight, Download, Edit2, FileIcon, FileText, Info, PackageOpen, Tag, Store, Copy } from 'lucide-vue-next';
import { index, edit, finalize } from '@/actions/App/Http/Controllers/PurchaseController';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{ 
    purchase: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchasing Inbound', href: index().url },
    { title: `Inbound #${props.purchase.id}`, href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });
};

const formatSize = (bytes: number) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const typeLabels: Record<string, string> = {
    purchase: 'Pembelian Stok',
    gift: 'Pemberian (Bonus)',
    adjustment: 'Penyesuaian Naik'
};

const formatDateTime = (isoString?: string) => {
    if (!isoString) return '-';
    return new Date(isoString).toLocaleString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

const paymentMethodLabels: Record<string, string> = {
    cash: 'Tunai (Cash)',
    transfer: 'Transfer Bank',
    credit: 'Kredit (Hutang)'
};

const paymentMethodVariants: Record<string, string> = {
    cash: 'bg-blue-100 text-blue-700 border-blue-200',
    transfer: 'bg-purple-100 text-purple-700 border-purple-200',
    credit: 'bg-amber-100 text-amber-700 border-amber-200'
};

const { confirmDialog } = useConfirm();

const handleFinalize = async () => {
    if (await confirmDialog(
        'Finalisasi Transaksi?', 
        'Pastikan data sudah benar. Setelah finalisasi, stok fisik & harga aset akan berubah, dan dokumen ini akan DIGEMBOK permanen secara sistem.'
    )) {
        router.post(`/purchasing/${props.purchase.id}/finalize`);
    }
};
</script>

<template>
<Head title="Detail Inbound" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] font-sans">
        
        <!-- Header Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="icon" class="bg-white hover:bg-slate-100 shadow-none border border-slate-200">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight flex items-center gap-3">
                        Berita Acara Inbound
                        <Badge v-if="purchase.status === 'finalized'" variant="secondary" class="bg-emerald-100 text-emerald-800 border-none font-bold">
                            FINALIZED
                        </Badge>
                        <Badge v-else variant="secondary" class="bg-slate-200 text-slate-800 border-none font-bold">
                            DRAFT
                        </Badge>
                    </h1>
                    <p class="text-muted-foreground mt-1">{{ formatDate(purchase.tanggal) }} &bull; TR-{{ String(purchase.id).padStart(5, '0') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-3" v-if="purchase.status === 'draft'">
                <Link :href="`/purchasing/${purchase.id}/edit`">
                    <Button variant="outline" class="gap-2">
                        <Edit2 class="h-4 w-4" /> Edit Draft
                    </Button>
                </Link>
                <Button @click="handleFinalize" class="gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold h-10 px-6 shadow-none ">
                    <CheckCircle2 class="h-4 w-4" /> Finalisasi & Masukkan Stok
                </Button>
            </div>
            <div v-else>
                <!-- Maybe Print button later -->
            </div>
        </div>

        <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-6 items-start pb-20">
            <!-- LEFT COLUMN: Main Data -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                <!-- INVOICE INFO -->
                <Card class="border-slate-200 overflow-hidden bg-white">
                    <div class="flex flex-col md:flex-row justify-between p-6 gap-6">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Tipe Transaksi</h3>
                            <div class="flex flex-col gap-1.5">
                                <p class="text-lg font-bold text-foreground">
                                    {{ typeLabels[purchase.transaction_type] || purchase.transaction_type }}
                                </p>
                                <Badge variant="outline" :class="['w-fit font-semibold px-2 py-0.5 text-[10px] uppercase tracking-wider', paymentMethodVariants[purchase.payment_method] || 'bg-slate-100 text-slate-700 border-slate-200']">
                                    {{ paymentMethodLabels[purchase.payment_method] || purchase.payment_method }}
                                </Badge>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 md:items-end">
                            <h3 class="text-xs font-bold md:text-right uppercase tracking-widest text-muted-foreground">Total Nilai</h3>
                            <p class="text-3xl font-extrabold text-primary tracking-tighter">
                                {{ formatCurrency(purchase.total_biaya) }}
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-slate-100 border-t border-slate-200">
                        <div class="bg-white p-5 flex flex-col gap-2">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-muted-foreground">
                                <Store class="h-4 w-4" /> Vendor Pengirim
                            </div>
                            <div v-if="purchase.vendor">
                                <p class="font-bold text-foreground">{{ purchase.vendor.nama }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5 line-clamp-2">{{ purchase.vendor.alamat || 'Alamat tidak diisi' }}</p>
                            </div>
                            <div v-else class="text-sm font-medium italic text-muted-foreground">
                                Transaksi Internal / Tanpa Vendor
                            </div>
                        </div>
                        <div class="bg-white p-5 flex flex-col gap-2">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-muted-foreground">
                                <FileText class="h-4 w-4" /> Referensi
                            </div>
                            <p class="font-bold text-foreground">{{ purchase.no_invoice || 'Tanpa Referensi' }}</p>
                            <p class="text-xs text-muted-foreground mt-0.5 italic line-clamp-2">{{ purchase.keterangan || 'Tidak ada catatan tambahan' }}</p>
                        </div>
                    </div>
                </Card>

                <!-- ITEMS TABLE -->
                <Card class="border-slate-200 bg-white overflow-hidden">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Rincian Barang</h3>
                    </div>
                    <div class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50">
                                    <TableHead class="pl-6 w-[45%]">Deskripsi Produk</TableHead>
                                    <TableHead class="text-right">Qty</TableHead>
                                    <TableHead class="text-right">Hrg. Satuan</TableHead>
                                    <TableHead class="text-right pr-6">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in purchase.items" :key="item.id">
                                    <TableCell class="pl-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="h-8 w-8 rounded bg-secondary flex items-center justify-center shrink-0">
                                                <PackageOpen class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-sm">{{ item.produk.nama }}</span>
                                                <span class="text-xs text-muted-foreground">{{ item.produk.sku || '--' }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right font-medium">
                                        {{ Number(item.jumlah) }} <span class="text-xs text-muted-foreground ml-1">{{ item.produk.satuan?.simbol || '-' }}</span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <span v-if="Number(item.harga_satuan) > 0">{{ formatCurrency(Number(item.harga_satuan)) }}</span>
                                        <Badge v-else variant="outline" class="text-xs bg-slate-50 text-slate-500 rounded">Rp 0</Badge>
                                    </TableCell>
                                    <TableCell class="text-right pr-6 font-bold">
                                        {{ formatCurrency(Number(item.jumlah) * Number(item.harga_satuan)) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            <!-- RIGHT COLUMN: Metadata & Signature -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <!-- Signature Audit Trail -->
                <Card class="border-slate-200 bg-white" v-if="purchase.status === 'finalized'">
                    <div class="p-0">
                        <div class="bg-emerald-500 text-white p-5 flex flex-col gap-2 relative overflow-hidden">
                            <CheckCircle2 class="absolute -right-4 -bottom-4 h-24 w-24 text-emerald-600/30 rotate-12" />
                            <h3 class="font-bold flex items-center gap-2 z-10"><CheckCircle2 class="h-5 w-5" /> Dokumen Disahkan</h3>
                            <p class="text-xs text-emerald-100 z-10">Stok riil dan perhitungan HPP Average telah diubah berdasarkan dokumen ini.</p>
                        </div>
                        <div class="p-5 flex flex-col gap-4 text-sm" v-if="purchase.signature_log">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Otorisator Utama</span>
                                <span class="font-bold">{{ purchase.signature_log.user_name || 'System' }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Waktu Stempel (Finalize)</span>
                                <span>{{ formatDateTime(purchase.signature_log.finalized_at) }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Lacak Jaringan (IP)</span>
                                <span class="font-mono text-xs bg-slate-100 p-1 rounded max-w-fit">{{ purchase.signature_log.ip_address || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </Card>
                
                <!-- If Draft, show warning instead -->
                <Card class="border border-amber-200 shadow-none bg-amber-50" v-else>
                    <div class="p-5 flex gap-3 text-amber-800">
                        <Info class="h-5 w-5 shrink-0 mt-0.5 text-amber-600" />
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-sm">Status Draft Aktif</span>
                            <span class="text-xs">Barang belum masuk ke stok gudang logistik dan total aset belum dihitung. Segera periksa kebenaran dokumen lalu klik tombol "Finalisasi".</span>
                        </div>
                    </div>
                </Card>

                <!-- Attachments -->
                <Card class="border-slate-200 bg-white">
                    <div class="border-b border-slate-100 px-6 py-4">
                        <h3 class="text-sm text-sm font-semibold text-slate-900 leading-none">Dokumen Terlampir</h3>
                    </div>
                    <div class="p-5">
                        <div v-if="purchase.attachments?.length > 0" class="flex flex-col gap-3">
                            <a 
                                v-for="att in purchase.attachments" 
                                :key="att.id"
                                :href="`/storage/${att.file_path}`" 
                                target="_blank"
                                class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-primary/50 hover:bg-slate-50 transition-colors group"
                            >
                                <div class="h-10 w-10 bg-primary/10 rounded flex items-center justify-center shrink-0">
                                    <Download class="h-4 w-4 text-primary group-hover:scale-110 transition-transform" />
                                </div>
                                <div class="flex flex-col overflow-hidden">
                                    <span class="text-sm font-bold truncate text-foreground group-hover:text-primary transition-colors">{{ att.file_name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</span>
                                </div>
                            </a>
                        </div>
                        <div v-else class="text-sm text-center py-6 text-muted-foreground italic border-2 border-dashed border-slate-200 rounded-lg">
                            <FileIcon class="h-6 w-6 mx-auto mb-2 opacity-50" />
                            Tidak ada lampiran.
                        </div>
                    </div>
                </Card>
            </div>
        </div>

    </div>
</AppLayout>
</template>
