<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';

const formatCurrency = (value: number | string) => {
    return new Intl.NumberFormat('id-ID').format(Number(value));
};
const props = defineProps<{
    product: any;
    units: any[];
    categories: any[];
    overhead_rate: number | null;
}>();

const productTypes = [
    { value: 'finished_good', label: 'Product Jadi (Finished Good)' },
    { value: 'intermediate_good', label: 'Bahan Setengah Jadi (Intermediate Good)' },
    { value: 'raw_material', label: 'Raw Materials (Raw Material)' },
    { value: 'service', label: 'Jasa / Layanan' },
];

import { watch } from 'vue';

watch(() => form.type, (newType) => {
    if (newType === 'service') {
        form.track_stock = false;
        form.is_batch_tracked = false;
        form.min_stock = 0;
    } else if (newType === 'finished_good' || newType === 'intermediate_good') {
        form.track_stock = true;
    }
});

import { edit as editBomAction, create as createBomAction } from '@/actions/App/Http/Controllers/BOMController';
import { index, update as updateAction } from '@/actions/App/Http/Controllers/ProductController';
import quickUnitAction from '@/actions/App/Http/Controllers/QuickCreateUnitController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import InputCurrency from '@/components/ui/input/InputCurrency.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Product', href: index().url },
    { title: 'Edit Product', href: '#' },
];

const form = useForm({
    sku: props.product.sku,
    barcode: props.product.barcode || '',
    name: props.product.name,
    category_id: props.product.category_id?.toString() || '',
    description: props.product.description || '',
    min_stock: props.product.min_stock,
    unit_id: props.product.unit_id?.toString() || '',
    type: props.product.type || 'finished_good',
    is_active: !!Number(props.product.is_active),
    track_stock: !!Number(props.product.track_stock),
    is_batch_tracked: !!Number(props.product.is_batch_tracked),
    overhead_rate: props.overhead_rate || 0,
    retail_price: props.product.current_price?.retail_price || 0,
    wholesale_price: props.product.current_price?.wholesale_price || 0 });

const submit = () => {
    form.put(updateAction({ id: props.product.id }).url);
};

import { toast } from 'vue-sonner';

const handleCreateUnit = async (name: string, onCreated?: (id: number) => void) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickUnitAction().url, {
            nama,
            simbol });

        const newUnit = response.data.unit;
        props.units.push(newUnit);
        
        if (onCreated) {
            onCreated(newUnit.id);
        } else {
            form.unit_id = newUnit.id.toString();
        }
        
        toast.success(`Unit ${nama} added successfully`);
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        toast.error('Gagal menambah unit. Mungkin nama/simbol sudah ada.');
    }
};
</script>

<template>
<Head title="Edit Product" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit Product</h1>
                <p class="text-sm text-slate-400 mt-0.5">Ubah detail product yang sudah ada di sistem.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Form Edit Product (3/4 bagian) -->
            <div class="lg:col-span-3">
                <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Product</h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Lengkapi detail informasi form di bawah.
                        </p>
                    </div>
                    <div class="p-6">
                        <form @submit.prevent="submit" class="flex flex-col gap-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="sku">SKU (Stock Keeping Unit)</Label>
                                    <Input id="sku" v-model="form.sku" placeholder="Contoh: IND-GR-ORI" required />
                                    <InputError :message="form.errors.sku" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="barcode">Barcode (Optional)</Label>
                                    <Input id="barcode" v-model="form.barcode" placeholder="Scan barcode barang..." />
                                    <InputError :message="form.errors.barcode" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="name">{{ form.type === 'service' ? 'Nama Jasa / Layanan' : 'Nama Product' }}</Label>
                                <Input id="name" v-model="form.name" :placeholder="form.type === 'service' ? 'Contoh: Cuci Kiloan Reguler' : 'Contoh: Indomie Goreng Original'" required />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="category_id">Category</Label>
                                    <Select v-model="form.category_id">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Pilih Kategori" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                                {{ cat.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.category_id" />
                                </div>
                                <div v-if="form.type !== 'service'" class="flex flex-col gap-2">
                                    <Label for="min_stock">Stok Minimal Alert</Label>
                                    <Input id="min_stock" type="number" v-model="form.min_stock" required />
                                    <InputError :message="form.errors.min_stock" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label>Tipe Product</Label>
                            <Combobox v-model="form.type" :options="productTypes" placeholder="Pilih Tipe" />
                            <InputError :message="form.errors.type" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <CreatableSelect v-model="form.unit_id" :options="units" label="Unit"
                                placeholder="Pilih atau Ketik untuk Tambah..." :error="form.errors.unit_id"
                                @create="handleCreateUnit" />
                        </div>
                    </div>
                    
                    <div v-if="form.type === 'finished_good' || form.type === 'intermediate_good'" class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <Label for="overhead_rate" class="text-slate-900 font-medium">Biaya Overhead per Unit</Label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm font-medium">Rp</span>
                            </div>
                            <Input 
                                id="overhead_rate" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                v-model="form.overhead_rate" 
                                class="pl-9 bg-white" 
                                placeholder="0.00"
                            />
                        </div>
                        <p class="text-xs text-slate-500">Estimasi biaya listrik & productsi per unit output.</p>
                        <InputError :message="form.errors.overhead_rate" />
                    </div>

                    <div v-if="form.type !== 'service'" class="flex items-center space-x-3 py-4 border-b border-muted/50">
                        <input 
                            type="checkbox"
                            id="track_stock" 
                            v-model="form.track_stock"
                            class="h-4 w-4 rounded border-slate-200 bg-background ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 accent-primary cursor-pointer"
                        />
                        <div class="grid gap-1.5 leading-none">
                            <label for="track_stock"
                                class="text-sm font-bold leading-none cursor-pointer">
                                Pantau Stok Barang
                            </label>
                            <p class="text-xs text-muted-foreground">
                                Jika aktif, sistem akan menghitung saldo barang ini secara otomatis.
                            </p>
                        </div>
                    </div>

                    <div v-if="form.track_stock" class="flex items-center space-x-3 py-4 border-b border-muted/50">
                        <input 
                            type="checkbox"
                            id="is_batch_tracked" 
                            v-model="form.is_batch_tracked"
                            class="h-4 w-4 rounded border-slate-200 bg-background ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 accent-primary cursor-pointer"
                        />
                        <div class="grid gap-1.5 leading-none">
                            <label for="is_batch_tracked"
                                class="text-sm font-bold leading-none cursor-pointer">
                                Lacak Batch & Expiry
                            </label>
                            <p class="text-xs text-muted-foreground">
                                Aktifkan untuk mencatat nomor batch, lot, dan tanggal kadaluarsa (FEFO).
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="description">Description (Optional)</Label>
                        <textarea id="description" v-model="form.description"
                                    class="flex min-h-[80px] w-full rounded-xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Keterangan tambahan product..."></textarea>
                        <InputError :message="form.errors.description" />
                    </div>

                    <!-- Manajemen Harga -->
                    <div class="flex flex-col gap-4 border-t pt-6 mt-4">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Manajemen Harga</h3>
                            <p class="text-sm text-muted-foreground">Atur harga jual product dan pantau histori harga beli.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="retail_price">{{ form.type === 'service' ? 'Tarif Jasa' : 'Harga Jual Eceran' }}</Label>
                                <InputCurrency id="retail_price" v-model="form.retail_price" required />
                                <InputError :message="form.errors.retail_price" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="wholesale_price">Harga Grosir (Optional)</Label>
                                <InputCurrency id="wholesale_price" v-model="form.wholesale_price" />
                                <InputError :message="form.errors.wholesale_price" />
                            </div>
                        </div>

                        <!-- Latest Purchase Price Info -->
                        <div v-if="product.current_price" class="p-4 rounded-xl bg-muted/50 border flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">Harga Beli Terakhir (Modal)</p>
                                <p class="text-xs text-muted-foreground">Dilihat dari restock terakhir pada unit <strong>{{ product.current_price.unit?.symbol }}</strong></p>
                            </div>
                            <p class="text-lg font-bold text-primary">Rp {{ formatCurrency(product.current_price.purchase_price) }}</p>
                        </div>

                        <!-- Purchase Price History -->
                        <div v-if="product.prices && product.prices.length > 1" class="space-y-2 mt-2">
                            <p class="text-sm font-medium">Histori Harga Beli (Modal)</p>
                            <div class="border rounded-xl overflow-hidden">
                                <Table>
                                    <TableBody>
                                        <TableRow v-for="price in product.prices" :key="price.id" :class="{'bg-muted/30': price.is_current}">
                                            <TableCell class="text-muted-foreground text-xs">{{ new Date(price.created_at).toLocaleDateString() }}</TableCell>
                                            <TableCell class="text-xs">{{ price.unit?.name }} ({{ price.unit?.symbol }})</TableCell>
                                            <TableCell class="text-right font-medium">Rp {{ formatCurrency(price.purchase_price) }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>

                            <div class="flex items-center space-x-3">
                                <input 
                                    type="checkbox"
                                    id="is_active" 
                                    v-model="form.is_active"
                                    class="h-4 w-4 rounded border-slate-200 bg-background ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 accent-primary cursor-pointer"
                                />
                                <Label for="is_active" class="cursor-pointer">Product Aktif</Label>
                            </div>

                            <div class="flex items-center justify-end border-t pt-6">
                                <FormActionButtons :processing="form.processing" @save="submit()" />
                            </div>
                        </form>
                    </div>
                </Card>
            </div>

            <!-- List BOM (1/4 bagian) -->
            <div class="lg:col-span-1" v-if="form.type !== 'raw_material'">
                <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Daftar Resep BOM</h3>
                        <p class="text-xs text-slate-400 mt-1">Komposisi bahan untuk product ini.</p>
                    </div>
                    <div class="p-6">
                        <div v-if="product.bom" class="space-y-4">
                            <ul class="text-sm space-y-2">
                                <li v-for="(item, index) in product.bom.items" :key="item.id" 
                                    class="flex justify-between pb-2" 
                                    :class="{ 'border-b': index !== product.bom.items.length - 1 }">
                                    <span>{{ item.product.name }}</span>
                                    <span class="font-medium text-muted-foreground">
                                        {{ Number(item.quantity) }} {{ item.unit ? item.unit.symbol : (item.product.unit ? item.product.unit.symbol : '') }}
                                    </span>
                                </li>
                            </ul>
                            
                            <Link :href="editBomAction({ bom: product.bom.id }).url" class="block w-full">
                                <Button type="button" variant="outline" class="w-full">
                                    Edit Resep BOM
                                </Button>
                            </Link>
                        </div>
                        <div v-else class="text-center py-6 space-y-4">
                            <p class="text-sm text-muted-foreground">Belum ada resep BOM.</p>
                            <Link :href="createBomAction().url">
                                <Button type="button" variant="outline" class="w-full">
                                    Buat Resep BOM
                                </Button>
                            </Link>
                        </div>
                    </div>
                </Card>
            </div>
            
        </div>
    </div>
</AppLayout>
</template>
