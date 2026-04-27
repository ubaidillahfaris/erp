<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, ArrowLeft, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import { store, index as restockIndex } from '@/actions/App/Http/Controllers/RestockController';
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
    bahanBakus: any[]; 
    units: any[];
    vendors: any[];
    productId?: string | number;
}>();

const paymentStatuses = [
    { value: 'lunas', label: 'Lunas' },
    { value: 'hutang', label: 'Hutang (Belum Bayar)' },
    { value: 'bayar_berkala', label: 'Bayar Berkala / DP' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Restock', href: restockIndex.url() },
    { title: 'Catat Restock', href: '#' },
];

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    vendor_id: '' as string | number,
    notes: '',
    status_pembayaran: 'lunas',
    total_bayar: 0,
    cost_tambahan: [] as { name: string, nominal: number }[],
    items: [
        { product_id: props.productId || '', unit_id: '', quantity: 1, unit_price: 0, batch_number: '', lot_number: '', expiry_date: '' }
    ]
});

// Auto-fill unit_id and price if productId is provided
if (props.productId) {
    const product = props.bahanBakus.find(p => p.id == props.productId);
    if (product) {
        form.items[0].unit_id = product.unit_id;
        if (product.current_price) {
            form.items[0].unit_price = Number(product.current_price.purchase_price);
        }
    }
}

const addItem = () => {
    form.items.push({ product_id: '', unit_id: '', quantity: 1, unit_price: 0, batch_number: '', lot_number: '', expiry_date: '' });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const addAdjustment = () => {
    form.cost_tambahan.push({ name: '', nominal: 0 });
};

const removeAdjustment = (index: number) => {
    form.cost_tambahan.splice(index, 1);
};

const handleProductChange = (index: number, productId: string | number) => {
    const product = props.bahanBakus.find(p => p.id == productId);
    if (product) {
        form.items[index].unit_id = product.unit_id;
        
        // Auto-fill last purchase price if available
        if (product.current_price) {
            form.items[index].unit_price = Number(product.current_price.purchase_price);
        }
    }
};





const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const totalBiaya = computed(() => {
    const itemsTotal = form.items.reduce((total, item) => {
        return total + (item.quantity * item.unit_price);
    }, 0);
    
    const adjustmentsTotal = form.cost_tambahan.reduce((total, adj) => {
        return total + Number(adj.nominal || 0);
    }, 0);
    
    return itemsTotal + adjustmentsTotal;
});

const sisaPembayaran = computed(() => {
    return totalBiaya.value - form.total_bayar;
});

const submit = () => {
    // Ensure total_bayar is a number before submitting
    if (form.total_bayar === null || form.total_bayar === undefined) {
        form.total_bayar = 0;
    }
    
    // For "lunas", ensure total_bayar equals totalBiaya
    if (form.status_pembayaran === 'lunas') {
        form.total_bayar = totalBiaya.value;
    }

    form.post(store.url());
};

// Auto-sync total_bayar based on payment status
watch(() => form.status_pembayaran, (newStatus) => {
    if (newStatus === 'lunas') {
        form.total_bayar = totalBiaya.value;
    } else if (newStatus === 'hutang') {
        form.total_bayar = 0;
    }
});

// If status is "lunas", update total_bayar whenever totalBiaya changes
watch(totalBiaya, (newTotal) => {
    if (form.status_pembayaran === 'lunas') {
        form.total_bayar = newTotal;
    }
});
</script>

<template>
<Head title="Catat Restock Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="restockIndex.url()">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Catat Restock Baru</h1>
                <p class="text-sm text-slate-400 mt-0.5">Catat bahan baku yang baru dibeli untuk menambah stok productsi.</p>
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
                    <p class="text-xs text-slate-400 mt-1">Pilih tanggal pengadaan dan keterangan (opsional).</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <Label for="date">Tanggal Pembelian</Label>
                            <Input id="date" type="date" v-model="form.date" required />
                            <p v-if="form.errors.date" class="text-sm text-destructive">{{ form.errors.date }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label>Supplier / Vendor</Label>
                            <Combobox 
                                v-model="form.vendor_id" 
                                :options="vendors.map(v => ({ value: v.id.toString(), label: v.name }))" 
                                placeholder="Pilih Vendor" 
                            />
                            <InputError :message="form.errors.vendor_id" />
                        </div>
                        <div class="space-y-2 col-span-1 md:col-span-1">
                            <Label for="notes">Keterangan Tambahan (Opsional)</Label>
                            <Input id="notes" v-model="form.notes"
                                placeholder="Misal: No Nota, Catatan tambahan" />
                            <p v-if="form.errors.notes" class="text-sm text-destructive">{{
                                form.errors.notes }}</p>
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
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Daftar Raw Materials Dibeli</h3>
                        <p class="text-xs text-slate-400 mt-1">Tambahkan bahan baku berserta jumlah dan harga satuannya.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addItem" :disabled="!!props.productId">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Baris
                    </Button>
                </div>
                <div class="p-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Raw Materials</TableHead>
                                <TableHead width="100">Unit</TableHead>
                                <TableHead width="100">Qty</TableHead>
                                <TableHead width="150">Harga Unit</TableHead>
                                <TableHead>Batch / Exp (FEFO)</TableHead>
                                <TableHead width="150" class="text-right">Subtotal</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, idx) in form.items" :key="idx">
                                <!-- Select Bahan -->
                                <TableCell>
                                    <Combobox 
                                        v-model="item.product_id" 
                                        :options="bahanBakus.map(b => ({ value: b.id.toString(), label: b.name }))" 
                                        placeholder="Pilih Bahan"
                                        @update:modelValue="(val) => handleProductChange(idx, val)"
                                        :disabled="!!props.productId && idx === 0"
                                    />
                                    <InputError :message="form.errors[`items.${idx}.product_id`]" />
                                </TableCell>
                                <!-- Select Unit -->
                                <TableCell>
                                    <CreatableSelect 
                                        v-model="item.unit_id" 
                                        :options="units" 
                                        placeholder="Unit"
                                        hide-label
                                        display-expr="symbol"
                                        value-expr="id"
                                    />
                                    <InputError :message="form.errors[`items.${idx}.unit_id`]" />
                                </TableCell>
                                <!-- Input Jumlah -->
                                <TableCell>
                                    <Input type="number" step="0.0001" v-model="item.quantity" required min="0.0001" />
                                    <InputError :message="form.errors[`items.${idx}.quantity`]" />
                                </TableCell>
                                <!-- Input Harga Unit -->
                                <TableCell>
                                    <InputCurrency v-model="item.unit_price" required />
                                    <InputError :message="form.errors[`items.${idx}.unit_price`]" />
                                </TableCell>
                                <!-- Batch Info -->
                                <TableCell>
                                    <div v-if="bahanBakus.find(p => p.id == item.product_id)?.is_batch_tracked" class="flex flex-col gap-2 min-w-[200px]">
                                        <Input v-model="item.batch_number" placeholder="Nomor Batch" class="h-8 text-xs" />
                                        <div class="flex gap-1 items-center">
                                            <span class="text-[10px] text-muted-foreground shrink-0 w-8">Exp:</span>
                                            <Input type="date" v-model="item.expiry_date" class="h-8 text-xs" />
                                        </div>
                                        <InputError :message="form.errors[`items.${idx}.batch_number`]" />
                                        <InputError :message="form.errors[`items.${idx}.expiry_date`]" />
                                    </div>
                                    <div v-else class="text-xs text-muted-foreground italic">
                                        Tidak dilacak
                                    </div>
                                </TableCell>
                                <!-- Subtotal Text -->
                                <TableCell class="text-right font-medium">
                                    {{ formatCurrency(item.quantity * item.unit_price) }}
                                </TableCell>
                                <!-- Tombol Hapus -->
                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" @click="removeItem(idx)"
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
                    <Table v-if="form.cost_tambahan.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama Biaya / Penyesuaian</TableHead>
                                <TableHead width="200">Nominal (Rp)</TableHead>
                                <TableHead width="50"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(adj, adjIdx) in form.cost_tambahan" :key="adjIdx">
                                <TableCell>
                                    <Input v-model="adj.name" placeholder="Misal: Ongkir, Diskon" />
                                    <InputError :message="form.errors[`cost_tambahan.${adjIdx}.name`]" />
                                </TableCell>
                                <TableCell>
                                    <InputCurrency v-model="adj.nominal" placeholder="Gunakan minus untuk diskon" />
                                    <InputError :message="form.errors[`cost_tambahan.${adjIdx}.nominal`]" />
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
                    Simpan Restock
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
</template>
