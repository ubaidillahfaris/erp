<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { 
  ArrowLeft, Plus, Search, Calendar as CalendarIcon, Clock, User, Phone, 
  Trash2, CreditCard, Receipt, CheckCircle2, Wrench, PauseCircle, PlayCircle, 
  PackageCheck, Users, ChevronLeft, ChevronRight, Briefcase, X, Filter, ShoppingBag
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
    <Head title="Service POS" />

    <div class="p-0 lg:p-4 space-y-4">
        <!-- ===== Top bar (Compact ERP Style) ===== -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-card p-6 rounded-[2rem] border border-border shadow-sm">
          <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-inner">
              <Briefcase class="h-6 w-6" />
            </div>
            <div class="leading-tight">
              <h1 class="text-xl font-black tracking-tight">Service Terminal</h1>
              <p class="text-[11px] text-muted-foreground font-bold uppercase tracking-widest mt-0.5">Booking & Order Pipeline</p>
            </div>
          </div>

          <div class="flex items-center gap-2 bg-secondary/50 p-1 rounded-full border border-border/50">
            <button
              @click="tab = 'new'"
              :class="['px-6 h-10 rounded-full text-xs font-black uppercase tracking-widest transition-all', tab === 'new' ? 'bg-card text-foreground shadow-sm ring-1 ring-border' : 'text-muted-foreground hover:text-foreground']"
            >
              <Plus class="inline h-3.5 w-3.5 -mt-0.5 mr-1.5" /> New Booking
            </button>
            <button
              @click="tab = 'track'"
              :class="['px-6 h-10 rounded-full text-xs font-black uppercase tracking-widest transition-all relative', tab === 'track' ? 'bg-card text-foreground shadow-sm ring-1 ring-border' : 'text-muted-foreground hover:text-foreground']"
            >
              <Wrench class="inline h-3.5 w-3.5 -mt-0.5 mr-1.5" /> Pipeline
              <Badge v-if="orders.length > 0" class="absolute -top-1 -right-1 bg-primary text-white border-white scale-75">{{ orders.length }}</Badge>
            </button>
          </div>
        </header>

        <div v-if="tab === 'new'" class="grid grid-cols-1 xl:grid-cols-[1fr_420px] gap-6">
            <!-- ---------- LEFT: Catalog ---------- -->
            <section class="rounded-[2.5rem] bg-card border border-border p-8 space-y-8 shadow-sm">
              <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="relative flex-1">
                  <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input
                    v-model="search"
                    placeholder="Search services (e.g. Cuci Lipat, AC Service...)"
                    class="h-14 pl-12 rounded-[1.25rem] bg-background border-none ring-offset-transparent focus-visible:ring-primary/20 focus-visible:bg-card transition-all text-sm font-medium shadow-inner"
                  />
                </div>
                
                <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide no-scrollbar">
                  <button
                      v-for="c in categories"
                      :key="c"
                      @click="activeCat = c"
                      :class="['shrink-0 h-11 px-6 rounded-2xl text-[11px] font-black uppercase tracking-widest border transition-all', 
                        activeCat === c ? 'bg-foreground text-card border-foreground shadow-lg' : 'bg-card border-border text-muted-foreground hover:border-muted hover:bg-secondary'
                      ]"
                  >
                      {{ c }}
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-3 gap-5">
                <button
                    v-for="s in filteredServices"
                    :key="s.id"
                    @click="addToCart(s)"
                    class="text-left rounded-[2rem] border border-border/50 p-6 hover:border-primary/40 hover:shadow-2xl hover:shadow-primary/5 transition-all bg-card group relative overflow-hidden"
                >
                    <div class="flex items-start justify-between relative z-10">
                      <div class="text-4xl bg-secondary h-16 w-16 rounded-2xl flex items-center justify-center group-hover:bg-primary/10 group-hover:scale-110 transition-all duration-500 shadow-inner">{{ s.emoji }}</div>
                      <Badge variant="outline" class="text-[9px] uppercase tracking-[0.2em] font-black border-border bg-background/50 backdrop-blur-sm px-2 py-1">{{ s.category }}</Badge>
                    </div>
                    <div class="mt-6 relative z-10">
                        <h4 class="font-black text-foreground text-lg leading-tight line-clamp-2 min-h-[3rem] group-hover:text-primary transition-colors tracking-tight">{{ s.name }}</h4>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-border/50">
                          <span class="text-[10px] text-muted-foreground font-black uppercase tracking-widest inline-flex items-center gap-1.5">
                            <Clock class="h-3.5 w-3.5 text-primary" /> {{ s.duration || 60 }}m
                          </span>
                          <span class="font-black text-xl text-primary tabular-nums tracking-tighter">{{ fmtIdr(s.price) }}</span>
                        </div>
                    </div>
                    <!-- Hover Decoration -->
                    <div class="absolute -right-4 -bottom-4 h-24 w-24 bg-primary/5 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity" />
                </button>
                
                <div v-if="filteredServices.length === 0" class="col-span-full text-center py-24 bg-secondary/30 rounded-[2.5rem] border border-dashed border-border">
                    <p class="text-muted-foreground font-black uppercase tracking-widest text-[11px]">No services found</p>
                </div>
              </div>
            </section>

            <!-- ---------- RIGHT: Booking Side Panel ---------- -->
            <aside class="space-y-4">
                <div class="rounded-[2.5rem] bg-card border border-border p-8 flex flex-col gap-8 shadow-xl shadow-border/20 sticky top-6">
                    <!-- Section: Customer -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] flex items-center gap-2">
                          <span class="h-1 w-4 bg-primary rounded-full" /> Customer Information
                        </h3>
                        <div class="space-y-3">
                            <Input
                                v-model="customer.name"
                                placeholder="Full Name"
                                class="h-12 rounded-xl border-border bg-secondary/50 focus-visible:bg-card focus-visible:ring-primary/20 font-bold"
                            />
                            <div class="relative">
                                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="customer.phone"
                                    placeholder="WhatsApp / Phone"
                                    class="h-12 rounded-xl pl-12 border-border bg-secondary/50 focus-visible:bg-card focus-visible:ring-primary/20 font-bold"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Schedule -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] flex items-center gap-2">
                          <span class="h-1 w-4 bg-primary rounded-full" /> Schedule & Priority
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <Input
                                type="date"
                                v-model="bookingDate"
                                class="h-12 rounded-xl border-border bg-secondary/50 font-bold"
                            />
                            <div class="relative">
                              <select
                                  v-model="bookingTime"
                                  class="w-full h-12 rounded-xl border border-border bg-secondary/50 px-4 text-sm font-black text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20 appearance-none"
                              >
                                  <option v-for="t in timeSlots" :key="t" :value="t">{{ t }}</option>
                              </select>
                              <Clock class="absolute right-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Staff -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] flex items-center gap-2">
                          <span class="h-1 w-4 bg-primary rounded-full" /> Assigned Staff
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                v-for="s in employees"
                                :key="s.id"
                                @click="staff = s.id"
                                :class="['text-left p-4 rounded-2xl border transition-all duration-300 relative overflow-hidden', 
                                    staff === s.id ? 'border-primary bg-primary/5 ring-4 ring-primary/5' : 'border-border hover:bg-secondary/50'
                                ]"
                            >
                                <p class="font-black text-foreground text-[11px] uppercase tracking-tight">{{ s.name }}</p>
                                <p class="text-[9px] text-muted-foreground font-black uppercase tracking-tighter mt-0.5">{{ s.position }}</p>
                                <CheckCircle2 v-if="staff === s.id" class="absolute -right-1 -bottom-1 h-6 w-6 text-primary/20" />
                            </button>
                        </div>
                    </div>

                    <!-- Section: Items -->
                    <div class="flex-1 space-y-4">
                        <div class="flex items-center justify-between">
                          <h3 class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] flex items-center gap-2">
                            <span class="h-1 w-4 bg-primary rounded-full" /> Selection ({{ cart.length }})
                          </h3>
                          <button v-if="cart.length > 0" @click="clearAll" class="text-[9px] font-black text-destructive uppercase tracking-widest hover:underline">Clear all</button>
                        </div>
                        
                        <div class="max-h-[320px] overflow-y-auto -mx-2 px-2 space-y-3 custom-scrollbar">
                            <div v-if="cart.length === 0" class="text-center py-12 bg-secondary/30 rounded-[2rem] border border-dashed border-border">
                                <p class="text-[10px] text-muted-foreground font-black uppercase tracking-widest opacity-60">Cart is empty</p>
                            </div>
                            <div v-else v-for="l in cart" :key="l.id" class="flex items-center gap-4 p-4 rounded-[1.5rem] bg-secondary/40 border border-border/50 hover:border-border transition-all group">
                                <span class="text-3xl bg-card h-12 w-12 rounded-xl flex items-center justify-center shadow-sm">{{ l.emoji }}</span>
                                <div class="flex-1 min-w-0">
                                  <p class="text-[13px] font-black text-foreground truncate leading-tight">{{ l.name }}</p>
                                  <p class="text-[10px] text-muted-foreground font-bold uppercase tracking-tighter mt-0.5">{{ fmtIdr(l.price) }} · {{ l.duration || 60 }}m</p>
                                </div>
                                <div class="flex flex-col items-end gap-2">
                                  <div class="flex items-center gap-1 bg-card rounded-full p-0.5 border border-border shadow-sm">
                                    <button @click="adjustQty(l.id, -1)" class="h-6 w-6 flex items-center justify-center hover:bg-secondary rounded-full text-muted-foreground transition-colors" aria-label="Decrease">
                                      <ChevronLeft class="h-3 w-3" />
                                    </button>
                                    <span class="w-4 text-center text-xs font-black text-foreground tabular-nums">{{ l.qty }}</span>
                                    <button @click="adjustQty(l.id, 1)" class="h-6 w-6 flex items-center justify-center hover:bg-secondary rounded-full text-primary transition-colors" aria-label="Increase">
                                      <ChevronRight class="h-3 w-3" />
                                    </button>
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Totals & Checkout -->
                    <div class="space-y-6 border-t border-border pt-8">
                        <div class="space-y-2">
                          <div class="flex justify-between text-[11px] font-black text-muted-foreground uppercase tracking-widest"><span>Subtotal</span><span class="text-foreground">{{ fmtIdr(subtotal) }}</span></div>
                          <div class="flex justify-between text-[11px] font-black text-muted-foreground uppercase tracking-widest"><span>Tax (11%)</span><span class="text-foreground">{{ fmtIdr(tax) }}</span></div>
                          <div class="flex justify-between text-[11px] font-black text-primary uppercase tracking-widest"><span>Est. Duration</span><span>{{ Math.floor(totalDuration/60) }}h {{ totalDuration%60 }}m</span></div>
                        </div>
                        
                        <div class="flex items-end justify-between pt-2">
                            <div class="flex flex-col">
                              <span class="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">Grand Total</span>
                              <span class="text-3xl font-black text-foreground tracking-tighter tabular-nums">{{ fmtIdr(total) }}</span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-secondary flex items-center justify-center border border-border">
                              <Receipt class="h-6 w-6 text-muted-foreground" />
                            </div>
                        </div>

                        <Button class="w-full h-16 rounded-[1.5rem] bg-foreground hover:bg-primary text-card font-black text-lg uppercase tracking-widest shadow-2xl shadow-foreground/10 transition-all duration-500 active:scale-95 group" @click="confirmBooking">
                            <CheckCircle2 class="h-6 w-6 mr-3 group-hover:animate-bounce" /> Complete Booking
                        </Button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- ---------- TRACKING TAB ---------- -->
        <div v-else class="space-y-6">
          <Tabs defaultValue="all" class="space-y-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-card p-4 rounded-[2rem] border border-border shadow-sm">
              <TabsList class="rounded-full bg-secondary p-1 h-12">
                <TabsTrigger value="all" class="rounded-full px-10 font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-card data-[state=active]:shadow-lg">All Status</TabsTrigger>
                <TabsTrigger value="active" class="rounded-full px-10 font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-foreground data-[state=active]:text-card">Active</TabsTrigger>
                <TabsTrigger value="done" class="rounded-full px-10 font-black uppercase text-[10px] tracking-widest data-[state=active]:bg-primary data-[state=active]:text-card">Completed</TabsTrigger>
              </TabsList>
              
              <div class="flex items-center gap-3">
                <div class="relative w-64">
                  <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                  <Input placeholder="Filter orders..." class="h-10 pl-10 rounded-full bg-secondary/50 border-none text-[11px] font-bold" />
                </div>
                <Button variant="outline" size="sm" class="rounded-full h-10 px-6 font-black uppercase text-[10px] tracking-widest text-muted-foreground hover:bg-secondary">
                  <Filter class="h-3.5 w-3.5 mr-2" /> More Filters
                </Button>
              </div>
            </div>

            <div v-for="view in ['all', 'active', 'done']" :key="view">
              <TabsContent :value="view" class="m-0">
                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-6">
                  <article 
                    v-for="o in orders.filter(o => {
                        if (view === 'active') return ['Queued', 'Confirmed', 'In Progress'].includes(o.status);
                        if (view === 'done') return ['Done', 'Picked Up'].includes(o.status);
                        return true;
                    })" 
                    :key="o.id" 
                    class="rounded-[2.5rem] bg-card border border-border p-8 space-y-6 hover:shadow-2xl hover:border-primary/20 transition-all group relative overflow-hidden"
                  >
                        <div class="flex items-start justify-between relative z-10">
                          <div>
                            <p class="font-black text-[10px] text-muted-foreground/50 uppercase tracking-[0.2em] mb-1">{{ o.id }}</p>
                            <h3 class="font-black text-2xl text-foreground group-hover:text-primary transition-colors tracking-tight">{{ o.customer }}</h3>
                            <p class="text-[11px] text-muted-foreground font-bold inline-flex items-center gap-2 mt-2 bg-secondary/50 px-3 py-1 rounded-full border border-border/50">
                              <Phone class="h-3.5 w-3.5 text-primary" /> {{ o.phone }}
                            </p>
                          </div>
                          <span :class="['inline-flex items-center gap-2 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.2em] shadow-sm ring-1 ring-border', getStatusClass(o.status)]">
                            <span class="h-2 w-2 rounded-full bg-current animate-pulse" /> {{ o.status }}
                          </span>
                        </div>

                        <div class="rounded-3xl bg-secondary/30 p-6 border border-border/50 space-y-4 relative z-10">
                            <ul class="space-y-3">
                              <li v-for="(it, i) in o.items" :key="i" class="flex justify-between text-[11px] font-black uppercase tracking-tight text-foreground/70">
                                <span class="flex items-center gap-2"><span class="h-1 w-1 bg-primary rounded-full" /> {{ it.name }}</span>
                                <span class="bg-card px-2.5 py-1 rounded-lg border border-border text-foreground text-[10px]">×{{ it.qty }}</span>
                              </li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-between border-t border-border/50 pt-6 relative z-10">
                          <div class="space-y-1">
                            <span class="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Schedule</span>
                            <p class="text-[11px] font-black text-foreground uppercase tracking-tighter inline-flex items-center gap-2">
                              <CalendarIcon class="h-3.5 w-3.5 text-primary" /> {{ o.scheduledAt }}
                            </p>
                          </div>
                          <div class="text-right space-y-1">
                            <span class="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Assigned Staff</span>
                            <p class="text-[11px] font-black text-foreground uppercase tracking-tighter inline-flex items-center gap-2">
                              <User class="h-3.5 w-3.5 text-primary" /> {{ o.staff }}
                            </p>
                          </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 relative z-10">
                          <div class="flex flex-col">
                            <span class="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Total Amount</span>
                            <span class="font-black text-2xl text-primary tracking-tighter tabular-nums">{{ fmtIdr(o.total) }}</span>
                          </div>
                          <div class="flex gap-2">
                            <Button size="sm" variant="ghost" class="rounded-2xl h-12 w-12 p-0 text-muted-foreground hover:text-destructive hover:bg-destructive/10 border border-border hover:border-destructive/30 transition-all" @click="cancelOrder(o.id)">
                              <X class="h-5 w-5" />
                            </Button>
                            <Button v-if="o.status !== 'Picked Up'" size="sm" class="rounded-2xl h-12 px-8 bg-foreground text-card font-black text-[10px] uppercase tracking-[0.2em] hover:bg-primary transition-all shadow-lg active:scale-95" @click="advanceStatus(o.id)">
                                <template v-if="o.status === 'Queued'">
                                    <PlayCircle class="h-4 w-4 mr-2" /> Confirm
                                </template>
                                <template v-else-if="o.status === 'Confirmed'">
                                    <Wrench class="h-4 w-4 mr-2" /> Process
                                </template>
                                <template v-else-if="o.status === 'In Progress'">
                                    <CheckCircle2 class="h-4 w-4 mr-2" /> Ready
                                </template>
                                <template v-else-if="o.status === 'Done'">
                                    <PackageCheck class="h-4 w-4 mr-2" /> Pickup
                                </template>
                            </Button>
                          </div>
                        </div>
                        
                        <!-- Card Decoration -->
                        <div class="absolute -left-10 -top-10 h-40 w-40 bg-primary/5 rounded-full blur-[60px] pointer-events-none" />
                  </article>
                </div>
              </TabsContent>
            </div>
          </Tabs>
        </div>
      </div>

      <!-- ===== Premium Receipt Dialog ===== -->
      <Dialog v-model:open="receiptOpen">
        <DialogContent class="rounded-[3rem] sm:max-w-md border-none shadow-3xl overflow-hidden p-0 bg-card">
          <div class="bg-foreground p-10 text-card text-center relative overflow-hidden">
            <div class="absolute -right-8 -top-8 h-48 w-48 rounded-full bg-primary/20 blur-[80px]" />
            <div class="absolute -left-8 -bottom-8 h-48 w-48 rounded-full bg-primary/10 blur-[80px]" />
            
            <div class="mx-auto h-24 w-24 rounded-[2rem] bg-card text-primary flex items-center justify-center shadow-2xl relative z-10 scale-110 border-4 border-foreground">
              <CheckCircle2 class="h-12 w-12" />
            </div>
            <DialogTitle class="text-3xl font-black mt-8 relative z-10 tracking-tighter">Booking Confirmed!</DialogTitle>
            <p class="text-card/60 font-black text-[11px] uppercase tracking-[0.3em] mt-2 relative z-10">Invoice {{ lastOrder?.id }}</p>
          </div>

          <div class="p-10 space-y-8 bg-card">
            <div v-if="lastOrder" class="space-y-4">
              <div class="rounded-[2rem] bg-secondary/50 p-8 space-y-4 border border-border">
                <div class="flex justify-between items-center"><span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Customer</span><span class="font-black text-foreground uppercase text-xs">{{ lastOrder.customer }}</span></div>
                <div class="flex justify-between items-center"><span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Schedule</span><span class="font-black text-foreground uppercase text-xs">{{ lastOrder.scheduledAt }}</span></div>
                <div class="flex justify-between items-center"><span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Assigned Staff</span><span class="font-black text-foreground uppercase text-xs">{{ lastOrder.staff }}</span></div>
                <div class="flex justify-between items-end border-t border-border pt-6 mt-2">
                  <div class="flex flex-col">
                    <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Grand Total</span>
                    <span class="font-black text-3xl text-primary tracking-tighter tabular-nums">{{ fmtIdr(lastOrder.total) }}</span>
                  </div>
                  <Badge class="bg-primary/10 text-primary border-primary/20 mb-1">PAID</Badge>
                </div>
              </div>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
              <Button class="rounded-2xl h-16 bg-foreground text-card font-black uppercase tracking-[0.2em] shadow-xl hover:bg-primary transition-all active:scale-95" @click="receiptOpen = false">
                <span class="flex items-center"><Receipt class="h-5 w-5 mr-3" /> Back to Terminal</span>
              </Button>
              <div class="flex gap-3">
                <Button variant="outline" class="flex-1 rounded-2xl h-14 border-border text-muted-foreground font-black uppercase text-[10px] tracking-widest hover:bg-secondary">
                  <Phone class="h-4 w-4 mr-2 text-emerald-500" /> WhatsApp
                </Button>
                <Button variant="outline" class="flex-1 rounded-2xl h-14 border-border text-muted-foreground font-black uppercase text-[10px] tracking-widest hover:bg-secondary">
                  <Printer class="h-4 w-4 mr-2" /> Print PDF
                </Button>
              </div>
            </div>
          </div>
        </DialogContent>
      </Dialog>
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
