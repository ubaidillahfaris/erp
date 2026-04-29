<script setup lang="ts">
import { ClipboardCheck } from 'lucide-vue-next';

interface Product {
    id: number;
    name: string;
    price: number;
    unit_symbol: string;
    category?: string;
}

const props = defineProps<{
    product: Product;
}>();

const emit = defineEmits<{
    add: [product: Product];
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
    <button 
        @click="emit('add', product)"
        class="group flex flex-col bg-white border border-slate-200 rounded-2xl p-4 text-left transition-all hover:shadow-xl hover:shadow-primary/5 hover:border-primary/30 active:scale-[0.98]"
    >
        <div class="h-10 w-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 group-hover:bg-primary/10 group-hover:text-primary transition-colors mb-4">
            <ClipboardCheck class="h-5 w-5" />
        </div>
        <h3 class="font-bold text-slate-900 text-sm leading-tight mb-1 group-hover:text-primary transition-colors">{{ product.name }}</h3>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-3">{{ product.unit_symbol }}</p>
        <p class="mt-auto font-bold text-primary text-sm">{{ formatCurrency(product.price) }}</p>
    </button>
</template>
