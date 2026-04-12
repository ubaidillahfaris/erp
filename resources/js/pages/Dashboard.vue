<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Map from '@/components/Map.vue';
import SalesTrendChart from '@/components/Dashboard/SalesTrendChart.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import {
    Banknote, AlertTriangle, FlaskConical, TrendingDown,
    Plus, ChevronRight, FileText, PackageOpen, Store,
    BarChart3, Activity, ArrowUpRight, ShoppingBag, Package,
    ClipboardList, Users, ArrowUp, ArrowDown, Minus
} from 'lucide-vue-next';

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
    sales_trend: Array<{ date: string; total: number }>;
    heatmap_data: Array<{ date: string; count: number }>;
    current_interval: string;
}>();

const updateInterval = (interval: string) => {
    router.get(dashboard.url(), { interval }, {
        preserveState: true,
        preserveScroll: true,
        only: ['sales_trend', 'heatmap_data', 'current_interval']
    });
};

const formatRupiah = (value: number | string) => {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (dateStr: string) => new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
});

const formatTime = (dateStr: string) => new Date(dateStr).toLocaleTimeString('id-ID', {
    hour: '2-digit', minute: '2-digit',
});

const showVendors = ref(true);

const mapMarkers = computed(() => {
    if (!showVendors.value || !props.vendors) return [];
    return props.vendors.map(v => ({
        position: [v.latitude, v.longitude] as [number, number],
        title: v.nama,
        content: `<div class="p-1 font-sans text-xs"><b>${v.alamat}</b><br/>${v.telepon}</div>`
    }));
});

// Heatmap mini — peak hour by count
const peakHour = computed(() => {
    if (!props.heatmap_data?.length) return null;
    return props.heatmap_data.reduce((a, b) => a.count > b.count ? a : b, props.heatmap_data[0]);
});

const totalTrend = computed(() =>
    props.sales_trend?.reduce((sum, d) => sum + d.total, 0) || 0
);

const quickActions = [
    { label: 'Catat Penjualan', icon: ShoppingBag, href: '/pos', color: 'text-blue-600 bg-blue-50' },
    { label: 'Tambah Produk', icon: Package, href: '/produk/create', color: 'text-violet-600 bg-violet-50' },
    { label: 'Purchasing', icon: ClipboardList, href: '/purchasing/create', color: 'text-emerald-600 bg-emerald-50' },
    { label: 'Kelola Produksi', icon: FlaskConical, href: '/production', color: 'text-amber-600 bg-amber-50' },
];

const metricCards = computed(() => [
    {
        label: 'Penjualan Hari Ini',
        value: formatRupiah(props.metrics.sales_today),
        icon: Banknote,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
        trend: props.metrics.sales_today > 0 ? 'up' : 'flat',
        sub: 'Real-time settlement',
    },
    {
        label: 'Produksi Aktif',
        value: String(props.metrics.active_productions),
        icon: FlaskConical,
        color: 'text-amber-600',
        bg: 'bg-amber-50',
        trend: 'flat',
        sub: 'Batch berjalan',
    },
    {
        label: 'Stok Kritis',
        value: String(props.metrics.critical_stocks),
        icon: AlertTriangle,
        color: props.metrics.critical_stocks > 0 ? 'text-red-600' : 'text-emerald-600',
        bg: props.metrics.critical_stocks > 0 ? 'bg-red-50' : 'bg-emerald-50',
        trend: props.metrics.critical_stocks > 0 ? 'down' : 'up',
        sub: 'Perlu restock',
    },
    {
        label: 'Biaya Operasional',
        value: formatRupiah(props.metrics.expenses_today),
        icon: TrendingDown,
        color: 'text-violet-600',
        bg: 'bg-violet-50',
        trend: 'flat',
        sub: 'Hari ini',
    },
]);
</script>

<template>
<Head title="Dashboard" />

<div class="min-h-screen bg-slate-50 font-['Plus_Jakarta_Sans',sans-serif]">
<div class="p-6 md:p-8 space-y-6">

    <!-- ─── GREETING ROW ─── -->
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">
                {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
            </p>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">
                Selamat datang kembali, {{ user?.name?.split(' ')[0] ?? 'Admin' }} 👋
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <Link v-for="action in quickActions" :key="action.label" :href="action.href"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 hover:border-slate-300 hover:bg-slate-50 rounded-xl transition-all text-xs font-semibold text-slate-600"
            >
                <component :is="action.icon" class="h-3.5 w-3.5" :class="action.color.split(' ')[0]" />
                {{ action.label }}
            </Link>
        </div>
    </div>

    <!-- ─── METRIC CARDS ROW ─── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="card in metricCards" :key="card.label"
            class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col gap-3 hover:border-slate-300 transition-all"
        >
            <div class="flex items-center justify-between">
                <div :class="['p-2 rounded-lg', card.bg]">
                    <component :is="card.icon" :class="['h-4 w-4', card.color]" />
                </div>
                <div :class="['flex items-center gap-1 text-xs font-bold',
                    card.trend === 'up' ? 'text-emerald-600' :
                    card.trend === 'down' ? 'text-red-500' : 'text-slate-400'
                ]">
                    <ArrowUp v-if="card.trend === 'up'" class="h-3 w-3" />
                    <ArrowDown v-else-if="card.trend === 'down'" class="h-3 w-3" />
                    <Minus v-else class="h-3 w-3" />
                </div>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 leading-none">{{ card.label }}</p>
                <h3 class="text-xl font-black text-slate-900 mt-1.5 tabular-nums leading-none">{{ card.value }}</h3>
                <p class="text-[11px] text-slate-400 mt-1">{{ card.sub }}</p>
            </div>
        </div>
    </div>

    <!-- ─── MAIN CONTENT GRID ─── -->
    <div class="grid grid-cols-12 gap-6">

        <!-- Sales Trend Chart — span 8 -->
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-violet-50 rounded-lg">
                            <BarChart3 class="h-4 w-4 text-violet-600" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Revenue Flow</p>
                            <p class="text-xs text-slate-400">{{ formatRupiah(totalTrend) }} total periode</p>
                        </div>
                    </div>
                    <!-- Interval Switcher -->
                    <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-lg p-1">
                        <button
                            v-for="intv in ['H', 'D', 'W', 'M', 'Y']"
                            :key="intv"
                            @click="updateInterval(intv)"
                            :class="[
                                'px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider transition-all',
                                current_interval === intv
                                    ? 'bg-white text-slate-900 border border-slate-200'
                                    : 'text-slate-400 hover:text-slate-600'
                            ]"
                        >{{ intv }}</button>
                    </div>
                </div>
                <!-- Chart -->
                <div class="h-[260px] p-4">
                    <SalesTrendChart :data="sales_trend" :interval="current_interval" />
                </div>
            </div>
        </div>

        <!-- Stats Sidebar — span 4 -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">
            <!-- Total Revenue Highlight -->
            <div class="bg-slate-900 text-white border border-slate-800 rounded-xl p-5 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Terminal Revenue</p>
                    <ArrowUpRight class="h-4 w-4 text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-2xl font-black tabular-nums tracking-tighter">{{ formatRupiah(metrics.sales_today) }}</h3>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></div>
                        <p class="text-xs text-slate-400 font-medium">Live · Hari ini</p>
                    </div>
                </div>
            </div>

            <!-- Vendor Map Widget -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden flex-1">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 bg-blue-50 rounded-xl">
                            <Store class="h-3.5 w-3.5 text-blue-600" />
                        </div>
                        <p class="text-xs font-bold text-slate-900">Vendor Map</p>
                    </div>
                    <button
                        @click="showVendors = !showVendors"
                        :class="['text-xs font-bold px-2.5 py-1 rounded-xl border transition-all',
                            showVendors ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-500 border-slate-200'
                        ]"
                    >{{ showVendors ? 'Aktif' : 'Nonaktif' }}</button>
                </div>
                <div class="h-[200px] w-full relative z-0">
                    <Map
                        :center="[-6.200000, 106.816666]"
                        :zoom="11"
                        :markers="mapMarkers"
                    />
                </div>
                <div class="px-4 py-2 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-xs text-slate-400 font-medium">{{ vendors.length }} titik vendor</p>
                    <Link href="/vendors" class="text-xs font-bold text-blue-600 hover:underline">Lihat semua →</Link>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── BOTTOM GRID: Recent Sales + Quick Nav ─── -->
    <div class="grid grid-cols-12 gap-6">

        <!-- Recent Sales Table — span 8 -->
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <FileText class="h-4 w-4 text-slate-600" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900">Transaksi Terakhir</p>
                            <p class="text-xs text-slate-400">{{ recent_sales.length }} transaksi terbaru</p>
                        </div>
                    </div>
                    <Link href="/pos" class="text-xs font-bold text-blue-600 hover:underline flex items-center gap-1">
                        Ke POS <ChevronRight class="h-3 w-3" />
                    </Link>
                </div>

                <!-- Table -->
                <div class="divide-y divide-slate-50">
                    <div
                        v-for="(sale, i) in recent_sales" :key="sale.id"
                        class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/60 transition-colors group"
                    >
                        <div class="flex items-center gap-4">
                            <div class="h-9 w-9 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs font-black group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                {{ i + 1 }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                                    {{ sale.invoice_number || `TRX#${String(sale.id).padStart(4, '0')}` }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ formatDate(sale.tanggal) }} · {{ formatTime(sale.tanggal) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-sm font-black text-slate-900 tabular-nums">{{ formatRupiah(sale.total_amount) }}</p>
                                <div class="flex items-center justify-end gap-1 mt-0.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                    <p class="text-xs text-emerald-600 font-bold">Lunas</p>
                                </div>
                            </div>
                            <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-slate-500 transition-colors" />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="recent_sales.length === 0" class="py-16 flex flex-col items-center gap-3">
                        <div class="h-14 w-14 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center">
                            <PackageOpen class="h-7 w-7 text-slate-300" />
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-bold text-slate-500">Belum ada transaksi</p>
                            <p class="text-xs text-slate-400 mt-1">Mulai dari menu POS untuk mencatat penjualan</p>
                        </div>
                        <Link href="/pos">
                            <button class="mt-2 px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition-colors">
                                Buka POS →
                            </button>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Navigation Panel — span 4 -->
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">
            <!-- Quick Links Card -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <p class="text-sm font-bold text-slate-900">Akses Cepat</p>
                    <p class="text-xs text-slate-400 mt-0.5">Modul yang sering digunakan</p>
                </div>
                <div class="divide-y divide-slate-50">
                    <Link v-for="action in quickActions" :key="action.label" :href="action.href"
                        class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50 transition-colors group"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="['p-2 rounded-lg', action.color.split(' ')[1]]">
                                <component :is="action.icon" :class="['h-4 w-4', action.color.split(' ')[0]]" />
                            </div>
                            <p class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">{{ action.label }}</p>
                        </div>
                        <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-slate-500 transition-colors" />
                    </Link>
                </div>
            </div>

            <!-- Critical Stock Alert -->
            <div v-if="metrics.critical_stocks > 0"
                class="bg-red-50 border border-red-200 rounded-xl p-5"
            >
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-red-100 rounded-lg shrink-0">
                        <AlertTriangle class="h-4 w-4 text-red-600" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-red-700">{{ metrics.critical_stocks }} Stok Kritis</p>
                        <p class="text-xs text-red-500 mt-1">Beberapa barang hampir habis dan perlu segera di-restock.</p>
                        <Link href="/stock" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-red-600 hover:underline">
                            Cek Stok <ChevronRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- All good state -->
            <div v-else class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-emerald-100 rounded-lg shrink-0">
                        <Package class="h-4 w-4 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-emerald-700">Semua Stok Aman</p>
                        <p class="text-xs text-emerald-600 mt-1">Tidak ada stok yang perlu perhatian saat ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
</template>

<style scoped>
::-webkit-scrollbar { display: none; }
* { scrollbar-width: none; }
</style>
