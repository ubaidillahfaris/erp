<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Trash2, ArrowLeft, Save, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import { update, index as restockIndex } from '@/actions/App/Http/Controllers/RestockController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import InputCurrency from '@/components/ui/input/InputCurrency.vue';
import InputError from '@/components/InputError.vue';
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

const props = defineProps<{ 
    restock: any;
    bahanBakus: any[]; 
    satuans: any[];
    vendors: any[];
}>();

const paymentStatuses = [
    { value: 'lunas', label: 'Lunas' },
    { value: 'hutang', label: 'Hutang (Belum Bayar)' },
    { value: 'bayar_berkala', label: 'Bayar Berkala / DP' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Restock', href: restockIndex.url() },
    { title: 'Edit Restock', href: '#' },
];

// Format initial items from props
const initialItems = props.restock.items.map((item: any) => {
    let satuanId = item.satuan_id?.toString() || '';
    
    // Recovery: If satuan_id is missing, try to find it from bahanBakus
    if (!satuanId && item.produk_id) {
        const product = props.bahanBakus.find(p => p.id == item.produk_id);
        if (product) {
            satuanId = product.satuan_id?.toString() || '';
        }
    }

    return {
        produk_id: item.produk_id.toString(),
        satuan_id: satuanId,
        jumlah: Number(item.jumlah),
        harga_satuan: Number(item.harga_satuan)
    };
});

const form = useForm({
    tanggal: props.restock.tanggal.split('T')[0],
    vendor_id: props.restock.vendor_id?.toString() || '',
    keterangan: props.restock.keterangan || '',
    status_pembayaran: props.restock.status_pembayaran || 'lunas',
    total_bayar: Number(props.restock.total_bayar || 0),
    biaya_tambahan: (props.restock.biaya_tambahan || []) as { nama: string, nominal: number }[],
    items: initialItems.length > 0 ? initialItems : [{ produk_id: '', satuan_id: '', jumlah: 1, harga_satuan: 0 }]
});

const addItem = () => {
    form.items.push({ produk_id: '', satuan_id: '', jumlah: 1, harga_satuan: 0 });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const addAdjustment = () => {
    form.biaya_tambahan.push({ nama: '', nominal: 0 });
};

const removeAdjustment = (index: number) => {
    form.biaya_tambahan.splice(index, 1);
};

const handleProductChange = (index: number, produkId: string | number) => {
    const product = props.bahanBakus.find(p => p.id == produkId);
    if (product) {
        form.items[index].satuan_id = product.satuan_id;
    }
};

const submit = () => {
    form.put(update({ restock: props.restock.id }).url);
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const totalBiaya = computed(() => {
    const itemsTotal = form.items.reduce((total: number, item: any) => {
        return total + (item.jumlah * item.harga_satuan);
    }, 0);
    
    const adjustmentsTotal = form.biaya_tambahan.reduce((total: number, adj: any) => {
        return total + Number(adj.nominal || 0);
    }, 0);
    
    return itemsTotal + adjustmentsTotal;
});

const sisaPembayaran = computed(() => {
    return totalBiaya.value - form.total_bayar;
});
</script>

<template>
<Head title="Edit Restock" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="restockIndex.url()">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit Restock</h1>
                <p class="text-sm text-slate-400 mt-0.5">Ubah detail transaksi pengadaan bahan baku.</p>
            </div>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="p-4 bg-destructive/10 border border-destructive/20 rounded-xl text-destructive">
            <p class="font-medium">Ada kesalahan pada form:</p>
            <ul class="list-disc list-inside text-sm">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <!-- Info Transaksi -->
            <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Transaksi</h3>
                    <p class="text-xs text-slate-400 mt-1">Ubah tanggal pengadaan dan keterangan jika diperlukan.</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="tanggal">Tanggal Pembelian</Label>
                            <Input id="tanggal" type="date" v-model="form.tanggal" required />
                            <p v-if="form.errors.tanggal" class="text-sm text-destructive">{{ form.errors.tanggal }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label>Supplier / Vendor</Label>
                            <Combobox 
                                v-model="form.vendor_id" 
                                :options="vendors.map(v => ({ value: v.id.toString(), label: v.nama }))" 
                                placeholder="Pilih Vendor" 
                            />
                            <InputError :message="form.errors.vendor_id" />
                        </div>
                        <div class="space-y-2">
                            <Label for="keterangan">Keterangan Tambahan (Opsional)</Label>
                            <Input id="keterangan" v-model="form.keterangan"
                                placeholder="Misal: No Nota, Catatan tambahan" />
                            <p v-if="form.errors.keterangan" class="text-sm text-destructive">{{
                                form.errors.keterangan }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Status Pembayaran</Label>
                            <Combobox 
                                v-model="form.status_pembayaran" 
                                :options="paymentStatuses" 
                                placeholder="Pilih Status" 
                            />
                            <InputError :message="form.errors.status_pembayaran" />
                        </div>
                        <div class="space-y-2" v-if="form.status_pembayaran !== 'lunas'">
                            <Label for="total_bayar">Jumlah Dibayar (DP)</Label>
                            <InputCurrency id="total_bayar" v-model="form.total_bayar" />
                            <p v-if="form.status_pembayaran === 'bayar_berkala'" class="text-xs text-muted-foreground italic">
                                Sisa hutang: {{ formatCurrency(sisaPembayaran) }}
                            </p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Daftar Barang -->
            <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-row items-center justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Daftar Bahan Baku Dibeli</h3>
                        <p class="text-xs text-slate-400 mt-1">Sesuaikan daftar bahan baku, jumlah, atau harganya.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addItem">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Baris
                    </Button>
                </div>
                <div class="p-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Bahan Baku</TableHead>
                                <TableHead width="150">Satuan</TableHead>
                                <TableHead width="120">Jumlah</TableHead>
                                <TableHead width="200">Harga Satuan (Rp)</TableHead>
                                <TableHead width="200" class="text-right">Subtotal</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, idx) in form.items" :key="idx">
                                <!-- Select Bahan -->
                                <TableCell>
                                    <Combobox 
                                        v-model="item.produk_id" 
                                        :options="bahanBakus.map(b => ({ value: b.id.toString(), label: b.nama }))" 
                                        placeholder="Pilih Bahan"
                                        @update:modelValue="(val: any) => handleProductChange(idx as number, val)"
                                    />
                                    <InputError :message="form.errors[`items.${idx}.produk_id` as keyof typeof form.errors]" />
                                </TableCell>
                                <!-- Select Satuan -->
                                <TableCell>
                                    <CreatableSelect 
                                        v-model="item.satuan_id" 
                                        :options="satuans" 
                                        placeholder="Satuan"
                                        hide-label
                                        display-expr="simbol"
                                        value-expr="id"
                                    />
                                    <InputError :message="form.errors[`items.${idx}.satuan_id` as keyof typeof form.errors]" />
                                </TableCell>
                                <!-- Input Jumlah -->
                                <TableCell>
                                    <Input type="number" step="0.0001" v-model="item.jumlah" required min="0.0001" />
                                    <InputError :message="form.errors[`items.${idx}.jumlah` as keyof typeof form.errors]" />
                                </TableCell>
                                <!-- Input Harga Satuan -->
                                <TableCell>
                                    <InputCurrency v-model="item.harga_satuan" required />
                                    <InputError :message="form.errors[`items.${idx}.harga_satuan` as keyof typeof form.errors]" />
                                </TableCell>
                                <!-- Subtotal Text -->
                                <TableCell class="text-right font-medium">
                                    {{ formatCurrency(item.jumlah * item.harga_satuan) }}
                                </TableCell>
                                <!-- Tombol Hapus -->
                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" @click="removeItem(idx as number)"
                                        :disabled="form.items.length <= 1">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <!-- Biaya Tambahan / Penyesuaian -->
            <Card class="border border-slate-200 rounded-xl bg-white">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-row items-center justify-between">
                    <div class="space-y-1">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Biaya Tambahan / Penyesuaian</h3>
                        <p class="text-xs text-slate-400 mt-1">Tambahkan biaya lain (ongkir, packing) atau diskon.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addAdjustment">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Biaya
                    </Button>
                </div>
                <div class="p-6">
                    <Table v-if="form.biaya_tambahan.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama Biaya / Penyesuaian</TableHead>
                                <TableHead width="200">Nominal (Rp)</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(adj, adjIdx) in form.biaya_tambahan" :key="adjIdx">
                                <TableCell>
                                    <Input v-model="adj.nama" placeholder="Misal: Ongkir, Diskon" />
                                    <InputError :message="form.errors[`biaya_tambahan.${adjIdx}.nama`]" />
                                </TableCell>
                                <TableCell>
                                    <InputCurrency v-model="adj.nominal" placeholder="Gunakan minus untuk diskon" />
                                    <InputError :message="form.errors[`biaya_tambahan.${adjIdx}.nominal`]" />
                                </TableCell>
                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" @click="removeAdjustment(adjIdx)">
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <div v-else class="text-center py-6 bg-muted/30 rounded-xl border border-dashed">
                        <p class="text-sm text-muted-foreground">Belum ada biaya tambahan (ongkir/diskon).</p>
                    </div>
                </div>
            </Card>

            <!-- Ringkasan Total -->
            <Card class="border border-slate-200 rounded-xl bg-white">
                <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-sm text-muted-foreground">Status Pembayaran: <span class="font-medium text-foreground uppercase">{{ form.status_pembayaran.replace('_', ' ') }}</span></span>
                        <span v-if="form.status_pembayaran !== 'lunas'" class="text-sm text-muted-foreground italic">
                            Sudah dibayar: {{ formatCurrency(form.total_bayar) }}
                        </span>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-sm text-muted-foreground mr-4">Total Keseluruhan:</span>
                        <span class="text-2xl font-bold">{{ formatCurrency(totalBiaya) }}</span>
                    </div>
                </div>
            </Card>

            <div class="flex justify-end gap-3 py-6 border-t mt-4">
                <Link :href="restockIndex.url()">
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
