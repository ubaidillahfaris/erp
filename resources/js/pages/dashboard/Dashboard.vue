<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import DashboardHeader from './DashboardHeader.vue';
import { dashboard } from '@/routes';

import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';

import {
  Calendar, ArrowRight, Mic, Banknote, FlaskConical, TrendingDown,
  AlertTriangle, PackageOpen, Lock, Clock, BarChart3, RefreshCw,
  Filter, ShoppingBag, Users, Building2, ArrowDownLeft, ArrowUpRight,
  Download, Search
} from 'lucide-vue-next';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import CashFlowChart from '@/components/Dashboard/CashFlowChart.vue';

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
};

const props = defineProps<{
  metrics: MetricData;
  recent_sales: SaleInfo[];
  vendors: VendorInfo[];
  cash_flow_trend: Array<{ month: string; income: number; expense: number; }>;
  heatmap_data: Array<{ date: string; count: number }>;
  current_interval: string;
}>();

const updateInterval = (interval: string | number) => {
  router.get(dashboard.url(), { interval: String(interval) }, {
    preserveState: true,
    preserveScroll: true,
    only: ['cash_flow_trend', 'heatmap_data', 'current_interval']
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

const formatLabel = (dateStr: string, interval: string) => {
  const date = new Date(dateStr);
  if (interval === 'H') return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
  if (interval === 'W') return 'Minggu ' + Math.ceil(date.getDate() / 7) + ', ' + date.toLocaleDateString('id-ID', { month: 'short' });
  if (interval === 'M') return date.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
  if (interval === 'Y') return date.toLocaleDateString('id-ID', { year: 'numeric' });
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
};

const formattedCashFlow = computed(() => {
  return (props.cash_flow_trend || []).map(item => ({
    month: formatLabel(item.month, props.current_interval),
    income: item.income,
    expense: item.expense
  }));
});

const totalIncome = computed(() => props.cash_flow_trend?.reduce((sum, d) => sum + d.income, 0) || 0);
const totalExpense = computed(() => props.cash_flow_trend?.reduce((sum, d) => sum + d.expense, 0) || 0);
const netCashFlow = computed(() => totalIncome.value - totalExpense.value);
const isNetPositive = computed(() => netCashFlow.value >= 0);
</script>

<template>
<Head title="Dashboard ERP - Lovable" />


<div
  class="mx-auto max-w-[1480px] rounded-3xl bg-background/80 backdrop-blur-xl shadow-sm p-5 md:p-8 space-y-6 animate-fade-up border border-white/20 dark:border-white/5">

  <!-- ===== Top Bar ===== -->
  <DashboardHeader :user="user" />

  <!-- ===== Hero strip ===== -->
  <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-center pb-6 border-b border-border/60">
    <div class="flex items-center gap-5">
      <div class="h-20 w-20 rounded-full border border-border/50 flex items-center justify-center bg-card shadow-none">
        <span class="text-3xl font-extrabold">{{ new Date().getDate() }}</span>
      </div>
      <div class="leading-tight">
        <div class="text-base text-foreground font-semibold">{{ new Date().toLocaleDateString('id-ID', {
          weekday:
            'short'
        }) }},</div>
        <div class="text-base text-foreground font-semibold">{{ new Date().toLocaleDateString('id-ID', {
          month: 'long'
        }) }}</div>
      </div>
      <div class="h-12 w-px bg-border/60 mx-2" />
      <Link href="/pos"
        class="hidden sm:flex items-center h-14 px-7 rounded-full bg-primary hover:bg-primary/90 text-primary-foreground text-base font-semibold gap-3 shadow-none transition hover:-translate-y-0.5">
        Terminal POS
        <span class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center">
          <ArrowRight class="h-4 w-4 text-white" />
        </span>
      </Link>
      <button
        class="relative h-12 w-12 rounded-full bg-card border border-border/50 flex items-center justify-center shadow-none hover:scale-105 transition"
        aria-label="Calendar">
        <Calendar class="h-5 w-5 text-muted-foreground" />
        <span class="absolute top-2.5 right-2.5 h-2.5 w-2.5 rounded-full bg-primary border-2 border-card" />
      </button>
    </div>

    <div class="flex items-center justify-between gap-4 lg:justify-end">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-foreground tracking-tight flex items-center gap-2">
          Halo, selamat datang!
          <span class="inline-block origin-bottom animate-wave">👋</span>
        </h1>
        <p class="mt-1 flex items-center text-lg md:text-xl font-medium text-muted-foreground tracking-tight">
          <span class="h-6 w-1 rounded-full bg-foreground mr-3"></span> Ringkasan operasional.
        </p>
      </div>
      <button
        class="h-14 w-14 rounded-full bg-card border border-border/50 flex items-center justify-center shrink-0 hover:bg-muted shadow-none transition"
        aria-label="Voice">
        <Mic class="h-5 w-5 text-muted-foreground" />
      </button>
    </div>
  </section>

  <!-- ===== Main Grid ===== -->
  <section class="grid grid-cols-12 gap-5 pt-2">
    <!-- Penjualan Hari Ini (replaces VISA) -->
    <article
      class="col-span-12 md:col-span-6 lg:col-span-4 bg-card rounded-3xl p-6 shadow-none border border-border/40 relative overflow-hidden flex flex-col justify-between group hover:border-primary/30 transition-colors">
      <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-primary/10 rounded-full blur-3xl pointer-events-none">
      </div>
      <div>
        <div class="flex items-start justify-between relative z-10">
          <div class="font-extrabold italic text-xl tracking-tighter text-foreground flex items-center gap-2">
            <Banknote class="h-5 w-5 text-primary" /> INCOME
          </div>
          <!-- Mini Quick Actions inside Card -->
          <button
            class="h-9 px-4 rounded-full border border-border/50 bg-background text-sm font-medium flex items-center gap-2 shadow-none hover:bg-muted">
            Rekap
            <ArrowRight class="h-3.5 w-3.5" />
          </button>
        </div>
        <div class="mt-8 relative z-10">
          <div class="text-xs text-muted-foreground font-bold tracking-widest uppercase mb-1">Penjualan Hari Ini</div>
          <div class="mt-4 flex items-end gap-2 flex-wrap">
            <div class="text-3xl font-bold tracking-tighter">{{ formatRupiah(metrics.sales_today) }}
            </div>
          </div>
        </div>
      </div>
      <div>
        <div class="mt-8 flex gap-2 relative z-10">
          <Link href="/pos"
            class="flex items-center justify-center flex-1 h-12 rounded-full bg-slate-900 dark:bg-zinc-100 text-white dark:text-zinc-900 border border-transparent font-bold shadow-none hover:scale-[1.02] transition">
            Kasir POS</Link>
          <Link href="/sales"
            class="flex items-center justify-center flex-1 h-12 rounded-full bg-card border border-border/60 font-bold shadow-none hover:bg-muted transition text-foreground">
            Histori</Link>
        </div>
        <div class="mt-6 pt-5 border-t border-border/50 flex items-end justify-between relative z-10">
          <div>
            <div class="text-xs text-muted-foreground font-semibold">Produksi aktif berjalan</div>
            <div class="text-xl font-extrabold mt-0.5">{{ metrics.active_productions }} Baris</div>
          </div>
          <Link href="/production"
            class="flex items-center gap-2 text-primary text-sm font-bold hover:opacity-80 transition group-hover:translate-x-1 duration-300">
            <span class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
              <FlaskConical class="h-4 w-4" />
            </span>
            <span class="leading-tight text-left">Kelola<br />Produksi</span>
          </Link>
        </div>
      </div>
    </article>

    <!-- Biaya Operasional (replaces Total Income / Paid) -->
    <article
      class="col-span-12 md:col-span-6 lg:col-span-4 bg-card rounded-3xl p-6 shadow-none border border-border/40 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between">
          <span class="h-10 w-10 rounded-full bg-rose-500/10 flex items-center justify-center shadow-none">
            <TrendingDown class="h-4 w-4 text-rose-500" />
          </span>
          <button
            class="h-9 px-4 rounded-full border border-border/50 text-sm font-medium flex items-center gap-2 hover:bg-muted">
            {{ new Date().toLocaleDateString('id-ID', { weekday: 'short' }) }}
            <ArrowRight class="h-3.5 w-3.5 rotate-45" />
          </button>
        </div>
        <div class="mt-6">
          <div class="text-xs text-muted-foreground font-bold tracking-widest uppercase mb-1">Pengeluaran Hari Ini</div>
          <div class="mt-4 flex items-end justify-between flex-wrap gap-2">
            <div class="text-3xl font-bold tracking-tighter text-foreground">
              {{ formatRupiah(metrics.expenses_today) }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6">
        <div class="flex items-center justify-between pt-5 border-t border-border/50">
          <span class="h-10 w-10 rounded-full bg-amber-500/10 flex items-center justify-center shadow-none">
            <AlertTriangle class="h-4 w-4 text-amber-500" />
          </span>
          <Link href="/restock"
            class="h-9 px-4 rounded-full border border-border/50 text-sm font-medium flex items-center gap-2 hover:bg-muted">
            Inventaris
            <ArrowRight class="h-3.5 w-3.5" />
          </Link>
        </div>
        <div class="mt-6 flex items-end justify-between flex-wrap gap-2">
          <div>
            <div class="text-xs text-muted-foreground font-bold tracking-widest uppercase mb-1">Stok Fase Kritis</div>
            <div class="text-2xl font-bold flex items-baseline gap-1.5 text-foreground">
              {{ metrics.active_productions }}<span class="text-sm font-semibold text-muted-foreground">Order</span>
            </div>
          </div>
          <Link href="/stock"
            class="flex items-center gap-2 text-amber-600 dark:text-amber-500 text-sm font-bold hover:opacity-80 transition hover:translate-x-1 duration-300">
            <span class="h-8 w-8 rounded-full bg-amber-500/10 flex items-center justify-center">
              <PackageOpen class="h-4 w-4 text-amber-600 dark:text-amber-500" />
            </span>
            <span class="leading-tight text-left">Pesan<br />Restock</span>
          </Link>
        </div>
      </div>
    </article>

    <!-- System Lock & Time Efficiency (replaces System Lock + Growth) -->
    <div class="col-span-12 lg:col-span-4 grid grid-cols-2 gap-5">
      <!-- Secure Node -->
      <article
        class="col-span-1 bg-card rounded-3xl p-5 shadow-none border border-border/40 flex flex-col items-center justify-center text-center relative overflow-hidden group">
        <div
          class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
        </div>
        <Lock class="h-8 w-8 text-emerald-500 drop-shadow-none transition-transform group-hover:scale-110 duration-500" />
        <div class="mt-4 text-sm font-bold tracking-wide relative z-10">Otomasi Aktif</div>
      </article>

      <!-- Live Node Server -->
      <article
        class="col-span-1 bg-card rounded-3xl p-5 shadow-none border border-border/40 flex flex-col justify-center">
        <Clock class="h-5 w-5 text-muted-foreground mb-3" />
        <div class="text-xl font-bold tracking-tight leading-none text-foreground">
          {{ String(new Date().getHours()).padStart(2, '0') }}<span class="animate-pulse">:</span>{{ String(new
            Date().getMinutes()).padStart(2, '0') }}
        </div>
        <div class="text-[11px] font-bold text-muted-foreground tracking-widest uppercase mt-1">Waktu Basis Server</div>
        <div class="mt-4 grid grid-cols-5 gap-1.5">
          <span v-for="i in 10" :key="i" :class="['h-1.5 rounded-full w-full', i < 8 ? 'bg-primary' : 'bg-muted']" />
        </div>
      </article>

      <!-- KPI Ring -->
      <article
        class="col-span-2 bg-slate-900 dark:bg-black text-white rounded-3xl p-5 shadow-none flex items-center justify-center relative overflow-hidden border border-slate-800 dark:border-white/5">
        <div
          class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/30 via-transparent to-transparent">
        </div>
        <div class="relative h-36 w-36">
          <!-- SVG Donut -->
          <svg class="absolute inset-0 -rotate-90 transform origin-center transition-all duration-1000"
            viewBox="0 0 100 100">
            <circle cx="50" cy="50" r="42" stroke="currentColor" class="opacity-10" stroke-width="12" fill="none" />
            <!-- Growth rate 87% = ~230 dasharray out of 264 -->
            <circle cx="50" cy="50" r="42" stroke="currentColor"
              class="text-primary drop-shadow-none" stroke-width="12" fill="none"
              stroke-linecap="round" stroke-dasharray="230 264" />
          </svg>
          <div class="absolute inset-0 flex flex-col items-center justify-center pt-1">
            <div class="text-3xl font-bold tracking-tighter">87%</div>
            <div class="text-[10px] font-bold text-white/50 uppercase tracking-widest mt-0.5">Efficiency</div>
          </div>
        </div>
      </article>
    </div>

  </section>

  <!-- ===== Cash Flow Chart ===== -->
  <section class="grid grid-cols-12 gap-5 pt-4">
    <article
      class="col-span-12 lg:col-span-7 bg-card rounded-3xl p-6 lg:p-8 shadow-none border border-border/40 relative">
      <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
          <h3 class="text-xl font-extrabold flex items-center gap-2">
            Cash Flow
            <Badge class="bg-primary/15 text-primary hover:bg-primary/20 rounded-full px-2.5 font-bold">Live</Badge>
          </h3>
          <p class="text-sm font-medium text-muted-foreground mt-1">Income vs Expense &mdash; terakhir 9 bulan</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5 text-xs font-bold text-muted-foreground">
            <span class="h-3 w-3 rounded-full bg-primary shadow-none" /> Pemasukan
          </div>
          <div class="flex items-center gap-1.5 text-xs font-bold text-muted-foreground ml-3">
            <span class="h-3 w-3 rounded-full bg-slate-900 dark:bg-white shadow-none" /> Pengeluaran
          </div>
          <Tabs :default-value="current_interval" @update:model-value="updateInterval">
            <TabsList class="h-10 rounded-full p-1 bg-muted/50 border border-border/50 shadow-none">
              <TabsTrigger v-for="intv in ['H', 'D', 'W', 'M', 'Y']" :key="intv" :value="intv"
                class="text-[11px] px-3.5 h-full rounded-full data-[state=active]:bg-card data-[state=active]:shadow-none data-[state=active]:text-foreground font-bold transition-all disabled:opacity-50">
                {{ intv }}
              </TabsTrigger>
            </TabsList>
          </Tabs>
        </div>
      </div>
      <div class="mt-8 h-[280px] w-full">
        <CashFlowChart :data="formattedCashFlow" />
      </div>
    </article>

    <!-- Cash flow summary -->
    <article
      class="col-span-12 lg:col-span-5 bg-slate-900 dark:bg-black text-white rounded-3xl p-7 shadow-none flex flex-col justify-between relative overflow-hidden border border-slate-800 dark:border-white/5">
      <div
        class="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white/30 via-transparent to-transparent">
      </div>
      <div class="relative z-10 w-full">
        <div class="flex items-center justify-between w-full">
          <span class="text-[11px] opacity-70 font-bold uppercase tracking-widest">Net cash flow</span>
          <Badge
            :class="['text-white rounded-full px-3 py-0.5 font-bold border-0', isNetPositive ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-rose-500 hover:bg-rose-600']">
            {{ isNetPositive ? '+' : '-' }} {{ ((Math.abs(netCashFlow) / (totalExpense || 1)) * 100).toFixed(1) }}%
          </Badge>
        </div>
        <div class="mt-3 text-3xl md:text-4xl font-bold flex items-baseline gap-1 tracking-tighter">
          <span class="text-primary mr-1 text-xl font-bold">Rp</span>{{ (Math.abs(netCashFlow) /
            1000000).toFixed(1).replace('.', ',') }}<span class="text-xl font-bold">Jt</span>
        </div>
        <p class="text-[13px] opacity-80 mt-3 font-semibold leading-relaxed max-w-[80%]">
          {{ isNetPositive ? `Sangat sehat. Pemasukan lebih besar daripada pengeluaran di periode ini.` : `Peringatan.
          Arus kas keluar lebih besar.` }}
        </p>
      </div>
      <div class="mt-8 grid grid-cols-2 gap-4 relative z-10">
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-md">
          <div class="flex items-center gap-2 text-xs font-bold text-primary">
            <ArrowDownLeft class="h-4 w-4" /> Masuk
          </div>
          <div class="mt-3 text-lg lg:text-xl font-bold tracking-tight flex items-baseline gap-1"><span
              class="text-sm opacity-50">Rp</span>{{ (totalIncome / 1000000).toFixed(1).replace('.', ',') }}Jt</div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/5 p-5 backdrop-blur-md">
          <div class="flex items-center gap-2 text-xs font-bold opacity-80">
            <ArrowUpRight class="h-4 w-4" /> Keluar
          </div>
          <div class="mt-3 text-lg lg:text-xl font-bold tracking-tight flex items-baseline gap-1"><span
              class="text-sm opacity-50">Rp</span>{{ (totalExpense / 1000000).toFixed(1).replace('.', ',') }}Jt</div>
        </div>
      </div>
    </article>
  </section>

  <!-- ===== Recent Transactions Table ===== -->
  <section class="pt-4">
    <article class="bg-card rounded-3xl p-6 lg:p-8 shadow-none border border-border/40">
      <div class="flex items-center justify-between flex-wrap gap-4 px-2">
        <div>
          <h3 class="text-xl font-bold tracking-tight text-foreground">Recent Transactions</h3>
          <p class="text-sm font-medium text-muted-foreground mt-1">Lalu lintas sistem dan riwayat order terkini</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="relative hidden lg:block">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <input type="text" placeholder="Cari transaksi ID ..."
              class="h-12 w-64 rounded-full pl-11 pr-4 bg-muted/30 border border-border/50 outline-none focus:ring-2 focus:ring-primary/40 focus:bg-background text-sm font-bold transition-all" />
          </div>
          <button
            class="h-12 px-5 rounded-full border border-border/60 text-sm font-bold flex items-center gap-2 hover:bg-muted transition">
            <Filter class="h-4 w-4" /> Filter
          </button>
          <button
            class="h-12 px-6 rounded-full bg-slate-900 dark:bg-white text-white dark:text-slate-900 border-transparent text-sm font-bold flex items-center gap-2 shadow-none hover:scale-[1.02] transition">
            <Download class="h-4 w-4" /> Ekspor
          </button>
        </div>
      </div>

      <div class="mt-8 overflow-hidden rounded-2xl border border-border/60 shadow-none">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="font-medium text-xs text-muted-foreground uppercase tracking-wider">Ref ID</TableHead>
              <TableHead class="font-medium text-xs text-muted-foreground uppercase tracking-wider">Kategori</TableHead>
              <TableHead class="font-medium text-xs text-muted-foreground uppercase tracking-wider">Tanggal</TableHead>
              <TableHead class="font-medium text-xs text-muted-foreground uppercase tracking-wider">Status</TableHead>
              <TableHead class="font-medium text-xs text-muted-foreground text-right uppercase tracking-wider">Total IDR
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(tx, idx) in recent_sales" :key="idx" class="cursor-pointer group">
              <TableCell>
                <div class="flex items-center gap-4">
                  <span
                    class="h-10 w-10 rounded-full flex items-center justify-center bg-primary/10 text-primary shrink-0 group-hover:scale-110 transition-transform">
                    <ArrowDownLeft class="h-4 w-4" />
                  </span>
                  <div class="leading-tight">
                    <div class="text-sm font-medium text-foreground">{{ tx.invoice_number ||
                      `Order #${tx.id}` }}</div>
                    <div class="text-xs text-muted-foreground mt-0.5">Penjualan
                      Retail</div>
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <span
                  class="text-xs font-medium px-2.5 py-1 rounded-md border border-border/60 bg-muted/20 text-foreground">Income</span>
              </TableCell>
              <TableCell class="text-sm font-medium text-muted-foreground">{{ formatDate(tx.tanggal) }}</TableCell>
              <TableCell>
                <span
                  class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                  <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                  Completed
                </span>
              </TableCell>
              <TableCell class="text-right font-medium text-sm text-primary">
                + {{ formatRupiah(tx.total_amount) }}
              </TableCell>
            </TableRow>
            <TableRow v-if="recent_sales.length === 0">
              <TableCell colspan="5" class="py-12 text-center text-muted-foreground font-semibold">
                <PackageOpen class="h-12 w-12 opacity-30 mx-auto mb-3" />
                Belum ada transaksi di periode ini.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </article>
  </section>

</div>
</template>

<style>
/* 
 * Memasukkan animasi wave dan kustomisasi variabel
 * ke Global/Scoped untuk mendapatkan efek visual serupa 
 */
.animate-wave {
  animation: wave 2.5s infinite;
  transform-origin: 70% 70%;
}

@keyframes wave {
  0% {
    transform: rotate(0.0deg)
  }

  10% {
    transform: rotate(14.0deg)
  }

  20% {
    transform: rotate(-8.0deg)
  }

  30% {
    transform: rotate(14.0deg)
  }

  40% {
    transform: rotate(-4.0deg)
  }

  50% {
    transform: rotate(10.0deg)
  }

  60% {
    transform: rotate(0.0deg)
  }

  100% {
    transform: rotate(0.0deg)
  }
}

.animate-fade-up {
  animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeUp {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Base resets & scrollbar removal per referensi desain */
::-webkit-scrollbar {
  display: none;
}
</style>
