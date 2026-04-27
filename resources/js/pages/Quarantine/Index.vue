<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { 
    AlertTriangle, 
    ArrowRight, 
    CheckCircle2, 
    ChevronRight, 
    Loader2, 
    Package, 
    RotateCcw, 
    ShieldAlert, 
    Trash2, 
    Wrench 
} from 'lucide-vue-next';
import { ref } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Dialog, 
    DialogContent, 
    DialogDescription, 
    DialogFooter, 
    DialogHeader, 
    DialogTitle 
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    quarantineItems: any[];
    warehouses: any[];
    quarantineWarehouse: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Quarantine Management', href: '/quarantine' },
];

const selectedItem = ref<any>(null);
const isDispositionOpen = ref(false);
const dispositionAction = ref<'restock' | 'repair' | 'write_off' | ''>('');

const form = useForm({
    credit_note_item_id: null as number | null,
    action: '' as any,
    quantity: 0,
    to_warehouse_id: null as number | null,
    notes: '',
});

const openDisposition = (item: any, action: 'restock' | 'repair' | 'write_off') => {
    selectedItem.value = item;
    dispositionAction.value = action;
    form.credit_note_item_id = item.id;
    form.action = action;
    form.quantity = item.remaining_quarantine_qty;
    form.to_warehouse_id = null;
    form.notes = '';
    isDispositionOpen.value = true;
};

const submitDisposition = () => {
    form.post('/dispositions', {
        onSuccess: () => {
            isDispositionOpen.value = false;
            form.reset();
        },
    });
};

const getActionTitle = (action: string) => {
    switch (action) {
        case 'restock': return 'Kembalikan ke Stok (Restock)';
        case 'repair': return 'Kirim ke Perbaikan (Repair)';
        case 'write_off': return 'Hapus Stok (Write-off/Scrap)';
        default: return 'Disposisi Barang';
    }
};

const getActionIcon = (action: string) => {
    switch (action) {
        case 'restock': return RotateCcw;
        case 'repair': return Wrench;
        case 'write_off': return Trash2;
        default: return ShieldAlert;
    }
};

const getActionColor = (action: string) => {
    switch (action) {
        case 'restock': return 'text-emerald-600 bg-emerald-50';
        case 'repair': return 'text-amber-600 bg-amber-50';
        case 'write_off': return 'text-rose-600 bg-rose-50';
        default: return 'text-slate-600 bg-slate-50';
    }
};
</script>

<template>
    <Head title="Quarantine Management" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Quarantine Dashboard" 
            description="Manajemen Barang Retur & Penentuan Kondisi (Disposition)" 
            back-href="/dashboard"
            :count="quarantineItems.length"
        />

        <div class="w-full max-w-7xl mx-auto">
            <div v-if="quarantineItems.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-slate-200">
                <div class="h-16 w-16 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-300 mb-4">
                    <CheckCircle2 class="h-8 w-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900">Gudang Karantina Kosong</h3>
                <p class="text-sm text-slate-500 max-w-xs text-center mt-1">
                    Semua barang retur telah diproses atau belum ada retur baru yang masuk.
                </p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Card v-for="item in quarantineItems" :key="item.id" class="rounded-3xl border-slate-200 shadow-sm hover:shadow-md transition-all group overflow-hidden">
                    <CardHeader class="pb-4">
                        <div class="flex justify-between items-start">
                            <Badge variant="outline" class="bg-amber-50 text-amber-700 border-amber-100 font-bold px-2 py-0.5 rounded-lg text-[10px] uppercase tracking-widest">
                                Quarantine
                            </Badge>
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-tighter">
                                #{{ item.credit_note.credit_note_number }}
                            </span>
                        </div>
                        <CardTitle class="text-[15px] font-black mt-3 flex items-center gap-2">
                            <Package class="h-4 w-4 text-slate-400" />
                            {{ item.product.name }}
                        </CardTitle>
                        <CardDescription class="text-[11px] font-medium flex items-center gap-1.5 mt-1">
                            SKU: <span class="text-slate-900 font-bold uppercase tracking-widest">{{ item.product.sku }}</span>
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent class="pb-6">
                        <div class="bg-slate-50 rounded-2xl p-4 flex items-center justify-between border border-slate-100">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-0.5">Saldo Karantina</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-2xl font-black tabular-nums tracking-tighter text-slate-900">{{ item.remaining_quarantine_qty }}</span>
                                    <span class="text-xs font-bold text-slate-500 uppercase">{{ item.sale_item?.unit?.name || 'Unit' }}</span>
                                </div>
                            </div>
                            <div class="h-10 w-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-amber-500 shadow-sm">
                                <AlertTriangle class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-500">Asal Nota Kredit:</span>
                                <Link :href="`/credit-notes/${item.credit_note_id}`" class="font-bold text-accent hover:underline">
                                    {{ item.credit_note.credit_note_number }}
                                </Link>
                            </div>
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="text-slate-500">Tanggal Masuk:</span>
                                <span class="font-bold text-slate-700">{{ new Date(item.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}</span>
                            </div>
                        </div>
                    </CardContent>

                    <CardFooter class="bg-slate-50/50 p-3 grid grid-cols-3 gap-2 border-t border-slate-100">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-12 flex-col gap-1 rounded-xl hover:bg-emerald-50 hover:text-emerald-700 transition-colors"
                            @click="openDisposition(item, 'restock')"
                        >
                            <RotateCcw class="h-3.5 w-3.5" />
                            <span class="text-[9px] font-black uppercase tracking-tighter">Restock</span>
                        </Button>
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-12 flex-col gap-1 rounded-xl hover:bg-amber-50 hover:text-amber-700 transition-colors"
                            @click="openDisposition(item, 'repair')"
                        >
                            <Wrench class="h-3.5 w-3.5" />
                            <span class="text-[9px] font-black uppercase tracking-tighter">Repair</span>
                        </Button>
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-12 flex-col gap-1 rounded-xl hover:bg-rose-50 hover:text-rose-700 transition-colors text-slate-400"
                            @click="openDisposition(item, 'write_off')"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            <span class="text-[9px] font-black uppercase tracking-tighter">Scrap</span>
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </div>

    <!-- Disposition Modal -->
    <Dialog :open="isDispositionOpen" @update:open="isDispositionOpen = $event">
        <DialogContent class="max-w-md rounded-3xl p-0 overflow-hidden border-none shadow-2xl">
            <DialogHeader class="px-6 py-6 border-b border-slate-100" :class="getActionColor(dispositionAction)">
                <DialogTitle class="text-lg font-black flex items-center gap-3">
                    <component :is="getActionIcon(dispositionAction)" class="h-5 w-5" />
                    {{ getActionTitle(dispositionAction) }}
                </DialogTitle>
                <DialogDescription class="text-xs font-medium opacity-80 pt-1">
                    Memproses barang karantina untuk dialihkan ke kondisi final.
                </DialogDescription>
            </DialogHeader>

            <div class="p-8 space-y-6">
                <!-- Product Preview -->
                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="h-12 w-12 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                        <Package class="h-6 w-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-slate-900 capitalize">{{ selectedItem?.product?.name }}</h4>
                        <p class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-widest">{{ selectedItem?.product?.sku }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Kuantitas Diproses</Label>
                        <div class="relative">
                            <Input 
                                type="number" 
                                v-model="form.quantity" 
                                step="any"
                                class="h-12 rounded-xl border-slate-200 focus:ring-accent/20 font-black text-lg pr-12"
                            />
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400 uppercase">
                                {{ selectedItem?.sale_item?.unit?.name }}
                            </span>
                        </div>
                        <p class="text-[9px] text-slate-400 ml-1">Maks: {{ selectedItem?.remaining_quarantine_qty }}</p>
                    </div>

                    <div v-if="dispositionAction !== 'write_off'" class="space-y-1.5">
                        <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Gudang Tujuan</Label>
                        <Select v-model="form.to_warehouse_id">
                            <SelectTrigger class="h-12 rounded-xl border-slate-200 focus:ring-accent/20 text-xs font-bold">
                                <SelectValue placeholder="Pilih Gudang" />
                            </SelectTrigger>
                            <SelectContent class="rounded-2xl">
                                <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                    {{ w.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Catatan Internal</Label>
                    <Textarea 
                        v-model="form.notes"
                        placeholder="Alasan disposisi atau detail perbaikan..."
                        class="min-h-[100px] rounded-2xl border-slate-200 focus:ring-accent/20 text-xs resize-none"
                    />
                </div>

                <div v-if="dispositionAction === 'write_off'" class="p-4 bg-rose-50 border border-rose-100 rounded-2xl flex gap-3">
                    <AlertTriangle class="h-5 w-5 text-rose-500 shrink-0" />
                    <p class="text-[10px] text-rose-900/70 font-medium leading-relaxed">
                        Tindakan ini akan menghapus stok secara permanen dan mencatat kerugian inventaris pada buku besar. Pastikan barang memang sudah tidak bernilai.
                    </p>
                </div>
            </div>

            <DialogFooter class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <Button variant="ghost" @click="isDispositionOpen = false" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:bg-slate-200 h-12 px-6 rounded-xl">
                    Batal
                </Button>
                <Button 
                    @click="submitDisposition" 
                    :disabled="form.processing || (dispositionAction !== 'write_off' && !form.to_warehouse_id)" 
                    class="bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest h-12 px-8 rounded-xl shadow-xl shadow-slate-200 gap-2"
                >
                    <Loader2 v-if="form.processing" class="h-3 w-3 animate-spin" />
                    Selesaikan Disposisi
                    <ArrowRight class="h-3 w-3" />
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
