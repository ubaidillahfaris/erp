<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Search, ShoppingBag, Info } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';

// Actions
import { store as storeAction } from '@/actions/App/Http/Controllers/ServiceOrderController';

// Components
import ServiceProductCard from '@/components/ServiceOrders/ServiceProductCard.vue';
import ServiceOrderPanel from '@/components/ServiceOrders/ServiceOrderPanel.vue';
import ServiceMetaFields from '@/components/ServiceOrders/ServiceMetaFields.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    products: any[];
    customers: any[];
    warehouses: any[];
    defaultWarehouseId: number;
}>();

// ============ State ============
const searchQuery = ref('');

const form = useForm({
    customer_id: null as number | null,
    order_type: 'laundry',
    estimated_at: '',
    notes: '',
    metadata: {
        weight_kg: 0,
        service_type: 'reguler', // reguler, express, kilat
    },
    items: [] as any[],
});

// ============ Computed ============
const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    const query = searchQuery.value.toLowerCase();
    return props.products.filter(p => p.name.toLowerCase().includes(query));
});

const subtotal = computed(() => {
    return form.items.reduce((sum: number, item: any) => sum + (item.price * item.qty), 0);
});

// ============ Methods ============
const addToCart = (product: any) => {
    const existing = form.items.find(item => item.product_id === product.id);
    if (existing) {
        existing.qty++;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            unit_id: product.unit_id,
            unit_symbol: product.unit_symbol,
            price: product.price,
            qty: 1,
        });
    }
};

const incrementQty = (productId: number) => {
    const item = form.items.find(i => i.product_id === productId);
    if (item) item.qty++;
};

const decrementQty = (productId: number) => {
    const index = form.items.findIndex(i => i.product_id === productId);
    if (index > -1) {
        if (form.items[index].qty > 1) {
            form.items[index].qty--;
        } else {
            form.items.splice(index, 1);
        }
    }
};

const submit = () => {
    if (!form.customer_id) {
        toast.error('Pilih pelanggan terlebih dahulu');
        return;
    }
    if (form.items.length === 0) {
        toast.error('Pilih minimal satu layanan');
        return;
    }
    
    form.post(storeAction().url, {
        onSuccess: () => {
            toast.success('Order berhasil dibuat');
            form.reset();
        },
        onError: (errors) => {
            Object.values(errors).forEach(err => toast.error(err as string));
        }
    });
};
</script>

<template>
    <Head title="Order Laundry" />

    <div class="flex flex-col lg:flex-row h-[calc(100vh-64px)] bg-slate-50 font-sans overflow-hidden">
        <!-- Left Side: Service Catalog -->
        <div class="flex-1 flex flex-col min-w-0 border-r border-slate-200">
            <!-- Header Catalog -->
            <div class="p-6 bg-white border-b border-slate-100 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-900 tracking-tight flex items-center gap-2">
                            <ShoppingBag class="h-5 w-5 text-primary" />
                            Catalog Layanan
                        </h1>
                        <p class="text-xs text-slate-400 font-medium">Pilih layanan jasa yang tersedia</p>
                    </div>
                    <div class="relative w-72">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                        <Input 
                            v-model="searchQuery"
                            placeholder="Cari layanan..." 
                            class="pl-9 h-10 bg-slate-50 border-none ring-offset-transparent focus-visible:ring-1 focus-visible:ring-primary/20 rounded-xl"
                        />
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                    <ServiceProductCard 
                        v-for="product in filteredProducts" 
                        :key="product.id"
                        :product="product"
                        @add="addToCart"
                    />
                </div>
                
                <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center py-20 opacity-20">
                    <Info class="h-12 w-12 mb-4" />
                    <p class="font-semibold uppercase tracking-widest text-sm">Layanan tidak ditemukan</p>
                </div>
            </div>
        </div>

        <!-- Right Side: Order Summary Panel -->
        <ServiceOrderPanel 
            v-model:customerId="form.customer_id"
            :customers="customers"
            :items="form.items"
            :subtotal="subtotal"
            :processing="form.processing"
            :errors="form.errors"
            @increment="incrementQty"
            @decrement="decrementQty"
            @submit="submit"
        >
            <template #metadata>
                <ServiceMetaFields 
                    :order-type="form.order_type"
                    v-model:metadata="form.metadata"
                    v-model:estimated-at="form.estimated_at"
                />
            </template>
        </ServiceOrderPanel>
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
