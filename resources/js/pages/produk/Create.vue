<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import CreatableSelect from '@/components/CreatableSelect.vue';
import axios from 'axios';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import { ArrowLeft } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { store, index } from '@/actions/App/Http/Controllers/ProdukController';
import quickSatuanAction from '@/actions/App/Http/Controllers/QuickCreateSatuanController';
const props = defineProps<{
    satuans: Array<{
        id: number;
        nama: string;
        simbol: string;
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
    kategori: '',
    deskripsi: '',
    stok_minimal: 0,
    satuan_id: '',
    type: 'finished_good',
    track_stock: true,
    add_another: false,
});

const submit = (addAnother = false) => {
    form.add_another = addAnother;
    form.post(store().url, {
        onSuccess: () => {
            if (addAnother) {
                form.reset();
                // Opsional: Focus kembali ke input pertama
                document.getElementById('nama')?.focus();
            }
        },
    });
};

const handleCreateSatuan = async (nama: string) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickSatuanAction().url, {
            nama,
            simbol,
        });

        // Add to local options
        props.satuans.push(response.data.satuan);
        // Select it
        form.satuan_id = response.data.satuan.id;
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        alert('Gagal menambah satuan. Mungkin nama/simbol sudah ada.');
    }
};
</script>

<template>
<Head title="Tambah Produk Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tambah Produk Baru</h1>
                <p class="text-muted-foreground">Lengkapi detail produk untuk ditambahkan ke dalam sistem warung.</p>
            </div>
        </div>

        <Card class="border-0 rounded-none shadow-none bg-transparent">
            <CardHeader class="px-0">
                <CardTitle>Informasi Produk</CardTitle>
                <CardDescription>
                    Lengkapi detail informasi form di bawah.
                </CardDescription>
            </CardHeader>
            <CardContent class="px-0">
                <form @submit.prevent="submit(false)" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="sku">SKU (Stock Keeping Unit)</Label>
                            <Input id="sku" v-model="form.sku" placeholder="Kosongkan untuk auto-generate" />
                            <p class="text-[10px] text-muted-foreground italic">*Contoh auto: IND-0001</p>
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
                            <CreatableSelect v-model="form.type" :options="productTypes" label="Tipe Produk"
                                placeholder="Pilih Tipe" display-expr="label" value-expr="value"
                                :error="form.errors.type" />
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

                    <FormActionButtons :processing="form.processing" show-add-another @save="submit(false)"
                        @save-and-add-another="submit(true)" />
                </form>
            </CardContent>
        </Card>
    </div>
</AppLayout>
</template>
