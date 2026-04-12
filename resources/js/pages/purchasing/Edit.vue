<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { Plus, Trash2, ArrowLeft, Save, FileIcon, ImageIcon, X, Building2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { index } from '@/actions/App/Http/Controllers/PurchaseController';
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
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{ 
    purchase: any;
    produks: any[]; 
    satuans: any[];
    vendors: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchasing Inbound', href: index().url },
    { title: 'Edit Inbound', href: '#' },
];

const transactionTypes = [
    { value: 'purchase', label: 'Pembelian (Hutang/Tunai)' },
    { value: 'gift', label: 'Pemberian (Bonus/Hadiah)' },
    { value: 'adjustment', label: 'Penyesuaian (Selisih Stok Naik)' },
];

const formItems = props.purchase.items.map((item: any) => ({
    produk_id: item.produk_id,
    satuan_id: item.satuan_id,
    jumlah: Number(item.jumlah),
    harga_satuan: Number(item.harga_satuan)
}));

const form = useForm({
    tanggal: props.purchase.tanggal,
    transaction_type: props.purchase.transaction_type,
    vendor_id: props.purchase.vendor_id || null,
    keterangan: props.purchase.keterangan || '',
    no_invoice: props.purchase.no_invoice || '',
    items: formItems.length > 0 ? formItems : [{ produk_id: '', satuan_id: '', jumlah: 1, harga_satuan: 0 }],
    attachments: [] as File[],
    _method: 'PUT'
});

const isQuickVendorOpen = ref(false);

const addItem = () => {
    form.items.push({ produk_id: '', satuan_id: '', jumlah: 1, harga_satuan: 0 });
};

const removeItem = (idx: number) => {
    form.items.splice(idx, 1);
};

const handleProductChange = (idx: number, productId: string | number) => {
    const product = props.produks.find(p => p.id == productId);
    if (product) {
        form.items[idx].satuan_id = product.satuan_id;
        if (form.transaction_type === 'purchase' && product.current_price) {
            form.items[idx].harga_satuan = Number(product.current_price.purchase_price);
        }
    }
};

watch(() => form.transaction_type, (newType) => {
    if (newType !== 'purchase') {
        form.vendor_id = null;
        form.items.forEach(item => {
            item.harga_satuan = 0;
        });
    }
});

const submit = () => {
    form.post(`/purchasing/${props.purchase.id}`, {
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

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const totalBiaya = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (Number(item.jumlah) * Number(item.harga_satuan));
    }, 0);
});

const handleVendorCreated = (vendor: { id: number; nama: string }) => {
    props.vendors.push(vendor);
    form.vendor_id = vendor.id;
};

const { confirmDialog } = useConfirm();

const deleteExistingAttachment = async (id: string, name: string) => {
    if (await confirmDialog('Hapus Lampiran?', `File ${name} akan dihapus secara permanen.`)) {
        router.delete(`/purchasing-attachment/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
<Head title="Edit Draft Inbound" />

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
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit Draft Inbound</h1>
                <p class="text-muted-foreground mt-1">Perbarui dokumen transaksi yang belum di finalisasi.</p>
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
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="tanggal">Tanggal Dokumen</Label>
                            <Input id="tanggal" type="date" v-model="form.tanggal" required />
                            <p v-if="form.errors.tanggal" class="text-sm text-destructive">{{ form.errors.tanggal }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-2">
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
                                display-expr="nama"
                                value-expr="id"
                                :disabled="form.transaction_type !== 'purchase'"
                                hide-label
                            />
                            <p v-if="form.errors.vendor_id" class="text-sm text-destructive">{{ form.errors.vendor_id }}</p>
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="no_invoice">No Invoice / Ref (Opsional)</Label>
                            <Input id="no_invoice" v-model="form.no_invoice" placeholder="Misal: INV/2026/04/001" />
                            <p v-if="form.errors.no_invoice" class="text-sm text-destructive">{{ form.errors.no_invoice }}</p>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <Label for="keterangan">Keterangan Tambahan (Opsional)</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" placeholder="Catatan transaksi..." rows="2" class="resize-none" />
                        <p v-if="form.errors.keterangan" class="text-sm text-destructive">{{ form.errors.keterangan }}</p>
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
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="mr-2 h-4 w-4" /> Tambah Baris
                        </Button>
                    </div>
                    <div class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50">
                                    <TableHead class="w-[35%] pl-6">Produk / Barang</TableHead>
                                    <TableHead class="w-[20%]">Satuan</TableHead>
                                    <TableHead class="w-[15%]">Kuantitas</TableHead>
                                    <TableHead class="w-[25%]">Harga Satuan (Rp)</TableHead>
                                    <TableHead class="w-[5%] text-right pr-6"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, idx) in form.items" :key="idx">
                                    <TableCell class="pl-6 align-top pt-4">
                                        <CreatableSelect 
                                            v-model="item.produk_id" 
                                            :options="produks" 
                                            placeholder="Pilih Produk..."
                                            hide-label
                                            display-expr="nama"
                                            value-expr="id"
                                            @update:modelValue="(val) => handleProductChange(idx, val)"
                                        />
                                        <p v-if="form.errors[`items.${idx}.produk_id`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.produk_id`] }}</p>
                                    </TableCell>
                                    <!-- Select Satuan -->
                                    <TableCell class="align-top pt-4">
                                        <CreatableSelect 
                                            v-model="item.satuan_id" 
                                            :options="satuans" 
                                            placeholder="Satuan"
                                            hide-label
                                            display-expr="simbol"
                                            value-expr="id"
                                        />
                                        <p v-if="form.errors[`items.${idx}.satuan_id`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.satuan_id`] }}</p>
                                    </TableCell>
                                    <!-- Input Kuantitas -->
                                    <TableCell class="align-top pt-4">
                                        <Input type="number" step="0.0001" v-model="item.jumlah" required min="0.0001" class="text-right" />
                                        <p v-if="form.errors[`items.${idx}.jumlah`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.jumlah`] }}</p>
                                    </TableCell>
                                    <!-- Input Harga Satuan -->
                                    <TableCell class="align-top pt-4">
                                        <Input 
                                            type="number" 
                                            v-model="item.harga_satuan" 
                                            required 
                                            min="0" 
                                            class="text-right"
                                            :disabled="form.transaction_type !== 'purchase'"
                                        />
                                        <p v-if="form.errors[`items.${idx}.harga_satuan`]" class="text-xs text-destructive mt-1">{{ form.errors[`items.${idx}.harga_satuan`] }}</p>
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
                <div class="flex flex-col gap-6">
                    <Card class="border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-semibold text-slate-900 leading-none">Existing Lampiran</h3>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-col gap-2" v-if="purchase.attachments?.length > 0">
                                <div 
                                    v-for="att in purchase.attachments" 
                                    :key="att.id"
                                    class="flex items-center justify-between p-3 border border-slate-200 rounded-xl bg-white group"
                                >
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="h-10 w-10 shrink-0 bg-secondary rounded flex items-center justify-center">
                                            <FileIcon class="h-5 w-5 text-muted-foreground" />
                                        </div>
                                        <div class="flex flex-col truncate">
                                            <a :href="`/storage/${att.file_path}`" target="_blank" class="text-sm font-medium text-primary hover:underline truncate">{{ att.file_name }}</a>
                                            <span class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</span>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="deleteExistingAttachment(att.id, att.file_name)"
                                        class="h-8 w-8 shrink-0 flex items-center justify-center rounded hover:bg-destructive/10 hover:text-destructive text-muted-foreground transition-colors"
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-sm text-muted-foreground italic text-center py-4">
                                Tidak ada lampiran sebelumnya
                            </div>
                        </div>
                    </Card>

                    <Card class="border-slate-200">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-semibold text-slate-900 leading-none">Tambah Lampiran Baru</h3>
                        </div>
                        <div class="p-6">
                            <FileDropzone 
                                v-model="form.attachments"
                                :max-size-m-b="20"
                                accept="image/*,application/pdf"
                                @error="(msg) => $toast?.error?.(msg)"
                            />
                            <p v-if="form.errors.attachments" class="text-xs text-destructive mt-2">{{ form.errors.attachments }}</p>
                        </div>
                    </Card>
                </div>
            </div>

            <div class="flex justify-end gap-4 py-4 mb-20">
                <Link :href="index().url">
                    <Button variant="outline" type="button" class="h-11 px-8 border-slate-200">Batal</Button>
                </Link>
                <Button type="submit" :disabled="form.processing" class="h-11 px-8 gap-2 bg-primary">
                    <Save class="h-4 w-4" />
                    Simpan Perubahan
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
