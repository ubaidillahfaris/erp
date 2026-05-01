<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import serviceOrders from '@/routes/service-orders';
import { 
  Plus, Search, Filter, MoreVertical, Eye, Trash2, 
  CheckCircle2, Clock, AlertCircle, Wrench, ArrowRight,
  ChevronRight, Calendar, User, LayoutGrid, Kanban as KanbanIcon
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn } from '@/lib/utils';

defineOptions({ layout: AppLayout });

interface Order {
    id: number;
    order_number: string;
    customer_type: string;
    party: { name: string };
    service: { name: string };
    current_status_code: string;
    status: string;
    total_amount: number;
}

interface ProcessingStatus {
    id: number;
    status_code: string;
    label?: string;
}

interface Service {
    id: number;
    name: string;
    processing_statuses?: ProcessingStatus[];
}

const props = defineProps<{
    orders: Order[];
    services: Service[];
}>();

// Get unique status codes from all orders or defined in services
const allStatusCodes = computed(() => {
    const codes = new Set<string>();
    props.services.forEach(s => {
        s.processing_statuses?.forEach(ps => codes.add(ps.status_code));
    });
    // Fallback to what's in orders if services don't have statuses pre-loaded
    if (codes.size === 0) {
        props.orders.forEach(o => codes.add(o.current_status_code));
    }
    return Array.from(codes);
});

const ordersByStatus = computed(() => {
    const map: Record<string, Order[]> = {};
    allStatusCodes.value.forEach(code => {
        map[code] = props.orders.filter(o => o.current_status_code === code);
    });
    return map;
});

const fmtIdr = (cents: number) =>
  new Intl.NumberFormat("id-ID", { 
    style: "currency", 
    currency: "IDR", 
    maximumFractionDigits: 0 
  }).format(cents / 100);

</script>

<template>
<Head title="Service Board" />

<div class="h-screen flex flex-col bg-[#F8F9FA]">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 px-8 h-20 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-6">
            <div class="leading-tight">
                <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <KanbanIcon class="h-5 w-5 text-primary" /> Service Board
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Pipeline Manajemen Layanan</p>
            </div>
            <div class="h-8 w-px bg-slate-100"></div>
            <nav class="flex items-center gap-2">
                <Link :href="serviceOrders.index.url()">
                    <Button variant="ghost" size="sm" class="rounded-xl text-slate-500 font-bold text-xs uppercase tracking-wider h-10 px-4">
                        <LayoutGrid class="h-4 w-4 mr-2" /> List View
                    </Button>
                </Link>
                <Button variant="secondary" size="sm" class="rounded-xl bg-slate-900 text-white font-bold text-xs uppercase tracking-wider h-10 px-4">
                    <KanbanIcon class="h-4 w-4 mr-2" /> Board View
                </Button>
            </nav>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative w-64">
                <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input placeholder="Cari order..." class="h-10 w-full pl-10 pr-4 rounded-xl bg-slate-50 border-transparent focus:bg-white focus:border-primary/20 text-sm transition-all" />
            </div>
            <Link :href="serviceOrders.create.url()">
                <Button class="rounded-xl bg-primary hover:bg-primary/90 h-10 px-6 font-bold uppercase tracking-widest text-[10px] shadow-lg shadow-primary/10">
                    <Plus class="h-4 w-4 mr-2" /> Create Order
                </Button>
            </Link>
        </div>
    </header>

    <!-- Board Area -->
    <main class="flex-1 overflow-x-auto overflow-y-hidden p-8 flex gap-6 custom-scrollbar">
        <div v-for="code in allStatusCodes" :key="code" class="w-80 shrink-0 flex flex-col gap-4">
            <!-- Column Header -->
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.15em]">{{ code }}</h3>
                    <Badge variant="secondary" class="bg-slate-200 text-slate-600 rounded-full h-5 min-w-[20px] px-1.5 flex items-center justify-center text-[10px] font-bold">
                        {{ ordersByStatus[code]?.length || 0 }}
                    </Badge>
                </div>
                <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg text-slate-400">
                    <MoreVertical class="h-4 w-4" />
                </Button>
            </div>

            <!-- Column Body -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-2 custom-scrollbar pb-10">
                <div v-for="order in ordersByStatus[code]" :key="order.id" class="group bg-white rounded-[1.5rem] border border-slate-200 p-5 shadow-sm hover:border-primary hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                    <div class="flex justify-between items-start mb-3">
                        <Badge variant="outline" class="text-[9px] font-black uppercase tracking-widest text-slate-400 border-slate-100 group-hover:border-primary/20">
                            {{ order.order_number }}
                        </Badge>
                        <Badge v-if="order.status === 'posted'" class="bg-emerald-100 text-emerald-700 text-[8px] font-black uppercase border-none">POSTED</Badge>
                    </div>

                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-primary transition-colors mb-1 line-clamp-1">
                        {{ order.party?.name }}
                    </h4>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight flex items-center gap-1.5">
                        <Wrench class="h-3 w-3" /> {{ order.service?.name }}
                    </p>

                    <div class="mt-4 pt-4 border-t border-slate-50 flex items-center justify-between">
                        <p class="text-xs font-black text-slate-900">{{ fmtIdr(order.total_amount) }}</p>
                        <Link :href="serviceOrders.show.url(order.id)">
                            <Button variant="ghost" size="sm" class="h-8 rounded-lg text-[9px] font-black uppercase tracking-widest text-slate-400 group-hover:text-primary group-hover:bg-primary/5">
                                Details <ChevronRight class="h-3 w-3 ml-1" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <div v-if="!ordersByStatus[code]?.length" class="h-32 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-[1.5rem] opacity-40">
                    <div class="h-8 w-8 rounded-full bg-slate-50 flex items-center justify-center mb-2">
                        <Plus class="h-4 w-4 text-slate-300" />
                    </div>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">No Orders</p>
                </div>
            </div>
        </div>
    </main>
</div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.05);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(0,0,0,0.1);
}
</style>
