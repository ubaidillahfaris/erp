<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';

const formatCurrency = (value: number | string) => {
    return new Intl.NumberFormat('id-ID').format(Number(value));
};
const props = defineProps<{
    produk: any;
    satuans: any[];
}>();

const productTypes = [
    { value: 'finished_good', label: 'Produk Jadi (Finished Good)' },
    { value: 'intermediate_good', label: 'Bahan Setengah Jadi (Intermediate Good)' },
    { value: 'raw_material', label: 'Bahan Baku (Raw Material)' },
];

import { edit as editBomAction, create as createBomAction } from '@/actions/App/Http/Controllers/BOMController';
import { index, update as updateAction } from '@/actions/App/Http/Controllers/ProdukController';
import quickSatuanAction from '@/actions/App/Http/Controllers/QuickCreateSatuanController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: index().url },
    { title: 'Edit Produk', href: '#' },
];

const form = useForm({
    sku: props.produk.sku,
    barcode: props.produk.barcode || '',
    nama: props.produk.nama,
    kategori: props.produk.kategori || '',
    deskripsi: props.produk.deskripsi || '',
    stok_minimal: props.produk.stok_minimal,
    satuan_id: props.produk.satuan_id?.toString() || '',
    type: props.produk.type || 'finished_good',
    is_active: !!Number(props.produk.is_active),
    track_stock: !!Number(props.produk.track_stock),
    retail_price: props.produk.current_price?.retail_price || 0,
    wholesale_price: props.produk.current_price?.wholesale_price || 0,
});

const submit = () => {
    form.put(updateAction({ id: props.produk.id }).url);
};

const handleCreateSatuan = async (nama: string, onCreated?: (id: number) => void) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickSatuanAction().url, {
            nama,
            simbol,
        });

        const newSatuan = response.data.satuan;
        props.satuans.push(newSatuan);
        
        if (onCreated) {
            onCreated(newSatuan.id);
        } else {
            form.satuan_id = newSatuan.id.toString();
        }
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        alert('Gagal menambah satuan. Mungkin nama/simbol sudah ada.');
    }
};
</script>

<template>
<Head title="Edit Produk" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Edit Produk</h1>
                <p class="text-muted-foreground">Ubah detail produk yang sudah ada di sistem.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Form Edit Produk (3/4 bagian) -->
            <div class="lg:col-span-3">
                <Card class="border-0 rounded-none shadow-none bg-transparent">
                    <CardHeader class="px-0">
                        <CardTitle>Informasi Produk</CardTitle>
                        <CardDescription>
                            Lengkapi detail informasi form di bawah.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-0">
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
                                <Label for="nama">Nama Produk</Label>
                                <Input id="nama" v-model="form.nama" placeholder="Contoh: Indomie Goreng Original" required />
                                <InputError :message="form.errors.nama" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-2">
                                    <Label for="kategori">Kategori</Label>
                                    <Input id="kategori" v-model="form.kategori" placeholder="Contoh: Makanan" />
                                    <InputError :message="form.errors.kategori" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="stok_minimal">Stok Minimal Alert</Label>
                                    <Input id="stok_minimal" type="number" v-model="form.stok_minimal" required />
                                    <InputError :message="form.errors.stok_minimal" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <CreatableSelect 
                                v-model="form.type" 
                                :options="productTypes" 
                                label="Tipe Produk"
                                placeholder="Pilih Tipe" 
                                display-expr="label"
                                value-expr="value"
                                :error="form.errors.type" 
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <CreatableSelect v-model="form.satuan_id" :options="satuans" label="Satuan"
                                placeholder="Pilih atau Ketik untuk Tambah..." :error="form.errors.satuan_id"
                                @create="handleCreateSatuan" />
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 py-4 border-b border-muted/50">
                        <Checkbox id="track_stock" :checked="form.track_stock"
                            @update:checked="(val: boolean) => form.track_stock = val" />
                        <div class="grid gap-1.5 leading-none">
                            <label for="track_stock"
                                class="text-sm font-bold leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Pantau Stok Barang
                            </label>
                            <p class="text-xs text-muted-foreground">
                                Jika aktif, sistem akan menghitung saldo barang ini secara otomatis.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="deskripsi">Deskripsi (Optional)</Label>
                        <textarea id="deskripsi" v-model="form.deskripsi"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Keterangan tambahan produk..."></textarea>
                        <InputError :message="form.errors.deskripsi" />
                    </div>

                    <!-- Manajemen Harga -->
                    <div class="flex flex-col gap-4 border-t pt-6 mt-4">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Manajemen Harga</h3>
                            <p class="text-sm text-muted-foreground">Atur harga jual produk dan pantau histori harga beli.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <Label for="retail_price">Harga Jual Eceran</Label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-3 text-muted-foreground text-sm">Rp</span>
                                    <Input id="retail_price" type="number" v-model="form.retail_price" class="pl-10" />
                                </div>
                                <InputError :message="form.errors.retail_price" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="wholesale_price">Harga Grosir (Optional)</Label>
                                <div class="relative flex items-center">
                                    <span class="absolute left-3 text-muted-foreground text-sm">Rp</span>
                                    <Input id="wholesale_price" type="number" v-model="form.wholesale_price" class="pl-10" />
                                </div>
                                <InputError :message="form.errors.wholesale_price" />
                            </div>
                        </div>

                        <!-- Latest Purchase Price Info -->
                        <div v-if="produk.current_price" class="p-4 rounded-md bg-muted/50 border flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-sm font-medium">Harga Beli Terakhir (Modal)</p>
                                <p class="text-xs text-muted-foreground">Dilihat dari restock terakhir pada unit <strong>{{ produk.current_price.satuan?.simbol }}</strong></p>
                            </div>
                            <p class="text-lg font-bold text-primary">Rp {{ formatCurrency(produk.current_price.purchase_price) }}</p>
                        </div>

                        <!-- Purchase Price History -->
                        <div v-if="produk.prices && produk.prices.length > 1" class="space-y-2 mt-2">
                            <p class="text-sm font-medium">Histori Harga Beli (Modal)</p>
                            <div class="border rounded-md overflow-hidden">
                                <Table>
                                    <TableBody>
                                        <TableRow v-for="price in produk.prices" :key="price.id" :class="{'bg-muted/30': price.is_current}">
                                            <TableCell class="text-muted-foreground text-xs">{{ new Date(price.created_at).toLocaleDateString() }}</TableCell>
                                            <TableCell class="text-xs">{{ price.satuan?.nama }} ({{ price.satuan?.simbol }})</TableCell>
                                            <TableCell class="text-right font-medium">Rp {{ formatCurrency(price.purchase_price) }}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>

                            <div class="flex items-center space-x-2">
                                <Checkbox id="is_active" :checked="form.is_active"
                                    @update:checked="(val: boolean) => form.is_active = val" />
                                <Label for="is_active">Produk Aktif</Label>
                            </div>

                            <div class="flex items-center justify-end border-t pt-6">
                                <FormActionButtons :processing="form.processing" @save="submit()" />
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <!-- List BOM (1/4 bagian) -->
            <div class="lg:col-span-1" v-if="form.type !== 'raw_material'">
                <Card class="border-0 rounded-none shadow-none bg-transparent">
                    <CardHeader class="px-0">
                        <CardTitle>Daftar Resep BOM</CardTitle>
                        <CardDescription>Komposisi bahan untuk produk ini.</CardDescription>
                    </CardHeader>
                    <CardContent class="px-0">
                        <div v-if="produk.bom" class="space-y-4">
                            <ul class="text-sm space-y-2">
                                <li v-for="(item, index) in produk.bom.items" :key="item.id" 
                                    class="flex justify-between pb-2" 
                                    :class="{ 'border-b': index !== produk.bom.items.length - 1 }">
                                    <span>{{ item.produk.nama }}</span>
                                    <span class="font-medium text-muted-foreground">
                                        {{ Number(item.jumlah) }} {{ item.satuan ? item.satuan.simbol : (item.produk.satuan ? item.produk.satuan.simbol : '') }}
                                    </span>
                                </li>
                            </ul>
                            
                            <Link :href="editBomAction({ bom: produk.bom.id }).url" class="block w-full">
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
                    </CardContent>
                </Card>
            </div>
            
        </div>
    </div>
</AppLayout>
</template>
