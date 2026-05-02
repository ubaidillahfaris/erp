<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    Search, Plus, Minus, Trash2, Wallet, QrCode, Banknote,
    CreditCard, Receipt, Users, ChevronRight, CircleDollarSign,
    Percent, UserPlus, X, Check, Clock, ArrowLeft, History,
    LayoutGrid, PackageOpen, Info, Layers, Weight, Tag,
    ClipboardList, Store, Wifi, WifiOff, Unlock, Lock
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import axios from 'axios';
import customerAction from '@/actions/App/Http/Controllers/CustomerController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn, fmtIdr } from '@/lib/utils';
import CreatableSelect from '@/components/ui/input/CreatableSelect.vue';
import { useSidebar } from '@/components/ui/sidebar';
import QuickCustomerModal from '@/components/QuickCustomerModal.vue';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

interface Pricing {
    id: number;
    pricing_basis: string;
    unit_name: string;
    unit_price: number;
}

interface ServiceType {
    id: number;
    code: string;
    name: string;
    pricings: Pricing[];
}

interface Service {
    id: number;
    code: string;
    name: string;
    service_category: string;
    service_types: ServiceType[];
}

const props = defineProps<{
    services: Service[];
    customers: any[];
    vendors: any[];
    currentWarehouseId: number;
}>();

// ============ State ============
const searchQuery = ref("");
const cart = ref<any[]>([]);
const selectedCustomerId = ref<number | null>(null);
const localCustomers = ref<any[]>([]);
const loadingCustomers = ref(false);
let debounceTimer: any = null;

const fetchCustomers = (search: string = '') => {
    if (debounceTimer) clearTimeout(debounceTimer);

    loadingCustomers.value = true;
    debounceTimer = setTimeout(async () => {
        try {
            const response = await axios.get(customerAction.index.url({
                query: { search, per_page: 20 }
            }), {
                headers: { 'Accept': 'application/json' }
            });
            // Handle both array and paginated object
            localCustomers.value = response.data.data || response.data;
        } catch (error) {
            console.error('Failed to fetch customers:', error);
            toast.error('Gagal mengambil data pelanggan');
        } finally {
            loadingCustomers.value = false;
        }
    }, 300);
};

onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
});

// Remove the watcher on props.customers as we'll use async fetching

// Modal states
const showPayment = ref(false);
const isQuickCustomerOpen = ref(false);
const quickCustomerInitialName = ref('');
const showConfig = ref(false);
const selectedService = ref<Service | null>(null);
const selectedType = ref<ServiceType | null>(null);
const selectedPricing = ref<Pricing | null>(null);
const qty = ref(1);

const { setOpen } = useSidebar();
onMounted(() => {
    setOpen(false);
    fetchCustomers(); // Initial fetch
});

// Environment states (Mock)
const online = ref(true);
const shiftOpen = ref(true);

// ============ Computed ============
const filteredServices = computed(() => {
    return props.services.filter(s => {
        const q = searchQuery.value.toLowerCase();
        return !q || s.name.toLowerCase().includes(q) || s.code.toLowerCase().includes(q);
    });
});

const subtotal = computed(() => {
    return cart.value.reduce((s, l) => s + (l.unit_price * l.qty), 0);
});

const totalAmount = computed(() => subtotal.value); // Add tax logic if needed

// ============ Actions ============

const openConfig = (service: Service) => {
    selectedService.value = service;
    selectedType.value = service.service_types[0] || null;
    selectedPricing.value = selectedType.value?.pricings[0] || null;
    qty.value = 1;
    showConfig.value = true;
};

const addToCart = () => {
    if (!selectedService.value || !selectedType.value || !selectedPricing.value) return;

    cart.value.push({
        service_id: selectedService.value.id,
        service_name: selectedService.value.name,
        type_id: selectedType.value.id,
        type_name: selectedType.value.name,
        pricing_id: selectedPricing.value.id,
        unit_name: selectedPricing.value.pricing_basis === 'per_kg' ? 'kg' : selectedPricing.value.unit_name,
        unit_price: selectedPricing.value.unit_price,
        qty: qty.value,
        total: selectedPricing.value.unit_price * qty.value
    });

    showConfig.value = false;
    toast.success('Layanan ditambahkan ke keranjang');
};

const removeFromCart = (index: number) => {
    cart.value.splice(index, 1);
};

const handleCreateCustomer = (name: string) => {
    quickCustomerInitialName.value = name;
    isQuickCustomerOpen.value = true;
};

const onCustomerCreated = (customer: { id: number; name: string }) => {
    localCustomers.value.push(customer as any);
    selectedCustomerId.value = customer.id;
};

// ============ Payment Form ============
const form = useForm({
    service_id: null as number | null,
    customer_type: 'customer' as 'customer' | 'vendor',
    party_id: null as number | null,
    payment_method: 'cash',
    received_amount: 0,
    items: [] as any[],
});

const submitOrder = () => {
    // Basic validation
    if (cart.value.length === 0) {
        toast.error('Keranjang masih kosong');
        return;
    }
    if (!selectedCustomerId.value) {
        toast.error('Pilih pelanggan terlebih dahulu');
        return;
    }

    // Map cart data to match backend validation
    form.service_id = cart.value[0].service_id; // Using first item's service_id
    form.customer_type = 'customer'; // Default to customer for POS
    form.party_id = selectedCustomerId.value;
    form.items = cart.value.map(item => ({
        service_type_id: item.type_id,
        quantity: item.qty,
        notes: item.notes || null
    }));

    form.post('/service-orders', {
        onSuccess: () => {
            cart.value = [];
            showPayment.value = false;
            toast.success('Order Servis berhasil dibuat!');
        }
    });
};

const StatusPill = {
    props: ['icon', 'label', 'tone'],
    template: `
        <div :class="[
            'h-9 px-3 rounded-full text-[11px] font-semibold uppercase tracking-wider flex items-center gap-2 transition-all',
            tone === 'primary' ? 'bg-primary/10 text-primary' :
            tone === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' :
            'bg-slate-100 text-slate-500'
        ]">
            <component :is="icon" class="h-3.5 w-3.5" />
            {{ label }}
        </div>
    `
};
</script>

<template>
<Head title="Service POS · Terminal" />

<div class="min-h-screen bg-[#F8F9FA] font-sans text-slate-900">
    <!-- ===== Top Bar ===== -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="mx-auto px-5 md:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/dashboard"
                    class="h-10 w-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div class="flex items-center gap-3">
                    <div class="leading-tight">
                        <h1 class="text-base font-semibold">Service Terminal</h1>
                        <p class="text-xs text-slate-400 font-medium -mt-0.5">POS Jasa & Layanan</p>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <StatusPill :icon="shiftOpen ? Unlock : Lock" label="Shift Aktif" tone="primary" />
                <StatusPill :icon="online ? Wifi : WifiOff" label="Online" tone="success" />
            </div>

            <div class="flex items-center gap-3">
                <Link href="/service-orders">
                    <Button variant="ghost" size="sm" class="gap-2 rounded-full h-9 text-slate-500">
                        <History class="h-4 w-4" /> Riwayat
                    </Button>
                </Link>
                <Link href="/service-orders/board">
                    <Button variant="ghost" size="sm" class="gap-2 rounded-full h-9 text-slate-500 font-semibold">
                        <ClipboardList class="h-4 w-4" /> Board
                    </Button>
                </Link>
            </div>
        </div>
    </header>

    <!-- ===== Body ===== -->
    <main class="mx-auto p-4 grid grid-cols-12 gap-6">
        <!-- ====== LEFT: Catalog ====== -->
        <section class="col-span-7 space-y-5">
            <div class="bg-white rounded-3xl border border-slate-200 p-4 space-y-4 shadow-sm">
                <div class="relative">
                    <Search class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <Input v-model="searchQuery" placeholder="Cari layanan jasa (Laundry, Cuci Sepatu, dll)..."
                        class="h-12 pl-11 rounded-2xl bg-slate-50 border-0 text-sm focus-visible:ring-primary/20" />
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                <button v-for="s in filteredServices" :key="s.id" @click="openConfig(s)"
                    class="group relative flex flex-col text-left rounded-2xl bg-white border border-slate-200 p-3 transition hover:border-primary hover:shadow-lg hover:-translate-y-0.5 shadow-sm">
                    <div
                        class="aspect-square w-full rounded-xl bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-primary/5 transition-colors">
                        <Layers class="h-10 w-10 text-slate-300 group-hover:text-primary transition-colors" />
                    </div>
                    <h3 class="text-[13px] font-semibold leading-snug line-clamp-2 min-h-[2.4em] mb-2 text-slate-800">
                        {{ s.name }}
                    </h3>
                    <div class="flex items-center justify-between mt-auto">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ s.service_category }}
                        </p>
                        <span
                            class="h-8 w-8 rounded-full bg-slate-100 group-hover:bg-primary group-hover:text-white flex items-center justify-center transition shrink-0">
                            <Plus class="h-4 w-4" />
                        </span>
                    </div>
                </button>
            </div>
        </section>

        <!-- ====== RIGHT: Cart ====== -->
        <aside
            class="col-span-5 lg:sticky lg:top-20 lg:h-[calc(100vh-100px)] bg-white rounded-3xl border border-slate-200 flex flex-col overflow-hidden shadow-sm">
            <div class="p-5 border-b border-slate-200 bg-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Order Servis Baru</h2>
                        <p
                            class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mt-0.5 uppercase tracking-tighter">
                            <Store class="h-3 w-3" /> Counter Checkout
                        </p>
                    </div>
                    <Button variant="ghost" size="icon" @click="cart = []" class="text-slate-300 hover:text-rose-500">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Pelanggan</label>
                    <CreatableSelect v-model="selectedCustomerId" :options="localCustomers"
                        placeholder="Cari atau tambah pelanggan..." displayExpr="name" valueExpr="id" hideLabel
                        :loading="loadingCustomers" @search="fetchCustomers" @create="handleCreateCustomer" />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4 custom-scrollbar">
                <div v-for="(item, idx) in cart" :key="idx"
                    class="flex gap-3 items-start group p-3 rounded-2xl border border-slate-50 bg-slate-50/30">
                    <div
                        class="h-10 w-10 rounded-lg bg-white border border-slate-100 flex items-center justify-center shrink-0">
                        <Layers class="h-5 w-5 text-slate-300" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-slate-800 leading-tight truncate">{{
                                    item.service_name }}</h4>
                                <p class="text-[10px] font-semibold text-primary uppercase tracking-widest mt-1">{{
                                    item.type_name }}</p>
                            </div>
                            <button @click="removeFromCart(idx)"
                                class="text-slate-200 hover:text-rose-500 transition-colors">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                            <div class="flex flex-col">
                                <span class="text-[11px] font-semibold text-slate-900 tabular-nums">{{ item.qty }} {{
                                    item.unit_name }}</span>
                                <span class="text-[9px] text-slate-400 font-medium">@ {{ fmtIdr(item.unit_price)
                                }}</span>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-slate-900">{{ fmtIdr(item.total) }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="cart.length === 0" class="flex flex-col items-center justify-center h-full opacity-20 py-10">
                    <PackageOpen class="h-12 w-12 mb-3" />
                    <p class="text-[11px] font-semibold uppercase tracking-widest">Belum ada layanan</p>
                </div>
            </div>

            <!-- Footer area -->
            <div class="border-t border-slate-200 p-5 space-y-4 bg-white">
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Estimasi Total</p>
                        <p class="text-3xl font-semibold tabular-nums text-slate-900 tracking-tight">{{
                            fmtIdr(totalAmount) }}</p>
                    </div>
                    <Badge variant="secondary" class="bg-primary/10 text-primary rounded-lg px-2.5 py-1 font-semibold">
                        {{ cart.length }} items</Badge>
                </div>
                <Button :disabled="cart.length === 0" @click="showPayment = true"
                    class="w-full h-14 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-semibold uppercase tracking-widest shadow-xl shadow-slate-900/10 gap-3">
                    <CircleDollarSign class="h-5 w-5" /> Bayar Sekarang
                </Button>
            </div>
        </aside>
    </main>

    <!-- Modal Config -->
    <Dialog v-model:open="showConfig">
        <DialogContent class="max-w-md rounded-3xl p-6 border-none shadow-2xl font-sans">
            <DialogHeader>
                <DialogTitle class="text-xl font-semibold tracking-tight">{{ selectedService?.name }}</DialogTitle>
                <DialogDescription class="text-xs uppercase font-semibold tracking-widest text-slate-400">Pilih Varian &
                    Kuantitas</DialogDescription>
            </DialogHeader>
            <div class="py-6 space-y-6">
                <div class="space-y-3">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Tipe
                        Layanan</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="t in selectedService?.service_types" :key="t.id"
                            @click="selectedType = t; selectedPricing = t.pricings[0]"
                            :class="cn('p-3 rounded-xl border-2 text-left transition-all', selectedType?.id === t.id ? 'border-primary bg-primary/5 ring-2 ring-primary/10' : 'border-slate-100 hover:border-slate-300 bg-slate-50/50')">
                            <span class="text-xs font-semibold block">{{ t.name }}</span>
                            <span class="text-[9px] font-semibold uppercase text-slate-400">{{ t.code }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="selectedType" class="space-y-3">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Aturan Harga /
                        Satuan</label>
                    <div class="grid grid-cols-1 gap-2">
                        <button v-for="p in selectedType.pricings" :key="p.id" @click="selectedPricing = p"
                            :class="cn('flex items-center justify-between p-3 rounded-xl border-2 transition-all', selectedPricing?.id === p.id ? 'border-primary bg-primary/5 ring-2 ring-primary/10' : 'border-slate-100 bg-slate-50/50')">
                            <div class="flex items-center gap-3">
                                <Weight class="h-4 w-4 text-slate-400" />
                                <span class="text-xs font-semibold uppercase">{{ p.unit_name }} ({{
                                    p.pricing_basis.replace('per_', '') }})</span>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{ fmtIdr(p.unit_price) }}</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">Kuantitas /
                        Berat</label>
                    <div class="flex items-center gap-4 bg-slate-100 rounded-2xl p-2 h-16">
                        <button @click="qty = Math.max(0.1, qty - 1)"
                            class="h-12 w-12 rounded-xl bg-white shadow-sm flex items-center justify-center active:scale-95 transition-all">
                            <Minus class="h-5 w-5" />
                        </button>
                        <Input type="number" v-model.number="qty" step="0.1"
                            class="flex-1 h-12 text-center text-2xl font-semibold border-none bg-transparent focus-visible:ring-0" />
                        <button @click="qty += 1"
                            class="h-12 w-12 rounded-xl bg-slate-900 text-white shadow-md flex items-center justify-center active:scale-95 transition-all">
                            <Plus class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button @click="addToCart" primary
                    class="w-full h-14 rounded-2xl font-semibold uppercase tracking-widest">Tambahkan Layanan</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- Modal Payment (Simplified Recap from POS) -->
    <Dialog v-model:open="showPayment">
        <DialogContent class="max-w-md rounded-4xl p-0 overflow-hidden border-none shadow-2xl">
            <div class="p-8 space-y-8 bg-white">
                <div>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-900">Konfirmasi Order</h3>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Lengkapi pembayaran untuk memproses servis.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button @click="form.payment_method = 'cash'"
                        :class="cn('p-4 rounded-2xl border-2 flex flex-col items-center gap-2 transition-all', form.payment_method === 'cash' ? 'border-primary bg-primary/5' : 'border-slate-100')">
                        <Banknote class="h-6 w-6" />
                        <span class="text-xs font-semibold uppercase">Tunai</span>
                    </button>
                    <button @click="form.payment_method = 'qris'"
                        :class="cn('p-4 rounded-2xl border-2 flex flex-col items-center gap-2 transition-all', form.payment_method === 'qris' ? 'border-primary bg-primary/5' : 'border-slate-100')">
                        <QrCode class="h-6 w-6" />
                        <span class="text-xs font-semibold uppercase">QRIS</span>
                    </button>
                </div>

                <div class="bg-slate-900 rounded-3xl p-6 text-white space-y-6">
                    <div class="flex justify-between items-end border-b border-slate-700/50 pb-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Total Tagihan
                            </p>
                            <p class="text-3xl font-semibold tabular-nums">{{ fmtIdr(totalAmount) }}</p>
                        </div>
                        <Badge class="bg-emerald-500 text-white border-none font-semibold">{{ cart.length }} Item
                        </Badge>
                    </div>
                    <Button @click="submitOrder" :disabled="form.processing" primary
                        class="w-full h-14 rounded-2xl text-slate-900 bg-white hover:bg-slate-100 font-semibold uppercase tracking-widest text-[11px]">
                        Bayar & Simpan Order
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</div>
<QuickCustomerModal v-model:open="isQuickCustomerOpen" :initial-name="quickCustomerInitialName"
    @created="onCustomerCreated" />
</template>
