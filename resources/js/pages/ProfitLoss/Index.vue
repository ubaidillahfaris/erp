<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Calendar, TrendingUp, TrendingDown, Minus, Info, Receipt, Landmark, PieChart } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import PageHeader from '@/components/PageHeader.vue';

const props = defineProps<{
    report: {
        revenue: { total: number; items: any[] };
        cogs: { total: number; items: any[] };
        expenses: { total: number; items: any[] };
    };
    summary: {
        gross_profit: number;
        net_profit: number;
        margin: number;
    };
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Laba Rugi', href: '/profit-loss' },
];

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

watch([startDate, endDate], debounce(([newStart, newEnd]) => {
    router.get('/profit-loss', {
        start_date: newStart,
        end_date: newEnd,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 500));

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
    }).format(new Date(date));
};

const handlePrint = () => {
    window.print();
};
</script>

<template>
<Head title="Laporan Laba Rugi" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <PageHeader 
            title="Laporan Laba Rugi" 
            :description="`Ringkasan Performa Periode ${formatDate(startDate)} - ${formatDate(endDate)}`" 
            back-href="/dashboard"
        >
            <template #actions>
                <div class="flex items-center gap-2 bg-muted/20 p-1 border border-muted">
                    <Input type="date" v-model="startDate"
                        class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs" />
                    <span class="text-xs text-muted-foreground">s/d</span>
                    <Input type="date" v-model="endDate"
                        class="h-8 w-36 border-none bg-transparent shadow-none focus-visible:ring-0 text-xs" />
                </div>
                <Button variant="outline" size="sm" class="rounded-none h-10 px-4" @click="handlePrint">
                    Cetak Laporan
                </Button>
            </template>
        </PageHeader>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="rounded-none border-muted bg-transparent shadow-none ">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center">
                        Gross Profit
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatCurrency(summary.gross_profit) }}</div>
                </CardContent>
            </Card>

            <Card class="rounded-none border-primary/20 bg-primary/5 shadow-none border">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-xs font-bold uppercase tracking-widest text-primary flex items-center">
                        Net Profit (Laba Bersih)
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-primary">
                        {{ formatCurrency(summary.net_profit) }}
                    </div>
                </CardContent>
            </Card>

            <Card class="rounded-none border-muted bg-transparent shadow-none ">
                <CardHeader class="pb-2">
                    <CardTitle
                        class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center">
                        Profit Margin
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold" :class="summary.margin >= 0 ? 'text-green-600' : 'text-red-600'">
                        {{ summary.margin.toFixed(2) }}%
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Profit & Loss Statement -->
        <div
            class=" border border-muted p-8 bg-card shadow-sm mx-auto w-full print:border-none print:shadow-none print:p-0">
            <div class="text-center mb-10">
                <h2 class="text-xl font-bold uppercase tracking-widest">Laporan Laba Rugi</h2>
                <p class="text-xs text-muted-foreground uppercase mt-1">Periode: {{ formatDate(startDate) }} sampai {{
                    formatDate(endDate) }}</p>
            </div>

            <div class="space-y-8">
                <!-- Revenue -->
                <section>
                    <h3 class="text-sm font-bold border-b border-muted pb-2 uppercase tracking-wide">Pendapatan</h3>
                    <div class="mt-4 space-y-2">
                        <div v-for="item in report.revenue.items" :key="item.label"
                            class="flex justify-between text-sm">
                            <span>{{ item.label }}</span>
                            <span>{{ formatCurrency(item.amount) }}</span>
                        </div>
                        <div v-if="report.revenue.items.length === 0"
                            class="text-sm text-muted-foreground italic text-center py-2">
                            Tidak ada data pendapatan
                        </div>
                        <div class="flex justify-between pt-4 font-bold border-t border-muted/50">
                            <span>Total Pendapatan</span>
                            <span>{{ formatCurrency(report.revenue.total) }}</span>
                        </div>
                    </div>
                </section>

                <!-- COGS -->
                <section>
                    <h3 class="text-sm font-bold border-b border-muted pb-2 uppercase tracking-wide">Beban Pokok
                        Penjualan (HPP)</h3>
                    <div class="mt-4 space-y-2">
                        <div v-for="item in report.cogs.items" :key="item.label" class="flex justify-between text-sm">
                            <span>{{ item.label }}</span>
                            <span class="text-red-500">({{ formatCurrency(item.amount) }})</span>
                        </div>
                        <div v-if="report.cogs.items.length === 0"
                            class="text-sm text-muted-foreground italic text-center py-2">
                            Tidak ada data HPP
                        </div>
                        <div class="flex justify-between pt-4 font-bold border-t border-muted/50">
                            <span>Total Beban Pokok</span>
                            <span class="text-red-500">({{ formatCurrency(report.cogs.total) }})</span>
                        </div>
                    </div>
                </section>

                <!-- Gross Profit Subtotal -->
                <div
                    class="p-4 bg-muted/30 border border-muted flex justify-between font-bold text-sm tracking-wide uppercase">
                    <span>Laba Kotor (Gross Profit)</span>
                    <span>{{ formatCurrency(summary.gross_profit) }}</span>
                </div>

                <!-- Operating Expenses -->
                <section>
                    <h3 class="text-sm font-bold border-b border-muted pb-2 uppercase tracking-wide">Beban Operasional
                    </h3>
                    <div class="mt-4 space-y-2">
                        <div v-for="item in report.expenses.items" :key="item.label"
                            class="flex justify-between text-sm">
                            <span>{{ item.label }}</span>
                            <span class="text-red-500">({{ formatCurrency(item.amount) }})</span>
                        </div>
                        <div v-if="report.expenses.items.length === 0"
                            class="text-sm text-muted-foreground italic text-center py-2">
                            Tidak ada data beban operasional
                        </div>
                        <div class="flex justify-between pt-4 font-bold border-t border-muted/50">
                            <span>Total Beban Operasional</span>
                            <span class="text-red-500">({{ formatCurrency(report.expenses.total) }})</span>
                        </div>
                    </div>
                </section>

                <!-- Net Profit Final -->
                <div
                    class="p-6 bg-primary text-primary-foreground flex justify-between font-bold text-lg tracking-widest uppercase">
                    <span>Laba Bersih (Net Profit)</span>
                    <span>{{ formatCurrency(summary.net_profit) }}</span>
                </div>
            </div>

            <div class="mt-16 text-xs text-muted-foreground text-center italic border-t pt-4 border-muted">
                Laporan dihasilkan secara otomatis oleh sistem pada {{ new Date().toLocaleString('id-ID') }}
            </div>
        </div>
    </div>
</AppLayout>
</template>
