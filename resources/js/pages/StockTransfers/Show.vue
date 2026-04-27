<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    ArrowRightLeft, Warehouse as WarehouseIcon, Truck, 
    CheckCircle2, XCircle, Clock, ArrowRight,
    ArrowLeft, Printer, Send, PackageCheck, Ban, Loader2,
    Package, Info, AlertTriangle
} from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { 
    Dialog, DialogContent, DialogDescription, 
    DialogFooter, DialogHeader, DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import { useConfirm } from '@/composables/useConfirm';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    transfer: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Persediaan', href: '/stock' },
    { title: 'Transfer Stok', href: '/stock-transfers' },
    { title: props.transfer.transfer_number, href: `/stock-transfers/${props.transfer.id}` },
];

const { confirmDialog } = useConfirm();
const isReceiveDialogOpen = ref(false);
const isCancelDialogOpen = ref(false);
const cancelReason = ref('');

const receiveForm = useForm({
    items: props.transfer.items.map((item: any) => ({
        id: item.id,
        product_name: item.product?.name,
        sku: item.product?.sku,
        quantity_requested: item.quantity_requested,
        quantity_received: item.quantity_requested, // Default to full amount
    })),
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'draft': return { class: 'bg-slate-50 text-slate-500 border-slate-100', icon: Clock, label: 'Draft' };
        case 'in_transit': return { class: 'bg-blue-50 text-blue-600 border-blue-100', icon: Truck, label: 'In Transit' };
        case 'completed': return { class: 'bg-emerald-50 text-emerald-600 border-emerald-100', icon: CheckCircle2, label: 'Completed' };
        case 'cancelled': return { class: 'bg-rose-50 text-rose-600 border-rose-100', icon: XCircle, label: 'Cancelled' };
        default: return { class: 'bg-slate-50 text-slate-500 border-slate-100', icon: Clock, label: status };
    }
};

const formatDate = (date: string) => {
    if (!date) return '--';
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const handleDispatch = async () => {
    const confirmed = await confirmDialog(
        'Kirim Barang Sekarang?',
        'Stok akan dikurangi dari gudang asal dan status akan berubah menjadi Dalam Perjalanan.'
    );

    if (confirmed) {
        router.post(`/stock-transfers/${props.transfer.id}/dispatch`, {}, {
            onSuccess: () => toast.success('Barang berhasil dikirim'),
        });
    }
};

const handleReceive = () => {
    receiveForm.post(`/stock-transfers/${props.transfer.id}/receive`, {
        onSuccess: () => {
            isReceiveDialogOpen.value = false;
            toast.success('Barang berhasil diterima dan stok tujuan diperbarui');
        },
    });
};

const handleCancel = () => {
    if (!cancelReason.value) {
        toast.error('Berikan alasan pembatalan');
        return;
    }

    router.post(`/stock-transfers/${props.transfer.id}/cancel`, { 
        reason: cancelReason.value 
    }, {
        onSuccess: () => {
            isCancelDialogOpen.value = false;
            toast.success('Transfer berhasil dibatalkan');
        },
    });
};
</script>

<template>
    <Head :title="`Transfer ${transfer.transfer_number}`" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <PageHeader 
            :title="transfer.transfer_number" 
            :description="`Dibuat oleh ${transfer.creator?.name || 'System'} pada ${formatDate(transfer.created_at)}`" 
            back-href="/stock-transfers"
        >
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button variant="outline" class="h-9 text-xs font-bold uppercase tracking-widest border-slate-200 bg-white">
                        <Printer class="h-4 w-4 mr-2" /> Cetak
                    </Button>
                    
                    <Button 
                        v-if="transfer.status === 'draft'"
                        @click="handleDispatch" 
                        class="h-9 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white shadow-lg shadow-accent/20"
                    >
                        <Send class="h-4 w-4 mr-2" /> Kirim Barang
                    </Button>
                    
                    <Button 
                        v-if="transfer.status === 'in_transit'"
                        @click="isReceiveDialogOpen = true" 
                        class="h-9 text-xs font-bold uppercase tracking-widest bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-200"
                    >
                        <PackageCheck class="h-4 w-4 mr-2" /> Terima Barang
                    </Button>
                    
                    <Button 
                        v-if="transfer.status === 'draft' || transfer.status === 'in_transit'"
                        @click="isCancelDialogOpen = true" 
                        variant="ghost" 
                        class="h-9 text-xs font-bold uppercase tracking-widest text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                    >
                        <Ban class="h-4 w-4 mr-2" /> Batalkan
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
            
            <!-- Status and Locations Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 animate-in fade-in slide-in-from-top-4 duration-500">
                <div class="md:col-span-1 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col items-center justify-center gap-3 text-center">
                    <div :class="getStatusBadge(transfer.status).class" class="h-12 w-12 rounded-xl flex items-center justify-center border-2">
                        <component :is="getStatusBadge(transfer.status).icon" class="h-6 w-6" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Status Transfer</span>
                        <span class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ getStatusBadge(transfer.status).label }}</span>
                    </div>
                </div>

                <div class="md:col-span-3 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center justify-between px-10">
                    <div class="flex flex-col gap-1.5 flex-1 max-w-[200px]">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                            <WarehouseIcon class="h-3 w-3" /> Gudang Asal
                        </span>
                        <span class="text-base font-bold text-slate-800 truncate">{{ transfer.from_warehouse?.name }}</span>
                        <code class="text-[10px] font-mono font-bold bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded w-fit uppercase">
                            {{ transfer.from_warehouse?.code }}
                        </code>
                    </div>

                    <div class="px-8 flex flex-col items-center gap-1">
                        <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-300 border border-slate-100">
                            <ArrowRight class="h-5 w-5" />
                        </div>
                        <span v-if="transfer.transferred_at" class="text-[9px] font-bold text-blue-500 uppercase tracking-tighter">
                            {{ formatDate(transfer.transferred_at) }}
                        </span>
                    </div>

                    <div class="flex flex-col gap-1.5 flex-1 max-w-[200px] items-end text-right">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                            Gudang Tujuan <WarehouseIcon class="h-3 w-3" />
                        </span>
                        <span class="text-base font-bold text-slate-800 truncate">{{ transfer.to_warehouse?.name }}</span>
                        <code class="text-[10px] font-mono font-bold bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded w-fit uppercase">
                            {{ transfer.to_warehouse?.code }}
                        </code>
                    </div>
                </div>
            </div>

            <!-- Notes Banner if exists -->
            <div v-if="transfer.notes" class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-start gap-4 animate-in fade-in duration-700">
                <div class="h-8 w-8 shrink-0 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                    <Info class="h-4 w-4" />
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-blue-800">Catatan Transfer</span>
                    <p class="text-sm text-blue-700 leading-relaxed font-medium whitespace-pre-wrap">{{ transfer.notes }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-none animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                        <Package class="h-3.5 w-3.5 text-accent" /> Daftar Barang
                    </h3>
                    <Badge variant="outline" class="text-[10px] font-mono font-bold">{{ transfer.items.length }} Items</Badge>
                </div>
                
                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50/30">
                            <TableHead class="pl-6">Nama Produk</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead class="text-right">Diminta</TableHead>
                            <TableHead class="text-right">Diterima</TableHead>
                            <TableHead class="text-center pr-6">Satuan</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="item in transfer.items" :key="item.id" class="group transition-colors hover:bg-slate-50/50">
                            <TableCell class="pl-6 py-4">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-accent transition-colors">{{ item.product?.name }}</span>
                            </TableCell>
                            <TableCell>
                                <code class="text-[10px] font-mono font-bold text-slate-400">{{ item.product?.sku }}</code>
                            </TableCell>
                            <TableCell class="text-right">
                                <span class="text-sm font-bold text-slate-600">{{ Number(item.quantity_requested).toLocaleString('id-ID') }}</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <span v-if="transfer.status === 'completed'" class="text-sm font-bold text-emerald-600">{{ Number(item.quantity_received).toLocaleString('id-ID') }}</span>
                                <span v-else class="text-sm font-medium text-slate-300">--</span>
                            </TableCell>
                            <TableCell class="text-center pr-6">
                                <Badge variant="outline" class="text-[10px] font-bold uppercase tracking-tight h-5 px-1.5">{{ item.unit?.name }}</Badge>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- Receive Dialog -->
        <Dialog v-model:open="isReceiveDialogOpen">
            <DialogContent class="sm:max-w-[500px]">
                <form @submit.prevent="handleReceive">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <PackageCheck class="h-5 w-5 text-emerald-600" />
                            Konfirmasi Penerimaan Barang
                        </DialogTitle>
                        <DialogDescription>
                            Verifikasi jumlah barang yang diterima secara fisik.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="py-6 max-h-[400px] overflow-y-auto px-1">
                        <div class="flex flex-col gap-4">
                            <div v-for="(item, index) in receiveForm.items" :key="item.id" class="p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs font-bold text-slate-700">{{ item.product_name }}</span>
                                    <span class="text-[9px] font-mono text-slate-400">Req: {{ Number(item.quantity_requested).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="w-32">
                                    <div class="relative">
                                        <Input v-model="item.quantity_received" type="number" step="0.0001" class="h-9 text-right pr-12 border-slate-200 text-xs font-bold" />
                                        <span class="absolute right-3 top-2.5 text-[9px] font-bold text-slate-400 uppercase">QTY</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="isReceiveDialogOpen = false" class="h-10 text-xs font-bold uppercase tracking-widest">
                            Batal
                        </Button>
                        <Button 
                            type="submit" 
                            class="h-10 bg-emerald-600 text-white text-xs font-bold uppercase tracking-widest px-8"
                            :disabled="receiveForm.processing"
                        >
                            <Loader2 v-if="receiveForm.processing" class="h-3 w-3 mr-2 animate-spin" />
                            Konfirmasi Penerimaan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Cancel Dialog -->
        <Dialog v-model:open="isCancelDialogOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-rose-600">
                        <AlertTriangle class="h-5 w-5" />
                        Batalkan Transfer
                    </DialogTitle>
                    <DialogDescription>
                        Berikan alasan pembatalan. Jika barang sudah dikirim, stok akan dikembalikan ke gudang asal.
                    </DialogDescription>
                </DialogHeader>
                
                <div class="py-4">
                    <Label class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2 block">Alasan Pembatalan</Label>
                    <Input v-model="cancelReason" placeholder="Contoh: Kesalahan input produk..." class="h-10 border-slate-200" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="ghost" @click="isCancelDialogOpen = false" class="h-10 text-xs font-bold uppercase tracking-widest">
                        Batal
                    </Button>
                    <Button 
                        @click="handleCancel"
                        class="h-10 bg-rose-600 text-white text-xs font-bold uppercase tracking-widest px-8"
                    >
                        Batalkan Transfer
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
