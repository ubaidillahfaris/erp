<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, PlayCircle, Info, Beaker, Package, Calendar, Tag, BarChart3, Receipt, HistoryIcon, ArrowDownLeft, ArrowUpRight, TrendingUp, AlertTriangle } from 'lucide-vue-next';
import { index, edit } from '@/actions/App/Http/Controllers/ProductionController';
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
    <div class="px-6 py-8 space-y-6 animate-fade-up font-sans text-slate-700">
        
        <!-- Header: Minimalist & Clean -->
        <header class="flex items-center justify-between gap-4 pb-6 border-b border-border/60">
            <div class="flex items-center gap-4">
                <Link :href="index().url" class="h-10 w-10 rounded-full bg-card border border-border/50 flex items-center justify-center hover:bg-muted transition shadow-sm">
                    <ArrowLeft class="h-5 w-5 text-muted-foreground" />
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-normal tracking-tight text-foreground">{{ production.sku }}</h1>
                        <Badge :class="['rounded-full px-2.5 py-0.5 font-normal text-[10px] uppercase tracking-widest border-0', 
                            production.status === 'completed' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600']">
                            {{ production.status === 'completed' ? 'Selesai' : 'Diproses' }}
                        </Badge>
                    </div>
                    <p class="text-[13px] font-normal text-muted-foreground mt-1 tracking-tight">Batch #{{ production.id }} &bull; {{ formatDate(production.created_at) }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link v-if="production.status !== 'completed'" :href="edit(production.id).url" class="h-10 px-5 rounded-full bg-primary hover:bg-primary/90 text-primary-foreground text-sm font-normal flex items-center gap-2 transition hover:-translate-y-0.5 shadow-none">
                    Finish Batch
                    <CheckCircle2 class="h-3.5 w-3.5" />
                </Link>
            </div>
        </header>

        <main class="grid grid-cols-12 gap-5">
            <!-- Summary Panel -->
            <div class="col-span-12 lg:col-span-4 space-y-5">
                <article class="bg-card rounded-3xl p-6 shadow-none border border-border/40 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mr-6 -mt-6 w-24 h-24 bg-primary/5 rounded-full blur-2xl"></div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <span class="text-[11px] font-normal text-muted-foreground uppercase tracking-[0.2em]">Output Info</span>
                        <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                            <Package class="h-4 w-4" />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <h2 class="text-xl font-normal tracking-tight text-foreground leading-tight">{{ production.produk?.nama }}</h2>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-[11px] font-normal text-muted-foreground/60 uppercase tracking-widest">BOM: {{ production.bom?.nama || '-' }}</span>
                        </div>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3">
                        <div class="p-5 rounded-2xl bg-slate-50/50 border border-border/40">
                            <p class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest mb-2">Target</p>
                            <p class="text-2xl font-normal tracking-tighter tabular-nums">
                                {{ formatNumber(production.target_yield) }}
                                <span class="text-[12px] font-normal text-muted-foreground ml-0.5">{{ production.produk?.satuan?.simbol }}</span>
                            </p>
                        </div>
                        <div class="p-5 rounded-2xl bg-slate-50/50 border border-border/40">
                            <p class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest mb-2">Actual</p>
                            <p class="text-2xl font-normal tracking-tighter tabular-nums" :class="production.actual_yield ? 'text-emerald-600' : 'text-muted-foreground/30'">
                                {{ production.actual_yield ? formatNumber(production.actual_yield) : '--' }}
                                <span v-if="production.actual_yield" class="text-[12px] font-normal text-muted-foreground ml-0.5">{{ production.produk?.satuan?.simbol }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t border-border/40 grid grid-cols-2 gap-0">
                        <div class="pr-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] text-muted-foreground font-normal uppercase tracking-widest">Production Cost</span>
                            </div>
                            <div class="text-2xl font-normal tracking-tighter tabular-nums text-foreground">
                                {{ formatCurrency(production.total_cost) }}
                            </div>
                        </div>
                        <div class="pl-6 border-l border-border/40">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] text-muted-foreground font-normal uppercase tracking-widest">HPP / Unit</span>
                                <Badge v-if="production.is_estimated" class="bg-amber-500/10 text-amber-600 rounded-full px-1.5 py-0 h-3.5 border-0 text-[8px] uppercase font-normal">Est</Badge>
                            </div>
                            <div class="text-2xl font-normal tracking-tighter tabular-nums text-accent">
                                {{ formatCurrency(production.total_cost / (production.actual_yield || production.target_yield || 1)) }}
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Audit Log -->
                <article class="bg-card rounded-3xl p-6 shadow-none border border-border/40">
                    <h3 class="text-[11px] font-normal text-muted-foreground uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                        <HistoryIcon class="h-3 w-3" /> Audit Trail
                    </h3>
                    <div class="space-y-6 relative before:absolute before:left-[5px] before:top-1 before:bottom-1 before:w-[1px] before:bg-border/60">
                        <div class="flex gap-4 relative z-10 pl-5 before:absolute before:left-[-1.5px] before:top-1.5 before:h-2 before:w-2 before:rounded-full before:bg-emerald-500">
                            <div>
                                <p class="text-[13px] font-normal text-foreground">Started</p>
                                <p class="text-[11px] text-muted-foreground mt-1 font-normal uppercase tracking-widest">{{ formatDate(production.created_at) }} &bull; {{ formatTime(production.created_at) }}</p>
                            </div>
                        </div>
                        <div v-if="production.status === 'completed'" class="flex gap-4 relative z-10 pl-5 before:absolute before:left-[-1.5px] before:top-1.5 before:h-2 before:w-2 before:rounded-full before:bg-primary">
                            <div>
                                <p class="text-[13px] font-normal text-foreground">Completed</p>
                                <p class="text-[11px] text-muted-foreground mt-1 font-normal uppercase tracking-widest">{{ formatDate(production.updated_at) }} &bull; {{ formatTime(production.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Components Table -->
            <div class="col-span-12 lg:col-span-8">
                <article class="bg-card rounded-3xl p-6 shadow-none border border-border/40 h-full flex flex-col">
                    <div class="flex items-center justify-between mb-8 px-2">
                        <div>
                            <h3 class="text-xl font-normal tracking-tight text-foreground flex items-center gap-2">
                                <Beaker class="h-5 w-5 text-orange-500" />
                                Ingredients Usage
                            </h3>
                        </div>
                        <span class="text-[12px] font-normal text-muted-foreground bg-muted/50 px-3 py-1 rounded-full border border-border/40">
                            {{ production.items?.length || 0 }} Items
                        </span>
                    </div>

                    <div class="flex-1 overflow-hidden rounded-2xl border border-border/40">
                        <Table>
                            <TableHeader>
                                <TableRow class="hover:bg-transparent bg-slate-50/50">
                                    <TableHead class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest h-12 px-6">Product</TableHead>
                                    <TableHead class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest h-12 px-4 text-right">Planned</TableHead>
                                    <TableHead class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest h-12 px-4 text-right">Actual</TableHead>
                                    <TableHead class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest h-12 px-6 text-right">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in production.items" :key="item.id" class="border-b border-border/40 last:border-0 hover:bg-muted/10 transition-colors">
                                    <TableCell class="px-6 py-5">
                                        <div class="leading-tight">
                                            <div class="text-[14px] font-normal text-foreground leading-none">{{ item.produk?.nama }}</div>
                                            <div class="text-[11px] text-muted-foreground mt-2 font-normal uppercase tracking-widest">{{ item.produk?.sku || 'NO-SKU' }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-4 py-5 text-right font-normal text-muted-foreground/60 tabular-nums text-[12px]">
                                        {{ formatNumber(item.planned_qty) }} <span class="text-[11px] ml-0.5 opacity-50">{{ item.satuan?.simbol }}</span>
                                    </TableCell>
                                    <TableCell class="px-4 py-5 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[14px] font-normal tabular-nums" :class="parseFloat(item.actual_qty) > parseFloat(item.planned_qty) ? 'text-rose-600' : 'text-foreground'">
                                                {{ formatNumber(item.actual_qty) }}
                                            </span>
                                            <span class="text-[11px] font-normal uppercase text-muted-foreground/50 tracking-tighter">{{ item.satuan?.simbol }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="px-6 py-5 text-right">
                                        <div class="flex flex-col items-end">
                                            <span class="text-[14px] font-normal text-primary tabular-nums tracking-tighter">{{ formatCurrency(item.cost) }}</span>
                                            <span v-if="production.is_estimated" class="text-[9px] font-normal text-amber-600 uppercase tracking-widest mt-1">Est</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="mt-6 flex items-center gap-3 p-5 rounded-2xl bg-slate-50/50 border border-border/40">
                        <Info class="h-4 w-4 text-muted-foreground shrink-0" />
                        <p class="text-[11px] font-normal text-muted-foreground leading-relaxed uppercase tracking-tight opacity-70">
                            Unit cost is locked at start. Differences between Planned & Actual affect final COGS automatically.
                        </p>
                    </div>
                </article>
            </div>
        </main>
    </div>
>
</AppLayout>
</template>
