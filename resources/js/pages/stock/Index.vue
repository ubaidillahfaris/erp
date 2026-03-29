<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, History, Settings2, AlertTriangle, CheckCircle2, Loader2, MoreHorizontal, ShoppingCart, TestTube, ChevronRight, Package, Boxes, Warehouse } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

import DataTable from '@/components/DataTable.vue';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    produks: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    satuans: any[];
    conversions: any[];
    filters: {
        search?: string;
        type?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory Control', href: '/stock' },
];

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || 'all');
const perPage = ref(props.filters.per_page || String(props.produks.per_page));

const columns = [
    { key: 'product', label: 'Product Specification' },
    { key: 'type', label: 'Tipe' },
    { key: 'balance', label: 'Saldo Saat Ini', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, type, perPage], debounce(([newSearch, newType, newPerPage]) => {
    router.get('/stock', {
        search: newSearch,
        type: newType === 'all' ? undefined : newType,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

// Adjustment Dialog logic
const isAdjustmentOpen = ref(false);
const selectedProduk = ref<any>(null);
const adjustmentForm = useForm({
    produk_id: null as number | null,
    satuan_id: '' as string | number,
    physical_qty: 1,
    keterangan: '',
});

const getConversionRatio = (fromId: any, toId: any) => {
    if (String(fromId) === String(toId) || !fromId || !toId) return 1.0;

    const queue: [any, number][] = [[fromId, 1.0]];
    const visited = new Set([String(fromId)]);

    while (queue.length > 0) {
        const [currentId, currentRatio] = queue.shift()!;
        if (String(currentId) === String(toId)) return currentRatio;

        // Direct
        props.conversions.filter(c => String(c.satuan_id) === String(currentId)).forEach(c => {
            if (!visited.has(String(c.to_satuan_id))) {
                visited.add(String(c.to_satuan_id));
                queue.push([c.to_satuan_id, currentRatio * parseFloat(c.rasio)]);
            }
        });

        // Inverse
        props.conversions.filter(c => String(c.to_satuan_id) === String(currentId)).forEach(c => {
            if (!visited.has(String(c.satuan_id))) {
                visited.add(String(c.satuan_id));
                queue.push([c.satuan_id, currentRatio * (1.0 / parseFloat(c.rasio))]);
            }
        });
    }
    return 1.0;
};

const delta = ref(0);
const systemQtySelectedUnit = ref(0);

watch([() => adjustmentForm.physical_qty, () => adjustmentForm.satuan_id], () => {
    if (!selectedProduk.value) return;

    const currentBalance = parseFloat(selectedProduk.value.stock?.balance || 0);
    const ratio = getConversionRatio(selectedProduk.value.satuan_id, adjustmentForm.satuan_id);

    systemQtySelectedUnit.value = currentBalance * ratio;
    delta.value = (adjustmentForm.physical_qty || 0) - systemQtySelectedUnit.value;
}, { immediate: true });

const openAdjustment = (produk: any) => {
    selectedProduk.value = produk;
    adjustmentForm.produk_id = produk.id;
    adjustmentForm.satuan_id = String(produk.satuan_id);
    // Set initial physical qty to current system qty
    const currentBalance = parseFloat(produk.stock?.balance || 0);
    adjustmentForm.physical_qty = currentBalance;
    adjustmentForm.keterangan = 'Opname berkala';
    isAdjustmentOpen.value = true;
};

const submitAdjustment = () => {
    adjustmentForm.post('/stock/adjustment', {
        onSuccess: () => {
            isAdjustmentOpen.value = false;
            adjustmentForm.reset();
        },
    });
};

const getStockStatus = (produk: any) => {
    const balance = parseFloat(produk.stock?.balance || 0);
    const min = produk.stok_minimal || 0;

    if (balance <= 0) return { label: 'OOS', variant: 'destructive', icon: AlertTriangle, styles: 'bg-destructive/5 text-destructive border-destructive/10' };
    if (balance <= min) return { label: 'LOW', variant: 'outline', icon: AlertTriangle, styles: 'bg-orange-50 text-orange-600 border-orange-100' };
    return { label: 'OK', variant: 'secondary', icon: CheckCircle2, styles: 'bg-emerald-50 text-emerald-600 border-emerald-100' };
};
</script>

<template>
    <Head title="Inventory & Stock Control" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Warehouse</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Inventory Levels</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Stock Ledger</h1>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="produks"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari SKU atau Nama Produk..."
                toolbar-title="Global Inventory"
            >
                <template #cell(product)="{ row }">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                            <Package class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.nama }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] font-mono font-bold text-muted-foreground/40 uppercase tracking-widest">{{ row.sku }}</span>
                                <span class="text-[10px] text-muted-foreground/20 italic">•</span>
                                <span class="text-[11px] font-medium text-muted-foreground/50 lowercase">{{ row.satuan?.nama }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell(type)="{ row }">
                    <Badge variant="outline" class="h-5 px-1.5 rounded text-[9px] font-bold uppercase tracking-widest border-border/40 text-muted-foreground/60 shadow-none">
                        {{ row.type?.replace('_', ' ') }}
                    </Badge>
                </template>

                <template #cell(balance)="{ row }">
                    <div class="flex flex-col items-end gap-0.5">
                        <span class="text-[18px] font-bold tabular-nums tracking-tighter" :class="parseFloat(row.stock?.balance || 0) <= row.stok_minimal ? 'text-destructive' : 'text-foreground'">
                            {{ parseFloat(row.stock?.balance || 0).toLocaleString('id-ID') }}
                        </span>
                        <span class="text-[9px] font-bold uppercase tracking-tighter text-muted-foreground/30">Min. {{ row.stok_minimal }}</span>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-2 rounded-md text-[9px] font-bold uppercase tracking-widest transition-all gap-1.5" :class="getStockStatus(row).styles">
                        <component :is="getStockStatus(row).icon" class="h-3 w-3" />
                        {{ getStockStatus(row).label }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/stock/${row.id}`">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                <History class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40 font-sans">
                                <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5 text-center text-xs">Inventory Ops</DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <DropdownMenuItem @click="router.get(`/restock/create?produk_id=${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <ShoppingCart class="h-3.5 w-3.5 text-muted-foreground/60" /> Restock Belanja
                                </DropdownMenuItem>

                                <DropdownMenuItem v-if="row.type !== 'finished_good'" @click="router.get(`/production/create?produk_id=${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <TestTube class="h-3.5 w-3.5 text-muted-foreground/60" /> Gunakan Produksi
                                </DropdownMenuItem>

                                <DropdownMenuItem @click="openAdjustment(row)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-accent">
                                    <Settings2 class="h-3.5 w-3.5" /> Adjust (Opname)
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <Warehouse class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Tidak ada data stok ditemukan</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>

    <!-- Adjustment Dialog -->
    <Dialog :open="isAdjustmentOpen" @update:open="isAdjustmentOpen = $event">
        <DialogContent class="max-w-md rounded-2xl p-0 overflow-hidden border-none shadow-2xl">
            <DialogHeader class="bg-muted/10 px-6 py-5 border-b border-border/40">
                <DialogTitle class="text-[16px] font-bold flex items-center gap-2">
                    <Settings2 class="h-4 w-4 text-accent" />
                    Penyesuaian Stok Ledger
                </DialogTitle>
                <DialogDescription class="text-[12px] pt-1">
                    Sesuaikan saldo fisik secara manual untuk item <span class="font-bold text-foreground">{{ selectedProduk?.nama }}</span>.
                </DialogDescription>
            </DialogHeader>

            <div class="p-6 flex flex-col gap-6">
                <!-- Status Comparison -->
                <div class="grid grid-cols-2 gap-px bg-border/20 rounded-xl overflow-hidden border border-border/40">
                    <div class="bg-white p-4">
                        <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground/60 block mb-1">Stok System</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl font-bold font-mono tracking-tighter">{{ systemQtySelectedUnit.toLocaleString('id-ID') }}</span>
                            <span class="text-[10px] font-medium text-muted-foreground uppercase">{{ satuans.find(s => String(s.id) === String(adjustmentForm.satuan_id))?.nama }}</span>
                        </div>
                    </div>
                    <div class="bg-white p-4">
                        <span class="text-[9px] font-bold uppercase tracking-widest text-muted-foreground/60 block mb-1">Variance (Diff)</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold font-mono tracking-tighter" :class="delta > 0 ? 'text-emerald-600' : delta < 0 ? 'text-destructive' : 'text-muted-foreground/40'">
                                {{ delta > 0 ? '+' : '' }}{{ delta.toLocaleString('id-ID') }}
                            </span>
                            <Badge v-if="delta !== 0" :variant="delta > 0 ? 'secondary' : 'destructive'" class="h-4 px-1 rounded text-[8px] font-bold uppercase transition-all" :class="delta > 0 ? 'bg-emerald-50 text-emerald-600 border-none' : ''">
                                {{ delta > 0 ? 'GAINED' : 'LOST' }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60 ml-1">Satuan Hitung</Label>
                        <Select v-model="adjustmentForm.satuan_id">
                            <SelectTrigger class="h-11 rounded-xl border-border/40 bg-secondary/20 font-medium text-[13px] shadow-none">
                                <SelectValue placeholder="Pilih Satuan" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-xl">
                                <SelectItem v-for="s in satuans" :key="s.id" :value="String(s.id)" class="rounded-lg">
                                    {{ s.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60 ml-1">Kuantitas Fisik Riil</Label>
                        <div class="relative group">
                             <Input 
                                type="number" 
                                step="any" 
                                v-model="adjustmentForm.physical_qty" 
                                class="h-14 text-2xl font-black font-mono tracking-tighter rounded-xl border-accent/20 bg-accent/[0.02] focus:ring-accent/10 pr-16 transition-all" 
                            />
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-accent uppercase tracking-widest opacity-40 group-focus-within:opacity-100 transition-opacity">
                                Units
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-1">
                        <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground/60 ml-1">Catatan Opname</Label>
                        <Textarea 
                            v-model="adjustmentForm.keterangan"
                            placeholder="Contoh: Barang rusak, selisih hitung gudang..." 
                            class="min-h-[80px] rounded-xl border-border/40 bg-white text-[13px] h-20 resize-none shadow-none focus:ring-accent/10" 
                        />
                    </div>
                </div>
            </div>

            <div class="bg-muted/5 px-6 py-5 flex items-center justify-between border-t border-border/40 transition-all" :class="adjustmentForm.processing ? 'opacity-50 grayscale' : ''">
                <Button variant="ghost" @click="isAdjustmentOpen = false" class="text-muted-foreground hover:bg-secondary rounded-xl px-5 text-xs font-bold uppercase tracking-widest">
                    Batal
                </Button>
                <Button @click="submitAdjustment" :disabled="adjustmentForm.processing" class="bg-accent hover:bg-accent/90 text-white rounded-xl h-11 px-6 text-xs font-bold uppercase tracking-widest shadow-lg shadow-accent/20 gap-2">
                    <Loader2 v-if="adjustmentForm.processing" class="h-3.5 w-3.5 animate-spin" />
                    Commit Update
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
