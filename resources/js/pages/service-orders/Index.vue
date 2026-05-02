<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Search, FileText, User as UserIcon,
    ShoppingCart, CreditCard, ChevronRight,
    Calendar, CircleCheck, CircleAlert, Filter,
    X, ClipboardList, Clock, History, Plus, RotateCcw,
    KanbanIcon, LayoutGrid, Settings2, Trash2, ListTodo,
    Save, DollarSign, AlertCircle, CheckCircle2
} from 'lucide-vue-next';
import draggable from 'vuedraggable';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input, InputCurrency } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogFooter, DialogDescription, DialogTrigger
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import serviceOrdersRoutes from '@/routes/service-orders';
import { useDebounceFn } from '@vueuse/core';
import { cn, fmtIdr } from '@/lib/utils';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    orders: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        date_start?: string;
        date_end?: string;
        status?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
        view?: string;
        service_id?: string;
        production_step_id?: string;
    };
    steps: Array<{ id: number, code: string, name: string, is_final: boolean, is_start: boolean }>;
    services: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Manajemen Order', href: '/service-orders' },
];

const view = ref(props.filters.view || 'kanban');
const search = ref(props.filters.search || '');
console.log('Search Initialized:', search.value);
const date_start = ref(props.filters.date_start || '');
const date_end = ref(props.filters.date_end || '');
const status = ref(props.filters.status || 'all');
const perPage = ref(props.filters.per_page || String(props.orders.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');
const serviceId = ref(props.filters.service_id || 'all');
const productionStepId = ref(props.filters.production_step_id || 'all');
const serviceSearch = ref('');

const columns = [
    { key: 'order', label: 'Order / Invoice', sortKey: 'order_number' },
    { key: 'customer', label: 'Pelanggan', sortable: false },
    { key: 'items', label: 'Layanan', sortable: false },
    { key: 'total', label: 'Total', sortKey: 'total_amount' },
    { key: 'status_badge', label: 'Progress Status', sortKey: 'current_status_code' },
];

const debouncedFilter = useDebounceFn(() => {
    console.log('Debounced Filter Executing:', {
        search: search.value,
        serviceId: serviceId.value,
        stepId: productionStepId.value
    });
    router.get('/service-orders', {
        view: view.value,
        search: search.value || undefined,
        service_id: serviceId.value === 'all' ? undefined : serviceId.value,
        production_step_id: productionStepId.value === 'all' ? undefined : productionStepId.value,
        date_start: date_start.value || undefined,
        date_end: date_end.value || undefined,
        status: status.value === 'all' ? undefined : status.value,
        per_page: perPage.value,
        sort: sort.value || undefined,
        direction: sort.value ? (direction.value || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([view, search, serviceId, productionStepId, date_start, date_end, status, perPage, sort, direction], debouncedFilter);

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const resetFilters = () => {
    search.value = '';
    serviceId.value = 'all';
    productionStepId.value = 'all';
    date_start.value = '';
    date_end.value = '';
    status.value = 'all';
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

const hasActiveFilters = computed(() => {
    return search.value || serviceId.value !== 'all' || productionStepId.value !== 'all' || date_start.value || date_end.value || status.value !== 'all';
});

const filteredServices = computed(() => {
    if (!serviceSearch.value) return props.services;
    return props.services.filter(s =>
        s.name.toLowerCase().includes(serviceSearch.value.toLowerCase())
    );
});

// --- KANBAN LOGIC ---
const localOrdersByStep = ref<Record<number, any[]>>({});

// Sync props to local state for draggable
watch(() => [props.orders.data, props.steps], () => {
    const map: Record<number, any[]> = {};
    props.steps.forEach(step => {
        map[step.id] = props.orders.data.filter(o => o.production_step_id === step.id);
    });
    // Add "Uncategorized" column if any orders have no step
    const unassigned = props.orders.data.filter(o => !o.production_step_id);
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
</script>

<template>
<Head title="Manajemen Order" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader title="Manajemen Order" description="Pantau dan kelola seluruh alur kerja servis" back-href="/dashboard"
        :count="orders.total">
        <template #actions>
            <div class="flex items-center gap-4">
                <!-- Simple Tabs Switcher -->
                <div class="flex p-1 bg-slate-200/50 rounded-xl gap-1">
                    <Button size="sm" variant="ghost" :class="cn(
                        'h-8 px-4 rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all',
                        view === 'kanban' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                    )" @click="view = 'kanban'">
                        <KanbanIcon class="h-3.5 w-3.5 mr-2" :class="view === 'kanban' ? 'text-orange-500' : ''" />
                        Board
                    </Button>
                    <Button size="sm" variant="ghost" :class="cn(
                        'h-8 px-4 rounded-lg font-bold text-[10px] uppercase tracking-wider transition-all',
                        view === 'table' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900'
                    )" @click="view = 'table'">
                        <LayoutGrid class="h-3.5 w-3.5 mr-2" :class="view === 'table' ? 'text-orange-500' : ''" />
                        List
                    </Button>
                </div>

                <div class="h-8 w-px bg-slate-200"></div>

                <Link href="/service-orders/create">
                    <Button primary class="h-10 rounded-xl">
                        <Plus class="h-4 w-4 mr-1.5" />
                        Order Baru
                    </Button>
                </Link>
            </div>
        </template>
    </PageHeader>

    <!-- 0. SERVICE TABS -->
    <div class="w-full max-w-7xl mx-auto -mt-2">
        <div class="flex items-center justify-between border-b border-slate-200 px-1">
            <div class="flex items-center gap-8 overflow-x-auto no-scrollbar py-1">
                <button @click="serviceId = 'all'" :class="cn(
                    'pb-3 text-xs transition-all relative whitespace-nowrap uppercase tracking-wider',
                    serviceId === 'all' ? 'text-orange-600' : 'text-slate-400 hover:text-slate-600'
                )">
                    Semua Layanan
                    <div v-if="serviceId === 'all'"
                        class="absolute bottom-0 left-0 right-0 h-0.5 bg-orange-600 rounded-full"></div>
                </button>

                <button v-for="service in filteredServices" :key="service.id" @click="serviceId = String(service.id)"
                    :class="cn(
                        'pb-3 text-xs transition-all relative whitespace-nowrap uppercase tracking-wider',
                        String(serviceId) === String(service.id) ? 'text-orange-600' : 'text-slate-400 hover:text-slate-600'
                    )">
                    {{ service.name }}
                    <div v-if="String(serviceId) === String(service.id)"
                        class="absolute bottom-0 left-0 right-0 h-0.5 bg-orange-600 rounded-full"></div>
                </button>
            </div>

            <!-- Service Search within Tabs -->
            <div class="flex items-center gap-2 pl-4 mb-2">
                <div class="relative group">
                    <Search
                        class="h-3.5 w-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors" />
                    <input v-model="serviceSearch" placeholder="Cari layanan..."
                        class="h-8 pl-8 pr-3 rounded-lg bg-white border border-slate-200 text-[11px] font-bold uppercase tracking-tight transition-all outline-none focus:border-orange-500/30 focus:ring-4 focus:ring-orange-500/5 w-40 placeholder:text-slate-300" />
                    <button v-if="serviceSearch" @click="serviceSearch = ''"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500">
                        <X class="h-3 w-3" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col min-h-0">

        <!-- 1. TABLE VIEW -->
        <DataTable v-if="view === 'table'" :data="orders" :columns="columns" v-model:search="search"
            v-model:perPage="perPage" search-placeholder="Cari nomor order..." toolbar-title="Service Ledger"
            :title="'Riwayat Servis'" :sort="sort" :direction="direction as any" @sort-change="handleSortChange"
            :total-count="orders.total">
            <template #toolbar-prefix>
                <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                    <div class="flex items-center bg-white border border-slate-200 rounded-lg px-2 h-9">
                        <Calendar class="h-3.5 w-3.5 text-muted-foreground mr-2" />
                        <input type="date" v-model="date_start"
                            class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                        <span class="text-xs text-muted-foreground mx-1">-</span>
                        <input type="date" v-model="date_end"
                            class="text-xs font-medium bg-transparent border-none focus:ring-0 p-0 w-28" />
                    </div>

                    <Select v-model="productionStepId">
                        <SelectTrigger
                            class="h-9 w-[160px] text-xs font-bold uppercase bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Semua Proses" />
                        </SelectTrigger>
                        <SelectContent class="rounded-xl">
                            <SelectItem value="all">Semua Proses</SelectItem>
                            <SelectItem v-for="s in steps" :key="s.id" :value="String(s.id)">
                                {{ s.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm"
                        class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground font-semibold uppercase tracking-tight"
                        @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(order)="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <ClipboardList class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[13px] font-semibold text-foreground font-mono leading-none">#{{ row.order_number
                            }}</p>
                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-tighter mt-1.5">{{
                            formatDate(row.created_at) }}</p>
                    </div>
                </div>
            </template>

            <template #cell(customer)="{ row }">
                <div class="flex items-center gap-2">
                    <div
                        class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200/50">
                        <UserIcon class="h-3 w-3 text-slate-400" />
                    </div>
                    <span class="text-[12px] font-semibold text-foreground/80 leading-none"
                        :class="!row.party && 'italic opacity-50'">
                        {{ row.party?.name || 'Walk-in' }}
                    </span>
                </div>
            </template>

            <template #cell(items)="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="text-[12px] font-semibold text-foreground leading-none">{{ row.service?.name }}</span>
                    <span
                        class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground opacity-60">{{
                            row.items_count || 0 }} Tipe Layanan</span>
                </div>
            </template>

            <template #cell(total)="{ row }">
                <span class="text-[13px] font-semibold text-foreground tabular-nums">
                    {{ fmtIdr(row.total_amount) }}
                </span>
            </template>

            <template #cell(status_badge)="{ row }">
                <Badge
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-semibold px-1.5 h-5 whitespace-nowrap"
                    :class="{
                        'bg-amber-50 text-amber-600 border-amber-100': !row.production_step?.is_final,
                        'bg-slate-100 text-slate-500 border-slate-200': !row.production_step
                    }">
                    <Clock v-if="!row.production_step?.is_final" class="h-3 w-3 mr-1" />
                    <CircleCheck v-else class="h-3 w-3 mr-1" />
                    {{ row.production_step?.name || 'PENDING' }}
                </Badge>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-1">
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 text-orange-500 hover:text-orange-600 hover:bg-orange-50" title="Sesuaikan Harga"
                        @click="openPriceModal(row)">
                        <DollarSign class="h-4 w-4" />
                    </Button>
                    <Button v-if="!row.production_step?.is_final" variant="ghost" size="icon"
                        class="h-8 w-8 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50"
                        title="Selesaikan Langsung" @click="finalizeOrder(row.id)">
                        <CircleCheck class="h-4 w-4" />
                    </Button>
                    <Link :href="serviceOrdersRoutes.show.url(row.id)"
                        class="flex items-center justify-center h-8 w-8 rounded-lg hover:bg-secondary transition-all">
                        <ChevronRight class="h-4 w-4 text-muted-foreground" />
                    </Link>
                </div>
            </template>
        </DataTable>

        <!-- 2. KANBAN BOARD VIEW -->
        <div v-else class="flex flex-col h-full min-h-[600px]">
            <!-- Board Toolbar -->
            <div class="flex items-center justify-between mb-6">
                <div class="relative group max-w-sm w-full">
                    <Search
                        class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-orange-500 transition-colors" />
                    <Input v-model="search" placeholder="Cari nomor order atau pelanggan..."
                        class="h-10 pl-10 pr-4 rounded-xl bg-white border-slate-200 focus:ring-4 focus:ring-orange-500/5 focus:border-orange-500/30 text-xs font-medium transition-all outline-none" />
                </div>

                <div class="flex items-center gap-3">
                    <Button variant="outline"
                        class="h-10 rounded-xl border-slate-200 bg-white shadow-sm hover:bg-slate-50 gap-2 px-4"
                        @click="showAddStepModal = true">
                        <Settings2 class="h-4 w-4 text-slate-500" />
                        <span class="text-xs font-bold text-slate-600 uppercase tracking-tight">Atur Proses</span>
                    </Button>
                </div>
            </div>

            <div class="flex-1 overflow-x-auto overflow-y-hidden flex gap-6 pb-4 custom-scrollbar">
                <!-- Dynamic Steps Columns -->
                <div v-for="step in steps" :key="step.id" class="w-80 shrink-0 flex flex-col gap-4">
                    <!-- Column Header -->
                    <div class="flex items-center justify-between px-3 h-10 group/header">
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'h-2 w-2 rounded-full shadow-sm',
                                step.is_final ? 'bg-emerald-500 shadow-emerald-200' : 'bg-blue-500 shadow-blue-200'
                            ]"></div>
                            <h3 class="text-[10px] font-semibold text-slate-900 uppercase tracking-[0.2em]">{{ step.name
                                }}
                            </h3>
                            <div
                                class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-400 shadow-sm tabular-nums">
                                {{ localOrdersByStep[step.id]?.length || 0 }}
                            </div>
                        </div>

                        <button @click="deleteStep(step.id)"
                            class="h-7 w-7 rounded-lg opacity-0 group-hover/header:opacity-100 hover:bg-white hover:text-red-500 text-slate-300 transition-all flex items-center justify-center">
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Column Body -->
                    <draggable v-model="localOrdersByStep[step.id]" group="orders"
                        @change="onDragChange($event, step.id)" item-key="id"
                        class="flex-1 overflow-y-auto space-y-4 pr-1 custom-scrollbar pb-10" ghost-class="opacity-50">
                        <template #item="{ element: order }">
                            <div class="group relative bg-white rounded-[1.5rem] border border-slate-200/80 p-5 shadow-sm hover:shadow-2xl hover:shadow-slate-200/50 hover:border-orange-500/30 hover:-translate-y-1.5 transition-all duration-500 cursor-pointer overflow-hidden active:scale-[0.98]"
                                @click="router.visit(serviceOrdersRoutes.show.url(order.id))">

                                <div :class="[
                                    'absolute top-0 right-0 w-24 h-24 -mr-8 -mt-8 opacity-[0.03] transition-opacity group-hover:opacity-[0.08]',
                                    step.is_final ? 'bg-emerald-500' : 'bg-blue-500'
                                ]" style="border-radius: 40%"></div>

                                <div class="flex justify-between items-start mb-4 relative z-10">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-[10px] font-medium font-mono text-slate-400 group-hover:text-orange-500 transition-colors uppercase tracking-tight leading-none">
                                            #{{ order.order_number }}
                                        </span>
                                        <div
                                            class="h-0.5 w-4 bg-slate-100 group-hover:w-8 group-hover:bg-orange-500 transition-all duration-500">
                                        </div>
                                    </div>
                                    <CheckCircle2 v-if="step.is_final" class="h-3.5 w-3.5 text-emerald-500" />
                                </div>

                                <h4
                                    class="text-sm font-semibold text-slate-900 group-hover:text-orange-600 transition-colors mb-1.5 line-clamp-1 pr-4 leading-tight">
                                    {{ order.party?.name }}
                                </h4>

                                <div class="flex items-center gap-2 mb-4">
                                    <div
                                        class="h-5 px-2 rounded-md bg-slate-50 flex items-center gap-1.5 border border-slate-100">
                                        <span
                                            class="text-[9px] font-semibold uppercase text-slate-500 tracking-wider">{{
                                                order.service?.name }}</span>
                                    </div>
                                </div>

                                <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <p class="text-xs font-semibold text-slate-900 tabular-nums leading-none">{{
                                        fmtIdr(order.total_amount) }}</p>
                                    <div class="flex items-center gap-1">
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 text-orange-400 hover:text-orange-600 hover:bg-orange-50 rounded-xl"
                                            title="Sesuaikan Harga" @click.stop="openPriceModal(order)">
                                            <DollarSign class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button v-if="!step.is_final" variant="ghost" size="icon"
                                            class="h-8 w-8 text-emerald-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-xl"
                                            title="Selesaikan Langsung" @click.stop="finalizeOrder(order.id)">
                                            <CheckCircle2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- Kanban Pagination Info -->
            <div class="mt-4 py-3 border-t border-slate-200 flex items-center justify-between">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Menampilkan {{ orders.data.length }} dari {{ orders.total }} order aktif
                </p>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm"
                        class="h-8 rounded-lg text-[10px] font-bold uppercase tracking-tighter"
                        :disabled="!orders.links[0]?.url" @click="router.visit(orders.links[0].url)">
                        Previous
                    </Button>
                    <Button variant="outline" size="sm"
                        class="h-8 rounded-lg text-[10px] font-bold uppercase tracking-tighter"
                        :disabled="!orders.links[orders.links.length - 1]?.url"
                        @click="router.visit(orders.links[orders.links.length - 1].url)">
                        Next
                    </Button>
                </div>
            </div>
        </div>
    </div>

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
                        langsung
                        memperbarui total tagihan.
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

    <!-- MANAGE STEPS MODAL -->
    <Dialog v-model:open="showAddStepModal">
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
                    <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Kategori
                        Proses</Label>
                    <div class="grid grid-cols-3 gap-3">
                        <!-- Normal Step -->
                        <label :class="[
                            'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                            !stepForm.is_start && !stepForm.is_final ? 'border-orange-500 bg-orange-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                        ]">
                            <input type="radio" name="step_type" class="sr-only"
                                :checked="!stepForm.is_start && !stepForm.is_final"
                                @change="() => { stepForm.is_start = false; stepForm.is_final = false; }" />
                            <ListTodo class="h-4 w-4"
                                :class="!stepForm.is_start && !stepForm.is_final ? 'text-orange-500' : 'text-slate-400'" />
                            <span class="text-[9px] font-bold uppercase tracking-tight"
                                :class="!stepForm.is_start && !stepForm.is_final ? 'text-orange-700' : 'text-slate-500'">Normal</span>
                        </label>

                        <!-- Start Step -->
                        <label :class="[
                            'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                            stepForm.is_start ? 'border-blue-500 bg-blue-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                        ]">
                            <input type="radio" name="step_type" class="sr-only" :checked="stepForm.is_start"
                                @change="() => { stepForm.is_start = true; stepForm.is_final = false; }" />
                            <Clock class="h-4 w-4" :class="stepForm.is_start ? 'text-blue-500' : 'text-slate-400'" />
                            <span class="text-[9px] font-bold uppercase tracking-tight"
                                :class="stepForm.is_start ? 'text-blue-700' : 'text-slate-500'">Mulai</span>
                        </label>

                        <!-- Final Step -->
                        <label :class="[
                            'relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition-all gap-2',
                            stepForm.is_final ? 'border-emerald-500 bg-emerald-50/50' : 'border-slate-100 bg-white hover:bg-slate-50'
                        ]">
                            <input type="radio" name="step_type" class="sr-only" :checked="stepForm.is_final"
                                @change="() => { stepForm.is_start = false; stepForm.is_final = true; }" />
                            <CheckCircle2 class="h-4 w-4"
                                :class="stepForm.is_final ? 'text-emerald-500' : 'text-slate-400'" />
                            <span class="text-[9px] font-bold uppercase tracking-tight"
                                :class="stepForm.is_final ? 'text-emerald-700' : 'text-slate-500'">Selesai</span>
                        </label>
                    </div>
                </div>
            </div>
            <DialogFooter>
                <Button @click="submitStep" :disabled="stepForm.processing"
                    class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold uppercase tracking-widest text-[10px] h-11 shadow-lg shadow-slate-900/10 active:scale-[0.98]">
                    {{ stepForm.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Step' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

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
