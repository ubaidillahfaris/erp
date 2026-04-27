<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Plus, Trash2, ArrowLeft, Save, Building2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { store, index } from '@/actions/App/Http/Controllers/PurchaseController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow } from '@/components/ui/table';
import { Card } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import FileDropzone from '@/components/FileDropzone.vue';
import QuickVendorModal from '@/components/QuickVendorModal.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{ 
    products: any[]; 
    units: any[];
    vendors: any[];
    productId?: string | number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchasing Inbound', href: index().url },
    { title: 'Catat Inbound Baru', href: '#' },
];

const transactionTypes = [
    { value: 'purchase', label: 'Pembelian (Hutang/Tunai)' },
    { value: 'gift', label: 'Pemberian (Bonus/Hadiah)' },
    { value: 'adjustment', label: 'Penyesuaian (Selisih Stok Naik)' },
];

const form = useForm({
    date: new Date().toISOString().split('T')[0],
    transaction_type: 'purchase',
    payment_method: 'cash',
    vendor_id: '' as string | number | null,
    notes: '',
    no_invoice: '',
    items: [
        { product_id: props.productId || '', unit_id: '', quantity: 1, unit_price: 0, batch_number: '', expiry_date: '' }
    ],
    attachments: [] as File[]
});

// Auto-fill unit_id and price if productId is provided from backend link
if (props.productId) {
    const product = props.products.find(p => p.id == props.productId);
    if (product) {
        form.items[0].unit_id = product.unit_id;
        if (product.current_price) {
            form.items[0].unit_price = Number(product.current_price.purchase_price);
        }
    }
}

const isQuickVendorOpen = ref(false);

const addItem = () => {
    form.items.push({ product_id: '', unit_id: '', quantity: 1, unit_price: 0, batch_number: '', expiry_date: '' });
};

const removeItem = (idx: number) => {
    form.items.splice(idx, 1);
};

const handleProductChange = (idx: number, productId: string | number) => {
    const product = props.products.find(p => p.id == productId);
    if (product) {
        form.items[idx].unit_id = product.unit_id;
        if (form.transaction_type === 'purchase' && product.current_price) {
            form.items[idx].unit_price = Number(product.current_price.purchase_price);
        }
    }
};

// Force unit_price to 0 if not purchase
watch(() => form.transaction_type, (newType) => {
    if (newType !== 'purchase') {
        form.vendor_id = null;
        form.items.forEach(item => {
            item.unit_price = 0;
        });
    }
});

const submit = () => {
    form.post(store().url, {
        forceFormData: true,
        preserveScroll: true
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

const totalBiaya = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (Number(item.quantity) * Number(item.unit_price));
    }, 0);
});

const handleVendorCreated = (vendor: { id: number; name: string }) => {
    props.vendors.push(vendor);
    form.vendor_id = vendor.id;
};
</script>

<template>
<Head title="Catat Inbound Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <QuickVendorModal 
            v-model:open="isQuickVendorOpen" 
            @created="handleVendorCreated" 
        />

        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="ghost" size="icon" class="bg-white hover:bg-slate-100 shadow-none border border-slate-200">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Catat Inbound Baru</h1>
                <p class="text-muted-foreground mt-1">Buat dokumen penerimaan barang untuk memperbarui stok gudang.</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6 max-w-7xl">
            <!-- HEADER DAFTAR -->
            <Card class="border-slate-200">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-sm font-semibold text-slate-900 leading-none">Metadata Transaksi</h3>
                    <p class="text-xs text-slate-400 mt-1">Pilih tipe transaksi dan profil rekanan.</p>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <Label for="transaction_type">Tipe Transaksi</Label>
                            <Select v-model="form.transaction_type" id="transaction_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih Tipe Inbound" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in transactionTypes" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">Selain opsi "Pembelian", harga satuan akan dikunci ke Rp0.</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="date">Tanggal Dokumen</Label>
                            <Input id="date" type="date" v-model="form.date" required />
                            <p v-if="form.errors.date" class="text-sm text-destructive">{{ form.errors.date }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
                            <Label for="payment_method">Metode Pembayaran</Label>
                            <Select v-model="form.payment_method" id="payment_method">
                                <SelectTrigger :disabled="form.transaction_type !== 'purchase'">
                                    <SelectValue placeholder="Pilih Metode Bayar" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="cash">Tunai (Cash)</SelectItem>
                                    <SelectItem value="transfer">Transfer Bank</SelectItem>
                                    <SelectItem value="credit">Kredit (Hutang Vendor)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground mt-1">
                                {{ form.payment_method === 'credit' ? 'Tagihan akan masuk ke daftar Hutang Usaha.' : 'Pembayaran akan langsung memotong saldo Kas/Bank.' }}
                            </p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="no_invoice">No Invoice / Ref (Opsional)</Label>
                            <Input id="no_invoice" v-model="form.no_invoice" placeholder="Misal: INV/2026/04/001" />
                            <p v-if="form.errors.no_invoice" class="text-sm text-destructive">{{ form.errors.no_invoice }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2 col-span-1">
                            <div class="flex items-center justify-between">
                                <Label for="vendor_id">Supplier / Vendor</Label>
                                <button type="button" v-if="form.transaction_type === 'purchase'" @click="isQuickVendorOpen = true" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                                    <Plus class="h-3 w-3" /> Rekanan Baru
                                </button>
                            </div>
                            <CreatableSelect 
                                v-model="form.vendor_id" 
                                :options="vendors" 
                                placeholder="Pilih Vendor..." 
                                display-expr="name"
                                value-expr="id"
                                :disabled="form.transaction_type !== 'purchase'"
                                hide-label
                            />
                            <p v-if="form.errors.vendor_id" class="text-sm text-destructive">{{ form.errors.vendor_id }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <Label for="notes">Keterangan Tambahan (Opsional)</Label>
                        <Textarea id="notes" v-model="form.notes" placeholder="Catatan transaksi..." rows="2" class="resize-none" />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>
                </div>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- ITEMS TABLE (Span 2) -->
                <Card class="border-slate-200 lg:col-span-2 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-row items-center justify-between">
                        <div class="space-y-1">
                            <h3 class="text-sm font-semibold text-slate-900 leading-none">Daftar Barang Masuk</h3>
                        </div>
                        <Button type="button" variant="outline" size="sm" @click="addItem" :disabled="!!props.productId && form.items.length === 1 && !form.items[0].product_id">
                            <Plus class="mr-2 h-4 w-4" /> Tambah Baris
                        </Button>
                    </div>
                    <div class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50">
                                    <TableHead class="w-[35%] pl-6">Product / Barang</TableHead>
                                    <TableHead class="w-[20%]">Unit</TableHead>
                                    <TableHead class="w-[15%]">Kuantitas</TableHead>
                                    <TableHead class="w-[25%]">Harga Unit (Rp)</TableHead>
                                    <TableHead class="w-[5%] text-right pr-6"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, idx) in form.items" :key="idx">
                                    <TableCell class="pl-6 align-top pt-4">
                                        <CreatableSelect 
                                            v-model="item.product_id" 
                                            :options="products" 
                                            placeholder="Pilih Product..."
                                            hide-label
                                            display-expr="name"
                                            value-expr="id"
                                            @update:modelValue="(val) => handleProductChange(idx, val)"
                                            :disabled="!!props.productId && idx === 0"
                                        />
                                        <!-- Batch Info -->
                                        <div v-if="products.find(p => p.id == item.product_id)?.is_batch_tracked" class="mt-2 flex flex-col gap-1 p-2 bg-slate-50 rounded-lg border border-slate-100">
                                            <div class="flex gap-2">
                                                <Input v-model="item.batch_number" placeholder="No. Batch" class="h-7 text-[10px]" />
                                                <Input type="date" v-model="item.expiry_date" class="h-7 text-[10px]" />
                                            </div>
                                            <p class="text-[9px] text-muted-foreground italic">Lacak batch & expiry untuk FEFO</p>
                                        </div>
                                        <p v-if="form.errors[`items.${idx}.product_id`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.product_id`] }}</p>
                                    </TableCell>
                                    <!-- Select Unit -->
                                    <TableCell class="align-top pt-4">
                                        <CreatableSelect 
                                            v-model="item.unit_id" 
                                            :options="units" 
                                            placeholder="Unit"
                                            hide-label
                                            display-expr="symbol"
                                            value-expr="id"
                                        />
                                        <p v-if="form.errors[`items.${idx}.unit_id`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.unit_id`] }}</p>
                                    </TableCell>
                                    <!-- Input Kuantitas -->
                                    <TableCell class="align-top pt-4">
                                        <Input type="number" step="0.0001" v-model="item.quantity" required min="0.0001" class="text-right" />
                                        <p v-if="form.errors[`items.${idx}.quantity`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.quantity`] }}</p>
                                    </TableCell>
                                    <!-- Input Harga Unit -->
                                    <TableCell class="align-top pt-4">
                                        <Input 
                                            type="number" 
                                            v-model="item.unit_price" 
                                            required 
                                            min="0" 
                                            class="text-right"
                                            :disabled="form.transaction_type !== 'purchase'"
                                        />
                                        <p v-if="form.errors[`items.${idx}.unit_price`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.unit_price`] }}</p>
                                    </TableCell>
                                    <!-- Hapus -->
                                    <TableCell class="text-right pr-6 align-top pt-4">
                                        <Button type="button" variant="ghost" size="icon" @click="removeItem(idx)" :disabled="form.items.length <= 1">
                                            <Trash2 class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        
                        <div class="bg-muted/20 p-4 border-t flex items-center justify-end gap-3 text-sm">
                            <span class="text-muted-foreground mr-2 font-medium">Estimasi Subtotal:</span>
                            <span class="text-lg font-bold text-foreground tracking-tight">{{ formatCurrency(totalBiaya) }}</span>
                        </div>
                    </div>
                </Card>

                <!-- ATTACHMENTS (Span 1) -->
                <Card class="border-slate-200">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-sm font-semibold text-slate-900 leading-none">Lampiran Pendukung</h3>
                        <p class="text-xs text-slate-400 mt-1">Upload foto nota atau invoice.</p>
                    </div>
                    <div class="p-6">
                        <FileDropzone 
                            v-model="form.attachments"
                            :max-size-m-b="20"
                            accept="image/*,application/pdf"
                            @error="(msg) => toast.error(msg)"
                        />
                        <p v-if="form.errors.attachments" class="text-xs text-destructive mt-2">{{ form.errors.attachments }}</p>
                    </div>
                </Card>
            </div>

            <div class="flex justify-end gap-4 py-4 mb-20">
                <Link :href="index().url">
                    <Button variant="outline" type="button" class="h-11 px-8 border-slate-200">Cancel</Button>
                </Link>
                <Button type="submit" :disabled="form.processing" class="h-11 px-8 gap-2 bg-primary">
                    <Save class="h-4 w-4" />
                    Simpan Sebagai Draft
                </Button>
            </div>
        </form>
    </div>
</AppLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
