<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Plus, Trash2, ArrowLeft, Save, Info } from 'lucide-vue-next';
import { computed } from 'vue';
import { index as bomIndex, update } from '@/actions/App/Http/Controllers/BOMController';
import quickSatuanAction from '@/actions/App/Http/Controllers/QuickCreateSatuanController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

interface BOMItem {
    produk_id: string;
    satuan_id: string | number | null;
    jumlah: number;
}

const props = defineProps<{ bom: any; produks: any[]; bahanBakus: any[]; satuans: any[]; conversions: any[]; latest_production_yield?: number | null; }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'BOM', href: bomIndex.url() },
    { title: 'Edit BOM', href: '#' },
];

const form = useForm({
    sku: props.bom.sku || '',
    produk_id: props.bom.produk_id.toString(),
    nama: props.bom.nama || '',
    is_active: props.bom.is_active === true || Number(props.bom.is_active) === 1,
    expected_yield: props.bom.expected_yield || 1,
    auto_deduct_on_sale: props.bom.auto_deduct_on_sale === true || Number(props.bom.auto_deduct_on_sale) === 1,
    items: props.bom.items.map((item: any) => ({
        produk_id: item.produk_id.toString(),
        satuan_id: item.satuan_id ? item.satuan_id.toString() : null,
        jumlah: Number(item.jumlah)
    })) as BOMItem[]
});

const selectedProdukSatuan = computed(() => {
    const prd = props.produks.find((p: any) => p.id.toString() === form.produk_id);
    return prd?.satuan?.simbol || '';
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
    form.items.push({ produk_id: '', satuan_id: null, jumlah: 1 } as BOMItem);
};

const removeItem = (index: number | string) => {
    form.items.splice(Number(index), 1);
};

const onBahanSelected = (item: any, selectedProdukId: string) => {
    const bahan = props.bahanBakus.find(b => b.id.toString() === selectedProdukId);
    if (bahan && bahan.satuan_id) {
        item.satuan_id = bahan.satuan_id.toString();
    }
};

import { toast } from 'vue-sonner';

const handleCreateSatuan = async (nama: string, callback?: (id: number) => void) => {
    try {
        const simbol = nama.substring(0, 3).toLowerCase();
        const response = await axios.post(quickSatuanAction().url, {
            nama,
            simbol,
        });

        const newSatuan = response.data.satuan;
        props.satuans.push(newSatuan);

        if (callback) {
            callback(newSatuan.id);
        }
        toast.success(`Satuan ${nama} berhasil ditambahkan`);
    } catch (error) {
        console.error('Gagal menambah satuan:', error);
        toast.error('Gagal menambah satuan. Mungkin nama/simbol sudah ada.');
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
            if (conv.satuan_id === currentId && !visited.has(conv.to_satuan_id)) {
                visited.add(conv.to_satuan_id);
                queue.push([conv.to_satuan_id, currentRatio * Number(conv.rasio)]);
            }
        }

        // Inverse
        for (const conv of props.conversions) {
            if (conv.to_satuan_id === currentId && !visited.has(conv.satuan_id)) {
                visited.add(conv.satuan_id);
                queue.push([conv.satuan_id, currentRatio * (1.0 / Number(conv.rasio))]);
            }
        }
    }

    return 1.0;
};

const getItemCost = (item: any) => {
    const bahan = props.bahanBakus.find(b => b.id.toString() === item.produk_id);
    if (bahan && item.jumlah) {
        const ingredientPrice = Number(bahan.current_price?.purchase_price || 0);
        const fromUnitId = bahan.satuan_id || bahan.current_price?.satuan_id;
        const ratio = getConversionRatio(Number(fromUnitId), Number(item.satuan_id));

        return (ingredientPrice * ratio * Number(item.jumlah));
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
    <div class="p-6 flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="bomIndex.url()">
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Edit BOM</h1>
                <p class="text-muted-foreground">Sesuaikan resep produksi untuk barang jadi.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div v-if="shouldShowSyncBanner"
                class="bg-blue-500/15 border border-blue-500/50 text-blue-700 dark:text-blue-400 p-4 rounded-lg flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                <div class="flex gap-3">
                    <Info class="h-5 w-5 mt-0.5 shrink-0" />
                    <div class="space-y-1">
                        <p class="font-medium">Data Produksi Aktual Berbeda</p>
                        <p class="text-sm opacity-90">Sistem mendeteksi bahwa pada produksi terbaru, resep ini
                            menghasilkan <strong>{{ latest_production_yield }} {{ selectedProdukSatuan }}</strong>
                            aktual (berbeda dari estimasi {{ form.expected_yield }} {{ selectedProdukSatuan }}).</p>
                    </div>
                </div>
                <Button type="button" @click="syncYield" variant="default"
                    class="bg-blue-600 hover:bg-blue-700 text-white shrink-0">
                    Sync HPP Sekarang
                </Button>
            </div>

            <Card class="border-0 rounded-none shadow-none bg-transparent">
                <CardHeader class="px-0">
                    <CardTitle>Informasi Utama</CardTitle>
                    <CardDescription>Pilih produk hasil akhir dan beri nama resep.</CardDescription>
                </CardHeader>
                <CardContent class="px-0 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <CreatableSelect v-model="form.produk_id" :options="produks"
                                label="Barang Jadi / Setengah Jadi" placeholder="Pilih Produk"
                                :error="form.errors.produk_id" />
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
                            <Label for="nama">Nama Resep (Opsional)</Label>
                            <Input id="nama" v-model="form.nama" placeholder="Contoh: Resep Standar" />
                            <p v-if="form.errors.nama" class="text-sm text-destructive">{{ form.errors.nama }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="expected_yield">Estimasi Hasil Jadi (Yield)</Label>
                            <div class="flex flex-row items-center gap-2">
                                <Input id="expected_yield" type="number" step="0.0001" min="0.0001"
                                    v-model="form.expected_yield" />
                                <span class="text-muted-foreground whitespace-nowrap min-w-8">{{ selectedProdukSatuan
                                }}</span>
                            </div>
                            <p v-if="form.errors.expected_yield" class="text-sm text-destructive">{{
                                form.errors.expected_yield }}</p>
                            <p class="text-xs text-muted-foreground">Jumlah barang jadi yang dihasilkan dari komposisi
                                di bawah.</p>
                        </div>

                        <div class="flex items-center space-x-2 py-4 border-t border-muted/50 mt-4">
                            <input 
                                id="auto_deduct_on_sale" 
                                type="checkbox" 
                                v-model="form.auto_deduct_on_sale"
                                class="size-4 rounded border-input accent-primary transition-all cursor-pointer"
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
                </CardContent>
            </Card>

            <Card class="border-0 rounded-none shadow-none bg-transparent">
                <CardHeader class="px-0 flex flex-row items-center justify-between">
                    <div class="space-y-1">
                        <CardTitle>Bahan Baku & Komposisi</CardTitle>
                        <CardDescription>Daftar bahan baku yang dibutuhkan untuk 1 unit barang jadi.
                        </CardDescription>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addItem">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Bahan
                    </Button>
                </CardHeader>
                <CardContent class="px-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Bahan Baku</TableHead>
                                <TableHead width="150">Jumlah</TableHead>
                                <TableHead width="200">Satuan</TableHead>
                                <TableHead class="text-right">Harga</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, idx) in form.items" :key="idx">
                                <TableCell>
                                    <CreatableSelect v-model="item.produk_id" :options="bahanBakus"
                                        placeholder="Pilih Bahan" hide-label hide-error
                                        @update:model-value="onBahanSelected(item, $event)" />
                                </TableCell>
                                <TableCell>
                                    <Input type="number" step="0.0001" v-model="item.jumlah" />
                                </TableCell>
                                <TableCell>
                                    <CreatableSelect v-model="item.satuan_id" :options="satuans" placeholder="Satuan"
                                        hide-label hide-error display-expr="simbol"
                                        @create="(nama: string) => handleCreateSatuan(nama, (id: number) => item.satuan_id = id)" />
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
                </CardContent>
            </Card>

            <Card class="border-0 rounded-none shadow-none bg-muted/20 mt-4">
                <CardContent class="p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-sm text-muted-foreground block">Total Modal Resep:</span>
                        <span class="text-lg font-semibold">{{ formatCurrency(totalEstimatedCost) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-muted-foreground block">Estimasi HPP per {{ selectedProdukSatuan ||
                            'Unit' }}:</span>
                        <span class="text-2xl font-bold text-primary">{{ formatCurrency(totalEstimatedCost /
                            (form.expected_yield || 1)) }}</span>
                    </div>
                </CardContent>
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
