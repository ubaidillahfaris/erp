<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { store, update, destroy as destroyPrice } from '@/actions/App/Http/Controllers/CustomerPriceController';
import { Plus, Edit2, Trash2, History, TrendingUp, Calendar, User, Package, ArrowRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
    DialogFooter, DialogDescription,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useConfirm } from '@/composables/useConfirm';
import { format } from 'date-fns';
import { id as localeId } from 'date-fns/locale';
import type { BreadcrumbItem } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    customer: {
        id: number;
        name: string;
        type?: { name: string };
        status?: { name: string };
    };
    customerPrices: any[];
    produks: any[];
    satuans: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Customer', href: '/customers' },
    { title: 'Harga Khusus', href: `/customers/${props.customer.id}/prices` },
];

const { confirmDialog } = useConfirm();
const isDialogOpen = ref(false);
const editingPriceId = ref<number | null>(null);

const form = useForm({
    produk_id: '',
    satuan_id: '',
    custom_price: 0,
    valid_until: '',
});

const activePrices = computed(() => props.customerPrices.filter(p => p.is_active));

const historyLog = computed(() => {
    return props.customerPrices.flatMap(cp =>
        cp.histories.map((h: any) => ({
            ...h,
            produk_nama: cp.produk.nama,
            satuan_nama: cp.satuan.nama,
        }))
    ).sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
});

const openAddDialog = () => {
    editingPriceId.value = null;
    form.reset();
    form.produk_id = '';
    form.satuan_id = '';
    form.custom_price = 0;
    form.valid_until = '';
    isDialogOpen.value = true;
};

const openEditDialog = (price: any) => {
    editingPriceId.value = price.id;
    form.produk_id = String(price.produk_id);
    form.satuan_id = String(price.satuan_id);
    form.custom_price = price.custom_price;
    form.valid_until = price.valid_until || '';
    isDialogOpen.value = true;
};

const submit = () => {
    if (editingPriceId.value) {
        form.put(update({ customer: props.customer.id, price: editingPriceId.value }).url, {
            onSuccess: () => { isDialogOpen.value = false; },
        });
    } else {
        form.post(store({ customer: props.customer.id }).url, {
            onSuccess: () => { isDialogOpen.value = false; },
        });
    }
};

const handleDelete = async (priceId: number) => {
    if (await confirmDialog('Nonaktifkan Harga?', 'Harga khusus ini akan dinonaktifkan dan dipindahkan ke riwayat. Lanjutkan?')) {
        router.delete(destroyPrice({ customer: props.customer.id, price: priceId }).url);
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (date: string | null) => {
    if (!date) return 'Seumur Hidup';
    return format(new Date(date), 'dd MMM yyyy', { locale: localeId });
};

const formatDateTime = (date: string) => {
    return format(new Date(date), 'dd MMM yyyy HH:mm', { locale: localeId });
};

const actionLabel: Record<string, string> = {
    created: 'Dibuat',
    updated: 'Diubah',
    deleted: 'Dihapus',
};
</script>

<template>
<Head :title="`Harga Khusus - ${customer.name}`" />

<div class="px-6 py-8 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] font-sans">

    <!-- PAGE HEADER -->
    <PageHeader :title="`Harga Khusus — ${customer.name}`" description="Kelola daftar harga spesial untuk customer ini."
        backHref="/customers">
        <template #actions>
            <Button @click="openAddDialog" class="bg-accent hover:bg-accent/90 text-white rounded-xl shadow-none gap-2">
                <Plus class="h-4 w-4" />
                Tambah Harga
            </Button>
        </template>
    </PageHeader>

    <!-- CONTENT -->
    <div class="max-w-7xl mx-auto w-full flex flex-col gap-8">

        <!-- SECTION 1: HARGA AKTIF -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <SectionHeader title="Daftar Harga Aktif"
                    description="Harga khusus yang sedang berlaku untuk customer ini." />
                <Badge variant="outline" class="rounded-full px-3 font-bold">
                    {{ activePrices.length }} Aktif
                </Badge>
            </div>

            <Table>
                <TableHeader>
                    <TableRow class="bg-slate-50/50 hover:bg-slate-50/50">
                        <TableHead class="font-bold text-slate-700 py-4 pl-6">Produk</TableHead>
                        <TableHead class="font-bold text-slate-700">Satuan</TableHead>
                        <TableHead class="font-bold text-slate-700">Harga Khusus</TableHead>
                        <TableHead class="font-bold text-slate-700">Berlaku Hingga</TableHead>
                        <TableHead class="font-bold text-slate-700 text-right pr-6">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="price in activePrices" :key="price.id"
                        class="border-slate-100 hover:bg-slate-50/40 transition-colors">
                        <TableCell class="py-4 pl-6">
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-lg bg-orange-50 flex items-center justify-center text-orange-500 shrink-0">
                                    <Package class="h-4 w-4" />
                                </div>
                                <span class="font-semibold text-slate-900 text-sm">{{ price.produk.nama }}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge variant="secondary"
                                class="rounded-lg font-medium bg-slate-100 text-slate-600 border-none text-xs">
                                {{ price.satuan.nama }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span class="font-bold text-accent text-sm">{{ formatCurrency(price.custom_price) }}</span>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-2 text-slate-500">
                                <Calendar class="h-3.5 w-3.5 shrink-0" />
                                <span class="text-sm">{{ formatDate(price.valid_until) }}</span>
                            </div>
                        </TableCell>
                        <TableCell class="text-right pr-6">
                            <div class="flex items-center justify-end gap-1">
                                <Button variant="ghost" size="icon" @click="openEditDialog(price)"
                                    class="h-8 w-8 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50">
                                    <Edit2 class="h-3.5 w-3.5" />
                                </Button>
                                <Button variant="ghost" size="icon" @click="handleDelete(price.id)"
                                    class="h-8 w-8 rounded-lg text-slate-400 hover:text-destructive hover:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="activePrices.length === 0">
                        <TableCell colspan="5" class="h-32 text-center text-muted-foreground text-sm italic">
                            Belum ada harga khusus yang aktif.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- SECTION 2: HISTORY LOG -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <SectionHeader title="Riwayat Perubahan"
                    description="Log audit setiap perubahan harga untuk customer ini." />
                <History class="h-4 w-4 text-slate-400" />
            </div>

            <Table>
                <TableHeader>
                    <TableRow class="bg-slate-50/50 hover:bg-slate-50/50">
                        <TableHead class="font-bold text-slate-700 py-3 pl-6 text-xs uppercase tracking-wide">Waktu
                        </TableHead>
                        <TableHead class="font-bold text-slate-700 text-xs uppercase tracking-wide">Aksi</TableHead>
                        <TableHead class="font-bold text-slate-700 text-xs uppercase tracking-wide">Produk</TableHead>
                        <TableHead class="font-bold text-slate-700 text-xs uppercase tracking-wide">Perubahan Harga
                        </TableHead>
                        <TableHead class="font-bold text-slate-700 text-xs uppercase tracking-wide">Masa Berlaku
                        </TableHead>
                        <TableHead class="font-bold text-slate-700 text-xs uppercase tracking-wide pr-6">Oleh
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(log, idx) in historyLog" :key="idx"
                        class="border-slate-100 hover:bg-slate-50/40 transition-colors">
                        <TableCell class="py-3 pl-6 text-xs text-slate-500 font-medium whitespace-nowrap">
                            {{ formatDateTime(log.created_at) }}
                        </TableCell>
                        <TableCell>
                            <span
                                class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide border"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': log.action === 'created',
                                    'bg-blue-50 text-blue-700 border-blue-200': log.action === 'updated',
                                    'bg-rose-50 text-rose-700 border-rose-200': log.action === 'deleted',
                                }">
                                {{ actionLabel[log.action] || log.action }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold text-slate-800">{{ log.produk_nama }}</span>
                                <span class="text-[10px] text-slate-400">{{ log.satuan_nama }}</span>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1.5 text-xs">
                                <template v-if="log.action === 'created'">
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(log.new_price) }}</span>
                                </template>
                                <template v-else-if="log.action === 'deleted'">
                                    <span class="text-slate-400 line-through">{{ formatCurrency(log.old_price) }}</span>
                                </template>
                                <template v-else>
                                    <span class="text-slate-400 line-through">{{ formatCurrency(log.old_price) }}</span>
                                    <ArrowRight class="h-3 w-3 text-slate-300 shrink-0" />
                                    <span class="font-bold text-blue-600">{{ formatCurrency(log.new_price) }}</span>
                                </template>
                            </div>
                        </TableCell>
                        <TableCell>
                            <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                <template v-if="log.action === 'created'">
                                    {{ formatDate(log.new_valid_until) }}
                                </template>
                                <template v-else-if="log.action === 'deleted'">
                                    <span class="line-through">{{ formatDate(log.old_valid_until) }}</span>
                                </template>
                                <template v-else>
                                    <span>{{ formatDate(log.old_valid_until) }}</span>
                                    <ArrowRight class="h-3 w-3 text-slate-300 shrink-0" />
                                    <span>{{ formatDate(log.new_valid_until) }}</span>
                                </template>
                            </div>
                        </TableCell>
                        <TableCell class="pr-6">
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-5 w-5 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                                    <User class="h-2.5 w-2.5 text-slate-400" />
                                </div>
                                <span class="text-xs font-semibold text-slate-700 truncate max-w-[120px]">
                                    {{ log.changedBy?.name || 'System' }}
                                </span>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="historyLog.length === 0">
                        <TableCell colspan="6" class="h-32 text-center text-muted-foreground text-sm italic">
                            Belum ada log riwayat.
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <!-- DIALOG FORM -->
    <!-- Key fix: removed overflow-hidden from DialogContent, moved styling inside -->
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[440px] p-0 gap-0">

            <!-- Dialog Header -->
            <div class="bg-slate-900 px-6 py-7 text-white rounded-t-lg relative overflow-hidden">
                <div class="absolute -right-4 -top-4 opacity-5">
                    <TrendingUp class="h-32 w-32" />
                </div>
                <DialogHeader>
                    <DialogTitle class="text-xl font-bold tracking-tight text-white">
                        {{ editingPriceId ? 'Edit Harga Khusus' : 'Tambah Harga Khusus' }}
                    </DialogTitle>
                    <DialogDescription class="text-slate-400 text-sm mt-1">
                        Set harga spesial untuk produk pilihan.
                    </DialogDescription>
                </DialogHeader>
            </div>

            <!-- Dialog Body -->
            <form @submit.prevent="submit" class="px-6 py-6 flex flex-col gap-5 bg-white rounded-b-lg">

                <!-- Produk -->
                <div class="flex flex-col gap-1.5">
                    <Label class="text-xs font-bold uppercase tracking-widest text-slate-500">Produk</Label>
                    <Select v-model="form.produk_id" :disabled="!!editingPriceId">
                        <SelectTrigger class="h-10 rounded-xl border-slate-200 shadow-none text-sm">
                            <SelectValue placeholder="Pilih Produk" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="p in produks" :key="p.id" :value="String(p.id)">
                                {{ p.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="form.errors.produk_id" class="text-xs text-destructive">{{ form.errors.produk_id
                        }}</span>
                </div>

                <!-- Satuan -->
                <div class="flex flex-col gap-1.5">
                    <Label class="text-xs font-bold uppercase tracking-widest text-slate-500">Satuan</Label>
                    <Select v-model="form.satuan_id" :disabled="!!editingPriceId">
                        <SelectTrigger class="h-10 rounded-xl border-slate-200 shadow-none text-sm">
                            <SelectValue placeholder="Pilih Satuan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in satuans" :key="s.id" :value="String(s.id)">
                                {{ s.nama }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="form.errors.satuan_id" class="text-xs text-destructive">{{ form.errors.satuan_id
                        }}</span>
                </div>

                <!-- Harga -->
                <div class="flex flex-col gap-1.5">
                    <Label for="price" class="text-xs font-bold uppercase tracking-widest text-slate-500">Harga
                        Khusus</Label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400 pointer-events-none">Rp</span>
                        <Input id="price" type="number" v-model="form.custom_price"
                            class="h-10 pl-10 rounded-xl border-slate-200 shadow-none font-bold text-sm" required />
                    </div>
                    <span v-if="form.errors.custom_price" class="text-xs text-destructive">{{ form.errors.custom_price
                        }}</span>
                </div>

                <!-- Valid Until -->
                <div class="flex flex-col gap-1.5">
                    <Label for="valid_until" class="text-xs font-bold uppercase tracking-widest text-slate-500">
                        Berlaku Hingga <span
                            class="text-slate-300 font-normal normal-case tracking-normal">(opsional)</span>
                    </Label>
                    <Input id="valid_until" type="date" v-model="form.valid_until"
                        class="h-10 rounded-xl border-slate-200 shadow-none text-sm" />
                    <p class="text-[10px] text-slate-400 italic">Kosongkan jika berlaku seumur hidup.</p>
                    <span v-if="form.errors.valid_until" class="text-xs text-destructive">{{ form.errors.valid_until
                        }}</span>
                </div>

                <!-- Footer -->
                <DialogFooter class="pt-2 gap-2">
                    <Button type="button" variant="ghost" @click="isDialogOpen = false"
                        class="rounded-xl text-xs font-bold uppercase tracking-widest h-10">
                        Batal
                    </Button>
                    <Button type="submit" :disabled="form.processing"
                        class="bg-accent hover:bg-accent/90 text-white rounded-xl h-10 px-6 text-xs font-bold uppercase tracking-widest shadow-none">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Harga' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</div>
</template>