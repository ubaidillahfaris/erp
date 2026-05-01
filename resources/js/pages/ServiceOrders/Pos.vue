<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
  ArrowLeft, Plus, Search, Calendar as CalendarIcon, Clock, User, Phone, 
  Trash2, CreditCard, Receipt, CheckCircle2, Wrench, PauseCircle, PlayCircle, 
  PackageCheck, Users, ChevronLeft, ChevronRight, Briefcase, X, Filter, ShoppingBag, History
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

interface Product {
    id: number;
    name: string;
    category: string;
    unit_symbol: string;
    price: number;
    emoji: string;
    duration?: number; // fallback to 60 if not provided
}

interface Order {
    id: string;
    customer: string;
    phone: string;
    items: { name: string; qty: number }[];
    staff: string;
    scheduledAt: string;
    total: number;
    status: 'Queued' | 'Confirmed' | 'In Progress' | 'Done' | 'Picked Up';
}

interface Employee {
    id: number;
    name: string;
    position: string;
}

const props = defineProps<{
    products: Product[];
    customers: any[];
    employees: Employee[];
    initialOrders: Order[];
}>();

// -------- Catalog filters --------
const activeCat = ref('All');
const search = ref('');

const categories = computed(() => ['All', ...new Set(props.products.map(p => p.category))]);

const filteredServices = computed(() => {
    return props.products.filter(s =>
        (activeCat.value === 'All' || s.category === activeCat.value) &&
        (search.value === '' || s.name.toLowerCase().includes(search.value.toLowerCase()))
    );
});

// -------- Cart state --------
const cart = ref<any[]>([]);
const customer = ref({ id: null as number | null, name: '', phone: '' });
const staff = ref<number | null>(null);
const bookingDate = ref(new Date().toISOString().slice(0, 10));
const bookingTime = ref('09:00');
const timeSlots = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00", "17:00"];

// -------- Orders pipeline --------
const orders = ref<Order[]>(props.initialOrders);
const tab = ref<'new' | 'track'>('new');

// -------- Receipt dialog --------
const receiptOpen = ref(false);
const lastOrder = ref<Order | null>(null);

// ---- Cart totals ----
const subtotal = computed(() => cart.value.reduce((s, l) => s + l.price * l.qty, 0));
const tax = computed(() => Math.round(subtotal.value * 0.11)); // PPN 11%
const total = computed(() => subtotal.value + tax.value);
const totalDuration = computed(() => cart.value.reduce((s, l) => s + (l.duration || 60) * l.qty, 0));

const fmtIdr = (n: number) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(n);

// ---- Cart ops ----
const addToCart = (s: Product) => {
    const found = cart.value.find(l => l.id === s.id);
    if (found) {
        found.qty++;
    } else {
        cart.value.push({ ...s, qty: 1 });
    }
};

const adjustQty = (id: number, delta: number) => {
    const item = cart.value.find(l => l.id === id);
    if (item) {
        item.qty += delta;
        if (item.qty <= 0) {
            cart.value = cart.value.filter(l => l.id !== id);
        }
    }
};

const removeLine = (id: number) => {
    cart.value = cart.value.filter(l => l.id !== id);
};

const clearAll = () => {
    cart.value = [];
    customer.value = { id: null, name: '', phone: '' };
    staff.value = null;
    toast.info("Draft dibatalkan");
};

const confirmBooking = () => {
    if (cart.value.length === 0) return toast.error("Cart masih kosong");
    if (!customer.value.name) return toast.error("Isi nama customer dulu");
    if (!staff.value) return toast.error("Pilih staff yang assigned");

    // In a real app, we would send this to the server
    // For now, let's simulate the success
    const selectedStaff = props.employees.find(e => e.id === staff.value);
    const newOrder: Order = {
      id: `BK-${Math.floor(2050 + Math.random() * 900)}`,
      customer: customer.value.name,
      phone: customer.value.phone || "-",
      items: cart.value.map(l => ({ name: l.name, qty: l.qty })),
      staff: selectedStaff?.name ?? 'Unknown',
      scheduledAt: `${bookingDate.value} · ${bookingTime.value}`,
      total: total.value,
      status: "Confirmed",
    };

    // Use Inertia to submit if we wanted to persist
    /*
    useForm({
        customer_id: customer.value.id,
        items: cart.value,
        metadata: { staff_id: staff.value, staff_name: selectedStaff?.name },
        estimated_at: `${bookingDate.value} ${bookingTime.value}:00`
    }).post('/service-orders');
    */

    orders.value = [newOrder, ...orders.value];
    lastOrder.value = newOrder;
    receiptOpen.value = true;
    cart.value = [];
    customer.value = { id: null, name: '', phone: '' };
    staff.value = null;
    toast.success(`Booking ${newOrder.id} berhasil dibuat`);
};

const statusFlow: Order['status'][] = ["Queued", "Confirmed", "In Progress", "Done", "Picked Up"];

const advanceStatus = (id: string) => {
    const order = orders.value.find(o => o.id === id);
    if (order) {
        const idx = statusFlow.indexOf(order.status);
        order.status = statusFlow[Math.min(idx + 1, statusFlow.length - 1)];
        toast.success("Status diperbarui");
    }
};

const cancelOrder = (id: string) => {
    orders.value = orders.value.filter(o => o.id !== id);
    toast.info(`Booking ${id} dibatalkan`);
};

const getStatusClass = (status: Order['status']) => {
  const map: Record<Order['status'], string> = {
    Queued: "bg-slate-100 text-slate-700",
    Confirmed: "bg-orange-50 text-orange-600",
    "In Progress": "bg-slate-900 text-white",
    Done: "bg-emerald-100 text-emerald-700",
    "Picked Up": "bg-blue-100 text-blue-700",
  };
  return map[status];
};

const selectCustomer = (c: any) => {
    customer.value = { id: c.id, name: c.name, phone: c.phone || '' };
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
                <div class="leading-tight">
                    <h1 class="text-base font-bold">Service Terminal</h1>
                    <p class="text-xs text-slate-400 font-medium -mt-0.5">Service Center · Terminal #03</p>
                </div>
            </div>

            <div class="hidden md:flex items-center gap-2 bg-slate-100 p-1 rounded-full">
                <button
                  @click="tab = 'new'"
                  :class="['px-6 h-8 rounded-full text-xs font-bold uppercase tracking-wider transition-all', tab === 'new' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900']"
                >
                  <Plus class="inline h-3.5 w-3.5 -mt-0.5 mr-1" /> New Booking
                </button>
                <button
                  @click="tab = 'track'"
                  :class="['px-6 h-8 rounded-full text-xs font-bold uppercase tracking-wider transition-all relative', tab === 'track' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900']"
                >
                  <Wrench class="inline h-3.5 w-3.5 -mt-0.5 mr-1" /> Pipeline
                  <Badge v-if="orders.length > 0" class="absolute -top-1 -right-1 bg-primary text-white scale-75">{{ orders.length }}</Badge>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" class="rounded-full h-9 gap-2 text-slate-500">
                    <History class="h-4 w-4" /> Riwayat
                </Button>
            </div>
        </div>
    </header>

    <main class="mx-auto p-4 max-w-[1600px]">
        <div v-if="tab === 'new'" class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6">
            <!-- ====== LEFT: Catalog ====== -->
            <section class="space-y-5">
                <div class="bg-white rounded-3xl border border-slate-200 p-4 space-y-4">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                            <Input v-model="search" placeholder="Cari layanan, sparepart, atau jasa..."
                                class="h-12 pl-11 rounded-2xl bg-slate-50 border-0 text-sm focus-visible:ring-primary/20" />
                        </div>
                    </div>

                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1 no-scrollbar">
                        <button v-for="cat in categories" :key="cat"
                            @click="activeCat = cat" :class="cn(
                                'shrink-0 h-11 px-5 rounded-2xl flex items-center gap-2 text-sm font-bold transition border',
                                activeCat === cat
                                    ? 'bg-slate-900 text-white border-slate-900'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                            )">
                            {{ cat }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-4">
                    <button v-for="s in filteredServices" :key="s.id"
                        @click="addToCart(s)"
                        class="group relative flex flex-col text-left rounded-2xl bg-white border border-slate-200 p-3 transition hover:border-primary hover:shadow-lg hover:-translate-y-0.5">
                        <div class="aspect-square w-full rounded-xl bg-slate-50 flex items-center justify-center mb-3 group-hover:bg-primary/5 transition-colors text-4xl">
                            {{ s.emoji }}
                        </div>

                        <h3 class="text-[13px] font-bold leading-snug line-clamp-2 min-h-[2.4em] mb-2 text-slate-800">
                            {{ s.name }}
                        </h3>

                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-slate-50">
                            <div>
                                <p class="text-sm font-bold text-slate-900 tabular-nums">
                                    {{ fmtIdr(s.price) }}
                                </p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                                    {{ s.duration || 60 }} mins
                                </p>
                            </div>
                            <span class="h-8 w-8 rounded-full bg-slate-100 group-hover:bg-primary group-hover:text-white flex items-center justify-center transition shrink-0">
                                <Plus class="h-4 w-4" />
                            </span>
                        </div>
                    </button>
                </div>
            </section>

            <!-- ====== RIGHT: Booking Panel ====== -->
            <aside class="space-y-4">
                <div class="bg-white rounded-3xl border border-slate-200 flex flex-col shadow-sm sticky top-20">
                    <div class="p-6 border-b border-slate-100 space-y-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Detail Booking</h2>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">Lengkapi informasi customer dan jadwal</p>
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Customer</label>
                                <Input v-model="customer.name" placeholder="Nama lengkap..." class="h-11 rounded-xl bg-slate-50 border-0" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">WhatsApp</label>
                                <Input v-model="customer.phone" placeholder="08xxx..." class="h-11 rounded-xl bg-slate-50 border-0" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Tanggal</label>
                                    <Input v-model="bookingDate" type="date" class="h-11 rounded-xl bg-slate-50 border-0" />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Waktu</label>
                                    <select v-model="bookingTime" class="w-full h-11 rounded-xl bg-slate-50 border-0 px-3 text-sm font-medium focus:ring-primary/20">
                                        <option v-for="t in timeSlots" :key="t" :value="t">{{ t }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400 ml-1">Pilih Staff</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button v-for="e in employees" :key="e.id"
                                        @click="staff = e.id"
                                        :class="cn(
                                            'p-3 rounded-xl border text-left transition',
                                            staff === e.id ? 'border-primary bg-primary/5' : 'border-slate-100 bg-slate-50 hover:bg-slate-100'
                                        )">
                                        <p class="text-[11px] font-bold text-slate-900 leading-tight">{{ e.name }}</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase">{{ e.position }}</p>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div class="p-6 flex-1 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Layanan Terpilih ({{ cart.length }})</h3>
                            <button v-if="cart.length > 0" @click="clearAll" class="text-[10px] font-bold text-rose-500 uppercase hover:underline">Reset</button>
                        </div>
                        
                        <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                            <div v-if="cart.length === 0" class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                                <p class="text-xs text-slate-400 font-medium">Belum ada layanan dipilih</p>
                            </div>
                            <div v-for="item in cart" :key="item.id" class="flex gap-3 items-center group">
                                <div class="h-10 w-10 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-xl shrink-0">
                                    {{ item.emoji }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold truncate text-slate-800">{{ item.name }}</h4>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase">{{ fmtIdr(item.price) }}</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <button @click="adjustQty(item.id, -1)" class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition">
                                        <ChevronLeft class="h-3 w-3" />
                                    </button>
                                    <span class="text-xs font-bold w-4 text-center">{{ item.qty }}</span>
                                    <button @click="adjustQty(item.id, 1)" class="h-6 w-6 rounded-full bg-slate-900 text-white flex items-center justify-center hover:bg-slate-800 transition">
                                        <ChevronRight class="h-3 w-3" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-slate-50 border-t border-slate-100 rounded-b-3xl space-y-4">
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span class="font-bold text-slate-800">{{ fmtIdr(subtotal) }}</span></div>
                            <div class="flex justify-between text-slate-500"><span>Pajak (11%)</span><span class="font-bold text-slate-800">{{ fmtIdr(tax) }}</span></div>
                        </div>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Grand Total</p>
                                <p class="text-2xl font-bold text-slate-900 tracking-tight">{{ fmtIdr(total) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Estimasi Durasi</p>
                                <p class="text-xs font-bold text-slate-800">{{ Math.floor(totalDuration/60) }}j {{ totalDuration%60 }}m</p>
                            </div>
                        </div>
                        <Button @click="confirmBooking" class="w-full h-14 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold uppercase tracking-widest shadow-lg shadow-slate-900/10">
                            Konfirmasi Booking
                        </Button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- ---------- TRACKING TAB ---------- -->
        <div v-else class="space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-3xl border border-slate-200 shadow-sm">
                <div class="flex gap-2">
                    <button class="px-6 h-10 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-900 text-white">Semua</button>
                    <button class="px-6 h-10 rounded-full text-xs font-bold uppercase tracking-wider bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">Aktif</button>
                    <button class="px-6 h-10 rounded-full text-xs font-bold uppercase tracking-wider bg-white text-slate-600 border border-slate-200 hover:bg-slate-50">Selesai</button>
                </div>
                <div class="relative w-full md:w-80">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <Input placeholder="Cari booking..." class="h-10 pl-10 rounded-full bg-slate-50 border-0 text-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-6">
                <article v-for="o in orders" :key="o.id"
                    class="bg-white rounded-3xl border border-slate-200 p-6 space-y-5 hover:border-primary transition group relative overflow-hidden shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ o.id }}</p>
                            <h3 class="text-xl font-bold text-slate-900">{{ o.customer }}</h3>
                            <p class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mt-1">
                                <Phone class="h-3 w-3" /> {{ o.phone }}
                            </p>
                        </div>
                        <span :class="cn('px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm', getStatusClass(o.status))">
                            {{ o.status }}
                        </span>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-2xl space-y-3">
                        <ul class="space-y-2">
                            <li v-for="(it, i) in o.items" :key="i" class="flex justify-between text-[11px] font-bold uppercase tracking-tight text-slate-600">
                                <span>• {{ it.name }}</span>
                                <span class="text-slate-400">×{{ it.qty }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between border-t border-slate-50 pt-4">
                        <div class="space-y-0.5">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Jadwal</p>
                            <p class="text-[11px] font-bold text-slate-800 uppercase flex items-center gap-1.5">
                                <CalendarIcon class="h-3 w-3 text-primary" /> {{ o.scheduledAt }}
                            </p>
                        </div>
                        <div class="text-right space-y-0.5">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Staff</p>
                            <p class="text-[11px] font-bold text-slate-800 uppercase flex items-center gap-1.5">
                                <User class="h-3 w-3 text-primary" /> {{ o.staff }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Total</p>
                            <p class="text-xl font-bold text-slate-900 tabular-nums tracking-tight">{{ fmtIdr(o.total) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <Button size="icon" variant="ghost" class="rounded-xl h-10 w-10 text-slate-300 hover:text-rose-500 hover:bg-rose-50" @click="cancelOrder(o.id)">
                                <X class="h-5 w-5" />
                            </Button>
                            <Button v-if="o.status !== 'Picked Up'" size="sm" class="rounded-xl h-10 px-6 bg-slate-900 text-white font-bold uppercase tracking-wider hover:bg-primary transition shadow-md" @click="advanceStatus(o.id)">
                                Update Status
                            </Button>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </main>

    <!-- Receipt Dialog -->
    <Dialog v-model:open="receiptOpen">
        <DialogContent class="rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl max-w-md bg-white">
            <div class="bg-slate-900 p-8 text-white text-center">
                <div class="mx-auto h-16 w-16 rounded-2xl bg-primary flex items-center justify-center mb-4">
                    <CheckCircle2 class="h-8 w-8" />
                </div>
                <h3 class="text-2xl font-bold tracking-tight">Booking Berhasil!</h3>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-1">Invoice {{ lastOrder?.id }}</p>
            </div>

            <div class="p-8 space-y-6">
                <div v-if="lastOrder" class="space-y-3 bg-slate-50 p-6 rounded-2xl border border-slate-100">
                    <div class="flex justify-between text-xs"><span class="text-slate-400 font-bold uppercase">Customer</span><span class="font-bold text-slate-800">{{ lastOrder.customer }}</span></div>
                    <div class="flex justify-between text-xs"><span class="text-slate-400 font-bold uppercase">Jadwal</span><span class="font-bold text-slate-800">{{ lastOrder.scheduledAt }}</span></div>
                    <div class="flex justify-between text-xs"><span class="text-slate-400 font-bold uppercase">Staff</span><span class="font-bold text-slate-800">{{ lastOrder.staff }}</span></div>
                    <div class="border-t border-slate-200 pt-3 mt-1">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-400 font-bold uppercase">Total Bayar</span>
                            <span class="text-2xl font-bold text-primary tracking-tight">{{ fmtIdr(lastOrder.total) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-2">
                    <Button class="rounded-xl h-12 bg-slate-900 text-white font-bold uppercase tracking-widest" @click="receiptOpen = false">
                        Selesai
                    </Button>
                    <div class="flex gap-2">
                        <Button variant="outline" class="flex-1 rounded-xl h-11 text-xs font-bold uppercase border-slate-200">WhatsApp</Button>
                        <Button variant="outline" class="flex-1 rounded-xl h-11 text-xs font-bold uppercase border-slate-200">Print</Button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: hsl(var(--border));
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: hsl(var(--muted-foreground));
}
</style>
