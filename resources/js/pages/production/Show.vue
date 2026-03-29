<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, PlayCircle, Info, Beaker, Package } from 'lucide-vue-next';
import { index } from '@/actions/App/Http/Controllers/ProductionController';
import { Badge } from '@/components/ui/badge';
import PageHeader from '@/components/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateString));
};
</script>

<template>
<Head :title="`Detail Produksi - ${production.sku}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6">
        <PageHeader 
            title="Production Detail" 
            :description="`${production.sku} - ${formatDate(production.created_at)}`"
            back-href="/production"
        >
            <template #actions>
                <Badge :variant="production.status === 'completed' ? 'default' : 'secondary'"
                    class="px-3 py-1 scale-110">
                    <span v-if="production.status === 'completed'" class="flex items-center gap-1.5">
                        <CheckCircle2 class="w-4 h-4" /> Selesai
                    </span>
                    <span v-else class="flex items-center gap-1.5">
                        <PlayCircle class="w-4 h-4" /> Diproses
                    </span>
                </Badge>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Side: Header Info & Result -->
            <div class="lg:col-span-1 space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm font-medium flex items-center gap-2 text-muted-foreground uppercase">
                            <Package class="w-4 h-4" /> Informasi Hasil Produksi
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Barang Yang Dibuat</p>
                            <p class="text-lg font-bold">{{ production.produk?.nama }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Resep (BOM)</p>
                            <p class="font-medium text-primary">{{ production.bom?.nama || '-' }}</p>
                        </div>
                        <div class="pt-4 border-t grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground uppercase">Target Hasil</p>
                                <p class="text-xl font-bold">{{ formatNumber(production.target_yield) }} <span
                                        class="text-sm font-normal">{{ production.produk?.satuan?.nama }}</span></p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground uppercase">Actual Yield</p>
                                <p class="text-xl font-black text-green-600">
                                    {{ production.actual_yield ? formatNumber(production.actual_yield) : '-' }}
                                    <span class="text-sm font-normal text-foreground" v-if="production.actual_yield">{{
                                        production.produk?.satuan?.nama }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="pt-4 border-t" v-if="production.status === 'completed' || production.status === 'in_progress'">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-xs text-muted-foreground uppercase">
                                    {{ production.is_estimated ? 'Estimasi Biaya' : 'Total Biaya Produksi' }}
                                </p>
                                <Badge v-if="production.is_estimated" variant="outline" class="rounded-none text-[10px] bg-orange-50 text-orange-600 border-orange-200 uppercase px-1.5 py-0">Estimasi</Badge>
                            </div>
                            <p class="text-3xl font-black text-primary">{{ formatCurrency(production.total_cost) }}</p>
                            <p class="text-xs text-muted-foreground mt-1" v-if="production.status === 'completed'">
                                HPP per {{ production.produk?.satuan?.nama }}:
                                {{ formatCurrency(production.total_cost / (production.actual_yield || 1)) }}
                            </p>
                            <p class="text-xs text-muted-foreground mt-1" v-else>
                                Target HPP per {{ production.produk?.satuan?.nama }}:
                                {{ formatCurrency(production.total_cost / (production.target_yield || 1)) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right Side: Ingredient Usage -->
            <div class="lg:col-span-2">
                <Card class="h-full">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Beaker class="w-5 h-5 text-orange-500" /> Pemakaian Bahan Baku
                        </CardTitle>
                        <CardDescription>Rincian bahan baku yang digunakan dalam batch produksi ini.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-muted/50">
                                        <TableHead>Nama Bahan</TableHead>
                                        <TableHead class="text-right">Planned</TableHead>
                                        <TableHead class="text-right">Actual</TableHead>
                                        <TableHead class="text-right">Biaya Satuan</TableHead>
                                        <TableHead class="text-right">Subtotal</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in production.items" :key="item.id"
                                        class="hover:bg-muted/20 transition-colors">
                                        <TableCell class="font-medium">
                                            {{ item.produk?.nama }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono text-xs">
                                            {{ formatNumber(item.planned_qty) }} {{ item.satuan?.nama }}
                                        </TableCell>
                                        <TableCell class="text-right font-mono text-sm font-bold"
                                            :class="parseFloat(item.actual_qty) > parseFloat(item.planned_qty) ? 'text-destructive' : ''">
                                            {{ formatNumber(item.actual_qty) }} {{ item.satuan?.nama }}
                                        </TableCell>
                                        <TableCell class="text-right text-xs">
                                            {{ formatCurrency(item.harga_satuan) }}
                                        </TableCell>
                                        <TableCell class="text-right font-bold text-primary">
                                            <div class="flex flex-col items-end">
                                                <span>{{ formatCurrency(item.cost) }}</span>
                                                <span v-if="production.is_estimated" class="text-[9px] text-orange-500 font-medium uppercase tracking-tighter">Estimasi</span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div
                            class="mt-4 flex items-start gap-2 text-xs text-muted-foreground bg-muted/30 p-3 rounded border">
                            <Info class="w-4 h-4 mt-0.5 shrink-0" />
                            <p>Harga satuan diambil dari harga beli bahan baku saat produksi diproses. Subtotal adalah
                                realisasi biaya untuk item tersebut.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</AppLayout>
</template>
