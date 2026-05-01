<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
    status_name: string;
    sequence_order: number;
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

// Get unique status codes from all services to build columns
const allStatuses = computed(() => {
    const statusMap = new Map<string, { code: string, name: string, sequence: number }>();
    
    props.services.forEach(s => {
        s.processing_statuses?.forEach(ps => {
            if (!statusMap.has(ps.status_code)) {
                statusMap.set(ps.status_code, {
                    code: ps.status_code,
                    name: ps.status_name || ps.status_code,
                    sequence: ps.sequence_order || 0
                });
            }
        });
    });

    // Fallback if no statuses defined in services
    if (statusMap.size === 0) {
        props.orders.forEach(o => {
            if (!statusMap.has(o.current_status_code)) {
                statusMap.set(o.current_status_code, {
                    code: o.current_status_code,
                    name: o.current_status_code,
                    sequence: 99
                });
            }
        });
    }

    return Array.from(statusMap.values()).sort((a, b) => a.sequence - b.sequence);
});

const ordersByStatus = computed(() => {
    const map: Record<string, Order[]> = {};
    allStatuses.value.forEach(status => {
        map[status.code] = props.orders.filter(o => o.current_status_code === status.code);
    });
    return map;
});

const getStatusColor = (code: string) => {
    const c = code.toLowerCase();
    if (c.includes('pending') || c.includes('wait')) return 'amber';
    if (c.includes('process') || c.includes('work')) return 'blue';
    if (c.includes('done') || c.includes('finish') || c.includes('ready')) return 'emerald';
    if (c.includes('pick') || c.includes('taken')) return 'slate';
    return 'indigo';
};

const fmtIdr = (cents: number) =>
  new Intl.NumberFormat("id-ID", { 
    style: "currency", 
    currency: "IDR", 
    maximumFractionDigits: 0 
  }).format(cents / 100);

</script>

<template>
<Head title="Service Board" />

<div class="h-screen flex flex-col bg-[#F1F5F9] font-sans overflow-hidden">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-8 h-20 flex items-center justify-between shrink-0 sticky top-0 z-30">
        <div class="flex items-center gap-8">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-0.5">
                    <div class="h-6 w-6 rounded-lg bg-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                        <KanbanIcon class="h-3.5 w-3.5 text-white" />
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900 tracking-tight leading-none">
                        Service Board
                    </h1>
                </div>
                <p class="text-[9px] text-slate-400 font-medium uppercase tracking-[0.2em] leading-none ml-8">Pipeline Manajemen Layanan</p>
            </div>

            <div class="h-10 w-px bg-slate-200/60"></div>

            <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                <Link :href="serviceOrders.index.url()">
                    <Button variant="ghost" size="sm" class="h-8 px-4 rounded-lg text-slate-500 font-semibold text-[10px] uppercase tracking-wider hover:bg-white hover:text-slate-900 transition-all">
                        <LayoutGrid class="h-3.5 w-3.5 mr-2" /> List View
                    </Button>
                </Link>
                <Button size="sm" class="h-8 px-4 rounded-lg bg-white text-slate-900 shadow-sm border border-slate-200/50 font-semibold text-[10px] uppercase tracking-wider">
                    <KanbanIcon class="h-3.5 w-3.5 mr-2 text-orange-500" /> Board View
                </Button>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative group">
                <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors" />
                <input 
                    placeholder="Cari order atau pelanggan..." 
                    class="h-10 w-72 pl-10 pr-4 rounded-xl bg-slate-50 border-slate-200/60 focus:bg-white focus:ring-4 focus:ring-orange-500/5 focus:border-orange-500/30 text-xs font-medium transition-all outline-none placeholder:font-normal" 
                />
            </div>
            
            <Link :href="serviceOrders.create.url()">
                <Button class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white h-10 px-6 font-semibold uppercase tracking-widest text-[10px] shadow-xl shadow-slate-900/10 active:scale-95 transition-all">
                    <Plus class="h-4 w-4 mr-2 text-orange-400" /> Create Order
                </Button>
            </Link>
        </div>
    </header>

    <!-- Board Area -->
    <main class="flex-1 overflow-x-auto overflow-y-hidden p-8 flex gap-8 custom-scrollbar bg-slate-50/50">
        <div v-for="status in allStatuses" :key="status.code" class="w-80 shrink-0 flex flex-col gap-6">
            <!-- Column Header -->
            <div class="flex items-center justify-between px-3 h-10">
                <div class="flex items-center gap-3">
                    <div :class="[
                        'h-2 w-2 rounded-full shadow-sm',
                        getStatusColor(status.code) === 'amber' ? 'bg-amber-400 shadow-amber-200' :
                        getStatusColor(status.code) === 'blue' ? 'bg-blue-500 shadow-blue-200' :
                        getStatusColor(status.code) === 'emerald' ? 'bg-emerald-500 shadow-emerald-200' :
                        'bg-slate-400 shadow-slate-200'
                    ]"></div>
                    <h3 class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.2em]">{{ status.name }}</h3>
                    <div class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-400 shadow-sm tabular-nums">
                        {{ ordersByStatus[status.code]?.length || 0 }}
                    </div>
                </div>
                <button class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-white hover:shadow-sm text-slate-300 hover:text-slate-600 transition-all">
                    <MoreVertical class="h-4 w-4" />
                </button>
            </div>

            <!-- Column Body -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-3 custom-scrollbar pb-20 -mr-3">
                <div v-for="order in ordersByStatus[status.code]" :key="order.id" 
                    class="group relative bg-white rounded-[1.5rem] border border-slate-200/80 p-5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/50 hover:border-orange-500/30 hover:-translate-y-1.5 transition-all duration-500 cursor-pointer overflow-hidden active:scale-[0.98]"
                    @click="router.visit(serviceOrders.show.url(order.id))"
                >
                    <!-- Status Gradient Accent -->
                    <div :class="[
                        'absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 opacity-[0.03] transition-opacity group-hover:opacity-[0.08]',
                        getStatusColor(status.code) === 'amber' ? 'bg-amber-500' :
                        getStatusColor(status.code) === 'blue' ? 'bg-blue-500' :
                        getStatusColor(status.code) === 'emerald' ? 'bg-emerald-500' :
                        'bg-slate-500'
                    ]" style="border-radius: 40%"></div>

                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-medium font-mono text-slate-400 group-hover:text-orange-500 transition-colors uppercase tracking-tight">
                                #{{ order.order_number }}
                            </span>
                            <div class="h-0.5 w-4 bg-slate-100 group-hover:w-8 group-hover:bg-orange-500 transition-all duration-500"></div>
                        </div>
                        <div class="flex -space-x-2">
                             <div class="h-6 w-6 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center text-[8px] font-semibold text-slate-400">
                                <User class="h-3 w-3" />
                             </div>
                        </div>
                    </div>

                    <h4 class="text-sm font-semibold text-slate-900 group-hover:text-orange-600 transition-colors mb-1.5 line-clamp-1 pr-4 leading-tight">
                        {{ order.party?.name }}
                    </h4>
                    
                    <div class="flex items-center gap-2 mb-4">
                        <div class="h-5 px-2 rounded-md bg-slate-50 flex items-center gap-1.5 border border-slate-100">
                            <Wrench class="h-2.5 w-2.5 text-slate-400" />
                            <span class="text-[9px] font-semibold uppercase text-slate-500 tracking-wider">{{ order.service?.name }}</span>
                        </div>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-50 flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-[9px] font-semibold text-slate-300 uppercase tracking-widest mb-0.5">Estimate</p>
                            <p class="text-xs font-semibold text-slate-900 tabular-nums leading-none">{{ fmtIdr(order.total_amount) }}</p>
                        </div>
                        <div class="h-8 w-8 rounded-xl bg-slate-50 group-hover:bg-orange-500 group-hover:text-white flex items-center justify-center text-slate-300 transition-all shadow-sm">
                            <ChevronRight class="h-4 w-4" />
                        </div>
                    </div>
                </div>

                <!-- Empty State for Column -->
                <div v-if="!ordersByStatus[status.code]?.length" class="h-32 flex flex-col items-center justify-center border-2 border-dashed border-slate-200/50 rounded-[2rem] opacity-40 bg-slate-100/50 group hover:opacity-100 transition-all duration-500">
                    <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center mb-2 shadow-sm group-hover:scale-110 transition-transform">
                        <Clock class="h-4 w-4 text-slate-300 group-hover:text-orange-400" />
                    </div>
                    <p class="text-[9px] font-semibold text-slate-400 uppercase tracking-[0.2em]">Queue Empty</p>
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
