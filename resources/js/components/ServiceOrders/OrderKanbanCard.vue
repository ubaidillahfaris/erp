<script setup lang="ts">
import { Package, Clock, Timer, CheckCircle2, ShoppingBag, ArrowRight } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

interface Order {
    id: number;
    order_number: string;
    status: string;
    customer: { name: string };
    metadata: Record<string, any>;
    items: any[];
    estimated_at?: string;
    sale_id?: number;
}

const props = defineProps<{
    order: Order;
}>();

const emit = defineEmits<{
    'update-status': [id: number, status: string];
    'pay': [order: Order];
}>();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const subtotal = Number(props.order.items.reduce((sum: number, i: any) => sum + Number(i.subtotal), 0));
</script>

<template>
    <Card 
        class="p-5 border-slate-200 rounded-2xl shadow-none hover:shadow-xl hover:shadow-slate-200/50 transition-all border-l-4 bg-white"
        :class="{
            'border-l-amber-500': order.status === 'pending',
            'border-l-blue-500': order.status === 'processing',
            'border-l-emerald-500': order.status === 'ready',
        }"
    >
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ order.order_number }}</p>
                <h3 class="font-bold text-slate-900 text-base leading-tight">{{ order.customer.name }}</h3>
            </div>
            <Badge 
                class="font-bold text-[10px] uppercase border"
                :class="{
                    'bg-amber-50 text-amber-600 border-amber-100': order.status === 'pending',
                    'bg-blue-50 text-blue-600 border-blue-100': order.status === 'processing',
                    'bg-emerald-50 text-emerald-600 border-emerald-100': order.status === 'ready',
                }"
            >
                {{ order.status === 'pending' ? 'Antri' : order.status === 'processing' ? 'Dicuci' : 'Siap' }}
            </Badge>
        </div>
        
        <div class="flex items-center gap-4 mb-4">
            <div class="flex items-center gap-1.5" v-if="order.metadata?.weight_kg">
                <Package class="h-3.5 w-3.5 text-slate-400" />
                <span class="text-xs font-bold text-slate-600">{{ order.metadata.weight_kg }} Kg</span>
            </div>
            <div class="flex items-center gap-1.5">
                <Clock class="h-3.5 w-3.5 text-slate-400" />
                <span class="text-xs font-bold text-slate-600">{{ order.estimated_at ? formatDate(order.estimated_at) : 'Tanpa Estimasi' }}</span>
            </div>
        </div>

        <div class="flex flex-col gap-3 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between">
                <span class="font-bold text-primary text-sm">{{ formatCurrency(subtotal) }}</span>
                
                <!-- Status specific badges/indicators -->
                <div v-if="order.status === 'ready'" class="flex items-center gap-1.5">
                    <template v-if="order.sale_id">
                        <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                        <span class="text-[10px] font-bold text-emerald-600 uppercase">Lunas</span>
                    </template>
                    <template v-else>
                        <div class="h-1.5 w-1.5 rounded-full bg-rose-500"></div>
                        <span class="text-[10px] font-bold text-rose-600 uppercase">Belum Bayar</span>
                    </template>
                </div>
            </div>

            <!-- Contextual Actions -->
            <div class="flex gap-2">
                <template v-if="order.status === 'pending'">
                    <Button @click="emit('update-status', order.id, 'processing')" size="sm" class="w-full h-8 rounded-lg text-[11px] font-bold uppercase tracking-wider gap-1.5">
                        Mulai Cuci
                        <ArrowRight class="h-3 w-3" />
                    </Button>
                </template>
                
                <template v-else-if="order.status === 'processing'">
                    <Button @click="emit('update-status', order.id, 'ready')" size="sm" variant="outline" class="w-full h-8 rounded-lg text-[11px] font-bold uppercase tracking-wider border-emerald-200 text-emerald-600 hover:bg-emerald-50 gap-1.5">
                        <CheckCircle2 class="h-3 w-3" />
                        Selesai
                    </Button>
                </template>

                <template v-else-if="order.status === 'ready'">
                    <div class="flex flex-col w-full gap-2">
                        <Button 
                            v-if="!order.sale_id" 
                            @click="emit('pay', order)"
                            size="sm" 
                            class="w-full h-8 rounded-lg bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-200/50 text-[10px] font-bold uppercase tracking-wider"
                        >
                            Bayar Sekarang
                        </Button>
                        <Button 
                            @click="emit('update-status', order.id, 'picked_up')" 
                            :disabled="!order.sale_id" 
                            class="w-full h-10 rounded-xl bg-slate-900 hover:bg-slate-800 text-[11px] font-bold uppercase tracking-wider gap-2"
                        >
                            <ShoppingBag class="h-3.5 w-3.5" />
                            Serahkan
                        </Button>
                    </div>
                </template>
            </div>
        </div>
    </Card>
</template>
