<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Search, ShoppingCart, Plus, Minus, 
    CreditCard, Banknote, QrCode, X, 
    Package, History, Landmark
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';


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
    return cart.value.reduce((total, item) => total + (item.qty * item.price), 0);
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
        },
        onError: () => {
            toast.error('Gagal memproses transaksi. Silakan periksa kembali input Anda.');
        },
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
<Head title="POS Penjualan" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-[calc(100vh-64px)] overflow-hidden">
        <!-- Main Product Area -->
        <div class="flex-1 flex flex-col p-6 overflow-hidden">
            <div class="flex items-center justify-between mb-6">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input 
                        v-model="searchQuery"
                        placeholder="Cari produk (Nama, SKU, atau Barcode)..." 
                        class="pl-10 h-11 border-muted bg-background shadow-none focus-visible:ring-1"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="icon" class="rounded-none shadow-none" title="History (Coming Soon)">
                        <History class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <div 
                        v-for="produk in filteredProduks" 
                        :key="produk.id"
                        @click="addToCart(produk)"
                        class="group cursor-pointer border border-muted bg-card hover:border-primary/50 transition-all p-4 flex flex-col gap-3 h-full relative"
                    >
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Badge variant="secondary" class="bg-primary/10 text-primary border-none text-[10px] uppercase font-bold tracking-tighter shadow-none">
                                <Plus class="h-3 w-3 mr-1" /> Tambah
                            </Badge>
                        </div>
                        
                        <div class="aspect-square bg-muted/30 flex items-center justify-center border border-muted/50 overflow-hidden">
                            <Package class="h-10 w-10 text-muted-foreground/20" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <h3 class="text-sm font-bold truncate group-hover:text-primary transition-colors">{{ produk.nama }}</h3>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground">{{ produk.base_unit }}</span>
                                <span class="text-[10px] font-mono text-muted-foreground/60">{{ produk.sku || '-' }}</span>
                            </div>
                            <div class="mt-2 text-base font-black text-primary">{{ formatCurrency(produk.price) }}</div>
                            <div class="mt-1 flex items-center justify-between text-[10px]">
                                <span class="uppercase tracking-widest text-muted-foreground/40 font-bold">Stok: {{ produk.stock }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="filteredProduks.length === 0" class="h-full flex flex-col items-center justify-center text-muted-foreground py-20 grayscale opacity-30">
                    <Search class="h-16 w-16 mb-4" />
                    <p class="text-xs uppercase tracking-widest font-bold font-mono">Produk tidak ditemukan</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Cart -->
        <div class="w-[400px] border-l border-muted bg-muted/5 flex flex-col h-full shadow-2xl">
            <div class="p-6 border-b border-muted bg-card">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-bold flex items-center">
                        <ShoppingCart class="mr-2 h-5 w-5 text-primary" />
                        Keranjang
                    </h2>
                    <Badge variant="secondary" class="rounded-none shadow-none">{{ cart.length }} items</Badge>
                </div>
                <p class="text-xs text-muted-foreground italic">Klik produk untuk menambah pesanan.</p>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-3 custom-scrollbar">
                <div 
                    v-for="(item, index) in cart" 
                    :key="index"
                    class="bg-card border border-muted p-3 flex flex-col gap-2 group animate-in slide-in-from-right-4 duration-200"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-sm font-bold truncate w-48">{{ item.nama }}</span>
                            <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-tight">{{ formatCurrency(item.price) }} / {{ item.base_unit }}</span>
                        </div>
                        <Button 
                            variant="ghost" 
                            size="icon" 
                            class="h-6 w-6 text-muted-foreground hover:text-destructive hover:bg-destructive/10 -mt-1 -mr-1"
                            @click="removeFromCart(index)"
                        >
                            <X class="h-3 w-3" />
                        </Button>
                    </div>

                    <div class="flex items-center justify-between mt-auto">
                        <div class="flex items-center border border-muted bg-muted/20">
                            <Button variant="ghost" size="icon" class="h-7 w-7 rounded-none" @click="updateQty(index, -1)">
                                <Minus class="h-3 w-3" />
                            </Button>
                            <span class="w-10 text-center text-xs font-bold font-mono">{{ item.qty }}</span>
                            <Button variant="ghost" size="icon" class="h-7 w-7 rounded-none font-bold" @click="updateQty(index, 1)">
                                <Plus class="h-3 w-3" />
                            </Button>
                        </div>
                        <div class="text-sm font-black text-right">
                            {{ formatCurrency(item.qty * item.price) }}
                        </div>
                    </div>
                </div>

                <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center py-20 opacity-20 text-muted-foreground pointer-events-none">
                    <ShoppingCart class="h-12 w-12 mb-4" />
                    <p class="text-[10px] uppercase font-bold tracking-widest text-center italic">Belum ada item pesanan.</p>
                </div>
            </div>

            <!-- Total & Checkout -->
            <div class="p-6 bg-card border-t-2 border-primary/20 space-y-4 shadow-[0_-10px_30px_rgba(0,0,0,0.05)]">
                <div class="space-y-2">
                    <div class="flex justify-between text-xs text-muted-foreground uppercase font-bold tracking-wider">
                        <span>Subtotal</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-primary font-bold border-t border-muted pt-2 uppercase tracking-widest text-lg">
                        <span>Total Akhir</span>
                        <span>{{ formatCurrency(totalAmount) }}</span>
                    </div>
                </div>

                <Dialog :open="isCheckoutOpen" @update:open="isCheckoutOpen = $event">
                    <DialogTrigger as-child>
                        <Button 
                            class="w-full h-14 text-lg font-black tracking-widest uppercase bg-primary hover:bg-primary/90 text-primary-foreground disabled:opacity-30 disabled:grayscale transition-all rounded-none shadow-lg"
                            :disabled="cart.length === 0 || totalAmount <= 0"
                        >
                            Bayar Sekarang
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-[98vw] min-w-[40rem] rounded-none border-none p-0 overflow-hidden shadow-2xl">
                        <DialogHeader class="p-6 bg-primary text-primary-foreground">
                            <DialogTitle class="text-2xl font-black uppercase tracking-widest">Selesaikan Pembayaran</DialogTitle>
                            <DialogDescription class="text-primary-foreground/60">Pilih metode pembayaran dan lengkapi detail transaksi.</DialogDescription>
                        </DialogHeader>
                        
                        <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-16">
                            <!-- Left Column: Summary & Method -->
                            <div class="space-y-6">
                                <div class="p-8 bg-muted/30 border border-muted text-center space-y-2">
                                    <span class="text-xs uppercase font-bold tracking-widest text-muted-foreground">Total Tagihan</span>
                                    <div class="text-5xl font-black text-primary">{{ formatCurrency(totalAmount) }}</div>
                                </div>

                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-50 italic">Metode Pembayaran</h4>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div 
                                            @click="form.payment_method = 'cash'"
                                            class="cursor-pointer border-2 p-4 text-center space-y-2 transition-all"
                                            :class="form.payment_method === 'cash' ? 'border-primary bg-primary/5 text-primary' : 'border-muted hover:border-muted-foreground/30'"
                                        >
                                            <Banknote class="mx-auto h-6 w-6" />
                                            <span class="text-xs font-black uppercase tracking-tight">Tunai</span>
                                        </div>
                                        <div 
                                            @click="form.payment_method = 'qris'"
                                            class="cursor-pointer border-2 p-4 text-center space-y-2 transition-all"
                                            :class="form.payment_method === 'qris' ? 'border-primary bg-primary/5 text-primary' : 'border-muted hover:border-muted-foreground/30'"
                                        >
                                            <QrCode class="mx-auto h-6 w-6" />
                                            <span class="text-xs font-black uppercase tracking-tight">QRIS</span>
                                        </div>
                                        <div 
                                            @click="form.payment_method = 'transfer'"
                                            class="cursor-pointer border-2 p-4 text-center space-y-2 transition-all"
                                            :class="form.payment_method === 'transfer' ? 'border-primary bg-primary/5 text-primary' : 'border-muted hover:border-muted-foreground/30'"
                                        >
                                            <CreditCard class="mx-auto h-6 w-6" />
                                            <span class="text-xs font-black uppercase tracking-tight">Transfer</span>
                                        </div>
                                        <div 
                                            @click="form.payment_method = 'credit'"
                                            class="cursor-pointer border-2 p-4 text-center space-y-2 transition-all"
                                            :class="form.payment_method === 'credit' ? 'border-primary bg-primary/5 text-primary' : 'border-muted hover:border-muted-foreground/30'"
                                        >
                                            <Landmark class="mx-auto h-6 w-6" />
                                            <span class="text-xs font-black uppercase tracking-tight">Piutang</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Cash Handling & Notes -->
                            <div class="space-y-6 flex flex-col justify-between">
                                <div v-if="form.payment_method === 'cash'" class="space-y-4 animate-in fade-in zoom-in duration-200">
                                    <div class="space-y-6">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-50 italic">Uang Diterima</label>
                                            <div class="relative">
                                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-bold text-muted-foreground">Rp</span>
                                                <Input 
                                                    v-model.number="form.received_amount"
                                                    type="number"
                                                    class="pl-12 h-16 text-3xl font-black border-primary/20 bg-primary/5 focus-visible:ring-primary rounded-none shadow-none"
                                                />
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-50 italic">Kembalian</label>
                                            <div class="h-16 flex items-center justify-end px-4 border border-muted bg-muted/10 text-3xl font-black text-destructive">
                                                {{ formatCurrency(changeAmount) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <Button 
                                            v-for="cash in [10000, 20000, 50000, 100000]" 
                                            :key="cash"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="rounded-none text-[10px] uppercase font-bold tracking-tighter shadow-none h-8 px-3"
                                            @click="form.received_amount = cash"
                                        >
                                            {{ formatCurrency(cash) }}
                                        </Button>
                                        <Button 
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="rounded-none text-[10px] uppercase font-bold tracking-tighter shadow-none h-8 px-3 border-primary/20 text-primary"
                                            @click="form.received_amount = totalAmount"
                                        >
                                            Uang Pas
                                        </Button>
                                    </div>
                                </div>

                                <div class="space-y-2 flex-1 flex flex-col">
                                    <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground opacity-50 italic">Catatan</label>
                                    <textarea 
                                        v-model="form.notes"
                                        class="w-full h-full min-h-[100px] border border-muted bg-muted/10 p-3 text-sm focus:outline-none focus:border-primary/50 transition-colors resize-none"
                                        placeholder="Opsional (Contoh: No Meja, Nama Pelanggan)..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="p-6 bg-muted/20 border-t border-muted">
                            <Button variant="ghost" @click="isCheckoutOpen = false" class="rounded-none uppercase tracking-widest font-bold">Batal</Button>
                            <Button 
                                :disabled="form.processing"
                                @click="handleCheckout" 
                                class="rounded-none px-10 h-11 uppercase tracking-widest font-black"
                            >
                                Konfirmasi & Simpan
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>
</AppLayout>
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
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>
