<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, 
    Calendar, 
    FileText, 
    CheckCircle2, 
    History, 
    RotateCcw, 
    AlertTriangle, 
    Edit2, 
    Trash2,
    ClipboardList,
    PackageOpen,
    UserCircle,
    BadgeCheck,
    Clock,
    FileInput
} from 'lucide-vue-next';
import { storno, reopen } from '@/actions/App/Http/Controllers/StockOpnameController';
import { useConfirm } from '@/composables/useConfirm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils';

const props = defineProps<{
    opname: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
    { title: `Detail #${props.opname.id}`, href: '#' },
];

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateString));
};

const formatDateTime = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateString));
};

const { confirmDialog } = useConfirm();

const cancelOpname = async () => {
    if (await confirmDialog('Batalkan Hasil Opname?', 'Batalkan hasil penyesuaian stok ini? Saldo stok akan dikembalikan ke kondisi semula. Tindakan ini akan tercatat sebagai pembatalan.')) {
        router.post(storno.url(props.opname.id));
    }
};

const reopenOpname = async () => {
    if (await confirmDialog('Edit Kembali Opname?', 'Ingin mengubah data opname ini? Hasil penyesuaian stok saat ini akan dibatalkan, dan data akan dikembalikan menjadi Draft agar bisa Anda edit kembali.')) {
        router.post(reopen.url(props.opname.id));
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'storno': return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'draft': return 'bg-slate-200 text-slate-800 border-slate-300';
        default: return 'bg-slate-100 text-slate-600 border-slate-200';
    }
};
</script>

<template>
<Head title="Detail Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] font-sans">
        
        <!-- Premium Header Area -->
        <div class="max-w-7xl mx-auto w-full flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/stock-opname">
                    <Button variant="ghost" size="icon" class="bg-white hover:bg-slate-100 shadow-sm border border-slate-200 rounded-xl">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">Hasil Stock Opname</h1>
                        <Badge :class="cn('px-2.5 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider border', getStatusColor(opname.status))">
                            {{ opname.status === 'storno' ? 'DIBATALKAN' : opname.status }}
                        </Badge>
                    </div>
                    <p class="text-[13px] text-muted-foreground mt-1 font-medium italic">Dokumen audit internal untuk sinkronisasi stok sistem dan fisik.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <Link v-if="opname.status === 'draft'" :href="`/stock-opname/${opname.id}/edit`">
                    <Button class="bg-primary hover:bg-primary/90 text-white rounded-xl h-10 px-6 font-bold shadow-lg shadow-primary/20 gap-2">
                        <Edit2 class="h-4 w-4" />
                        Lanjutkan Draft
                    </Button>
                </Link>
                <div v-if="opname.status === 'completed'" class="flex gap-2">
                    <Button variant="outline" class="rounded-xl border-slate-200 bg-white shadow-sm h-10 font-bold gap-2 text-slate-600" @click="reopenOpname">
                        <RotateCcw class="h-4 w-4" />
                        Edit Kembali
                    </Button>
                    <Button variant="destructive" class="rounded-xl h-10 px-6 font-bold shadow-lg shadow-rose-100 gap-2" @click="cancelOpname">
                        <Trash2 class="h-4 w-4" />
                        Batalkan Hasil
                    </Button>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Layout -->
        <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- LEFT: Primary Content (General Info & Comparison Table) -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                
                <!-- General Info Card -->
                <Card class="border-none rounded-2xl shadow-sm bg-white overflow-hidden p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="flex items-start gap-3">
                            <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 shrink-0">
                                <Calendar class="h-5 w-5 text-slate-400" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">Tanggal Audit</span>
                                <p class="text-[14px] font-bold text-slate-700 mt-1 tabular-nums">{{ formatDate(opname.tanggal) }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 md:col-span-2">
                            <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 shrink-0">
                                <FileText class="h-5 w-5 text-slate-400" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 leading-none">Keterangan / Catatan</span>
                                <p class="text-[14px] font-medium text-slate-600 mt-1 italic">{{ opname.keterangan || 'Tidak ada catatan khusus.' }}</p>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Comparison Table -->
                <Card class="border-none rounded-2xl shadow-sm bg-white overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center">
                                <PackageOpen class="h-4 w-4 text-primary" />
                            </div>
                            <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">Log Perbandingan Stok</h3>
                        </div>
                        <Badge variant="outline" class="text-[10px] font-bold border-slate-200 text-slate-400 rounded-lg">{{ opname.items?.length }} Items</Badge>
                    </div>

                    <div class="p-0 overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent border-slate-50">
                                    <TableHead class="pl-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Nama Barang</TableHead>
                                    <TableHead class="text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Data Sistem</TableHead>
                                    <TableHead class="text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Data Fisik</TableHead>
                                    <TableHead class="pr-8 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Varians</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in opname.items" :key="item.id" class="border-slate-50 hover:bg-slate-50/50 transition-colors group">
                                    <TableCell class="pl-8 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100 group-hover:bg-white group-hover:scale-110 transition-all">
                                                <PackageOpen class="h-4 w-4 text-slate-400 group-hover:text-primary" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-700 text-[13px] leading-tight">{{ item.produk?.nama }}</span>
                                                <span class="text-[10px] font-mono font-bold text-slate-300 uppercase tracking-tighter">{{ item.produk?.sku }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums text-slate-400 font-medium italic">
                                        {{ parseFloat(item.system_qty) }} <span class="text-[10px] uppercase font-black opacity-60">{{ item.satuan?.simbol || 'PCS' }}</span>
                                    </TableCell>
                                    <TableCell class="text-right font-black text-slate-700 tabular-nums">
                                        {{ parseFloat(item.physical_qty) }} <span class="text-[10px] uppercase font-black text-slate-400">{{ item.satuan?.simbol || 'PCS' }}</span>
                                    </TableCell>
                                    <TableCell class="text-right pr-8">
                                        <div :class="cn(
                                            'text-[14px] font-black tabular-nums italic',
                                            parseFloat(item.physical_qty) - parseFloat(item.system_qty) === 0 
                                                ? 'text-slate-300' 
                                                : (parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0 ? 'text-emerald-600' : 'text-rose-600')
                                        )">
                                            <span v-if="parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0">+</span>
                                            {{ (parseFloat(item.physical_qty) - parseFloat(item.system_qty)).toLocaleString('id-ID') }}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>

            <!-- RIGHT: Side Information & Audit Trail -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                
                <!-- Status & Audit Trail Card -->
                <Card class="border-none rounded-2xl shadow-sm bg-white p-6 flex flex-col gap-8 relative overflow-hidden group">
                    <!-- Final Seal Badge (Decorative) -->
                    <div v-if="opname.status === 'completed'" class="absolute -right-6 -top-6 h-32 w-32 bg-emerald-50 rounded-full border border-emerald-100 flex items-center justify-center rotate-12 transition-transform group-hover:scale-110">
                         <BadgeCheck class="h-16 w-16 text-emerald-200 mt-4 mr-4" />
                    </div>

                    <div class="flex items-center gap-3 relative z-10">
                        <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center border border-slate-100 shrink-0">
                            <History class="h-5 w-5 text-slate-400" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-[15px]">Audit Trail</h3>
                            <p class="text-xs text-muted-foreground font-medium">Jejak waktu & status sesi.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6 relative z-10">
                        <!-- Step: Created -->
                        <div class="flex gap-4 relative">
                            <div class="absolute left-[15px] top-8 bottom-0 w-0.5 bg-slate-100"></div>
                            <div class="h-8 w-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center shrink-0">
                                <FileInput class="h-4 w-4 text-slate-400" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[12px] font-black uppercase tracking-widest text-slate-400 leading-none">Draft Dibuat</span>
                                <span class="text-[13px] font-bold text-slate-600 mt-1.5">{{ formatDateTime(opname.created_at) }}</span>
                            </div>
                        </div>

                        <!-- Step: Storno/Completed -->
                        <div v-if="opname.status !== 'draft'" class="flex gap-4">
                            <div :class="cn('h-8 w-8 rounded-full flex items-center justify-center shrink-0 border shadow-sm', 
                                opname.status === 'storno' ? 'bg-rose-100 border-rose-200 text-rose-600' : 'bg-emerald-100 border-emerald-200 text-emerald-600')">
                                <BadgeCheck v-if="opname.status === 'completed'" class="h-4 w-4" />
                                <AlertTriangle v-else class="h-4 w-4" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[12px] font-black uppercase tracking-widest text-slate-400 leading-none">
                                    {{ opname.status === 'storno' ? 'Audit Dibatalkan' : 'Audit Disahkan' }}
                                </span>
                                <span class="text-[13px] font-bold text-slate-800 mt-1.5">
                                    {{ opname.status === 'storno' ? formatDateTime(opname.storno_at) : formatDateTime(opname.updated_at) }}
                                </span>
                                <p v-if="opname.status === 'storno'" class="text-[10px] mt-1 text-rose-500 font-bold italic leading-tight">Alasan: {{ opname.storno_reason || 'Dibatalkan oleh sistem/admin' }}</p>
                            </div>
                        </div>

                        <!-- Step: Active Progress (Only if Draft) -->
                        <div v-if="opname.status === 'draft'" class="flex gap-4">
                            <div class="h-8 w-8 rounded-full bg-amber-50 border border-amber-200 flex items-center justify-center shrink-0 text-amber-500 animate-pulse">
                                <Clock class="h-4 w-4" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[12px] font-black uppercase tracking-widest text-amber-600 leading-none">Sedang Diaudit</span>
                                <span class="text-[13px] font-bold text-slate-400 mt-1.5 italic">Menunggu finalisasi input fisiknya...</span>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Helpful Warning -->
                <div v-if="opname.status === 'draft'" class="p-6 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col gap-4 opacity-60 grayscale hover:grayscale-0 hover:opacity-100 transition-all cursor-default group">
                    <div class="flex items-center gap-2">
                        <AlertTriangle class="h-4 w-4 text-amber-500" />
                        <span class="text-[11px] font-black uppercase tracking-widest text-slate-500">Peringatan Audit</span>
                    </div>
                    <p class="text-[11px] leading-relaxed text-slate-500 font-medium">Data ini masih berupa **Draft**. Penyesuaian stok sistem belum dilakukan sampai Anda menekan tombol **Selesaikan Opname** di formulir edit.</p>
                </div>
            </div>
        </div>
    </div>
</AppLayout>
</template>
