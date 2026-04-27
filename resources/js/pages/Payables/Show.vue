<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import {
    Calendar, User, CreditCard, FileText,
    ArrowLeft, Ban, CheckCircle2, AlertCircle,
    Package, Info, Loader2, History as HistoryIcon,
    Wallet, Landmark, Receipt, PiggyBank,
    ArrowRightLeft, AlertTriangle, Clock,
    Link2, UserCircle, Calculator
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
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputCurrency from '@/components/ui/input/InputCurrency.vue';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    payable: any;
    party: any;
    reference: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Hutang & Piutang', href: '/payables' },
    { title: 'Detail Catatan', href: `/payables/${props.payable.id}` },
];

const isPaymentDialogOpen = ref(false);
const paymentForm = useForm({
    amount: props.payable.remaining_amount,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: '',
});

const submitPayment = () => {
    if (paymentForm.amount > props.payable.remaining_amount) {
        toast.error('Jumlah pembayaran tidak boleh melebihi sisa tagihan');
        return;
    }

    paymentForm.post(`/payables/${props.payable.id}/payments`, {
        onSuccess: () => {
            isPaymentDialogOpen.value = false;
            paymentForm.reset();
            toast.success('Pembayaran berhasil dicatat');
        },
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

const getStatusConfig = (status: string) => {
    switch (status) {
        case 'open':
            return { label: 'Open', class: 'bg-blue-50 text-blue-600 border-blue-100', icon: Clock };
        case 'partial':
            return { label: 'Parsial', class: 'bg-amber-50 text-amber-600 border-amber-100', icon: Clock };
        case 'paid':
            return { label: 'Lunas', class: 'bg-emerald-50 text-emerald-600 border-emerald-100', icon: CheckCircle2 };
        case 'overdue':
            return { label: 'Overdue', class: 'bg-rose-50 text-rose-600 border-rose-100', icon: AlertTriangle };
        default:
            return { label: status, class: 'bg-slate-50 text-slate-600 border-slate-100', icon: Info };
    }
};

const statusConfig = computed(() => getStatusConfig(props.payable.status));
const typeLabel = computed(() => props.payable.type === 'payable' ? 'Hutang' : 'Piutang');
const typeColor = computed(() => props.payable.type === 'payable' ? 'text-rose-600' : 'text-emerald-600');
</script>

<template>
<Head :title="`Detail ${typeLabel} - ${payable.reference_type}#${payable.reference_id}`" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader :title="`Detail ${typeLabel}`"
        :description="`Mencatat kewajiban finansial dari ${payable.reference_type}`" back-href="/payables">
        <template #actions>
            <div class="flex items-center gap-2">
                <Badge :class="[statusConfig.class, 'text-[11px] uppercase font-black px-3 h-8 border shadow-sm']">
                    <component :is="statusConfig.icon" class="h-3.5 w-3.5 mr-1.5" />
                    {{ statusConfig.label }}
                </Badge>

                <Dialog v-if="payable.status !== 'paid'" v-model:open="isPaymentDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="h-8 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90">
                            <Receipt class="h-3 w-3 mr-2" /> Catat Pembayaran
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-[425px]">
                        <form @submit.prevent="submitPayment">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2 text-foreground">
                                    <Receipt class="h-5 w-5 text-accent" />
                                    Catat Pembayaran
                                </DialogTitle>
                                <DialogDescription>
                                    Masukkan rincian pembayaran untuk tagihan ini.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="grid gap-5 py-6">
                                <div class="space-y-2">
                                    <Label
                                        class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Jumlah
                                        Pembayaran</Label>
                                    <InputCurrency v-model="paymentForm.amount"
                                        :placeholder="formatCurrency(payable.remaining_amount)" />
                                    <p v-if="paymentForm.errors.amount"
                                        class="text-xs text-destructive font-medium ml-1">{{ paymentForm.errors.amount
                                        }}</p>
                                    <p class="text-[10px] text-muted-foreground ml-1">Sisa tagihan: {{
                                        formatCurrency(payable.remaining_amount) }}</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label
                                            class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Date</Label>
                                        <Input type="date" v-model="paymentForm.payment_date"
                                            class="rounded-xl border-slate-200" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label
                                            class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Metode</Label>
                                        <Select v-model="paymentForm.payment_method">
                                            <SelectTrigger class="rounded-xl border-slate-200">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="cash">Tunai</SelectItem>
                                                <SelectItem value="transfer">Transfer</SelectItem>
                                                <SelectItem value="qris">QRIS</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label
                                        class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Catatan</Label>
                                    <Textarea v-model="paymentForm.notes" placeholder="Keterangan tambahan..."
                                        class="min-h-[80px] resize-none border-slate-200 rounded-xl" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" @click="isPaymentDialogOpen = false"
                                    class="text-xs font-bold uppercase tracking-widest">
                                    Batal
                                </Button>
                                <Button type="submit"
                                    :disabled="paymentForm.processing || !paymentForm.amount || paymentForm.amount <= 0"
                                    class="text-xs font-bold uppercase tracking-widest px-6 bg-accent">
                                    <Loader2 v-if="paymentForm.processing" class="h-3 w-3 mr-2 animate-spin" />
                                    Simpan Pembayaran
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Left Info Panel -->
            <div class="lg:col-span-1 flex flex-col gap-6 animate-in fade-in slide-in-from-left-4 duration-700">

                <!-- Financial Summary Card -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                        <h3
                            class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <Calculator class="h-3.5 w-3.5 text-accent" /> Ringkasan Finansial
                        </h3>
                        <Badge variant="outline" :class="['text-[10px] uppercase font-black', typeColor]">
                            {{ typeLabel }}
                        </Badge>
                    </div>
                    <div class="p-6 flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <div
                                class="flex justify-between items-center text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-60">
                                <span>Pokok</span>
                                <span>{{ formatCurrency(payable.principal_amount) }}</span>
                            </div>
                            <div
                                class="flex justify-between items-center text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-60">
                                <span>Total Bunga</span>
                                <span class="text-rose-500">{{ formatCurrency(payable.total_interest) }}</span>
                            </div>
                            <div class="h-px bg-slate-100 my-1"></div>
                            <div
                                class="flex justify-between items-center text-sm font-black text-foreground uppercase tracking-tight">
                                <span>Total Tagihan</span>
                                <span>{{ formatCurrency(payable.total_amount) }}</span>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Terbayar</span>
                                <span class="text-sm font-bold text-emerald-600 tabular-nums">{{
                                    formatCurrency(props.payable.paid_amount) }}</span>
                            </div>
                            <div class="h-1 w-full bg-white rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000"
                                    :style="{ width: `${(props.payable.paid_amount / props.payable.total_amount) * 100}%` }">
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Sisa
                                    Tagihan</span>
                                <span
                                    :class="['text-lg font-black tabular-nums', props.payable.remaining_amount > 0 ? 'text-rose-600' : 'text-slate-400']">
                                    {{ formatCurrency(props.payable.remaining_amount) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Jatuh
                                Tempo</label>
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-accent" />
                                <span
                                    :class="['text-sm font-bold uppercase tracking-tight', payable.status === 'overdue' ? 'text-rose-600' : 'text-foreground']">
                                    {{ formatDate(payable.due_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reference & Party Card -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3
                            class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <Link2 class="h-3.5 w-3.5 text-accent" /> Kaitan Transaksi
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Reference -->
                        <div class="flex flex-col gap-3">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Dokumen
                                Referensi</label>
                            <div class="flex items-center gap-3 bg-slate-50 p-3 rounded-xl border border-slate-100">
                                <div
                                    class="h-8 w-8 rounded-lg bg-white flex items-center justify-center border border-slate-200 shadow-sm text-accent">
                                    <FileText class="h-4 w-4" />
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-xs font-bold text-foreground uppercase">{{ payable.reference_type }}
                                    </p>
                                    <p class="text-[11px] font-mono font-medium text-muted-foreground">#{{
                                        reference?.invoice_number || reference?.sku || payable.reference_id }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Party -->
                        <div class="flex flex-col gap-3">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Pihak
                                Terkait ({{ payable.party_type }})</label>
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-10 w-10 rounded-full bg-accent/5 flex items-center justify-center border border-accent/10">
                                    <UserCircle class="h-6 w-6 text-accent" />
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-sm font-bold text-foreground">{{ party?.name || party?.name ||
                                        'External'
                                        }}</p>
                                    <p class="text-[11px] font-medium text-muted-foreground uppercase tracking-widest">
                                        {{
                                        payable.party_type }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Card -->
                <div v-if="payable.notes" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <label
                        class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground block mb-2">Catatan</label>
                    <p class="text-xs text-muted-foreground leading-relaxed font-medium italic">"{{ payable.notes }}"
                    </p>
                </div>
            </div>

            <!-- Right Tables Panel -->
            <div class="lg:col-span-2 flex flex-col gap-6 animate-in fade-in slide-in-from-right-4 duration-700">

                <!-- Payments History -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                        <h3
                            class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <HistoryIcon class="h-3.5 w-3.5 text-accent" /> Riwayat Pembayaran
                        </h3>
                        <Badge variant="outline" class="text-[10px] font-mono font-bold">{{ payable.payments?.length ||
                            0 }}
                            Bayar</Badge>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6">Date</TableHead>
                                <TableHead>Metode</TableHead>
                                <TableHead class="text-right">Quantity</TableHead>
                                <TableHead>Dicatat Oleh</TableHead>
                                <TableHead class="pr-6">Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="payment in payable.payments" :key="payment.id"
                                class="group transition-colors hover:bg-slate-50/50">
                                <TableCell class="pl-6 py-4">
                                    <p class="text-xs font-bold text-foreground">{{ formatDate(payment.payment_date) }}
                                    </p>
                                    <p class="text-[10px] text-muted-foreground uppercase tracking-tighter">{{
                                        formatDate(payment.created_at, true).split(' ')[3] || '' }}</p>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary"
                                        class="text-[9px] uppercase font-black tracking-widest h-5">
                                        {{ payment.payment_method }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right font-black text-emerald-600 tabular-nums">
                                    {{ formatCurrency(payment.amount) }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-1.5">
                                        <div
                                            class="h-5 w-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-bold text-slate-500">
                                            {{ payment.createdBy?.name?.[0]?.toUpperCase() || 'S' }}
                                        </div>
                                        <span class="text-[11px] font-medium">{{ payment.createdBy?.name || 'System'
                                            }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="pr-6 max-w-[200px]">
                                    <p class="text-[11px] text-muted-foreground italic truncate" :title="payment.notes">
                                        {{ payment.notes || '-' }}
                                    </p>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="!payable.payments?.length">
                                <TableCell colspan="5" class="h-32 text-center">
                                    <div class="flex flex-col items-center justify-center gap-2 opacity-30">
                                        <HistoryIcon class="h-8 w-8" />
                                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">
                                            Belum
                                            ada pembayaran</p>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Interest Schedules / Installments -->
                <div v-if="payable.interestSchedules?.length"
                    class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                        <h3
                            class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <Clock class="h-3.5 w-3.5 text-accent" /> Jadwal Cicilan & Bunga
                        </h3>
                        <Badge variant="outline" class="text-[10px] font-mono font-bold">{{
                            payable.interestSchedules.length
                            }} Periode</Badge>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6 w-16">Prd</TableHead>
                                <TableHead>Jatuh Tempo</TableHead>
                                <TableHead class="text-right">Pokok</TableHead>
                                <TableHead class="text-right">Bunga</TableHead>
                                <TableHead class="text-right">Total Tagihan</TableHead>
                                <TableHead class="text-center pr-6">Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(schedule, index) in payable.interestSchedules" :key="schedule.id"
                                class="group transition-colors hover:bg-slate-50/50">
                                <TableCell class="pl-6 py-4 font-black text-slate-400 text-xs">#{{
                                    schedule.period_number ??
                                    (index as number + 1) }}</TableCell>
                                <TableCell class="font-bold text-xs">{{ formatDate(schedule.due_date) }}</TableCell>
                                <TableCell class="text-right tabular-nums text-xs font-medium">{{
                                    formatCurrency(schedule.principal_portion) }}</TableCell>
                                <TableCell class="text-right tabular-nums text-xs font-medium text-rose-500">{{
                                    formatCurrency(schedule.interest_portion) }}</TableCell>
                                <TableCell class="text-right tabular-nums text-xs font-black text-foreground">{{
                                    formatCurrency(schedule.total_due) }}</TableCell>
                                <TableCell class="text-center pr-6">
                                    <Badge :class="['text-[9px] uppercase font-black px-1.5 h-4',
                                        schedule.status === 'paid' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' :
                                            schedule.status === 'overdue' ? 'bg-rose-50 text-rose-600 border-rose-100' :
                                                'bg-slate-50 text-slate-500 border-slate-100'
                                    ]">
                                        {{ schedule.status }}
                                    </Badge>
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
