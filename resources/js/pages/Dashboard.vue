<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Map from '@/components/Map.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Banknote, AlertTriangle, FlaskConical, TrendingDown,
    Plus, ChevronRight, MoreHorizontal,
    FileText, PackageOpen, MapPin, Store, Filter
} from 'lucide-vue-next';
import PageHeader from '@/components/PageHeader.vue';

// Define layout persistently to prevent Sidebar remounting
defineOptions({ layout: AppLayout });

const page = usePage();
const user = computed(() => (page.props.auth as any).user);

type MetricData = {
    sales_today: number;
    active_productions: number;
    critical_stocks: number;
    expenses_today: number;
};

type SaleInfo = {
    id: number;
    invoice_number: string;
    tanggal: string;
    total_amount: string | number;
};

type VendorInfo = {
    id: number;
    nama: string;
    alamat: string;
    latitude: number;
    longitude: number;
    telepon: string;
};

const props = defineProps<{
    metrics: MetricData;
    recent_sales: SaleInfo[];
    vendors: VendorInfo[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard.url() },
];

const formatRupiah = (value: number | string) => {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
    });
};

const formatTime = (dateStr: string) => {
    return new Date(dateStr).toLocaleTimeString('id-ID', {
        hour: '2-digit', minute: '2-digit',
    });
};

const metricCards = computed(() => [
    {
        label: 'Daily Sales',
        value: formatRupiah(props.metrics.sales_today),
        icon: Banknote,
        iconBg: 'bg-blue-50',
        iconColor: 'text-blue-500',
        dotColor: 'bg-blue-400',
    },
    {
        label: 'Active Productions',
        value: String(props.metrics.active_productions),
        icon: FlaskConical,
        iconBg: 'bg-amber-50',
        iconColor: 'text-amber-500',
        dotColor: 'bg-amber-400',
    },
    {
        label: 'Critical Stocks',
        value: String(props.metrics.critical_stocks),
        icon: AlertTriangle,
        iconBg: 'bg-red-50',
        iconColor: 'text-red-500',
        dotColor: 'bg-red-400',
    },
    {
        label: 'Daily Expenses',
        value: formatRupiah(props.metrics.expenses_today),
        icon: TrendingDown,
        iconBg: 'bg-violet-50',
        iconColor: 'text-violet-500',
        dotColor: 'bg-violet-400',
    },
]);

const showVendors = ref(true);

const mapMarkers = computed(() => {
    const markers: Array<{position: [number, number], title: string, content: string}> = [];
    
    if (showVendors.value && props.vendors) {
        props.vendors.forEach(v => {
            markers.push({
                position: [v.latitude, v.longitude],
                title: v.nama,
                content: `
                    <div class="flex flex-col gap-1">
                        <p class="text-[11px] font-medium leading-tight">${v.alamat}</p>
                        <p class="text-[10px] text-accent font-bold mt-1">${v.telepon}</p>
                    </div>
                `
            });
        });
    }
    
    return markers;
});
</script>

<template>
<Head title="Dashboard" />

<!-- Map panel -->
<div class="flex flex-row h-[600px] bg-[#F8F9FA] relative">
    <div class="w-full p-4 relative h-full">
        <!-- Floating Filter -->
        <div class="absolute top-8 right-8 z-[1001] flex flex-col gap-2">
            <div class="bg-white/90 backdrop-blur-md border border-border/40 p-1.5 rounded-xl shadow-xl flex items-center gap-1">
                <button 
                    @click="showVendors = !showVendors"
                    :class="[
                        'flex items-center gap-2 px-3 py-1.5 rounded-lg text-[11px] font-bold uppercase tracking-wider transition-all duration-300',
                        showVendors ? 'bg-accent text-white shadow-md' : 'bg-secondary text-muted-foreground hover:bg-muted'
                    ]"
                >
                    <Store class="w-3.5 h-3.5" />
                    Vendors
                    <div v-if="showVendors" class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" />
                </button>
            </div>
        </div>

        <Map 
            :center="[-6.200000, 106.816666]" 
            :zoom="13" 
            :markers="mapMarkers"
        />
    </div>
</div>
<!-- End map panel -->
<div class="flex flex-col lg:flex-row min-h-[calc(100vh-64px)] bg-[#F8F9FA]">

    <!-- first Head Pane -->


    <!-- ── LEFT PANE: Profile & Activity ── -->
    <div class="w-full lg:w-[380px] bg-white border-r border-border/40 p-6 flex flex-col gap-8">
        <!-- Profile Section -->
        <div class="flex flex-col items-center text-center gap-4 py-4">
            <div class="h-20 w-20 rounded-full border-4 border-secondary overflow-hidden shadow-sm">
                <img v-if="user?.profile_photo_url" :src="user.profile_photo_url" class="h-full w-full object-cover" />
                <div v-else
                    class="h-full w-full bg-accent flex items-center justify-center text-white font-bold text-2xl uppercase">
                    {{ user?.name?.charAt(0) }}
                </div>
            </div>
            <div>
                <h2 class="text-lg font-bold text-foreground">{{ user?.name || 'User' }}</h2>
                <p class="text-[11px] font-bold text-muted-foreground/50 uppercase tracking-widest mt-1">Group ID |
                    #5236852</p>
            </div>
            <div class="flex items-center gap-2 mt-2">
                <button
                    class="h-9 w-9 flex items-center justify-center rounded-lg bg-accent/10 text-accent hover:bg-accent hover:text-white transition-all">
                    <Banknote class="h-4 w-4" />
                </button>
                <button
                    class="h-9 w-9 flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-all">
                    <Plus class="h-4 w-4" />
                </button>
                <button
                    class="h-9 w-9 flex items-center justify-center rounded-lg bg-secondary text-muted-foreground hover:bg-muted hover:text-foreground transition-all">
                    <MoreHorizontal class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="h-px bg-border/40" />

        <!-- Activity Timeline -->
        <div class="flex flex-col gap-6">
            <h3 class="text-xs font-bold uppercase tracking-widest text-foreground/40 px-1">Recent Activities</h3>

            <div class="flex flex-col gap-6 relative px-1">
                <div class="absolute left-[17px] top-2 bottom-2 w-px bg-border/40" />

                <div v-for="i in 3" :key="i" class="flex gap-4 relative">
                    <div
                        class="h-9 w-9 rounded-full bg-secondary flex items-center justify-center shrink-0 border border-white shadow-sm relative z-10">
                        <FileText class="h-4 w-4 text-muted-foreground/60" />
                    </div>
                    <div class="flex flex-col gap-1 min-w-0">
                        <p class="text-[13px] text-foreground font-medium leading-relaxed">
                            <span class="font-bold">Team Member</span> added a new entry
                        </p>
                        <p class="text-[11px] text-muted-foreground/60 font-medium">Today 12:00 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── RIGHT PANE: Analytics & Data ── -->
    <div class="flex-1 p-8 overflow-y-auto">
        <div class="flex flex-col gap-8 max-w-6xl mx-auto">

            <PageHeader 
                title="Summary Analytics" 
                description="Overview Performa & Operasional Sistem"
            />

            <!-- Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="card in metricCards" :key="card.label"
                    class="bg-white border border-border/40 p-5 rounded-xl shadow-sm hover:shadow-md transition-all flex flex-col gap-3 group">
                    <div
                        class="flex items-center gap-2 text-[10px] font-bold text-muted-foreground/50 uppercase tracking-widest">
                        <div :class="['w-1.5 h-1.5 rounded-full', card.dotColor]" />
                        {{ card.label }}
                    </div>
                    <div class="text-xl font-bold text-foreground tabular-nums">
                        {{ card.value }}
                    </div>
                    <div
                        class="flex items-center gap-1.5 text-accent text-[11px] font-bold cursor-pointer group mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        View Report
                        <ChevronRight class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
                    </div>
                </div>
            </div>

            <!-- Sales List -->
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-md font-bold text-foreground">Recent Transactions</h3>
                    <Link href="/reports" class="text-[11px] font-bold uppercase text-accent hover:underline">View
                        All
                    </Link>
                </div>

                <div class="bg-white border border-border/40 rounded-xl overflow-hidden shadow-sm">
                    <div class="flex flex-col">
                        <div v-for="(sale, i) in recent_sales" :key="sale.id"
                            class="flex items-center justify-between px-6 py-4 border-b border-border/20 last:border-0 hover:bg-secondary/20 transition-all group">
                            <div class="flex items-center gap-4 flex-1">
                                <div
                                    class="h-9 w-9 flex items-center justify-center rounded-lg bg-secondary text-muted-foreground group-hover:bg-accent group-hover:text-white transition-all">
                                    <FileText class="h-4.5 w-4.5" />
                                </div>
                                <div class="flex flex-col min-w-0 pr-4">
                                    <p class="text-[13px] font-bold text-foreground truncate max-w-sm">
                                        {{ sale.invoice_number || `TRX-${String(sale.id).padStart(4, '0')}` }}
                                    </p>
                                    <p class="text-[10px] text-muted-foreground/50 font-medium">
                                        {{ formatDate(sale.tanggal) }} • {{ formatTime(sale.tanggal) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-16">
                                <div class="text-right flex flex-col items-end w-32">
                                    <span class="text-[13px] font-bold text-foreground tabular-nums">{{
                                        formatRupiah(sale.total_amount) }}</span>
                                    <span
                                        class="text-[10px] font-bold text-emerald-500 uppercase tracking-tighter">Processed</span>
                                </div>
                                <button
                                    class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div v-if="recent_sales.length === 0"
                            class="px-6 py-20 text-center flex flex-col items-center gap-3">
                            <PackageOpen class="h-10 w-10 text-muted-foreground/20" />
                            <p class="text-[11px] font-bold text-muted-foreground/40 uppercase tracking-widest">No
                                Recent activity</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</template>
