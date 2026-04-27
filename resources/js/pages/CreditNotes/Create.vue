<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { 
    ArrowLeft, Search, ShoppingCart, User, 
    Calendar, Tag, AlertCircle, Info, Loader2,
    CheckCircle2, History, RotateCcw
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import { getSaleDetails, store as storeAction } from '@/actions/App/Http/Controllers/CreditNoteController';
import { index as salesIndex } from '@/actions/App/Http/Controllers/SalesController';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    sale?: any;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Nota Kredit', href: '/credit-notes' },
    { title: 'Tambah Retur', href: '#' },
];

const selectedSaleId = ref<number | null>(props.sale?.id || null);
const selectedSale = ref<any>(props.sale || null);
const isLoadingSale = ref(false);
const salesOptions = ref<any[]>([]);
const isSearching = ref(false);

const form = useForm({
    sale_id: props.sale?.id || null,
    reason: '',
    items: [] as any[],
});

// Initialize items if sale is provided from props
if (props.sale) {
    form.items = props.sale.items.map((item: any) => ({
        sale_item_id: item.id,
        product_name: item.product.name,
        unit_name: item.unit.name,
        qty_sold: item.qty,
        returnable_qty: item.returnable_qty,
        price: item.price,
        quantity_returned: 0,
    }));
}

const handleSearchSales = async (q: string) => {
    isSearching.value = true;
    try {
        // Use sales index with wantsJson and search parameter
        const response = await axios.get(salesIndex().url, { 
            params: { search: q },
            headers: { 'Accept': 'application/json' }
        });
        
        // Map from pagination data (response.data.data)
        salesOptions.value = response.data.data.map((s: any) => ({
            value: s.id,
            label: `${s.invoice_number} - ${s.sale_customer?.customer?.name ?? 'Common Customer'} (${new Date(s.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}) - ${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(s.total_amount)}`,
        }));
    } catch (error) {
        console.error('Search failed', error);
    } finally {
        isSearching.value = false;
    }
};

const handleSaleSelect = async (saleId: number) => {
    if (!saleId) return;
    
    isLoadingSale.value = true;
    try {
        const response = await axios.get(getSaleDetails({ sale: saleId }).url);
        selectedSale.value = response.data;
        form.sale_id = saleId;
        
        // Initialize form items
        form.items = selectedSale.value.items.map((item: any) => ({
            sale_item_id: item.id,
            product_name: item.product.name,
            unit_name: item.unit.name,
            qty_sold: item.qty,
            returnable_qty: item.returnable_qty,
            price: item.price,
            quantity_returned: 0,
        }));
    } catch (error) {
        console.error('Failed to load sale details', error);
    } finally {
        isLoadingSale.value = false;
    }
};

watch(selectedSaleId, (newVal) => {
    if (newVal) {
        handleSaleSelect(Number(newVal));
    } else {
        selectedSale.value = null;
        form.sale_id = null;
        form.items = [];
    }
});

const submit = () => {
    // Filter out items with 0 quantity returned
    const itemsToReturn = form.items.filter(i => i.quantity_returned > 0);
    
    if (itemsToReturn.length === 0) {
        alert('Silakan tentukan barang yang ingin dikembalikan.');
        return;
    }

    form.transform((data) => ({
        ...data,
        items: itemsToReturn.map(i => ({
            sale_item_id: i.sale_item_id,
            quantity_returned: i.quantity_returned,
        })),
    })).post(storeAction().url);
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const calculateTotalReturn = () => {
    return form.items.reduce((total, item) => {
        return total + (item.quantity_returned * (item.price || 0));
    }, 0);
};

</script>

<template>
<Head title="Tambah Retur (Nota Kredit)" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader 
        title="Tambah Retur" 
        description="Pilih invoice penjualan untuk melakukan retur barang" 
        back-href="/credit-notes" 
    />

    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
        <!-- 1. SALE SELECTION -->
        <Card class="p-6 border-none shadow-sm rounded-2xl bg-white overflow-visible relative z-50">
            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-2 mb-2">
                    <div class="h-8 w-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <Search class="h-4 w-4" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900">Cari Invoice Penjualan</h3>
                </div>
                
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label>Nomor Invoice atau Nama Pelanggan</Label>
                        <Combobox 
                            v-model="selectedSaleId" 
                            :options="salesOptions" 
                            placeholder="Ketik minimal 3 karakter..." 
                            @search="handleSearchSales"
                            :loading="isSearching"
                        />
                        <p class="text-[10px] text-muted-foreground flex items-center gap-1">
                            <Info class="h-3 w-3" /> Masukkan nomor invoice (misal: INV-2604...)
                        </p>
                    </div>

                    <div v-if="selectedSale" class="p-4 rounded-xl bg-blue-50/50 border border-blue-100/50 flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="text-[11px] font-bold text-blue-600 uppercase tracking-widest">Detail Terpilih</span>
                            <Badge variant="outline" class="bg-white border-blue-200 text-blue-700 text-[10px] font-bold uppercase">
                                {{ selectedSale.status }}
                            </Badge>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-black text-slate-900">#{{ selectedSale.invoice_number }}</span>
                            <span class="text-xs text-slate-500 font-medium">{{ selectedSale.sale_customer?.customer?.name ?? 'Common Customer' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <Transition
            mode="out-in"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="isLoadingSale" class="flex flex-col items-center justify-center py-20 gap-4">
                <Loader2 class="h-8 w-8 text-primary animate-spin" />
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Memuat Detail Penjualan...</p>
            </div>

            <div v-else-if="selectedSale" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- 2. ITEMS TABLE -->
                <div class="lg:col-span-2 flex flex-col gap-6">
                    <Card class="border-none shadow-sm rounded-2xl bg-white overflow-hidden">
                        <div class="p-5 border-b border-slate-50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                                    <ShoppingCart class="h-4 w-4" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900">Daftar Barang Penjualan</h3>
                            </div>
                            <span class="text-xs font-bold text-slate-400 tabular-nums">{{ form.items.length }} Items</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50">
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100">Produk</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 text-center">Terjual</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 text-center">Retur Max</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 w-32">Qty Retur</th>
                                        <th class="px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400 border-b border-slate-100 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, index) in form.items" :key="item.sale_item_id" class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-4">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-900">{{ item.product_name }}</span>
                                                <span class="text-[11px] text-slate-400 font-medium">{{ formatCurrency(item.price) }} / {{ item.unit_name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="text-xs font-bold text-slate-600">{{ item.qty_sold }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <Badge variant="outline" class="bg-slate-50 border-slate-200 text-slate-600 font-bold text-[10px]">
                                                {{ item.returnable_qty }}
                                            </Badge>
                                        </td>
                                        <td class="px-5 py-4">
                                            <Input 
                                                type="number" 
                                                v-model="item.quantity_returned" 
                                                class="h-8 text-center font-black rounded-lg border-slate-200 focus:ring-amber-500 focus:border-amber-500"
                                                :max="item.returnable_qty"
                                                min="0"
                                            />
                                        </td>
                                        <td class="px-5 py-4 text-right tabular-nums">
                                            <span class="text-sm font-black text-slate-900">
                                                {{ formatCurrency(item.quantity_returned * item.price) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card class="p-6 border-none shadow-sm rounded-2xl bg-white flex flex-col gap-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-8 w-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-600">
                                <AlertCircle class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">Alasan & Catatan Retur</h3>
                        </div>
                        <Textarea 
                            v-model="form.reason" 
                            placeholder="Jelaskan alasan pengembalian barang (misal: Barang rusak, Salah kirim, Kadaluarsa)..." 
                            class="min-h-[100px] rounded-xl border-slate-200 focus:ring-amber-500 focus:border-amber-500 text-sm"
                        />
                        <div v-if="form.errors.reason" class="text-xs text-destructive font-bold">{{ form.errors.reason }}</div>
                    </Card>
                </div>

                <!-- 3. SUMMARY & SUBMIT -->
                <div class="flex flex-col gap-6 sticky top-24">
                    <Card class="border-none shadow-sm rounded-2xl bg-white overflow-hidden">
                        <div class="p-5 bg-amber-600 text-white flex items-center gap-3">
                            <RotateCcw class="h-5 w-5" />
                            <h3 class="font-bold tracking-tight">Ringkasan Retur</h3>
                        </div>
                        
                        <div class="p-6 flex flex-col gap-6">
                            <div class="flex flex-col gap-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 font-medium">Invoice Asal</span>
                                    <span class="font-black text-slate-900">#{{ selectedSale.invoice_number }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500 font-medium">Total Item Retur</span>
                                    <span class="font-black text-slate-900 tabular-nums">
                                        {{ form.items.filter(i => i.quantity_returned > 0).length }} Jenis
                                    </span>
                                </div>
                                <Separator class="bg-slate-100" />
                                <div class="flex flex-col gap-1 pt-2">
                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Total Nilai Retur</span>
                                    <span class="text-2xl font-black text-amber-600 tabular-nums">
                                        {{ formatCurrency(calculateTotalReturn()) }}
                                    </span>
                                </div>
                            </div>

                            <Button 
                                @click="submit" 
                                class="w-full h-12 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-black uppercase tracking-widest text-xs gap-3 shadow-lg shadow-amber-200/50 transition-all active:scale-95"
                                :disabled="form.processing || calculateTotalReturn() <= 0"
                            >
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <CheckCircle2 v-else class="h-4 w-4" />
                                Simpan Draft Retur
                            </Button>

                            <p class="text-[10px] text-center text-slate-400 italic font-medium px-4">
                                * Nota kredit akan disimpan sebagai draft. Silakan posting di halaman detail untuk memperbarui stok dan piutang.
                            </p>
                        </div>
                    </Card>

                    <Link :href="`/sales/${selectedSale.id}`" class="group">
                        <div class="p-4 rounded-xl border border-slate-200 border-dashed hover:border-accent hover:bg-accent/5 transition-all flex items-center justify-between">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Lihat Original Invoice</span>
                                <span class="text-xs font-bold text-slate-600 group-hover:text-accent transition-colors">#{{ selectedSale.invoice_number }}</span>
                            </div>
                            <History class="h-4 w-4 text-slate-300 group-hover:text-accent transition-colors" />
                        </div>
                    </Link>
                </div>

            </div>
        </Transition>

        <!-- EMPTY STATE -->
        <div v-if="!selectedSale && !isLoadingSale" class="flex flex-col items-center justify-center py-32 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="relative">
                <div class="absolute -inset-8 bg-amber-500/10 rounded-full blur-3xl animate-pulse" />
                <div class="h-20 w-20 rounded-2xl bg-white shadow-xl flex items-center justify-center text-amber-500 relative">
                    <RotateCcw class="h-10 w-10" />
                </div>
            </div>
            <div class="flex flex-col items-center text-center gap-2">
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Belum Ada Invoice Terpilih</h3>
                <p class="text-sm text-slate-500 max-w-xs font-medium">Silakan cari nomor invoice di atas untuk memulai proses pengembalian barang pelanggan.</p>
            </div>
        </div>
    </div>
</div>
</template>

<style scoped>
.font-sans {
    font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
