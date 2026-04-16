<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, CheckCircle2, History, RotateCcw, AlertTriangle, Edit2, Trash2 } from 'lucide-vue-next';
import { storno, reopen } from '@/actions/App/Http/Controllers/StockOpnameController';
import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/composables/useConfirm';
import { Badge } from '@/components/ui/badge';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
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

const props = defineProps<{
    opname: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
    { title: 'Detail Hasil', href: '#' },
];

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed': return 'default';
        case 'storno': return 'destructive';
        case 'draft': return 'secondary';
        default: return 'outline';
    }
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
</script>

<template>
<Head title="Detail Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <PageHeader 
            title="Detail Stock Opname" 
            description="Hasil perbandingan stok fisik dan stok sistem"
            back-href="/stock-opname"
        >
            <template #actions>
                <div class="flex gap-2">
                    <Link v-if="opname.status === 'draft'" :href="`/stock-opname/${opname.id}/edit`">
                        <Button class="rounded-none">
                            Lanjutkan Draft
                        </Button>
                    </Link>
                    <Button 
                        v-if="opname.status === 'completed'" 
                        variant="outline" 
                        class="rounded-none border-slate-200"
                        @click="reopenOpname"
                    >
                        <Edit2 class="mr-2 h-4 w-4" />
                        Edit Kembali
                    </Button>
                    <Button 
                        v-if="opname.status === 'completed'" 
                        variant="destructive" 
                        class="rounded-none"
                        @click="cancelOpname"
                    >
                        <Trash2 class="mr-2 h-4 w-4" />
                        Hapus Hasil
                    </Button>
                </div>
            </template>
        </PageHeader>

        <!-- Vertical Content Stack -->
        <div class="flex flex-col gap-10">
            <!-- 1. Ringkasan -->
            <section class="space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground">1. Ringkasan</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-muted-foreground uppercase tracking-widest">TANGGAL</div>
                        <div class="text-sm font-medium">{{ formatDate(opname.tanggal) }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-muted-foreground uppercase tracking-widest">STATUS</div>
                        <div>
                            <Badge :variant="getStatusVariant(opname.status)" class="rounded-none text-xs px-2 py-0">
                                {{ opname.status === 'storno' ? 'DIBATALKAN' : opname.status.toUpperCase() }}
                            </Badge>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs font-bold text-muted-foreground uppercase tracking-widest">KETERANGAN</div>
                        <div class="text-sm font-medium">{{ opname.keterangan || '-' }}</div>
                    </div>
                </div>

                <!-- Cancellation Information -->
                <div v-if="opname.status === 'storno'" class="flex items-start gap-4 p-4 bg-rose-50 border border-rose-100 rounded-none mt-4">
                    <AlertTriangle class="h-5 w-5 text-rose-600 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <div class="text-sm font-bold text-rose-900">Hasil Opname Telah Dibatalkan</div>
                        <p class="text-xs text-rose-700 leading-relaxed">
                            Hasil opname ini telah dibatalkan pada <strong>{{ formatDateTime(opname.storno_at) }}</strong>. 
                            <span v-if="opname.storno_reason && opname.storno_reason !== 'Dibatalkan oleh pengguna'">Alasan: {{ opname.storno_reason }}</span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- 2. Daftar Perbandingan Stok -->
            <section class="space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground">2. Daftar Perbandingan Stok</h3>
                <div class="overflow-x-auto">
                    <Table class="rounded-none border-none">
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-b border-muted">
                                <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground">Barang</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Stok Sistem</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Stok Fisik</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground">Selisih</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in opname.items" :key="item.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                                <TableCell class="px-0 py-4">
                                    <div class="font-bold text-sm tracking-tight capitalize">{{ item.produk?.nama }}</div>
                                    <div class="text-xs font-mono text-muted-foreground uppercase tracking-tighter mt-0.5">{{ item.produk?.sku }}</div>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right text-sm font-medium">
                                    {{ parseFloat(item.system_qty) }} <span class="text-xs text-muted-foreground uppercase">{{ item.satuan?.nama }}</span>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right text-sm font-medium">
                                    {{ parseFloat(item.physical_qty) }} <span class="text-xs text-muted-foreground uppercase">{{ item.satuan?.nama }}</span>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right">
                                    <div 
                                        class="text-sm font-bold"
                                        :class="parseFloat(item.physical_qty) - parseFloat(item.system_qty) === 0 ? 'text-muted-foreground' : (parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0 ? 'text-primary' : 'text-destructive')"
                                    >
                                        <span v-if="parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0">+</span>
                                        {{ parseFloat(item.physical_qty) - parseFloat(item.system_qty) }}
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </section>
        </div>
    </div>
</AppLayout>
</template>
