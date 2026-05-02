<script setup lang="ts">
import draggable from 'vuedraggable';
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import serviceOrdersRoutes from '@/routes/service-orders';
import {
    Search, Plus, KanbanIcon, Clock, User,
    MoreHorizontal, CheckCircle2, AlertCircle,
    ChevronRight, Pencil, Save, DollarSign, LayoutGrid,
    Settings2, Trash2, ListTodo
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { cn, fmtIdr } from '@/lib/utils';
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter, DialogDescription, DialogTrigger
} from '@/components/ui/dialog';
import { Input, InputCurrency } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';

defineOptions({ layout: AppLayout });

interface Order {
    id: number;
    order_number: string;
    customer_type: string;
    party: { name: string };
    service: { name: string };
    production_step_id: number | null;
    production_step?: { name: string; code: string };
    status: string;
    total_amount: number;
}

interface Step {
    id: number;
    name: string;
    code: string;
    sequence_order: number;
    is_final: boolean;
    is_start: boolean;
}

const props = defineProps<{
    orders: Order[];
    steps: Step[];
}>();

// --- STEP MANAGEMENT ---
const showAddStepModal = ref(false);
const stepForm = useForm({
    name: '',
    code: '',
    sequence_order: props.steps.length + 1,
    is_start: false,
    is_final: false,
});

const submitStep = () => {
    stepForm.post(serviceOrdersRoutes.steps.store.url(), {
        onSuccess: () => {
            showAddStepModal.value = false;
            stepForm.reset();
            toast.success("Step produksi berhasil ditambahkan");
        },
        onError: (err) => {
            toast.error(Object.values(err)[0] as string || "Gagal membuat step");
        }
    });
};

const deleteStep = (stepId: number) => {
    if (confirm('Hapus step ini? Pastikan tidak ada order di kolom ini.')) {
        router.delete(serviceOrdersRoutes.steps.destroy.url(stepId), {
            onSuccess: () => toast.success("Step berhasil dihapus"),
            onError: (err) => toast.error(Object.values(err)[0] as string || "Gagal menghapus step")
        });
    }
};

// --- KANBAN STATE ---
const localOrdersByStep = ref<Record<number, Order[]>>({});

// Sync props to local state for draggable
watch(() => [props.orders, props.steps], () => {
    const map: Record<number, Order[]> = {};
    props.steps.forEach(step => {
        map[step.id] = props.orders.filter(o => o.production_step_id === step.id);
    });
    // Add "Uncategorized" column if any orders have no step
    const unassigned = props.orders.filter(o => !o.production_step_id);
    if (unassigned.length > 0) {
        map[0] = unassigned;
    }
    localOrdersByStep.value = map;
}, { immediate: true, deep: true });

const onDragChange = (evt: any, stepId: number) => {
    if (evt.added) {
        const order = evt.added.element;
        router.patch(serviceOrdersRoutes.updateStep.url(order.id), {
            production_step_id: stepId
        }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Order #${order.order_number} dipindahkan ke ${props.steps.find(s => s.id === stepId)?.name}`);
            },
            onError: (err) => {
                toast.error(Object.values(err)[0] as string || "Gagal memindahkan order");
            }
        });
    }
};

// --- PRICE ADJUSTMENT STATE ---
const priceOpen = ref(false);
const selectedOrder = ref<any>(null);
const priceForm = useForm({
    total_amount: 0,
});

const openPriceModal = (order: any) => {
    selectedOrder.value = order;
    priceForm.total_amount = order.total_amount / 100;
    priceOpen.value = true;
};

const submitPriceAdjustment = () => {
    if (!selectedOrder.value) return;

    priceForm.patch(serviceOrdersRoutes.adjustPrice.url(selectedOrder.value.id), {
        onSuccess: () => {
            priceOpen.value = false;
            toast.success("Harga order berhasil diperbarui");
        },
        onError: (errors: any) => {
            toast.error(Object.values(errors)[0] as string);
        }
    });
};

const finalizeOrder = (orderId: number) => {
    if (confirm('Selesaikan order ini sekarang?')) {
        router.patch(serviceOrdersRoutes.finalize.url(orderId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success("Order telah diselesaikan"),
            onError: (err) => toast.error(Object.values(err)[0] as string || "Gagal menyelesaikan order")
        });
    }
};

const getStatusColor = (code: string) => {
    const c = code.toLowerCase();
    if (c.includes('pending') || c.includes('wait')) return 'amber';
    if (c.includes('process') || c.includes('work') || c.includes('cuci')) return 'blue';
    if (c.includes('done') || c.includes('finish') || c.includes('ready') || c.includes('packing')) return 'emerald';
    if (c.includes('pick') || c.includes('taken')) return 'slate';
    return 'indigo';
};


</script>

<template>
<Head title="Service Board" />

<div class="h-screen flex flex-col bg-[#F1F5F9] font-sans overflow-hidden">
    <!-- Header -->
    <header
        class="bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-8 h-20 flex items-center justify-between shrink-0 sticky top-0 z-30">
        <div class="flex items-center gap-8">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-0.5">
                    <div
                        class="h-6 w-6 rounded-lg bg-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                        <KanbanIcon class="h-3.5 w-3.5 text-white" />
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900 tracking-tight leading-none">
                        Service Board
                    </h1>
                </div>
                <p class="text-[9px] text-slate-400 font-medium uppercase tracking-[0.2em] leading-none ml-8">Pipeline
                    Manajemen Layanan</p>
            </div>

            <div class="h-10 w-px bg-slate-200/60"></div>

            <div class="flex p-1 bg-slate-100 rounded-xl gap-1">
                <Link :href="serviceOrdersRoutes.index.url()">
                    <Button variant="ghost" size="sm"
                        class="h-8 px-4 rounded-lg text-slate-500 font-semibold text-[10px] uppercase tracking-wider hover:bg-white hover:text-slate-900 transition-all">
                        <LayoutGrid class="h-3.5 w-3.5 mr-2" /> List View
                    </Button>
                </Link>
                <Button size="sm"
                    class="h-8 px-4 rounded-lg bg-white text-slate-900 shadow-sm border border-slate-200/50 font-semibold text-[10px] uppercase tracking-wider">
                    <KanbanIcon class="h-3.5 w-3.5 mr-2 text-orange-500" /> Board View
                </Button>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- Manage Steps Modal Trigger -->
            <Dialog v-model:open="showAddStepModal">
                <DialogTrigger asChild>
                    <Button variant="outline" class="h-10 rounded-xl border-slate-200 bg-white shadow-sm hover:bg-slate-50 gap-2 px-4">
                        <Settings2 class="h-4 w-4 text-slate-500" />
                        <span class="text-xs font-bold text-slate-600 uppercase tracking-tight">Atur Proses</span>
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px] rounded-[2rem]">
                    <DialogHeader>
                        <DialogTitle>Tambah Step Produksi</DialogTitle>
                        <DialogDescription>
                            Buat tahapan baru untuk alur kerja produksi Anda.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-6 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama Proses</Label>
                            <Input id="name" v-model="stepForm.name" placeholder="Contoh: Cuci & Kering" class="rounded-xl" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="code">Kode</Label>
                            <Input id="code" v-model="stepForm.code" placeholder="CUCI" class="rounded-xl" />
                        </div>
                        <div class="space-y-3">
                            <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Kategori Proses</Label>
                            <div class="grid grid-cols-3 gap-3">
                                <!-- Normal Step -->
                                <label :class="[
                                    'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                                    !stepForm.is_start && !stepForm.is_final ? 'border-orange-500 bg-orange-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                                ]">
                                    <input type="radio" name="step_type" class="sr-only" :checked="!stepForm.is_start && !stepForm.is_final" @change="() => { stepForm.is_start = false; stepForm.is_final = false; }" />
                                    <ListTodo class="h-4 w-4" :class="!stepForm.is_start && !stepForm.is_final ? 'text-orange-500' : 'text-slate-400'" />
                                    <span class="text-[9px] font-bold uppercase tracking-tight" :class="!stepForm.is_start && !stepForm.is_final ? 'text-orange-700' : 'text-slate-500'">Normal</span>
                                </label>

                                <!-- Start Step -->
                                <label :class="[
                                    'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                                    stepForm.is_start ? 'border-blue-500 bg-blue-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                                ]">
                                    <input type="radio" name="step_type" class="sr-only" :checked="stepForm.is_start" @change="() => { stepForm.is_start = true; stepForm.is_final = false; }" />
                                    <Clock class="h-4 w-4" :class="stepForm.is_start ? 'text-blue-500' : 'text-slate-400'" />
                                    <span class="text-[9px] font-bold uppercase tracking-tight" :class="stepForm.is_start ? 'text-blue-700' : 'text-slate-500'">Mulai</span>
                                </label>

                                <!-- Final Step -->
                                <label :class="[
                                    'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                                    stepForm.is_final ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                                ]">
                                    <input type="radio" name="step_type" class="sr-only" :checked="stepForm.is_final" @change="() => { stepForm.is_start = false; stepForm.is_final = true; }" />
                                    <CheckCircle2 class="h-4 w-4" :class="stepForm.is_final ? 'text-emerald-500' : 'text-slate-400'" />
                                    <span class="text-[9px] font-bold uppercase tracking-tight" :class="stepForm.is_final ? 'text-emerald-700' : 'text-slate-500'">Selesai</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button @click="submitStep" :disabled="stepForm.processing" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold uppercase tracking-widest text-[10px] h-11 shadow-lg shadow-slate-900/10 active:scale-[0.98]">
                            {{ stepForm.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Step' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div class="h-8 w-px bg-slate-200 mx-2"></div>

            <div class="relative group">
                <Search
                    class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors" />
                <input placeholder="Cari order..."
                    class="h-10 w-48 pl-10 pr-4 rounded-xl bg-slate-50 border-slate-200/60 focus:bg-white focus:ring-4 focus:ring-orange-500/5 focus:border-orange-500/30 text-xs font-medium transition-all outline-none placeholder:font-normal" />
            </div>

            <Link :href="serviceOrdersRoutes.create.url()">
                <Button
                    class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white h-10 px-6 font-semibold uppercase tracking-widest text-[10px] shadow-xl shadow-slate-900/10 active:scale-95 transition-all">
                    <Plus class="h-4 w-4 mr-2 text-orange-400" /> Create Order
                </Button>
            </Link>
        </div>
    </header>

    <!-- Board Area -->
    <main class="flex-1 overflow-x-auto overflow-y-hidden p-8 flex gap-8 custom-scrollbar bg-slate-50/50">
        <!-- Uncategorized Column -->
        <div v-if="localOrdersByStep[0]?.length || steps.length === 0" class="w-80 shrink-0 flex flex-col gap-6">
            <div class="flex items-center justify-between px-3 h-10">
                <div class="flex items-center gap-3">
                    <div class="h-2 w-2 rounded-full shadow-sm bg-slate-400 shadow-slate-200"></div>
                    <h3 class="text-[10px] font-semibold text-slate-500 uppercase tracking-[0.2em]">Belum Ada Step</h3>
                    <div class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-400 shadow-sm tabular-nums">
                        {{ localOrdersByStep[0]?.length || 0 }}
                    </div>
                </div>
            </div>

            <draggable
                v-model="localOrdersByStep[0]"
                group="orders"
                @change="onDragChange($event, 0)"
                item-key="id"
                class="flex-1 overflow-y-auto space-y-4 pr-3 custom-scrollbar pb-20 -mr-3"
                ghost-class="opacity-50"
            >
                <template #item="{ element: order }">
                    <div
                        class="group relative bg-white rounded-[1.5rem] border border-slate-200/80 p-5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/50 hover:border-orange-500/30 hover:-translate-y-1.5 transition-all duration-500 cursor-pointer overflow-hidden active:scale-[0.98]"
                        @click="router.visit(serviceOrdersRoutes.show.url(order.id))">
                        <div class="absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 opacity-[0.03] bg-slate-500" style="border-radius: 40%"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-medium font-mono text-slate-400 group-hover:text-orange-500 uppercase leading-none">#{{ order.order_number }}</span>
                                <div class="h-0.5 w-4 bg-slate-100 group-hover:w-8 group-hover:bg-orange-500 transition-all duration-500"></div>
                            </div>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900 group-hover:text-orange-600 transition-colors mb-1.5 line-clamp-1 pr-4 leading-tight">{{ order.party?.name }}</h4>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-5 px-2 rounded-md bg-slate-50 flex items-center gap-1.5 border border-slate-100">
                                <span class="text-[9px] font-semibold uppercase text-slate-500 tracking-wider">{{ order.service?.name }}</span>
                            </div>
                        </div>
                        <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between">
                            <p class="text-xs font-semibold text-slate-900 tabular-nums leading-none">{{ fmtIdr(order.total_amount) }}</p>
                            <Button 
                                variant="ghost" 
                                size="icon" 
                                class="h-8 w-8 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl"
                                title="Selesaikan Langsung"
                                @click.stop="finalizeOrder(order.id)"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <!-- Dynamic Steps Columns -->
        <div v-for="step in steps" :key="step.id" class="w-80 shrink-0 flex flex-col gap-6">
            <!-- Column Header -->
            <div class="flex items-center justify-between px-3 h-10 group/header">
                <div class="flex items-center gap-3">
                    <div :class="[
                        'h-2 w-2 rounded-full shadow-sm',
                        step.is_final ? 'bg-emerald-500 shadow-emerald-200' : 'bg-blue-500 shadow-blue-200'
                    ]"></div>
                    <h3 class="text-[10px] font-semibold text-slate-900 uppercase tracking-[0.2em]">{{ step.name }}</h3>
                    <div class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-400 shadow-sm tabular-nums">
                        {{ localOrdersByStep[step.id]?.length || 0 }}
                    </div>
                </div>
                
                <button @click="deleteStep(step.id)" class="h-7 w-7 rounded-lg opacity-0 group-hover/header:opacity-100 hover:bg-white hover:text-red-500 text-slate-300 transition-all flex items-center justify-center">
                    <Trash2 class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Column Body -->
            <draggable v-model="localOrdersByStep[step.id]" group="orders" @change="onDragChange($event, step.id)"
                item-key="id" class="flex-1 overflow-y-auto space-y-4 pr-3 custom-scrollbar pb-20 -mr-3"
                ghost-class="opacity-50">
                <template #item="{ element: order }">
                    <div class="group relative bg-white rounded-[1.5rem] border border-slate-200/80 p-5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/50 hover:border-orange-500/30 hover:-translate-y-1.5 transition-all duration-500 cursor-pointer overflow-hidden active:scale-[0.98]"
                        @click="router.visit(serviceOrdersRoutes.show.url(order.id))">
                        
                        <div :class="[
                            'absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 opacity-[0.03] transition-opacity group-hover:opacity-[0.08]',
                            step.is_final ? 'bg-emerald-500' : 'bg-blue-500'
                        ]" style="border-radius: 40%"></div>

                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] font-medium font-mono text-slate-400 group-hover:text-orange-500 transition-colors uppercase tracking-tight leading-none">
                                    #{{ order.order_number }}
                                </span>
                                <div class="h-0.5 w-4 bg-slate-100 group-hover:w-8 group-hover:bg-orange-500 transition-all duration-500"></div>
                            </div>
                            <CheckCircle2 v-if="step.is_final" class="h-3.5 w-3.5 text-emerald-500" />
                        </div>

                        <h4 class="text-sm font-semibold text-slate-900 group-hover:text-orange-600 transition-colors mb-1.5 line-clamp-1 pr-4 leading-tight">
                            {{ order.party?.name }}
                        </h4>

                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-5 px-2 rounded-md bg-slate-50 flex items-center gap-1.5 border border-slate-100">
                                <span class="text-[9px] font-semibold uppercase text-slate-500 tracking-wider">{{ order.service?.name }}</span>
                            </div>
                        </div>

                        <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between">
                            <p class="text-xs font-semibold text-slate-900 tabular-nums leading-none">{{ fmtIdr(order.total_amount) }}</p>
                            <Button 
                                v-if="!step.is_final"
                                variant="ghost" 
                                size="icon" 
                                class="h-8 w-8 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl"
                                title="Selesaikan Langsung"
                                @click.stop="finalizeOrder(order.id)"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <!-- Empty State for Steps -->
        <div v-if="steps.length === 0" class="flex-1 flex flex-col items-center justify-center border-2 border-dashed border-slate-200/50 rounded-[3rem] bg-slate-100/30 m-10">
            <div class="h-16 w-16 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mb-6">
                <ListTodo class="h-8 w-8 text-slate-300" />
            </div>
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-widest mb-2">Workflow Belum Diatur</h3>
            <p class="text-xs text-slate-400 max-w-xs text-center leading-relaxed">
                Klik tombol <strong class="text-slate-600">Atur Proses</strong> di atas untuk menambahkan tahapan produksi pertama Anda.
            </p>
        </div>
    </main>

    <!-- QUICK PRICE EDIT MODAL -->
    <Dialog v-model:open="priceOpen">
        <DialogContent class="max-w-sm rounded-2xl border-slate-200 shadow-2xl">
            <DialogHeader>
                <DialogTitle class="text-lg font-bold uppercase tracking-tight flex items-center gap-2">
                    <DollarSign class="h-5 w-5 text-orange-500" />
                    Sesuaikan Harga
                </DialogTitle>
                <DialogDescription class="text-xs font-medium">
                    Order #{{ selectedOrder?.order_number }} - {{ selectedOrder?.party?.name }}
                </DialogDescription>
            </DialogHeader>

            <div class="py-6 space-y-4">
                <div class="space-y-2">
                    <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Biaya
                        (IDR)</Label>
                    <InputCurrency v-model="priceForm.total_amount"
                        class="h-12 text-lg font-bold text-slate-900 bg-slate-50 border-slate-200 rounded-xl focus:ring-4 focus:ring-orange-500/5 focus:border-orange-500/20 transition-all" />
                </div>

                <div class="p-3 rounded-xl bg-orange-50 border border-orange-100 flex gap-3">
                    <AlertCircle class="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                    <p class="text-[10px] font-medium text-orange-700 leading-relaxed">
                        Pastikan Anda sudah mengonfirmasi perubahan harga ini kepada pelanggan. Perubahan ini akan
                        langsung memperbarui total tagihan.
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button @click="submitPriceAdjustment" :disabled="priceForm.processing"
                    class="w-full h-11 bg-slate-900 hover:bg-slate-800 text-white rounded-xl font-bold uppercase tracking-widest text-[11px] shadow-lg shadow-slate-900/10 active:scale-[0.98] transition-all">
                    <Save v-if="!priceForm.processing" class="h-3.5 w-3.5 mr-2 text-orange-400" />
                    {{ priceForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
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
    background: rgba(0, 0, 0, 0.05);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.1);
}
</style>
