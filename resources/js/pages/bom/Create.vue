<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { index as bomIndex, store } from '@/actions/App/Http/Controllers/BOMController';
import quickUnitAction from '@/actions/App/Http/Controllers/QuickCreateUnitController';
import CreatableSelect from '@/components/ui/input/CreatableSelect.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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

const props = defineProps<{
    products: any[];
    bahanBakus: any[];
    units: any[];
    conversions: any[];
}>();

const localUnits = ref([...props.units]);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'BOM', href: bomIndex.url() },
    { title: 'Tambah BOM', href: '#' },
];

const form = useForm({
    sku: '',
    product_id: '',
    name: '',
    is_active: true,
    expected_yield: 1,
    yield_unit_id: '' as string | number,
    auto_deduct_on_sale: false,
    items: [
        { product_id: '', unit_id: null, quantity: 1 } as BOMItem
    ]
});

const selectedYieldUnitSimbol = computed(() => {
    const sat = localUnits.value.find(s => s.id.toString() === form.yield_unit_id?.toString());
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

const addItem = () => {
    form.items.push({ product_id: '', unit_id: null, quantity: 1 } as BOMItem);
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
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
        localUnits.value.push(newUnit);

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
    form.post(store.url());
};
</script>

<template>
<Head title="Buat BOM Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="bomIndex.url()">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Buat BOM Baru</h1>
                <p class="text-sm text-slate-400 mt-0.5">Tentukan resep productsi untuk barang jadi.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
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
                            <Label for="sku">SKU Resep BOM (Opsional)</Label>
                            <Input id="sku" v-model="form.sku" placeholder="Kosongkan untuk generate otomatis" />
                            <p v-if="form.errors.sku" class="text-sm text-destructive">{{ form.errors.sku }}</p>
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
                                    <CreatableSelect v-model="form.yield_unit_id" :options="localUnits"
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
                            <input id="auto_deduct_on_sale" type="checkbox" v-model="form.auto_deduct_on_sale"
                                class="size-4 rounded border-slate-200 accent-primary transition-all cursor-pointer" />
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
                        <p class="text-xs text-slate-400 mt-1">Daftar bahan baku yang dibutuhkan untuk 1 unit barang jadi.</p>
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
                                    <CreatableSelect v-model="item.unit_id" :options="localUnits"
                                        placeholder="Unit" hide-label hide-error display-expr="symbol"
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
                    Simpan BOM
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
</template>
