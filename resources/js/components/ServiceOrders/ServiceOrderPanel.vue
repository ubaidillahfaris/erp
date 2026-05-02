<script setup lang="ts">
import { ShoppingBag, UserPlus, Minus, Plus, Wallet } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import CreatableSelect from '@/components/ui/input/CreatableSelect.vue';

interface CartItem {
    product_id: number;
    name: string;
    price: number;
    qty: number;
    unit_symbol: string;
}

const props = defineProps<{
    customers: any[];
    customerId: number | null;
    items: CartItem[];
    subtotal: number;
    processing: boolean;
    errors: Record<string, any>;
}>();

const emit = defineEmits<{
    'update:customerId': [id: number | null];
    'increment': [productId: number];
    'decrement': [productId: number];
    'submit': [];
}>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
<div class="w-full lg:w-[400px] bg-white flex flex-col shadow-2xl shadow-slate-200/50 relative z-10">
    <!-- Customer Section -->
    <div class="p-6 border-b border-slate-100 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-900 flex items-center gap-2">
                <UserPlus class="h-4 w-4 text-slate-400" />
                Detail Pelanggan
            </h2>
        </div>
        <CreatableSelect :model-value="customerId" @update:model-value="emit('update:customerId', $event)"
            :options="customers" placeholder="Pilih Pelanggan..." class="rounded-xl" />
        <p v-if="errors.customer_id" class="text-xs text-rose-500 font-medium">{{ errors.customer_id }}</p>
    </div>

    <!-- Metadata Slot -->
    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <slot name="metadata" />
    </div>

    <!-- Cart Items -->
    <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-900">Ringkasan Order</h2>
            <Badge variant="secondary" class="rounded-full bg-slate-100 text-slate-500 font-bold border-none">{{
                items.length }} Items</Badge>
        </div>

        <div class="space-y-3">
            <div v-for="item in items" :key="item.product_id"
                class="flex items-center gap-3 p-3 rounded-2xl bg-white border border-slate-100 hover:border-slate-200 transition-colors">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-900 text-[13px] truncate">{{ item.name }}</p>
                    <p class="text-[11px] text-slate-400 font-medium">{{ formatCurrency(item.price) }} / {{
                        item.unit_symbol }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="emit('decrement', item.product_id)"
                        class="h-7 w-7 rounded-lg border border-slate-100 flex items-center justify-center hover:bg-slate-50 active:scale-90 transition-all">
                        <Minus class="h-3.5 w-3.5 text-slate-400" />
                    </button>
                    <span class="font-bold text-slate-900 text-sm w-6 text-center tabular-nums">{{ item.qty }}</span>
                    <button @click="emit('increment', item.product_id)"
                        class="h-7 w-7 rounded-lg bg-primary/5 flex items-center justify-center hover:bg-primary/10 active:scale-90 transition-all">
                        <Plus class="h-3.5 w-3.5 text-primary" />
                    </button>
                </div>
            </div>

            <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-10 opacity-20">
                <ShoppingBag class="h-8 w-8 mb-2" />
                <p class="text-[10px] font-bold uppercase tracking-widest">Belum ada item</p>
            </div>
        </div>
    </div>

    <!-- Footer: Totals & Submit -->
    <div class="p-6 border-t border-slate-100 bg-white">
        <div class="flex items-center justify-between mb-6">
            <span class="text-slate-400 font-bold text-sm">TOTAL TAGIHAN</span>
            <span class="text-2xl font-black text-slate-900 tracking-tight">{{ formatCurrency(subtotal) }}</span>
        </div>

        <Button @click="emit('submit')" :disabled="processing"
            class="w-full h-14 rounded-2xl bg-primary hover:bg-primary/90 text-white font-bold text-lg shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
            <Wallet v-if="!processing" class="h-5 w-5" />
            <span v-if="processing">Memproses...</span>
            <span v-else>Simpan & Cetak Order</span>
        </Button>

        <p class="text-[10px] text-center text-slate-400 mt-4 font-bold uppercase tracking-widest">Syarat & Ketentuan
            Berlaku</p>
    </div>
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
</style>
