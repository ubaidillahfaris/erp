<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, PlayCircle, Info, Beaker, Package, Calendar, Tag, BarChart3, Receipt, HistoryIcon, ArrowDownLeft, ArrowUpRight, TrendingUp, AlertTriangle } from 'lucide-vue-next';
import { index } from '@/actions/App/Http/Controllers/ProductionController';
import { Badge } from '@/components/ui/badge';
import PageHeader from '@/components/PageHeader.vue';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    production: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produksi', href: index().url },
    { title: props.production.sku, href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatNumber = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 2
    }).format(value);
};

const formatDate = (dateStr: string) => new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric',
});

const formatTime = (dateStr: string) => new Date(dateStr).toLocaleTimeString('id-ID', {
    hour: '2-digit', minute: '2-digit',
});
</script>

<template>
<Head :title="`Detail Produksi - ${production.sku}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto max-w-[1480px] rounded-3xl bg-background/80 backdrop-blur-xl shadow-sm p-6 md:p-10 space-y-8 animate-fade-up border border-white/20 dark:border-white/5 font-sans min-h-[calc(100vh-80px)]">
        
        <!-- Header Section -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-8 border-b border-border/60">
            <div class="flex items-center gap-6">
                <Link href="/production" class="h-14 w-14 rounded-full bg-card border border-border/50 flex items-center justify-center shrink-0 hover:bg-muted shadow-none transition group">
                    <ArrowLeft class="h-6 w-6 text-muted-foreground group-hover:-translate-x-1 transition-transform" />
                </Link>
                <div class="leading-tight">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold tracking-tighter text-foreground">{{ production.sku }}</h1>
                        <Badge :class="['rounded-full px-4 py-1 font-black text-[10px] uppercase tracking-[0.2em] shadow-none border-0', 
                            production.status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600']">
                            {{ production.status === 'completed' ? 'Finalized' : 'In Progress' }}
                        </Badge>
                    </div>
                    <p class="text-lg font-medium text-muted-foreground mt-1 tracking-tight flex items-center gap-2">
                        <span class="h-5 w-1 rounded-full bg-primary/40"></span>
                        Batch Produksi #{{ production.id }} &bull; {{ formatDate(production.created_at) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button v-if="production.status !== 'completed'" class="h-14 px-8 rounded-full bg-primary hover:bg-primary/90 text-primary-foreground text-base font-bold flex items-center gap-3 shadow-none transition hover:-translate-y-0.5">
                    Selesaikan Batch
                    <span class="h-8 w-8 rounded-full bg-white/20 flex items-center justify-center">
                        <CheckCircle2 class="h-4 w-4" />
                    </span>
                </button>
                <button class="h-14 w-14 rounded-full bg-card border border-border/50 flex items-center justify-center shrink-0 hover:bg-muted shadow-none transition" aria-label="Print">
                    <Receipt class="h-5 w-5 text-muted-foreground" />
                </button>
            </div>
        </header>

        <main class="grid grid-cols-12 gap-6">
            <!-- Left Panel: Summary & Metrics -->
            <div class="col-span-12 lg:col-span-4 space-y-6">
                <!-- Main Result Card -->
                <article class="bg-card rounded-[2.5rem] p-8 shadow-none border border-border/40 relative overflow-hidden flex flex-col group hover:border-primary/30 transition-colors">
                    <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 bg-primary/5 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="flex items-start justify-between relative z-10">
                        <div class="font-black italic text-xl tracking-tighter text-foreground flex items-center gap-2 uppercase opacity-40">
                            <BarChart3 class="h-5 w-5" /> Output Info
                        </div>
                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <Package class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-8 relative z-10">
                        <div class="text-[11px] text-muted-foreground font-black tracking-[0.2em] uppercase mb-2">Produk Yang Dihasilkan</div>
                        <h2 class="text-2xl font-bold tracking-tight text-foreground leading-tight">{{ production.produk?.nama }}</h2>
                        <div class="mt-4 flex items-center gap-3">
                            <Badge variant="outline" class="rounded-full px-3 py-0.5 text-[10px] font-bold border-border/60 bg-muted/20 text-muted-foreground">
                                SKU: {{ production.produk?.sku || '-' }}
                            </Badge>
                            <Badge variant="outline" class="rounded-full px-3 py-0.5 text-[10px] font-bold border-border/60 bg-muted/20 text-muted-foreground">
                                BOM: {{ production.bom?.nama || '-' }}
                            </Badge>
                        </div>
                    </div>

                    <div class="mt-10 grid grid-cols-2 gap-4 relative z-10">
                        <div class="p-6 rounded-3xl border border-border/50 bg-slate-50/50">
                            <p class="text-[10px] font-black tracking-widest text-muted-foreground uppercase mb-2">Target Yield</p>
                            <p class="text-2xl font-bold tracking-tighter tabular-nums text-foreground">
                                {{ formatNumber(production.target_yield) }}
                                <span class="text-sm font-bold text-muted-foreground ml-1">{{ production.produk?.satuan?.simbol }}</span>
                            </p>
                        </div>
                        <div class="p-6 rounded-3xl border border-border/50 bg-slate-50/50">
                            <p class="text-[10px] font-black tracking-widest text-muted-foreground uppercase mb-2">Actual Yield</p>
                            <p class="text-2xl font-bold tracking-tighter tabular-nums" :class="production.actual_yield ? 'text-emerald-600' : 'text-slate-300'">
                                {{ production.actual_yield ? formatNumber(production.actual_yield) : '--' }}
                                <span v-if="production.actual_yield" class="text-sm font-bold text-muted-foreground ml-1">{{ production.produk?.satuan?.simbol }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-10 pt-8 border-t border-border/50 relative z-10">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] text-muted-foreground font-black tracking-[0.2em] uppercase">Total Biaya Produksi</span>
                            <Badge v-if="production.is_estimated" class="bg-amber-500/10 text-amber-600 rounded-full px-3 font-bold border-0 text-[10px] uppercase">Estimasi</Badge>
                        </div>
                        <div class="text-4xl font-bold tracking-tighter flex items-baseline gap-2">
                            <span class="text-primary text-xl font-bold">Rp</span>{{ (production.total_cost / 1000).toFixed(0) }}<span class="text-xl font-bold text-muted-foreground">k</span>
                            <p class="hidden sm:block text-[13px] text-muted-foreground ml-2 tabular-nums">({{ formatCurrency(production.total_cost) }})</p>
                        </div>
                        
                        <div class="mt-8 flex items-center justify-between p-5 rounded-3xl bg-slate-900 text-white shadow-xl shadow-slate-900/10 group-hover:scale-[1.02] transition-transform">
                            <div>
                                <p class="text-[10px] font-bold text-white/50 uppercase tracking-widest">HPP Per Unit</p>
                                <p class="text-xl font-bold tracking-tight mt-1 tabular-nums">{{ formatCurrency(production.total_cost / (production.actual_yield || production.target_yield || 1)) }}</p>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-white/10 flex items-center justify-center">
                                <TrendingUp class="h-5 w-5 text-primary" />
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Activity Log Card -->
                <article class="bg-card rounded-[2.5rem] p-8 shadow-none border border-border/40">
                    <h3 class="text-sm font-black tracking-[0.2em] text-muted-foreground uppercase mb-8 flex items-center gap-3">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary"></span> Activity Audit
                    </h3>
                    <div class="space-y-8 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-[2px] before:bg-muted/50">
                        <div class="flex gap-6 relative z-10">
                            <div class="h-4 w-4 rounded-full bg-card border-4 border-emerald-500 shrink-0 mt-1"></div>
                            <div>
                                <p class="text-sm font-bold text-foreground">Produksi Dimulai</p>
                                <p class="text-[11px] text-muted-foreground mt-1 font-bold uppercase tracking-widest">{{ formatDate(production.created_at) }} &bull; {{ formatTime(production.created_at) }}</p>
                            </div>
                        </div>
                        <div v-if="production.status === 'completed'" class="flex gap-6 relative z-10">
                            <div class="h-4 w-4 rounded-full bg-card border-4 border-primary shrink-0 mt-1"></div>
                            <div>
                                <p class="text-sm font-bold text-foreground">Produksi Selesai</p>
                                <p class="text-[11px] text-muted-foreground mt-1 font-bold uppercase tracking-widest">{{ formatDate(production.updated_at) }} &bull; {{ formatTime(production.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Right Panel: Ingredients Table -->
            <div class="col-span-12 lg:col-span-8">
                <article class="bg-card rounded-[2.5rem] p-6 lg:p-10 shadow-none border border-border/40 h-full flex flex-col">
                    <div class="flex items-start justify-between flex-wrap gap-4 mb-10 px-2">
                        <div>
                            <h3 class="text-2xl font-bold tracking-tighter text-foreground flex items-center gap-3">
                                <Beaker class="h-7 w-7 text-orange-500" />
                                Ingredient Usage
                            </h3>
                            <p class="text-sm font-medium text-muted-foreground mt-1">Rincian realisasi pemakaian bahan baku batch ini</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="h-12 px-6 rounded-full bg-muted/30 border border-border/50 flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full bg-primary"></span>
                                <span class="text-sm font-bold text-foreground">{{ production.items?.length || 0 }} Komponen</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-hidden rounded-[2rem] border border-border/60 shadow-none">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent border-b border-border/60">
                                    <TableHead class="font-black text-[11px] text-muted-foreground uppercase tracking-[0.2em] h-14 px-8">Bahan Baku</TableHead>
                                    <TableHead class="font-black text-[11px] text-muted-foreground uppercase tracking-[0.2em] h-14 px-4 text-right">Planned</TableHead>
                                    <TableHead class="font-black text-[11px] text-muted-foreground uppercase tracking-[0.2em] h-14 px-4 text-right">Actual</TableHead>
                                    <TableHead class="font-black text-[11px] text-muted-foreground uppercase tracking-[0.2em] h-14 px-4 text-right">Price</TableHead>
                                    <TableHead class="font-black text-[11px] text-muted-foreground uppercase tracking-[0.2em] h-14 px-8 text-right">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in production.items" :key="item.id" class="cursor-default group border-b border-border/40 last:border-0 hover:bg-muted/10 transition-colors">
                                    <TableCell class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                                                <FlaskConical class="h-4 w-4" />
                                            </div>
                                            <div class="leading-tight">
                                                <div class="text-[13px] font-bold text-foreground">{{ item.produk?.nama }}</div>
                                                <div class="text-[11px] text-muted-foreground mt-1 font-bold uppercase tracking-widest">{{ item.produk?.sku || 'NO-SKU' }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-5 text-right font-bold text-muted-foreground/60 tabular-nums text-xs">
                                        {{ formatNumber(item.planned_qty) }} <span class="text-[10px] ml-0.5 opacity-50">{{ item.satuan?.simbol }}</span>
                                    </TableCell>
                                    <TableCell class="px-4 py-5 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-sm font-bold tabular-nums" :class="parseFloat(item.actual_qty) > parseFloat(item.planned_qty) ? 'text-rose-600' : 'text-foreground'">
                                                {{ formatNumber(item.actual_qty) }}
                                            </span>
                                            <span class="text-[10px] font-black uppercase text-muted-foreground/50 tracking-tighter">{{ item.satuan?.simbol }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-5 text-right font-bold text-muted-foreground text-xs tabular-nums tracking-tighter">
                                        {{ formatCurrency(item.harga_satuan) }}
                                    </TableCell>
                                    <TableCell class="px-8 py-5 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-sm font-black text-primary tabular-nums">{{ formatCurrency(item.cost) }}</span>
                                            <span v-if="production.is_estimated" class="text-[9px] font-black text-amber-600 uppercase tracking-widest mt-1">Est</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="mt-8 flex items-center gap-4 p-6 rounded-[1.5rem] bg-slate-50 border border-border/50">
                        <span class="h-10 w-10 rounded-full bg-white flex items-center justify-center shadow-sm shrink-0">
                            <AlertTriangle class="h-5 w-5 text-amber-500" />
                        </span>
                        <p class="text-xs font-bold text-slate-500 leading-relaxed uppercase tracking-tight opacity-70">
                            Peringatan: Selisih antara Planned & Actual akan langsung mempengaruhi perhitungan HPP dan stok bahan baku secara real-time.
                        </p>
                    </div>
                </article>
            </div>
        </main>
    </div>
>
</AppLayout>
</template>
