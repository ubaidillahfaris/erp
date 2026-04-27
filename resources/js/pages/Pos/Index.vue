<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Search, Plus, Minus, Trash2, Coffee, Cookie, Sandwich, IceCream2, Pizza, Soup,
    Wallet, QrCode, Banknote, CreditCard, Printer, WifiOff, Wifi, ShieldAlert,
    Receipt, Users, Split, Merge, ArrowLeftRight, ChevronRight, Lock, Unlock,
    CircleDollarSign, Percent, UserPlus, MessageSquare, X, Check, Clock,
    ArrowLeft, AlertTriangle, History, LayoutGrid,
    LogIn, LogOut, ShoppingBag, Store, Fingerprint, Package, Info,
    HardHat, Paintbrush, Layers, Box, Settings
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';
import CreatableSelect from '@/components/CreatableSelect.vue';
import { useSidebar } from '@/components/ui/sidebar';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    products: any[];
    customers: any[];
    categories: any[];
}>();

// ============ Types & Constants ============
type PaymentMethod = "cash" | "qris" | "transfer" | "credit";
type OrderType = "dine-in" | "takeaway" | "counter";

const getCategoryIcon = (name: string) => {
    const n = name.toLowerCase();
    if (n.includes('material') || n.includes('bangunan')) return HardHat;
    if (n.includes('cat') || n.includes('finish')) return Paintbrush;
    if (n.includes('plastik') || n.includes('product')) return Layers;
    if (n.includes('baku')) return Box;
    if (n.includes('penolong')) return Settings;
    return Package;
};

const dynamicCategories = computed(() => {
    return [
        { id: 'all', name: 'Semua', icon: LayoutGrid },
        ...props.categories.map(c => ({
            ...c,
            icon: getCategoryIcon(c.name)
        }))
    ];
});

const TABLES = [
    { id: "t1", label: "T-01", seats: 2, status: "open", bill: 86000, guests: 2, openedAt: "14:02" },
    { id: "t2", label: "T-02", seats: 4, status: "available" },
    { id: "t3", label: "T-03", seats: 4, status: "open", bill: 142000, guests: 3, openedAt: "13:48" },
    { id: "t4", label: "T-04", seats: 2, status: "reserved" },
    { id: "t5", label: "T-05", seats: 6, status: "available" },
    { id: "t6", label: "T-06", seats: 2, status: "open", bill: 54000, guests: 1, openedAt: "14:18" },
    { id: "t7", label: "T-07", seats: 4, status: "available" },
    { id: "t8", label: "T-08", seats: 8, status: "available" },
];

// ============ Local Sub-components ============
const StatusPill = {
    props: ['icon', 'label', 'tone'],
    template: `
        <div :class="[
            'h-9 px-3 rounded-full text-[11px] font-bold uppercase tracking-wider flex items-center gap-2 transition-all',
            tone === 'primary' ? 'bg-primary/10 text-primary' :
            tone === 'success' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' :
            tone === 'warning' ? 'bg-amber-50 text-amber-600 border border-amber-100' :
            'bg-slate-100 text-slate-500'
        ]">
            <component :is="icon" class="h-3.5 w-3.5" />
            {{ label }}
        </div>
    `
};

const IconBtn = {
    props: ['ariaLabel'],
    template: `
        <button
            :aria-label="ariaLabel"
            class="h-9 w-9 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-900 transition-all active:scale-90"
        >
            <slot />
        </button>
    `
};

// ============ State ============
const activeCat = ref<string | number>("all");
const searchQuery = ref("");
const cart = ref<any[]>([]);
const orderType = ref<OrderType>("counter");
const selectedTableId = ref("t1");
const discountPct = ref(0);
const selectedCustomerId = ref<number | null>(null);

// Modal states
const showPayment = ref(false);
const showVoid = ref(false);
const showTables = ref(false);
const showShift = ref(false);

const cashInputRef = ref<any>(null);

const { setOpen } = useSidebar();

onMounted(() => {
    setOpen(false);
});

// Environment states (Mock)
const online = ref(true);
const shiftOpen = ref(true);
const employee = ref<{ name: string; initial: string; checkedInAt: string } | null>({
    name: "Rizal A.", initial: "RA", checkedInAt: "07:58",
});

// ============ Computed ============
const filteredProducts = computed(() => {
    return props.products.filter(p => {
        const q = searchQuery.value.toLowerCase();
        const matchesQuery = !q ||
            p.name.toLowerCase().includes(q) ||
            p.sku?.toLowerCase().includes(q) ||
            p.barcode?.toLowerCase().includes(q);

        if (!matchesQuery) return false;

        if (activeCat.value === "all") return true;

        return p.category_id === activeCat.value;
    });
});

const selectedCustomer = computed(() => {
    return props.customers.find(c => c.id === selectedCustomerId.value);
});

const subtotal = computed(() => {
    return cart.value.reduce((s, l) => s + (l.price || 0) * l.qty, 0);
});

const discountAmt = computed(() => Math.round((subtotal.value * discountPct.value) / 100));
const afterDiscount = computed(() => subtotal.value - discountAmt.value);
const serviceCharge = computed(() => Math.round(afterDiscount.value * 0.05));
const tax = computed(() => Math.round((afterDiscount.value + serviceCharge.value) * 0.11));
const totalAmount = computed(() => afterDiscount.value + serviceCharge.value + tax.value);

const selectedTable = computed(() => TABLES.find(t => t.id === selectedTableId.value));

// ============ Actions ============
const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const fetchPrice = async (productId: number, satuanId: number, customerId: number | null) => {
    try {
        const response = await axios.get('/pos/price', {
            params: {
                product_id: productId,
                unit_id: satuanId,
                customer_id: customerId
            }
        });
        return response.data;
    } catch (error) {
        console.error('Error fetching price:', error);
        return { price: 0, price_type: 'retail' };
    }
};

const addToCart = async (product: any) => {
    const existingIndex = cart.value.findIndex(item => item.product_id === product.id);

    // Check stock first
    if (product.track_stock) {
        const currentQty = existingIndex > -1 ? cart.value[existingIndex].qty : 0;
        if (currentQty + 1 > product.stock) {
            toast.error(`Stok tidak mencukupi. Sisa: ${product.stock}`);
            return;
        }
    }

    const priceData = await fetchPrice(product.id, product.unit_id, selectedCustomerId.value);

    if (existingIndex > -1) {
        cart.value[existingIndex].qty += 1;
        cart.value[existingIndex].price = priceData.price;
    } else {
        cart.value.push({
            ...product,
            qty: 1,
            product_id: product.id,
            price: priceData.price,
            original_price: priceData.original_price,
            discount_rate: priceData.discount_rate,
            price_type: priceData.price_type
        });
    }
};

const updateQty = (productId: number, delta: number) => {
    const index = cart.value.findIndex(l => l.product_id === productId);
    if (index === -1) return;

    const product = props.products.find(p => p.id === productId);
    const newQty = cart.value[index].qty + delta;

    if (delta > 0 && product?.track_stock && newQty > (product.stock || 0)) {
        toast.error(`Stok tidak mencukupi. Maksimal: ${product.stock}`);
        return;
    }

    // Buttons only allow decrement down to 1
    if (newQty >= 1) {
        cart.value[index].qty = newQty;
    }
};

const setQty = (productId: number, val: number) => {
    const index = cart.value.findIndex(l => l.product_id === productId);
    if (index === -1) return;

    const product = props.products.find(p => p.id === productId);
    if (product?.track_stock && val > (product.stock || 0)) {
        toast.error(`Stok tidak mencukupi. Maksimal: ${product.stock}`);
        // Reset to max stock or previous value? 
        // Let's set it to max stock for better UX
        cart.value[index].qty = product.stock;
        return;
    }

    // Manual input allows 0, but doesn't delete the item
    // The cashier can explicitly delete using the X button
    cart.value[index].qty = isNaN(val) ? 0 : Math.max(0, val);
};

const removeFromCart = (productId: number) => {
    const index = cart.value.findIndex(l => l.product_id === productId);
    if (index > -1) cart.value.splice(index, 1);
};

const clearCart = () => cart.value = [];

// ============ Form & Checkout ============
const form = useForm({
    date: new Date().toISOString().split('T')[0],
    payment_method: 'cash' as PaymentMethod,
    customer_id: null as number | null,
    received_amount: 0,
    change_amount: 0,
    notes: '',
    items: [] as any[],
});

watch(() => form.payment_method, (newMethod) => {
    if (newMethod !== 'cash') {
        form.received_amount = totalAmount.value;
    } else {
        // Focus the input when switching to cash
        setTimeout(() => {
            cashInputRef.value?.$el?.focus();
        }, 100);
    }
});

watch(showPayment, (isOpen) => {
    if (isOpen && form.payment_method === 'cash') {
        setTimeout(() => {
            cashInputRef.value?.$el?.focus();
        }, 100);
    }
});

const handleCheckout = () => {
    form.customer_id = selectedCustomerId.value;
    form.items = cart.value.map(item => ({
        product_id: item.product_id,
        unit_id: item.unit_id,
        qty: item.qty,
        price: item.price,
        cost: item.cost,
    }));

    form.change_amount = Math.max(0, form.received_amount - totalAmount.value);

    form.post('/pos', {
        onSuccess: () => {
            cart.value = [];
            selectedCustomerId.value = null;
            showPayment.value = false;
            toast.success('Transaksi berhasil disimpan!');
        },
        onError: (errors) => {
            toast.error(errors.checkout || 'Gagal memproses transaksi.');
        },
    });
};

const suggestedAmounts = computed(() => {
    const total = totalAmount.value;
    if (total <= 0) return [];

    const denominations = [1000, 2000, 5000, 10000, 20000, 50000, 100000];
    const suggestions = new Set<number>();

    // Exact amount
    suggestions.add(total);

    // Common higher denominations
    denominations.forEach(d => {
        if (d > total) {
            suggestions.add(d);
        }

        // Round up to nearest denomination
        const rounded = Math.ceil(total / d) * d;
        if (rounded > total) {
            suggestions.add(rounded);
        }
    });

    return Array.from(suggestions)
        .sort((a, b) => a - b)
        .slice(0, 5); // Top 5 suggestions
});

const selectAmount = (amount: number) => {
    form.received_amount = amount;
};
</script>

<template>
<Head title="Cangkir POS · Terminal" />

<div class="min-h-screen bg-[#F8F9FA] font-sans text-slate-900">
    <!-- ===== Top Bar ===== -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200">
        <div class="mx-auto  px-5 md:px-8 h-16 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/dashboard"
                    class="h-10 w-10 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div class="flex items-center gap-3">
                    <div class="leading-tight">
                        <h1 class="text-base font-bold">{{ $page.props.name }}</h1>
                        <p class="text-xs text-slate-400 font-medium -mt-0.5">Cabang Utama · Terminal #02</p>
                    </div>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-2">
                <StatusPill :icon="shiftOpen ? Unlock : Lock" :label="shiftOpen ? 'Shift Aktif · 06:42' : 'Shift Tutup'"
                    :tone="shiftOpen ? 'primary' : 'muted'" />
                <StatusPill :icon="online ? Wifi : WifiOff"
                    :label="online ? 'Online · ERP synced' : 'Offline · 12 antrian'"
                    :tone="online ? 'success' : 'warning'" />
                <StatusPill :icon="Printer" label="Printer Ready" tone="muted" />
            </div>

            <div class="flex items-center gap-2">
                <Button :variant="shiftOpen ? 'outline' : 'default'" size="sm" @click="showShift = true"
                    :class="cn('gap-2 rounded-full h-9', !shiftOpen && 'bg-primary text-white')">
                    <component :is="shiftOpen ? LogOut : LogIn" class="h-4 w-4" />
                    {{ shiftOpen ? 'Tutup Shift' : 'Buka Shift' }}
                </Button>

                <Button variant="ghost" size="sm" @click="showVoid = true"
                    class="gap-2 rounded-full h-9 text-slate-500">
                    <ShieldAlert class="h-4 w-4" /> Void
                </Button>

                <Link href="/sales">
                    <Button variant="ghost" size="sm" class="gap-2 rounded-full h-9 text-slate-500">
                        <History class="h-4 w-4" /> Riwayat
                    </Button>
                </Link>
            </div>
        </div>
    </header>

    <!-- ===== Body ===== -->
    <main class="mx-auto p-4 grid grid-cols-9 gap-6">
        <!-- ====== LEFT: Catalog & Tables ====== -->
        <section class="col-span-6 space-y-5">
            <div class="bg-white rounded-3xl  border border-slate-200 p-4 space-y-4">
                <div class="flex flex-col md:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                        <Input v-model="searchQuery" placeholder="Cari menu, scan barcode, atau ketik SKU…"
                            class="h-12 pl-11 rounded-2xl bg-slate-50 border-0 text-sm focus-visible:ring-primary/20" />
                    </div>
                    <div class="flex gap-2">
                        <div class="h-12 p-1 rounded-2xl bg-slate-100 flex items-center">
                            <button v-for="opt in (['counter', 'dine-in', 'takeaway'] as OrderType[])" :key="opt"
                                @click="orderType = opt" :class="cn(
                                    'h-full px-4 rounded-xl text-xs font-bold uppercase tracking-wider transition',
                                    orderType === opt ? 'bg-white text-slate-900 ' : 'text-slate-500 hover:text-slate-900'
                                )">
                                {{ opt.replace('-', ' ') }}
                            </button>
                        </div>

                        <Button v-if="orderType === 'dine-in'" variant="outline"
                            class="h-12 rounded-2xl gap-2 border-slate-200" @click="showTables = true">
                            <Users class="h-4 w-4" />
                            Meja {{ selectedTable?.label }}
                        </Button>
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 no-scrollbar">
                    <button v-for="cat in dynamicCategories" :key="cat.id" @click="activeCat = cat.id" :class="cn(
                        'shrink-0 h-11 px-4 rounded-2xl flex items-center gap-2 text-sm font-bold transition border',
                        activeCat === cat.id
                            ? 'bg-slate-900 text-white border-slate-900'
                            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                    )">
                        <component :is="cat.icon" class="h-4 w-4" />
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                <button v-for="p in filteredProducts" :key="p.id" :disabled="p.stock === 0" @click="addToCart(p)"
                    class="group relative flex flex-col text-left rounded-2xl bg-white border border-slate-200 p-3 transition hover:border-primary hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                    <span v-if="p.track_stock && p.stock <= 0"
                        class="absolute top-2 right-2 z-10 text-[10px] font-bold uppercase tracking-wider bg-destructive text-white px-2 py-0.5 rounded-full">
                        Habis
                    </span>
                    <span v-else-if="p.track_stock" :class="cn(
                        'absolute top-2 right-2 z-10 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full',
                        p.stock < 10 ? 'bg-orange-500 text-white' : 'bg-slate-900 text-white'
                    )">
                        Stok {{ p.stock }}
                    </span>

                    <div
                        class="aspect-square w-full rounded-xl bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-primary/5 transition-colors">
                        <Package class="h-10 w-10 text-slate-300 group-hover:text-primary transition-colors" />
                    </div>

                    <h3 class="text-[13px] font-bold leading-snug line-clamp-2 min-h-[2.4em] mb-2 text-slate-800">
                        {{ p.name }}
                    </h3>

                    <div class="flex items-center justify-between mt-auto">
                        <p class="text-sm font-bold text-slate-900 tabular-nums">
                            {{ formatCurrency(p.price) }}
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
            class="col-span-3 lg:sticky lg:top-20 lg:h-[calc(100vh-100px)] bg-white rounded-3xl border border-slate-200  flex flex-col overflow-hidden">
            <div class="p-5 border-b border-slate-200">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Order Aktif</h2>
                        <p class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mt-0.5">
                            <template v-if="orderType === 'dine-in'">
                                <Users class="h-3 w-3" /> Meja {{ selectedTable?.label }} · {{ selectedTable?.guests }}
                                tamu
                            </template>
                            <template v-else-if="orderType === 'takeaway'">
                                <ShoppingBag class="h-3 w-3" /> Takeaway
                            </template>
                            <template v-else>
                                <Store class="h-3 w-3" /> Counter / Walk-in
                            </template>
                        </p>
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-if="orderType === 'dine-in'">
                            <IconBtn aria-label="Split bill">
                                <Split class="h-4 w-4" />
                            </IconBtn>
                            <IconBtn aria-label="Pindah meja">
                                <ArrowLeftRight class="h-4 w-4" />
                            </IconBtn>
                            <IconBtn aria-label="Gabung meja">
                                <Merge class="h-4 w-4" />
                            </IconBtn>
                        </template>
                        <IconBtn aria-label="Bersihkan cart" @click="clearCart">
                            <Trash2 class="h-4 w-4" />
                        </IconBtn>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Pilih
                            Customer</label>
                        <button v-if="selectedCustomerId" @click="selectedCustomerId = null"
                            class="text-[10px] font-bold text-primary hover:underline flex items-center gap-1">
                            <X class="h-2.5 w-2.5" /> Lepas
                        </button>
                    </div>
                    <CreatableSelect v-model="selectedCustomerId" :options="customers"
                        placeholder="Cari customer / member..." displayExpr="name" valueExpr="id" hideLabel
                        class="w-full" />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-3 min-h-[200px] custom-scrollbar">
                <div v-for="line in cart" :key="line.product_id" class="flex gap-3 items-start group">
                    <div
                        class="h-12 w-12 rounded-xl bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                        <Package class="h-5 w-5 text-slate-300" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold leading-tight truncate text-slate-800">{{ line.name }}</h4>
                                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ formatCurrency(line.price) }}
                                </p>
                            </div>
                            <button @click="removeFromCart(line.product_id)"
                                class="opacity-0 group-hover:opacity-100 transition text-slate-300 hover:text-destructive">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center gap-1 bg-slate-100 rounded-full p-0.5">
                                <button @click="updateQty(line.product_id, -1)"
                                    class="h-7 w-7 rounded-full bg-white hover:bg-slate-50  flex items-center justify-center text-slate-600 transition shadow-sm active:scale-90">
                                    <Minus class="h-3 w-3" />
                                </button>
                                <input type="number" :value="line.qty"
                                    @input="e => setQty(line.product_id, parseInt((e.target as HTMLInputElement).value) || 0)"
                                    class="w-12 text-center text-sm font-bold tabular-nums text-slate-800 bg-transparent border-0 focus:ring-0 p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                <button @click="updateQty(line.product_id, 1)"
                                    class="h-7 w-7 rounded-full bg-slate-900 text-white flex items-center justify-center transition shadow-sm active:scale-90">
                                    <Plus class="h-3 w-3" />
                                </button>
                            </div>
                            <p class="text-sm font-bold tabular-nums text-slate-900">{{ formatCurrency(line.price *
                                line.qty) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 p-5 space-y-3 bg-white">
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600"><span class="font-medium">Subtotal</span><span
                            class="tabular-nums font-bold">{{ formatCurrency(subtotal) }}</span></div>
                    <div class="flex justify-between text-slate-400">
                        <button class="flex items-center gap-1 font-medium"
                            @click="discountPct = discountPct > 0 ? 0 : 10">
                            <Percent class="h-3 w-3" /> Diskon {{ discountPct }}%
                        </button>
                        <span class="tabular-nums font-medium">- {{ formatCurrency(discountAmt) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400"><span>Pajak & Layanan</span><span
                            class="tabular-nums font-medium">{{ formatCurrency(serviceCharge + tax) }}</span></div>
                </div>
                <div class="flex items-end justify-between pt-2 border-t border-slate-100">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Bayar</p>
                        <p class="text-2xl font-bold tabular-nums text-slate-900 tracking-tight">{{
                            formatCurrency(totalAmount) }}
                        </p>
                    </div>
                    <Badge variant="secondary" class="bg-primary/10 text-primary rounded-lg px-2 py-1 font-bold">{{
                        cart.reduce((s,
                            l) => s + l.qty, 0)}} items</Badge>
                </div>
                <Button :disabled="cart.length === 0" @click="showPayment = true"
                    class="w-full h-14 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold uppercase tracking-widest shadow-xl shadow-slate-900/10">
                    <CircleDollarSign class="h-5 w-5" /> Bayar Sekarang
                </Button>
            </div>
        </aside>
    </main>

    <!-- Modals -->
    <Dialog :open="showPayment" @update:open="showPayment = $event">
        <DialogContent class="w-fit md:min-w-4xl lg:min-w-6xl rounded-4xl p-0 overflow-hidden border-none shadow-2xl">
            <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100 bg-white">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 tracking-tight">Selesaikan Pembayaran</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Pilih metode bayar dan konfirmasi transaksi.</p>
                </div>
            </div>
            <div class="grid md:grid-cols-[1fr_380px]">
                <div class="p-8 space-y-8 bg-white">
                    <div class="grid grid-cols-4 gap-4">
                        <button
                            v-for="m in [{ id: 'cash', label: 'Cash', icon: Banknote }, { id: 'qris', label: 'QRIS', icon: QrCode }, { id: 'transfer', label: 'Transfer', icon: Wallet }, { id: 'credit', label: 'Piutang', icon: CreditCard }]"
                            :key="m.id" @click="form.payment_method = m.id as PaymentMethod"
                            :class="cn('text-center p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2', form.payment_method === m.id ? 'border-primary bg-primary/5 ring-4 ring-primary/10' : 'border-slate-100 hover:border-slate-300 bg-slate-50/50')">
                            <div
                                :class="cn('h-12 w-12 rounded-xl flex items-center justify-center transition-all', form.payment_method === m.id ? 'bg-primary text-white scale-110 shadow-md shadow-primary/20' : 'bg-white text-slate-400 border border-slate-100')">
                                <component :is="m.icon" class="h-6 w-6" />
                            </div>
                            <div class="space-y-0.5">
                                <p class="text-sm font-bold text-slate-900">{{ m.label }}</p>
                            </div>
                        </button>
                    </div>

                    <div v-if="form.payment_method === 'cash'"
                        class="bg-slate-50 rounded-3xl p-6 border border-slate-100 animate-in fade-in slide-in-from-top-2">
                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Pilih
                                    Nominal
                                    Cepat</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button v-for="amt in suggestedAmounts" :key="amt" @click="selectAmount(amt)"
                                        :class="cn(
                                            'h-12 rounded-xl text-sm font-bold border transition-all active:scale-95 shadow-sm',
                                            form.received_amount === amt
                                                ? 'bg-primary text-white border-primary shadow-md shadow-primary/20'
                                                : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400'
                                        )">
                                        {{ amt === totalAmount ? 'Uang Pas' : formatCurrency(amt).replace('Rp',
                                            '').trim()
                                        }}
                                    </button>
                                    <button @click="form.received_amount = 0"
                                        class="h-12 rounded-xl text-sm font-bold bg-white text-destructive border border-slate-200 hover:border-destructive/30 transition-all shadow-sm">
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-5">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Input
                                        Manual</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-lg">Rp</span>
                                        <Input ref="cashInputRef" type="number" v-model.number="form.received_amount"
                                            class="h-14 pl-12 text-2xl font-bold rounded-xl bg-white border-slate-200 focus-visible:ring-primary/20 shadow-inner" />
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between p-5 rounded-2xl bg-emerald-50 text-emerald-900 border border-emerald-100 shadow-sm transition-all">
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600">
                                            Kembalian</p>
                                        <p class="text-2xl font-bold tabular-nums">
                                            {{ formatCurrency(Math.max(0, form.received_amount - totalAmount)) }}
                                        </p>
                                    </div>
                                    <div v-if="form.received_amount >= totalAmount"
                                        class="h-10 w-10 rounded-full bg-emerald-500 flex items-center justify-center shadow-md shadow-emerald-500/20">
                                        <Check class="h-6 w-6 text-white" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.payment_method === 'qris'"
                        class="bg-slate-50 rounded-3xl p-8 flex flex-col items-center justify-center border border-slate-100 animate-in zoom-in-95">
                        <QrCode class="h-28 w-28 text-slate-800 mb-4" />
                        <p class="text-[10px] font-bold uppercase text-slate-400 tracking-widest">Scan QRIS Dinamis</p>
                    </div>
                </div>
                <div class="bg-slate-900 text-white p-8 flex flex-col justify-between">
                    <div class="space-y-5">
                        <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400">Ringkasan Pembayaran</h4>
                        <div class="space-y-2.5">
                            <div class="flex justify-between text-sm text-slate-300"><span>Total Belanja</span><span
                                    class="font-medium">{{ formatCurrency(subtotal) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-300"><span>Diskon</span><span
                                    class="font-medium">-{{ formatCurrency(discountAmt) }}</span></div>
                            <div class="flex justify-between text-sm text-slate-300"><span>Pajak & Svc</span><span
                                    class="font-medium">{{ formatCurrency(serviceCharge + tax) }}</span></div>
                        </div>
                        <div class="border-t border-slate-700/50 my-5"></div>
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-widest">Total
                                Bayar</span>
                            <span class="font-bold text-4xl tracking-tight text-white">{{ formatCurrency(totalAmount)
                                }}</span>
                        </div>
                    </div>
                    <div class="pt-8 space-y-3">
                        <Button @click="handleCheckout"
                            :disabled="form.processing || (form.payment_method === 'cash' && form.received_amount < totalAmount)"
                            class="w-full h-14 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold uppercase tracking-widest shadow-lg shadow-primary/20 transition-all active:scale-[0.98]">
                            Konfirmasi & Cetak
                        </Button>
                        <Button variant="ghost" @click="showPayment = false"
                            class="w-full h-12 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all">Cancel</Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog :open="showVoid" @update:open="showVoid = $event">
        <DialogContent class="max-w-md rounded-3xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-2xl bg-destructive/10 text-destructive flex items-center justify-center">
                    <AlertTriangle class="h-6 w-6" />
                </div>
                <h3 class="font-bold text-slate-900">Void Transaksi</h3>
            </div>
            <p class="text-sm text-slate-500 mb-6">Otorisasi supervisor diperlukan untuk pembatalan transaksi.</p>
            <div class="space-y-4">
                <Input type="password" placeholder="PIN Supervisor"
                    class="h-12 text-center text-2xl tracking-[0.5em] font-bold" />
                <Button variant="destructive" class="w-full h-12 font-bold uppercase tracking-widest">Void
                    Sekarang</Button>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog :open="showTables" @update:open="showTables = $event">
        <DialogContent class="max-w-2xl rounded-3xl p-6">
            <h3 class="font-bold text-lg text-slate-900 mb-4">Pilih Meja</h3>
            <div class="grid grid-cols-4 gap-3">
                <button v-for="t in TABLES" :key="t.id" @click="selectedTableId = t.id; showTables = false"
                    :class="cn('p-4 rounded-2xl border-2 text-left transition-all', t.status === 'open' ? 'border-primary bg-primary/5' : 'border-slate-200 hover:border-slate-400')">
                    <span class="text-lg font-bold block">{{ t.label }}</span>
                    <span class="text-[10px] text-slate-400 uppercase font-bold">{{ t.status }}</span>
                </button>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog :open="showShift" @update:open="showShift = $event">
        <DialogContent class="max-w-md rounded-3xl p-6">
            <div class="flex items-center gap-4 mb-4">
                <div
                    :class="cn('h-12 w-12 rounded-2xl flex items-center justify-center', shiftOpen ? 'bg-primary/10 text-primary' : 'bg-emerald-100 text-emerald-700')">
                    <component :is="shiftOpen ? LogOut : LogIn" class="h-6 w-6" />
                </div>
                <h3 class="font-bold text-slate-900">{{ shiftOpen ? 'Tutup Shift' : 'Buka Shift' }}</h3>
            </div>
            <div class="space-y-6">
                <div class="bg-slate-50 p-4 rounded-2xl space-y-2">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Petugas Aktif</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold">
                            {{ employee?.initial }}</div>
                        <span class="font-bold">{{ employee?.name }}</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">{{ shiftOpen ?
                        `Uang Fisik di Laci` : 'Modal Awal' }}</label>
                    <Input type="number" placeholder="0" class="h-14 text-center text-2xl font-bold" />
                </div>
                <div class="flex gap-3">
                    <Button variant="ghost" @click="showShift = false"
                        class="flex-1 h-12 font-bold uppercase">Cancel</Button>
                    <Button
                        @click="shiftOpen = !shiftOpen; employee = shiftOpen ? null : { name: 'Rizal A.', initial: 'RA', checkedInAt: '08:00' }; showShift = false"
                        class="flex-1 h-12 bg-slate-900 text-white font-bold uppercase">
                        {{ shiftOpen ? 'Konfirmasi Tutup' : 'Mulai Shift' }}
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}
</style>
