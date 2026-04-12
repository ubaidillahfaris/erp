<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Search, ShoppingCart, Plus, Minus, 
    CreditCard, Banknote, QrCode, X, 
    Package, History, Landmark, ChevronRight 
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Dialog, DialogContent, DialogHeader, DialogTitle, 
    DialogFooter, DialogTrigger, DialogDescription 
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    produks: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan (POS)', href: '/pos' },
];

const searchQuery = ref('');
const cart = ref<any[]>([]);
const isCheckoutOpen = ref(false);

const filteredProduks = computed(() => {
    if (!searchQuery.value) return props.produks;
    const q = searchQuery.value.toLowerCase();
    return props.produks.filter(p => 
        p.nama.toLowerCase().includes(q) || 
        p.sku?.toLowerCase().includes(q) || 
        p.barcode?.toLowerCase().includes(q)
    );
});

const addToCart = (produk: any) => {
    const existingIndex = cart.value.findIndex(item => item.id === produk.id);
    if (existingIndex > -1) {
        cart.value[existingIndex].qty += 1;
    } else {
        cart.value.push({
            ...produk,
            qty: 1,
            produk_id: produk.id,
        });
    }
};

const removeFromCart = (index: number) => {
    cart.value.splice(index, 1);
};

const updateQty = (index: number, delta: number) => {
    const newQty = cart.value[index].qty + delta;
    if (newQty > 0) {
        cart.value[index].qty = newQty;
    } else {
        removeFromCart(index);
    }
};

const totalAmount = computed(() => {
    return cart.value.reduce((total, item) => total + (item.qty * (item.price || 0)), 0);
});

const form = useForm({
    tanggal: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    received_amount: 0,
    change_amount: 0,
    notes: '',
    items: [] as any[],
});

const changeAmount = computed(() => {
    if (form.payment_method !== 'cash') return 0;
    const diff = form.received_amount - totalAmount.value;
    return diff > 0 ? diff : 0;
});

const handleCheckout = () => {
    form.items = cart.value.map(item => ({
        produk_id: item.produk_id,
        satuan_id: item.satuan_id,
        qty: item.qty,
        price: item.price,
        cost: item.cost,
    }));

    form.change_amount = changeAmount.value;
    
    form.post('/pos', {
        onSuccess: () => {
            cart.value = [];
            isCheckoutOpen.value = false;
            toast.success('Transaksi berhasil disimpan!');
        },
        onError: () => {
            toast.error('Gagal memproses transaksi.');
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
</script>

<template>
    <Head title="POS Penjualan" />

    <div class="flex h-[calc(100vh-64px)] overflow-hidden bg-[#F8F9FA]">
        
        <!-- ====== MAIN CONTENT: Product Selector ====== -->
        <div class="flex-1 flex flex-col p-8 overflow-hidden gap-8">
            
            <PageHeader 
                title="Point of Sale" 
                description="Terminal Penjualan & Checkout Kasir"
                back-href="/dashboard"
            >
                <template #actions>
                    <div class="relative w-full max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground" />
                        <Input 
                            v-model="searchQuery"
                            placeholder="Cari nama, SKU, atau barcode..." 
                            class="pl-9 h-10 border-slate-200 bg-white shadow-none focus-visible:ring-accent/5 rounded-xl text-sm"
                        />
                    </div>
                </template>
            </PageHeader>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 h-fit pb-10">
                    <div 
                        v-for="produk in filteredProduks" 
                        :key="produk.id"
                        @click="addToCart(produk)"
                        class="group cursor-pointer bg-white border border-slate-200 p-4 rounded-xl shadow-none hover:shadow-none hover:border-accent/30 transition-all flex flex-col gap-4"
                    >
                        <div class="aspect-square bg-[#F8F9FA] rounded-lg flex items-center justify-center border border-slate-200 overflow-hidden relative group-hover:bg-accent/5 transition-colors">
                            <Package class="h-10 w-10 text-muted-foreground group-hover:text-accent transition-all" />
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-accent/5-[1px]">
                                <div class="bg-accent text-white h-8 w-8 rounded-full flex items-center justify-center shadow-none ">
                                    <Plus class="h-4 w-4" />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 px-0.5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-[13px] font-bold text-foreground leading-tight truncate">{{ produk.nama }}</h3>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold font-mono text-muted-foreground uppercase">#{{ produk.sku || '--' }}</span>
                                <span class="text-xs text-muted-foreground italic">In Stock: {{ produk.stok }}</span>
                            </div>
                            <div class="mt-2 text-md font-bold text-foreground tabular-nums">{{ formatCurrency(produk.price) }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredProduks.length === 0" class="h-full flex flex-col items-center justify-center py-20 opacity-20">
                    <Search class="h-12 w-12 mb-4" />
                    <p class="text-xs font-bold uppercase tracking-widest text-center">Produk tidak ditemukan</p>
                </div>
            </div>
        </div>

        <!-- ====== SIDEBAR: Cart ====== -->
        <div class="w-[420px] bg-white border-l border-slate-200 flex flex-col h-full shadow-none">
            
            <div class="p-8 border-b border-slate-200 flex items-center justify-between">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <ShoppingCart class="h-5 w-5 text-accent" />
                        Live Order
                    </h2>
                    <p class="text-xs font-medium text-muted-foreground italic">Current session tracking enabled</p>
                </div>
                <Badge variant="secondary" class="h-6 rounded-xl px-2 bg-muted/30 text-muted-foreground font-bold tabular-nums">
                    {{ cart.length }} line items
                </Badge>
            </div>

            <!-- Items List -->
            <div class="flex-1 overflow-y-auto p-6 flex flex-col gap-4 custom-scrollbar">
                <div 
                    v-for="(item, index) in cart" 
                    :key="index"
                    class="bg-white border border-slate-200 p-4 rounded-xl flex flex-col gap-3 group animate-in slide-in-from-right-2 duration-300"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-0.5 min-w-0 pr-4">
                            <span class="text-[13px] font-bold text-foreground truncate">{{ item.nama }}</span>
                            <span class="text-xs font-bold text-muted-foreground uppercase tracking-tight">{{ formatCurrency(item.price) }} / {{ item.base_unit || 'PCS' }}</span>
                        </div>
                        <button 
                            class="h-7 w-7 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-all -mt-1 -mr-1"
                            @click="removeFromCart(index)"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-1">
                        <div class="flex items-center bg-secondary/50 rounded-lg p-0.5">
                            <button @click="updateQty(index, -1)" class="h-7 w-7 flex items-center justify-center rounded-xl hover:bg-white hover:shadow-none text-muted-foreground transition-all">
                                <Minus class="h-3 w-3" />
                            </button>
                            <span class="w-10 text-center text-[12px] font-bold tabular-nums">{{ item.qty }}</span>
                            <button @click="updateQty(index, 1)" class="h-7 w-7 flex items-center justify-center rounded-xl hover:bg-white hover:shadow-none text-muted-foreground transition-all">
                                <Plus class="h-3 w-3" />
                            </button>
                        </div>
                        <span class="text-[14px] font-bold text-foreground tabular-nums">
                            {{ formatCurrency(item.qty * (item.price || 0)) }}
                        </span>
                    </div>
                </div>

                <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center py-20 opacity-20 text-muted-foreground">
                    <div class="h-16 w-16 border-2 border-dashed border-muted-foreground rounded-full flex items-center justify-center mb-4">
                        <ShoppingCart class="h-6 w-6" />
                    </div>
                    <p class="text-xs font-bold font-mono italic uppercase tracking-widest text-center">Keranjang masih kosong</p>
                </div>
            </div>

            <!-- Totals & Checkout -->
            <div class="p-8 border-t border-slate-200 bg-secondary/5 flex flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <div class="flex justify-between items-center px-1">
                        <span class="text-xs font-bold text-muted-foreground uppercase tracking-widest opacity-60">Subtotal Order</span>
                        <span class="text-[13px] font-bold text-foreground tabular-nums">{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <div class="h-px bg-border/20 mx-1" />
                    <div class="flex justify-between items-center px-1 py-1">
                        <span class="text-xs font-bold text-foreground uppercase tracking-[0.1em]">Total Invoice</span>
                        <span class="text-2xl font-bold text-accent tabular-nums">{{ formatCurrency(totalAmount) }}</span>
                    </div>
                </div>

                <Dialog :open="isCheckoutOpen" @update:open="isCheckoutOpen = $event">
                    <DialogTrigger as-child>
                        <Button 
                            class="w-full h-14 text-sm font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white disabled:opacity-30 rounded-xl shadow-none shadow-accent/20 transition-all font-mono"
                            :disabled="cart.length === 0 || totalAmount <= 0"
                        >
                            Bayar Sekarang
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-2xl rounded-xl border-none p-0 overflow-hidden shadow-none ">
                        <DialogHeader class="p-8 bg-accent text-white">
                            <div class="flex flex-col gap-1">
                                <DialogTitle class="text-2xl font-bold uppercase tracking-widest">Selesaikan Pembayaran</DialogTitle>
                                <DialogDescription class="text-white/60">Pilih metode pembayaran dan konfirmasi transaksi.</DialogDescription>
                            </div>
                        </DialogHeader>
                        
                        <div class="p-10 flex flex-col gap-10 bg-white">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <!-- Summary & Method -->
                                <div class="flex flex-col gap-6">
                                    <div class="p-8 bg-[#F8F9FA] border border-slate-200 rounded-xl text-center flex flex-col gap-1 translate-y-0 hover:-translate-y-1 transition-transform">
                                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground opacity-50">Total Tagihan</span>
                                        <div class="text-4xl font-bold text-accent tabular-nums">{{ formatCurrency(totalAmount) }}</div>
                                    </div>

                                    <div class="flex flex-col gap-3">
                                        <h4 class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-1">Metode Pembayaran</h4>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button 
                                                v-for="method in [
                                                    { id: 'cash', label: 'Tunai', icon: Banknote },
                                                    { id: 'qris', label: 'QRIS', icon: QrCode },
                                                    { id: 'transfer', label: 'Transfer', icon: CreditCard },
                                                    { id: 'credit', label: 'Piutang', icon: Landmark }
                                                ]"
                                                :key="method.id"
                                                @click="form.payment_method = method.id"
                                                class="flex flex-col items-center justify-center p-4 rounded-xl border-2 transition-all gap-2"
                                                :class="form.payment_method === method.id ? 'border-accent bg-accent/5 text-accent' : 'border-slate-200 hover:border-slate-200 hover:bg-secondary/20 text-muted-foreground'"
                                            >
                                                <component :is="method.icon" class="h-6 w-6" />
                                                <span class="text-xs font-bold uppercase tracking-tight">{{ method.label }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Details -->
                                <div class="flex flex-col gap-6">
                                    <div v-if="form.payment_method === 'cash'" class="flex flex-col gap-4 animate-in fade-in slide-in-from-right-4 duration-300">
                                        <div class="flex flex-col gap-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-1">Uang Diterima</label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-muted-foreground">Rp</span>
                                                <Input 
                                                    v-model.number="form.received_amount"
                                                    type="number"
                                                    class="pl-12 h-14 text-2xl font-bold border-slate-200 bg-[#F8F9FA] focus:border-accent/30 rounded-xl shadow-none "
                                                />
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-1">Kembalian</label>
                                            <div class="h-14 flex items-center justify-end px-6 border border-slate-200 bg-secondary/20 rounded-xl text-2xl font-bold text-destructive tabular-nums">
                                                {{ formatCurrency(changeAmount) }}
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap gap-2 mt-1">
                                            <button 
                                                v-for="cash in [10000, 20000, 50000, 100000]" 
                                                :key="cash"
                                                @click="form.received_amount = cash"
                                                class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold hover:bg-muted transition-all"
                                            >
                                                {{ cash / 1000 }}K
                                            </button>
                                            <button @click="form.received_amount = totalAmount" class="px-3 py-1.5 rounded-lg border border-accent/20 bg-accent/5 text-accent text-xs font-bold hover:bg-accent/10 transition-all">
                                                Uang Pas
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-2 flex-1">
                                        <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-1">Catatan Transaksi</label>
                                        <textarea 
                                            v-model="form.notes"
                                            class="w-full flex-1 min-h-[100px] border border-slate-200 bg-[#F8F9FA] rounded-xl p-4 text-sm focus:outline-none focus:border-accent/30 transition-all resize-none font-medium"
                                            placeholder="Opsional: Nama meja, ID member..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="p-8 bg-secondary/10 border-t border-slate-200 flex items-center justify-end gap-3">
                            <Button variant="ghost" @click="isCheckoutOpen = false" class="text-xs font-bold uppercase tracking-widest rounded-lg">Kembali</Button>
                            <Button 
                                :disabled="form.processing"
                                @click="handleCheckout" 
                                class="h-12 px-10 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white rounded-lg shadow-none shadow-accent/10"
                            >
                                Konfirmasi & Simpan
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.05);
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>
