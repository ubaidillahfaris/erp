<script setup lang="ts">
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { 
    Calendar, User, CreditCard, FileText, 
    ArrowLeft, Ban, CheckCircle2, AlertCircle,
    Package, Info, Loader2, History as HistoryIcon
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    sale: any;
    payable: any | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Riwayat Penjualan', href: '/sales' },
    { title: 'Detail Transaksi', href: `/sales/${props.sale.id}` },
];

const page = usePage();
const canVoid = computed(() => (page.props.permissions as string[]).includes('void sales'));

const isVoidDialogOpen = ref(false);
const voidForm = useForm({
    reason: '',
});

const handleVoid = () => {
    voidForm.post(`/sales/${props.sale.id}/void`, {
        onSuccess: () => {
            isVoidDialogOpen.value = false;
            toast.success('Transaksi berhasil dibatalkan');
        },
        onError: (errors) => {
            if (errors.period_lock) {
                toast.error('Cannot void: accounting period is closed');
            } else if (errors.reason) {
                toast.error(errors.reason);
            } else {
                toast.error(Object.values(errors)[0] as string || 'Gagal membatalkan transaksi');
            }
        }
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString: string, includeTime = false) => {
    if (!dateString) return '--';
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
<Head :title="`Detail Transaksi - ${sale.invoice_number}`" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <PageHeader 
        :title="`Detail Transaksi #${sale.invoice_number}`" 
        :description="`Dibuat pada ${formatDate(sale.date)}` || 'Penjualan Selesai'"
        back-href="/sales"
    >
        <template #actions>
            <div class="flex items-center gap-2">
                <Badge 
                    v-if="sale.status === 'completed'" 
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <CheckCircle2 class="h-3.5 w-3.5 mr-1.5" /> Transaksi Selesai
                </Badge>
                <Badge 
                    v-else-if="sale.status === 'voided'" 
                    class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <Ban class="h-3.5 w-3.5 mr-1.5" /> Transaksi Dibatalkan
                </Badge>
                <Badge 
                    v-else 
                    class="bg-amber-50 text-amber-600 border-amber-100 hover:bg-amber-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <AlertCircle class="h-3.5 w-3.5 mr-1.5" /> {{ sale.status }}
                </Badge>

                <Dialog v-if="sale.status === 'completed' && canVoid" v-model:open="isVoidDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" class="h-8 text-xs font-bold uppercase tracking-widest text-destructive hover:bg-destructive/5 hover:text-destructive border-destructive/20">
                            <Ban class="h-3 w-3 mr-2" /> Void Sale
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <form @submit.prevent="handleVoid">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2 text-destructive">
                                    <AlertCircle class="h-5 w-5" />
                                    Void Sale: {{ sale.invoice_number }}
                                </DialogTitle>
                                <DialogDescription class="space-y-3 pt-2">
                                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                        <div class="flex justify-between text-xs mb-1">
                                            <span class="text-muted-foreground uppercase font-bold tracking-tight">Invoice Number</span>
                                            <span class="font-mono font-bold">#{{ sale.invoice_number }}</span>
                                        </div>
                                        <div class="flex justify-between text-xs">
                                            <span class="text-muted-foreground uppercase font-bold tracking-tight">Total Amount</span>
                                            <span class="font-bold">{{ formatCurrency(sale.total_amount) }}</span>
                                        </div>
                                    </div>
                                    <p class="text-sm font-bold text-rose-600">
                                        This action will reverse all journal entries and stock movements. This cannot be undone.
                                    </p>
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-4 py-6">
                                <div class="space-y-2">
                                    <Label for="reason" class="text-xs font-bold uppercase tracking-widest text-muted-foreground ml-1">Void Reason</Label>
                                    <Textarea 
                                        id="reason" 
                                        v-model="voidForm.reason" 
                                        placeholder="Enter reason for voiding this sale..." 
                                        class="min-h-[100px] resize-none border-slate-200 focus:border-destructive/30 focus:ring-destructive/10"
                                        required
                                    />
                                    <p v-if="voidForm.errors.reason" class="text-xs text-destructive font-medium ml-1">{{ voidForm.errors.reason }}</p>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" @click="isVoidDialogOpen = false" class="text-xs font-bold uppercase tracking-widest">
                                    Back
                                </Button>
                                <Button 
                                    type="submit" 
                                    variant="destructive"
                                    :disabled="voidForm.processing || voidForm.reason.length < 5"
                                    class="text-xs font-bold uppercase tracking-widest px-6"
                                >
                                    <Loader2 v-if="voidForm.processing" class="h-3 w-3 mr-2 animate-spin" />
                                    Confirm Void
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </template>
    </PageHeader>

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
        
        <!-- Void Alert Banner -->
        <div v-if="sale.status === 'voided'" class="bg-rose-50 border border-rose-200 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600">
                    <Info class="h-5 w-5" />
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-bold text-rose-900 uppercase tracking-tight">Informasi Pembatalan (Storno)</p>
                    <p class="text-[13px] text-rose-700 leading-relaxed font-medium">"{{ sale.storno_reason }}"</p>
                </div>
            </div>
            <div class="bg-white/50 border border-rose-200 px-4 py-2 rounded-xl flex items-center gap-2">
                <HistoryIcon class="h-3.5 w-3.5 text-rose-400" />
                <span class="text-[11px] font-bold text-rose-600 uppercase tracking-widest">Dibatalkan pada {{ formatDate(sale.storno_at, true) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left Info Panel -->
            <div class="lg:col-span-1 flex flex-col gap-6 translate-y-0 opacity-100 animate-in fade-in slide-in-from-left-4 duration-700">
                <!-- Transaction Info Card -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <FileText class="h-3.5 w-3.5 text-accent" /> Detail Transaksi
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">No. Invoice</label>
                            <p class="text-sm font-bold text-foreground font-mono">#{{ sale.invoice_number }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Waktu Penjualan</label>
                            <p class="text-sm font-bold text-foreground">{{ formatDate(sale.date) }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Metode Pembayaran</label>
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-4 w-4 text-accent" />
                                <span class="text-sm font-bold text-foreground uppercase tracking-tight">{{ sale.payment_method }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Card -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <User class="h-3.5 w-3.5 text-accent" /> Pelanggan
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="sale.sale_customer" class="flex flex-col gap-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-accent/5 flex items-center justify-center border border-accent/10">
                                    <User class="h-5 w-5 text-accent" />
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-sm font-bold text-foreground">{{ sale.sale_customer.customer.name }}</p>
                                    <p class="text-[11px] font-medium text-muted-foreground uppercase tracking-tight">{{ sale.sale_customer.customer.type?.name || 'Reguler' }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200">
                            <User class="h-5 w-5 text-slate-300" />
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 italic">Antonian / Walk-in</p>
                        </div>
                    </div>
                </div>

                <!-- Notes Card -->
                <div v-if="sale.notes" class="bg-white border border-slate-200 rounded-2xl p-6">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground block mb-2">Catatan Tambahan</label>
                    <p class="text-xs text-muted-foreground leading-relaxed font-medium italic">"{{ sale.notes }}"</p>
                </div>

                <!-- Status Piutang Card -->
                <div v-if="sale.payment_method === 'credit' && payable" class="bg-white border border-slate-200 rounded-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-700">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <CreditCard class="h-3.5 w-3.5 text-accent" /> Status Piutang
                        </h3>
                        <Badge 
                            v-if="payable.status === 'paid'"
                            class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-bold px-1.5 h-5 line-height-none"
                        >
                            Lunas
                        </Badge>
                        <Badge 
                            v-else-if="payable.status === 'partial'"
                            class="bg-amber-50 text-amber-600 border-amber-100 hover:bg-amber-50 text-[10px] uppercase font-bold px-1.5 h-5"
                        >
                            Cicilan
                        </Badge>
                        <Badge 
                            v-else
                            class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[10px] uppercase font-bold px-1.5 h-5"
                        >
                            Belum Bayar
                        </Badge>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground font-medium uppercase tracking-tighter">Total Tagihan</span>
                            <span class="font-bold text-foreground">{{ formatCurrency(payable.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground font-medium uppercase tracking-tighter">Terbayar</span>
                            <span class="font-bold text-emerald-600 leading-none">{{ formatCurrency(payable.paid_amount || (payable.total_amount - payable.remaining_amount)) }}</span>
                        </div>
                        <div class="h-px bg-slate-100"></div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground font-medium uppercase tracking-tighter">Sisa Tagihan</span>
                            <span class="font-bold" :class="payable.remaining_amount > 0 ? 'text-rose-600 text-lg' : 'text-foreground'">
                                {{ formatCurrency(payable.remaining_amount) }}
                            </span>
                        </div>
                        
                        <Button variant="outline" as-child class="w-full mt-2 h-9 text-xs font-bold uppercase tracking-widest border-slate-200 hover:bg-slate-50">
                            <Link :href="`/payables/${payable.id}`">
                                Lihat Detail Piutang
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Right Items Panel -->
            <div class="lg:col-span-2 flex flex-col gap-6 animate-in fade-in slide-in-from-right-4 duration-700">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-none">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <Package class="h-3.5 w-3.5 text-accent" /> Item Belanja
                        </h3>
                        <Badge variant="outline" class="text-[10px] font-mono font-bold">{{ sale.items?.length }} Lines</Badge>
                    </div>
                    
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6 w-full">Product</TableHead>
                                <TableHead class="text-center px-4">Qty</TableHead>
                                <TableHead class="text-right px-4">Price</TableHead>
                                <TableHead class="text-right pr-6">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in sale.items" :key="item.id">
                                <TableCell class="pl-6 py-4">
                                    <div class="flex flex-col gap-0.5 min-w-0">
                                        <p class="text-sm font-bold text-foreground truncate">{{ item.product.name }}</p>
                                        <p class="text-[10px] text-muted-foreground uppercase tracking-tighter font-mono">#{{ item.product.sku || '--' }}</p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center font-bold text-foreground text-[13px]">
                                    {{ item.qty }} <span class="text-[10px] font-normal text-muted-foreground uppercase ml-0.5">{{ item.unit?.symbol || 'PCS' }}</span>
                                </TableCell>
                                <TableCell class="text-right text-[13px] font-medium tabular-nums">{{ formatCurrency(item.price) }}</TableCell>
                                <TableCell class="text-right pr-6 font-bold tabular-nums text-foreground">{{ formatCurrency(item.subtotal) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="p-8 bg-slate-50/50 border-t border-slate-100 flex flex-col gap-3">
                        <div class="flex justify-between items-center text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-widest opacity-60">Subtotal Belanja</span>
                            <span class="text-sm font-bold tabular-nums">{{ formatCurrency(sale.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-muted-foreground">
                            <span class="text-xs font-bold uppercase tracking-widest opacity-60">Potongan / Pajak</span>
                            <span class="text-sm font-bold tabular-nums">{{ formatCurrency(0) }}</span>
                        </div>
                        <div class="h-px bg-slate-200 mt-2 mb-1"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold uppercase tracking-[0.2em] text-foreground">Total Penjualan</span>
                            <span class="text-3xl font-black text-accent tabular-nums tracking-tighter">
                                {{ formatCurrency(sale.total_amount) }}
                            </span>
                        </div>
                        
                        <div v-if="sale.payment_method === 'cash'" class="flex justify-between items-center mt-2 px-4 py-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 opacity-70">Uang Diterima</span>
                                <span class="text-sm font-bold text-emerald-700 tabular-nums">{{ formatCurrency(sale.received_amount) }}</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 opacity-70">Kembalian</span>
                                <span class="text-sm font-bold text-emerald-700 tabular-nums">{{ formatCurrency(sale.change_amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment History Card -->
                <div v-if="payable?.payments && payable.payments.length > 0" class="bg-white border border-slate-200 rounded-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-1000">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <HistoryIcon class="h-3.5 w-3.5 text-accent" /> Riwayat Pembayaran
                        </h3>
                    </div>
                    
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6 py-4">Date</TableHead>
                                <TableHead class="px-4">Metode</TableHead>
                                <TableHead class="text-right px-4">Quantity</TableHead>
                                <TableHead class="text-right pr-6">Dicatat Oleh</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payment in payable.payments" :key="payment.id" class="border-slate-100 hover:bg-slate-50/40">
                                <TableCell class="pl-6 py-4 text-sm font-medium">
                                    {{ formatDate(payment.payment_date || payment.created_at, true) }}
                                </TableCell>
                                <TableCell class="px-4">
                                    <Badge variant="outline" class="text-[10px] font-bold uppercase tracking-tighter px-2 h-5 bg-slate-50 border-slate-200">
                                        {{ payment.payment_method || 'Transfer' }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right px-4 font-bold text-emerald-600 tabular-nums">
                                    {{ formatCurrency(payment.amount) }}
                                </TableCell>
                                <TableCell class="text-right pr-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center">
                                            <User class="h-3 w-3 text-slate-400" />
                                        </div>
                                        <span class="text-xs font-semibold text-slate-700 whitespace-nowrap">{{ payment.created_by?.name || 'System' }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
