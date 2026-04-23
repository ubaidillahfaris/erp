<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';
import { store, index } from '@/actions/App/Http/Controllers/ProdukController';
import quickSatuanAction from '@/actions/App/Http/Controllers/QuickCreateSatuanController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
const props = defineProps<{
    satuans: Array<{
        id: number;
        nama: string;
        simbol: string;
    }>;
    categories: Array<{
        id: number;
        name: string;
    }>;
}>();

const productTypes = [
    { value: 'finished_good', label: 'Produk Jadi (Finished Good)' },
    { value: 'intermediate_good', label: 'Bahan Setengah Jadi (Intermediate Good)' },
    { value: 'raw_material', label: 'Bahan Baku (Raw Material)' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produk', href: index().url },
    { title: 'Tambah Produk', href: '#' },
];

const form = useForm({
    sku: '',
    barcode: '',
    nama: '',
    category_id: '',
    deskripsi: '',
    stok_minimal: 0,
    satuan_id: '',
    type: 'finished_good',
    track_stock: true,
    overhead_rate: 0,
    add_another: false });

const submit = (addAnother = false) => {
    form.add_another = addAnother;
    form.post(store().url, {
        onSuccess: () => {
            if (addAnother) {
                form.reset();
                // Opsional: Focus kembali ke input pertama
                document.getElementById('nama')?.focus();
            }
        } });
};

import { toast } from 'vue-sonner';

const handleCreateSatuan = async (nama: string) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickSatuanAction().url, {
            nama,
            simbol });

        // Add to local options
        props.satuans.push(response.data.satuan);
        // Select it
        form.satuan_id = response.data.satuan.id;
        toast.success(`Satuan ${nama} berhasil ditambahkan`);
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        toast.error('Gagal menambah satuan. Mungkin nama/simbol sudah ada.');
    }
};
</script>

<template>
<Head title="Tambah Produk Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Tambah Produk Baru</h1>
                <p class="text-sm text-slate-400 mt-0.5">Lengkapi detail produk untuk ditambahkan ke dalam sistem warung.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Produk</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Lengkapi detail informasi form di bawah.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit(false)" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="sku">SKU (Stock Keeping Unit)</Label>
                            <Input id="sku" v-model="form.sku" placeholder="Kosongkan untuk auto-generate" />
                            <p class="text-xs text-muted-foreground italic">*Contoh auto: IND-0001</p>
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
                            <Label for="category_id">Kategori</Label>
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
                        <div class="flex flex-col gap-2">
                            <Label for="stok_minimal">Stok Minimal Alert</Label>
                            <Input id="stok_minimal" type="number" v-model="form.stok_minimal" required />
                            <InputError :message="form.errors.stok_minimal" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label>Tipe Produk</Label>
                            <Combobox v-model="form.type" :options="productTypes" placeholder="Pilih Tipe" />
                            <InputError :message="form.errors.type" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <CreatableSelect v-model="form.satuan_id" :options="satuans" label="Satuan"
                                placeholder="Pilih atau Ketik untuk Tambah..." :error="form.errors.satuan_id" />
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
                        <p class="text-xs text-slate-500">Estimasi biaya listrik & produksi per unit output.</p>
                        <InputError :message="form.errors.overhead_rate" />
                    </div>

                    <div class="flex items-center space-x-3 py-4 border-b border-muted/50">
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

                    <div class="flex flex-col gap-2">
                        <Label for="deskripsi">Deskripsi (Optional)</Label>
                        <textarea id="deskripsi" v-model="form.deskripsi"
                            class="flex min-h-[80px] w-full rounded-xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan tambahan produk..."></textarea>
                        <InputError :message="form.errors.deskripsi" />
                    </div>

                    <FormActionButtons :processing="form.processing" show-add-another @save="submit(false)"
                        @save-and-add-another="submit(true)" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
