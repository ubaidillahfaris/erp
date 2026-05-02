<script setup lang="ts">
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import * as serviceOrdersRoutes from '@/routes/service-orders';
import { 
    Calendar, User, CreditCard, FileText, 
    ArrowLeft, Ban, CheckCircle2, AlertCircle,
    Package, Info, Loader2, History as HistoryIcon,
    Clock, Play, CheckCircle, ChevronRight, Layers,
    Receipt, DollarSign
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

interface Step {
    id: number;
    name: string;
    code: string;
    is_final: boolean;
}

interface OrderItem {
    id: number;
    service_type: {
        name: string;
        code: string;
    };
    pricing_basis: string;
    unit_name: string;
    unit_price: number;
    qty: number;
    subtotal: number;
}

interface Order {
    id: number;
    order_number: string;
    created_at: string;
    total_amount: number;
    received_amount: number;
    payment_method: string;
    notes: string | null;
    status: string;
    production_step_id: number | null;
    production_step: Step | null;
    customer: any | null;
    service: any;
    items: OrderItem[];
}

const props = defineProps<{
    order: Order;
    next_steps: Step[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Riwayat Servis', href: '/service-orders' },
    { title: `Order #${props.order.order_number}`, href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0
    }).format((value || 0) / 100);
};

const formatDate = (dateString: string, includeTime = true) => {
    if (!dateString) return '--';
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit', month: 'long', year: 'numeric'
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return new Date(dateString).toLocaleDateString('id-ID', options);
};

const updateStep = (stepId: number) => {
    router.patch(serviceOrdersRoutes.updateStep.url(props.order.id), {
        production_step_id: stepId
    }, {
        onSuccess: () => toast.success('Progress servis diperbarui')
    });
};

const isVoidDialogOpen = ref(false);
const voidForm = useForm({ reason: '' });

const handleVoid = () => {
    voidForm.post(`/service-orders/${props.order.id}/void`, {
        onSuccess: () => {
            isVoidDialogOpen.value = false;
            toast.success('Order berhasil dibatalkan');
        }
    });
};
</script>

<template>
<Head :title="`Order Detail - ${order.order_number}`" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans text-slate-900">
    
    <PageHeader 
        :title="`Order Detail #${order.order_number}`" 
        :description="`Masuk pada ${formatDate(order.created_at)}`"
        back-href="/service-orders"
    >
        <template #actions>
            <div class="flex items-center gap-2">
                <Badge 
                    v-if="order.status === 'cancelled'"
                    class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[11px] uppercase font-semibold px-3 h-8 shadow-sm"
                >
                    <Ban class="h-3.5 w-3.5 mr-1.5" />
                    Dibatalkan
                </Badge>
                <Badge 
                    v-else
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[11px] uppercase font-semibold px-3 h-8 shadow-sm"
                    :class="{
                        'bg-amber-50 text-amber-600 border-amber-100': !order.production_step?.is_final,
                        'bg-slate-100 text-slate-500 border-slate-200': !order.production_step
                    }"
                >
                    <Clock v-if="!order.production_step?.is_final" class="h-3.5 w-3.5 mr-1.5" />
                    <CheckCircle2 v-else class="h-3.5 w-3.5 mr-1.5" />
                    {{ order.production_step?.name || 'PENDING' }}
                </Badge>

                <Dialog v-if="order.status !== 'cancelled'" v-model:open="isVoidDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" class="h-8 text-[10px] font-semibold uppercase tracking-widest text-destructive hover:bg-destructive/5 hover:text-destructive border-destructive/20">
                            <Ban class="h-3 w-3 mr-2" /> Batalkan Order
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="rounded-3xl border-none shadow-2xl">
                        <form @submit.prevent="handleVoid">
                            <DialogHeader>
                                <DialogTitle class="flex items-center gap-2 text-destructive font-semibold uppercase tracking-tight">
                                    <AlertCircle class="h-5 w-5" /> Batalkan Order
                                </DialogTitle>
                                <DialogDescription class="pt-2">
                                    Tindakan ini akan mengembalikan stok bahan baku (jika ada) dan membatalkan jurnal keuangan.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="py-6 space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 ml-1">Alasan Pembatalan</Label>
                                    <Textarea v-model="voidForm.reason" placeholder="Ketik alasan pembatalan..." class="rounded-xl border-slate-200 focus:ring-destructive/10" required />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit" variant="destructive" :disabled="voidForm.processing" class="w-full h-12 font-semibold uppercase tracking-widest">Konfirmasi Pembatalan</Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </template>
    </PageHeader>

    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
        
        <!-- Workflow / Status Transition Panel -->
        <div v-if="next_steps.length > 0 && order.status !== 'cancelled'" class="bg-white border-2 border-primary/20 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                    <Play class="h-5 w-5 fill-current" />
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-semibold text-slate-900 uppercase tracking-tight">Langkah Selanjutnya</p>
                    <p class="text-[11px] text-slate-500 font-medium uppercase tracking-widest">Update progress servis ke tahap berikutnya:</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button v-for="s in next_steps" :key="s.id" @click="updateStep(s.id)" variant="outline" class="h-10 px-4 rounded-xl border-primary/20 text-primary hover:bg-primary hover:text-white font-semibold gap-2 transition-all">
                    {{ s.name }} <ChevronRight class="h-3.5 w-3.5" />
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left Info Panel -->
            <div class="lg:col-span-1 flex flex-col gap-6">
                <!-- Transaction Info -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <Receipt class="h-3.5 w-3.5" /> Ringkasan Order
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Nomor Order</label>
                            <p class="text-sm font-semibold text-slate-900 font-mono">#{{ order.order_number }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Layanan Utama</label>
                            <p class="text-sm font-semibold text-slate-900">{{ order.service?.name }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Metode Bayar</label>
                            <div class="flex items-center gap-2">
                                <CreditCard class="h-3.5 w-3.5 text-primary" />
                                <span class="text-sm font-semibold text-slate-900 uppercase tracking-tight">{{ order.payment_method }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Details -->
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <User class="h-3.5 w-3.5" /> Pelanggan
                        </h3>
                    </div>
                    <div class="p-6">
                        <div v-if="order.customer" class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200">
                                <User class="h-5 w-5 text-slate-400" />
                            </div>
                            <div class="flex flex-col">
                                <p class="text-sm font-semibold text-slate-900">{{ order.customer.name }}</p>
                                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-tight">{{ order.customer.phone || 'Member' }}</p>
                            </div>
                        </div>
                        <div v-else class="flex items-center gap-3 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200">
                            <User class="h-5 w-5 text-slate-300" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 italic">Anonim / Walk-in</p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div v-if="order.notes" class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 block mb-2">Catatan Order</label>
                    <p class="text-xs text-slate-600 leading-relaxed font-medium italic">"{{ order.notes }}"</p>
                </div>
            </div>

            <!-- Right Items Panel -->
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 flex items-center gap-2">
                            <Layers class="h-3.5 w-3.5" /> Rincian Layanan
                        </h3>
                        <Badge variant="outline" class="text-[10px] font-mono font-semibold bg-white border-slate-200">{{ order.items.length }} Varian</Badge>
                    </div>
                    
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6 w-full uppercase text-[10px] font-semibold tracking-widest">Varian Jasa</TableHead>
                                <TableHead class="text-center px-4 uppercase text-[10px] font-semibold tracking-widest">Qty / Berat</TableHead>
                                <TableHead class="text-right px-4 uppercase text-[10px] font-semibold tracking-widest">Harga</TableHead>
                                <TableHead class="text-right pr-6 uppercase text-[10px] font-semibold tracking-widest">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in order.items" :key="item.id" class="border-slate-50">
                                <TableCell class="pl-6 py-4">
                                    <div class="flex flex-col gap-0.5">
                                        <p class="text-[13px] font-semibold text-slate-900">{{ item.service_type.name }}</p>
                                        <p class="text-[10px] text-primary uppercase tracking-tighter font-semibold">{{ item.service_type.code }}</p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center font-semibold text-slate-900 text-[13px] tabular-nums">
                                    {{ item.qty }} <span class="text-[10px] font-semibold text-slate-400 uppercase ml-1">{{ item.unit_name || 'Unit' }}</span>
                                </TableCell>
                                <TableCell class="text-right text-[12px] font-semibold text-slate-600 tabular-nums">{{ formatCurrency(item.unit_price) }}</TableCell>
                                <TableCell class="text-right pr-6 font-semibold tabular-nums text-slate-900">{{ formatCurrency(item.subtotal) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="p-8 bg-slate-50/50 border-t border-slate-100 flex flex-col gap-3">
                        <div class="flex justify-between items-center text-slate-400">
                            <span class="text-[10px] font-semibold uppercase tracking-widest">Subtotal Belanja</span>
                            <span class="text-sm font-semibold tabular-nums">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                        <div class="h-px bg-slate-200 mt-2 mb-1"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-900">Total Tagihan</span>
                            <span class="text-3xl font-semibold text-slate-900 tabular-nums tracking-tighter" :class="{'line-through text-slate-400': order.status === 'cancelled'}">
                                {{ formatCurrency(order.total_amount) }}
                            </span>
                        </div>
                        
                        <div :class="[
                            'flex justify-between items-center mt-2 px-4 py-3 rounded-xl border shadow-sm transition-colors',
                            order.status === 'cancelled' ? 'bg-slate-50 border-slate-200' : 'bg-emerald-50 border-emerald-100'
                        ]">
                            <div class="flex flex-col">
                                <span :class="[
                                    'text-[9px] font-semibold uppercase tracking-widest opacity-70',
                                    order.status === 'cancelled' ? 'text-slate-500' : 'text-emerald-600'
                                ]">Uang Diterima</span>
                                <span :class="[
                                    'text-sm font-semibold tabular-nums',
                                    order.status === 'cancelled' ? 'text-slate-500' : 'text-emerald-700'
                                ]">{{ formatCurrency(order.received_amount) }}</span>
                            </div>
                            <div class="flex flex-col items-end">
                                <span :class="[
                                    'text-[9px] font-semibold uppercase tracking-widest opacity-70',
                                    order.status === 'cancelled' ? 'text-slate-500' : 'text-emerald-600'
                                ]">Status Bayar</span>
                                <span :class="[
                                    'text-sm font-semibold uppercase tracking-widest',
                                    order.status === 'cancelled' ? 'text-slate-500' : 'text-emerald-700'
                                ]">
                                    {{ order.status === 'cancelled' ? 'Batal' : 'Lunas' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
