<script setup lang="ts">
import { Head, router, Link, usePoll } from '@inertiajs/vue3';
import { ShoppingBag, Calendar, Plus } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';

// Actions
import { updateStatus as updateStatusAction, create as createAction, pay as payAction } from '@/actions/App/Http/Controllers/ServiceOrderController';

// Components
import OrderKanbanCard from '@/components/ServiceOrders/OrderKanbanCard.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    orders: any[];
}>();

// Inertia v2: Auto-poll data every 10 seconds to keep the board fresh
usePoll(10000);

const updateStatus = (id: number, status: string) => {
    router.patch(updateStatusAction({ service_order: id }).url, {
        status
    }, {
        onSuccess: () => toast.success('Status berhasil diperbarui'),
    });
};

const handlePay = (order: any) => {
    // Navigate to payment or trigger payment action
    // For now, we can use the pay action
    router.post(payAction({ service_order: order.id }).url, {}, {
        onSuccess: () => toast.success('Pembayaran berhasil diproses'),
    });
};
</script>

<template>
    <Head title="Board Order Laundry" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] font-sans">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Board Order Laundry</h1>
                <p class="text-sm text-slate-400 font-medium">Pantau dan kelola antrian cuci pelanggan</p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" class="rounded-xl border-slate-200 bg-white">
                    <Calendar class="h-4 w-4 mr-2" />
                    Hari Ini
                </Button>
                <Link :href="createAction().url">
                    <Button class="rounded-xl shadow-xl shadow-primary/20">
                        <Plus class="h-4 w-4 mr-2" />
                        Order Baru
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Kanban Columns -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full items-start">
            <!-- Pending Column -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></div>
                        <h2 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Antrian Baru</h2>
                    </div>
                    <Badge variant="secondary" class="bg-slate-100 text-slate-500 font-bold border-none">{{ orders.filter(o => o.status === 'pending').length }}</Badge>
                </div>
                
                <div class="space-y-4">
                    <OrderKanbanCard 
                        v-for="order in orders.filter(o => o.status === 'pending')" 
                        :key="order.id"
                        :order="order"
                        @update-status="updateStatus"
                    />
                </div>
            </div>

            <!-- Processing Column -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                        <h2 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Sedang Dicuci</h2>
                    </div>
                    <Badge variant="secondary" class="bg-slate-100 text-slate-500 font-bold border-none">{{ orders.filter(o => o.status === 'processing').length }}</Badge>
                </div>

                <div class="space-y-4">
                    <OrderKanbanCard 
                        v-for="order in orders.filter(o => o.status === 'processing')" 
                        :key="order.id"
                        :order="order"
                        @update-status="updateStatus"
                    />
                </div>
            </div>

            <!-- Ready Column -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                        <h2 class="font-bold text-slate-900 text-sm uppercase tracking-wider">Siap Diambil</h2>
                    </div>
                    <Badge variant="secondary" class="bg-slate-100 text-slate-500 font-bold border-none">{{ orders.filter(o => o.status === 'ready').length }}</Badge>
                </div>

                <div class="space-y-4">
                    <OrderKanbanCard 
                        v-for="order in orders.filter(o => o.status === 'ready')" 
                        :key="order.id"
                        :order="order"
                        @update-status="updateStatus"
                        @pay="handlePay"
                    />
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-32 opacity-20">
            <ShoppingBag class="h-16 w-16 mb-4" />
            <p class="text-lg font-bold uppercase tracking-widest text-slate-400">Belum ada order aktif</p>
        </div>
    </div>
</template>
