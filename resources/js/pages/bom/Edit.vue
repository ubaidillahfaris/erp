<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Trash2, ArrowLeft, Save, Info } from 'lucide-vue-next';
import { computed } from 'vue';
import { index as bomIndex, update } from '@/actions/App/Http/Controllers/BOMController';
import quickUnitAction from '@/actions/App/Http/Controllers/QuickCreateUnitController';
import CreatableSelect from '@/components/ui/input/CreatableSelect.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface BOMItem {
    product_id: string;
    unit_id: string | number | null;
    quantity: number;
}

const props = defineProps<{ bom: any; products: any[]; bahanBakus: any[]; units: any[]; conversions: any[]; latest_production_yield?: number | null; }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'BOM', href: bomIndex.url() },
    { title: 'Edit BOM', href: '#' },
];

const form = useForm({
    sku: props.bom.sku || '',
    product_id: props.bom.product_id.toString(),
    name: props.bom.name || '',
    is_active: props.bom.is_active === true || Number(props.bom.is_active) === 1,
    expected_yield: props.bom.expected_yield || 1,
    yield_unit_id: props.bom.yield_unit_id ? props.bom.yield_unit_id.toString() : '',
    auto_deduct_on_sale: props.bom.auto_deduct_on_sale === true || Number(props.bom.auto_deduct_on_sale) === 1,
    items: props.bom.items.map((item: any) => ({
        product_id: item.product_id.toString(),
        unit_id: item.unit_id ? item.unit_id.toString() : null,
        quantity: Number(item.quantity)
    })) as BOMItem[]
});

const selectedYieldUnitSimbol = computed(() => {
    const sat = props.units.find(s => s.id.toString() === form.yield_unit_id?.toString());
    return sat?.symbol || '';
});

import { watch } from 'vue';

watch(() => form.product_id, (newVal) => {
    if (newVal) {
        const prd = props.products.find((p: any) => p.id.toString() === newVal);
        if (prd && prd.unit_id) {
            form.yield_unit_id = prd.unit_id.toString();
        }
    }
});

const shouldShowSyncBanner = computed(() => {
    if (props.latest_production_yield === null || props.latest_production_yield === undefined) return false;
    return Number(props.latest_production_yield) !== Number(form.expected_yield);
});

const syncYield = () => {
    if (props.latest_production_yield !== null && props.latest_production_yield !== undefined) {
        form.expected_yield = Number(props.latest_production_yield);
    }
};

const addItem = () => {
    form.items.push({ product_id: '', unit_id: null, quantity: 1 } as BOMItem);
};

const removeItem = (index: number | string) => {
    form.items.splice(Number(index), 1);
};

const onBahanSelected = (item: any, selectedProductId: string) => {
    const bahan = props.bahanBakus.find(b => b.id.toString() === selectedProductId);
    if (bahan && bahan.unit_id) {
        item.unit_id = bahan.unit_id.toString();
    }
};

import { toast } from 'vue-sonner';

const handleCreateUnit = async (name: string, callback?: (id: number) => void) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickUnitAction().url, {
            nama,
            simbol });

        const newUnit = response.data.unit;
        props.units.push(newUnit);

        if (callback) {
            callback(newUnit.id);
        }
        toast.success(`Unit ${nama} added successfully`);
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        toast.error('Gagal menambah unit. Mungkin nama/simbol sudah ada.');
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const getConversionRatio = (fromId: number, toId: number) => {
    if (fromId === toId || !toId) return 1.0;

    const queue: [number, number][] = [[toId, 1.0]];
    const visited = new Set([toId]);

    while (queue.length > 0) {
        const [currentId, currentRatio] = queue.shift()!;

        if (currentId === fromId) return currentRatio;

        // Direct
        for (const conv of props.conversions) {
            if (conv.unit_id === currentId && !visited.has(conv.to_unit_id)) {
                visited.add(conv.to_unit_id);
                queue.push([conv.to_unit_id, currentRatio * Number(conv.rasio)]);
            }
        }

        // Inverse
        for (const conv of props.conversions) {
            if (conv.to_unit_id === currentId && !visited.has(conv.unit_id)) {
                visited.add(conv.unit_id);
                queue.push([conv.unit_id, currentRatio * (1.0 / Number(conv.rasio))]);
            }
        }
    }

    return 1.0;
};

const getItemCost = (item: any) => {
    const bahan = props.bahanBakus.find(b => b.id.toString() === item.product_id);
    if (bahan && item.quantity) {
        const ingredientPrice = Number(bahan.current_price?.purchase_price || 0);
        const fromUnitId = bahan.unit_id || bahan.current_price?.unit_id;
        const ratio = getConversionRatio(Number(fromUnitId), Number(item.unit_id));

        return (ingredientPrice * ratio * Number(item.quantity));
    }
    return 0;
};

const totalEstimatedCost = computed(() => {
    return form.items.reduce((total: number, item: any) => total + getItemCost(item), 0);
});

const submit = () => {
    form.put(update.url({ bom: props.bom.id.toString() }));
};
</script>

<template>
<Head title="Edit BOM" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="bomIndex.url()">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit BOM</h1>
                <p class="text-sm text-slate-400 mt-0.5">Sesuaikan resep productsi untuk barang jadi.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div v-if="shouldShowSyncBanner"
                class="bg-blue-500/15 border border-blue-500/50 text-blue-700 dark:text-blue-400 p-4 rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                <div class="flex gap-3">
                    <Info class="h-5 w-5 mt-0.5 shrink-0" />
                    <div class="space-y-1">
                        <p class="font-medium">Data Productsi Aktual Berbeda</p>
                        <p class="text-sm opacity-90">Sistem mendeteksi bahwa pada productsi terbaru, resep ini
                            menghasilkan <strong>{{ latest_production_yield }} {{ selectedYieldUnitSimbol }}</strong>
                            aktual (berbeda dari estimasi {{ form.expected_yield }} {{ selectedYieldUnitSimbol }}).</p>
                    </div>
                </div>
                <Button type="button" @click="syncYield" variant="default"
                    class="bg-blue-600 hover:bg-blue-700 text-white shrink-0">
                    Sync HPP Sekarang
                </Button>
            </div>

            <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Utama</h3>
                    <p class="text-xs text-slate-400 mt-1">Pilih product hasil akhir dan beri nama resep.</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Finished Goods / Setengah Jadi</Label>
                            <Combobox 
                                v-model="form.product_id"
                                :options="products.map(p => ({ value: p.id.toString(), label: p.name }))"
                                placeholder="Pilih Product"
                            />
                            <InputError :message="form.errors.product_id" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="sku">SKU Resep BOM</Label>
                            <Input id="sku" v-model="form.sku" placeholder="Contoh: BOM-0001" readonly
                                class="bg-muted cursor-not-allowed" />
                            <p class="text-xs text-muted-foreground italic">*SKU dibuat otomatis dan tidak dapat
                                diubah</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="name">Nama Resep (Opsional)</Label>
                            <Input id="name" v-model="form.name" placeholder="Contoh: Resep Standar" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="expected_yield">Estimasi Hasil Jadi (Yield)</Label>
                            <div class="flex flex-row items-center gap-2">
                                <Input id="expected_yield" type="number" step="0.0001" min="0.0001"
                                    v-model="form.expected_yield" class="w-1/2" />
                                <div class="flex-1">
                                    <CreatableSelect v-model="form.yield_unit_id" :options="units"
                                        placeholder="Pilih Unit" hide-label hide-error display-expr="symbol"
                                        @create="(name: string) => handleCreateUnit(nama, (id: number) => form.yield_unit_id = id)" />
                                </div>
                            </div>
                            <p v-if="form.errors.expected_yield" class="text-sm text-destructive">{{
                                form.errors.expected_yield }}</p>
                            <p v-if="form.errors.yield_unit_id" class="text-sm text-destructive">{{
                                form.errors.yield_unit_id }}</p>
                            <p class="text-xs text-muted-foreground">Jumlah barang jadi yang dihasilkan dari komposisi di bawah.</p>
                        </div>

                        <div class="flex items-center space-x-2 py-4 border-t border-muted/50 mt-4">
                            <input 
                                id="auto_deduct_on_sale" 
                                type="checkbox" 
                                v-model="form.auto_deduct_on_sale"
                                class="size-4 rounded border-slate-200 accent-primary transition-all cursor-pointer"
                            />
                            <div class="grid gap-1.5 leading-none">
                                <label for="auto_deduct_on_sale"
                                    class="text-sm font-bold leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    Gunakan Resep (Potong Otomatis)
                                </label>
                                <p class="text-xs text-muted-foreground">
                                    Saat barang laku di POS, bahan baku akan berkurang otomatis sesuai resep ini.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-row items-center justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Raw Materials & Komposisi</h3>
                        <p class="text-xs text-slate-400 mt-1">Daftar bahan baku yang dibutuhkan untuk 1 unit barang jadi.
                        </p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addItem">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Bahan
                    </Button>
                </div>
                <div class="p-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Raw Materials</TableHead>
                                <TableHead width="150">Quantity</TableHead>
                                <TableHead width="200">Unit</TableHead>
                                <TableHead class="text-right">Price</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, idx) in form.items" :key="idx">
                                <TableCell>
                                    <Combobox
                                        v-model="item.product_id"
                                        :options="bahanBakus.map(b => ({ value: b.id.toString(), label: b.name }))"
                                        placeholder="Pilih Bahan"
                                        @update:modelValue="onBahanSelected(item, $event)"
                                    />
                                </TableCell>
                                <TableCell>
                                    <Input type="number" step="0.0001" v-model="item.quantity" />
                                </TableCell>
                                <TableCell>
                                    <CreatableSelect v-model="item.unit_id" :options="units" placeholder="Unit"
                                        hide-label hide-error display-expr="symbol"
                                        @create="(name: string) => handleCreateUnit(nama, (id: number) => item.unit_id = id)" />
                                </TableCell>
                                <TableCell class="text-right whitespace-nowrap">
                                    {{ formatCurrency(getItemCost(item)) }}
                                </TableCell>
                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" @click="removeItem(idx)"
                                        :disabled="form.items.length <= 1">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <p v-if="form.errors.items" class="text-sm text-destructive mt-2">{{ form.errors.items }}</p>
                </div>
            </Card>

            <Card class="border border-slate-200 rounded-xl bg-white mt-4">
                <div class="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-sm text-muted-foreground block">Total Modal Resep:</span>
                        <span class="text-lg font-semibold">{{ formatCurrency(totalEstimatedCost) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-muted-foreground block">Estimasi HPP per {{ selectedYieldUnitSimbol ||
                            'Unit' }}:</span>
                        <span class="text-2xl font-bold text-primary">{{ formatCurrency(totalEstimatedCost /
                            (form.expected_yield || 1)) }}</span>
                    </div>
                </div>
            </Card>

            <div class="flex justify-end gap-3 py-6 border-t mt-4">
                <Link :href="bomIndex.url()">
                    <Button variant="outline" type="button">Kembali</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    <Save class="mr-2 h-4 w-4" />
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
</template>
