<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, History as HistoryIcon, Settings2, AlertTriangle, CheckCircle2, Loader2, MoreHorizontal, ShoppingCart, TestTube, ChevronRight, Package, Boxes, Warehouse, Printer } from 'lucide-vue-next';
import { exportMutationPdf } from '@/actions/App/Http/Controllers/StockController';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
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
import DateRangePicker from '@/components/DateRangePicker.vue';
import { Slider } from '@/components/ui/slider';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    products: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    units: any[];
    conversions: any[];
    filters: {
        search?: string;
        type?: string;
    per_page?: string;
        warehouse_id?: string;
        min_stock?: string;
        max_stock?: string;
        condition?: string;
    };
    warehouses: any[];
    currentWarehouseId: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Inventory Control', href: '/stock' },
];

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || 'all');
const perPage = ref(props.filters.per_page || String(props.products.per_page));
const warehouseId = ref(props.filters.warehouse_id || String(props.currentWarehouseId));
const condition = ref(props.filters.condition || 'all');
const stockRange = ref([Number(props.filters.min_stock || 0), Number(props.filters.max_stock || 5000)]);

const columns = [
    { key: 'product', label: 'Product Specification' },
    { key: 'type', label: 'Tipe' },
    { key: 'condition', label: 'Kondisi' },
    { key: 'balance', label: 'Saldo Saat Ini', align: 'right' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch([search, type, perPage, warehouseId, condition, stockRange], debounce(([newSearch, newType, newPerPage, newWarehouseId, newCondition, newRange]) => {
    router.get('/stock', {
        search: newSearch,
        type: newType === 'all' ? undefined : newType,
        per_page: newPerPage,
        warehouse_id: newWarehouseId === 'all' ? undefined : newWarehouseId,
        condition: newCondition === 'all' ? undefined : newCondition,
        min_stock: newRange[0],
        max_stock: newRange[1]
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

// Adjustment Dialog logic
const isAdjustmentOpen = ref(false);
const selectedProduct = ref<any>(null);
const adjustmentForm = useForm({
    product_id: null as number | null,
    warehouse_id: null as number | null,
    unit_id: '' as string | number,
    physical_qty: 1,
    notes: '',
});

const getConversionRatio = (fromId: any, toId: any) => {
    if (String(fromId) === String(toId) || !fromId || !toId) return 1.0;

    const queue: [any, number][] = [[fromId, 1.0]];
    const visited = new Set([String(fromId)]);

    while (queue.length > 0) {
        const [currentId, currentRatio] = queue.shift()!;
        if (String(currentId) === String(toId)) return currentRatio;

        // Direct
        props.conversions.filter(c => String(c.unit_id) === String(currentId)).forEach(c => {
            if (!visited.has(String(c.to_unit_id))) {
                visited.add(String(c.to_unit_id));
                queue.push([c.to_unit_id, currentRatio * parseFloat(c.rasio)]);
            }
        });

        // Inverse
        props.conversions.filter(c => String(c.to_unit_id) === String(currentId)).forEach(c => {
            if (!visited.has(String(c.unit_id))) {
                visited.add(String(c.unit_id));
                queue.push([c.unit_id, currentRatio * (1.0 / parseFloat(c.rasio))]);
            }
        });
    }
    return 1.0;
};

const delta = ref(0);
const systemQtySelectedUnit = ref(0);

watch([() => adjustmentForm.physical_qty, () => adjustmentForm.unit_id], () => {
    if (!selectedProduct.value) return;

    const currentBalance = parseFloat(selectedProduct.value.stock?.balance || 0);
    const ratio = getConversionRatio(selectedProduct.value.unit_id, adjustmentForm.unit_id);

    systemQtySelectedUnit.value = currentBalance * ratio;
    delta.value = (adjustmentForm.physical_qty || 0) - systemQtySelectedUnit.value;
}, { immediate: true });

const openAdjustment = (product: any) => {
    selectedProduct.value = product;
    adjustmentForm.product_id = product.id;
    adjustmentForm.warehouse_id = Number(warehouseId.value);
    adjustmentForm.unit_id = String(product.unit_id);
    // Set initial physical qty to current system qty
    const currentBalance = parseFloat(product.stock?.balance || 0);
    adjustmentForm.physical_qty = currentBalance;
    adjustmentForm.notes = 'Opname berkala';
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

const getStockStatus = (product: any) => {
    const balance = parseFloat(product.display_balance || 0);
    const min = product.min_stock || 0;

    if (balance <= 0) return { label: 'OOS', variant: 'destructive', icon: AlertTriangle, styles: 'bg-destructive/5 text-destructive border-destructive/10' };
    if (balance <= min) return { label: 'LOW', variant: 'outline', icon: AlertTriangle, styles: 'bg-orange-50 text-orange-600 border-orange-100' };
    return { label: 'OK', variant: 'secondary', icon: CheckCircle2, styles: 'bg-emerald-50 text-emerald-600 border-emerald-100' };
};

const isExporting = ref(false);
const isExportDialogOpen = ref(false);
const exportDateRange = ref({
    start: '',
    end: '',
});

const openExportDialog = () => {
    isExportDialogOpen.value = true;
};

const exportPdf = () => {
    isExporting.value = true;
    router.post(exportMutationPdf.url(), {
        type: type.value === 'all' ? undefined : type.value,
        search: search.value,
        start_date: exportDateRange.value.start,
        end_date: exportDateRange.value.end,
    }, {
        onSuccess: () => {
            isExportDialogOpen.value = false;
        },
        onFinish: () => isExporting.value = false
    });
};
</script>

<template>
    <Head title="Inventory & Stock Control" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Stock Ledger" 
            description="Manajemen Saldo & Inventaris Gudang" 
            back-href="/dashboard"
            :count="products.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="products"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari SKU atau Nama Product..."
                :toolbar-title="warehouseId === 'all' ? 'Inventory Konsolidasi' : `Inventory: ${warehouses.find(w => String(w.id) === warehouseId)?.name || 'Gudang'}`"
            >
                <template #toolbar-actions>
                    <div class="flex items-center gap-2 mr-3 px-3 border-r border-slate-100">
                        <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Gudang:</Label>
                        <Select v-model="warehouseId">
                            <SelectTrigger class="h-8 w-40 border-slate-200 text-xs font-bold bg-white shadow-none focus:ring-0">
                                <SelectValue placeholder="Pilih Gudang" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-none">
                                <SelectItem value="all">Semua Gudang</SelectItem>
                                <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                    {{ w.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center gap-2 mr-3 px-3 border-r border-slate-100">
                        <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Kondisi:</Label>
                        <Select v-model="condition">
                            <SelectTrigger class="h-8 w-32 border-slate-200 text-xs font-bold bg-white shadow-none focus:ring-0">
                                <SelectValue placeholder="Semua" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-none">
                                <SelectItem value="all">Semua Kondisi</SelectItem>
                                <SelectItem value="good">Layak Jual (Good)</SelectItem>
                                <SelectItem value="quarantine">Karantina (Retur)</SelectItem>
                                <SelectItem value="damaged">Rusak (Damaged)</SelectItem>
                                <SelectItem value="refurbished">Refurbished</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center gap-4 mr-6 px-3 border-r border-slate-100 min-w-[200px]">
                        <div class="flex flex-col gap-1 w-full">
                            <div class="flex items-center justify-between">
                                <Label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Range Stok:</Label>
                                <span class="text-[10px] font-mono font-bold text-accent">{{ stockRange[0] }} - {{ stockRange[1] }}</span>
                            </div>
                            <Slider v-model="stockRange" :min="0" :max="5000" :step="1" class="w-full h-4" />
                        </div>
                    </div>

                    <Button 
                        variant="outline" 
                        size="sm" 
                        class="h-9 gap-2 px-3 border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                        @click="openExportDialog"
                        :disabled="isExporting"
                    >
                        <Loader2 v-if="isExporting" class="h-3.5 w-3.5 animate-spin" />
                        <Printer v-else class="h-3.5 w-3.5" />
                        Cetak Mutasi
                    </Button>
                </template>

                <template #cell(product)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                            <Package class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-bold text-foreground capitalize truncate leading-none">{{ row.name }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[11px] font-mono font-bold text-muted-foreground uppercase tracking-widest">{{ row.sku }}</span>
                                <span class="text-[11px] text-muted-foreground italic">•</span>
                                <span class="text-[11px] font-medium text-muted-foreground lowercase">{{ row.unit?.name }}</span>
                            </div>
                        </div>
                    </div>
                </template>

                <template #cell(type)="{ row }">
                    <Badge variant="outline" class="h-4 px-1 rounded-sm text-[9px] font-black uppercase tracking-widest border-slate-200 text-muted-foreground shadow-none ">
                        {{ row.type?.replace('_', ' ') }}
                    </Badge>
                </template>

                <template #cell(condition)="{ row }">
                    <Badge 
                        variant="outline" 
                        class="h-4 px-1 rounded-sm text-[9px] font-black uppercase tracking-widest border-slate-200 shadow-none"
                        :class="{
                            'bg-blue-50 text-blue-600 border-blue-100': (row.stock?.condition || 'good') === 'good',
                            'bg-amber-50 text-amber-600 border-amber-100': (row.stock?.condition) === 'quarantine',
                            'bg-rose-50 text-rose-600 border-rose-100': (row.stock?.condition) === 'damaged',
                            'bg-indigo-50 text-indigo-600 border-indigo-100': (row.stock?.condition) === 'refurbished',
                        }"
                    >
                        {{ row.stock?.condition || 'good' }}
                    </Badge>
                </template>

                <template #cell(balance)="{ row }">
                    <div class="flex flex-col items-end gap-0.5">
                        <span class="text-[18px] font-bold tabular-nums tracking-tighter" :class="parseFloat(row.display_balance || 0) <= row.min_stock ? 'text-destructive' : 'text-foreground'">
                            {{ parseFloat(row.display_balance || 0).toLocaleString('id-ID') }}
                        </span>
                        <span class="text-xs font-bold uppercase tracking-tighter text-muted-foreground">Min. {{ row.min_stock }}</span>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all gap-1.5" :class="getStockStatus(row).styles">
                        <component :is="getStockStatus(row).icon" class="h-3 w-3" />
                        {{ getStockStatus(row).label }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/stock/${row.id}`">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                <HistoryIcon class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-none border-slate-200 font-sans">
                                <DropdownMenuLabel class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-2 py-1.5 text-center text-xs">Inventory Ops</DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <DropdownMenuItem @click="router.get(`/restock/create?product_id=${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <ShoppingCart class="h-3.5 w-3.5 text-muted-foreground" /> Restock Belanja
                                </DropdownMenuItem>

                                <DropdownMenuItem v-if="row.type !== 'finished_good'" @click="router.get(`/production/create?product_id=${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <TestTube class="h-3.5 w-3.5 text-muted-foreground" /> Gunakan Productsi
                                </DropdownMenuItem>

                                <DropdownMenuItem v-if="warehouseId !== 'all'" @click="openAdjustment(row)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium text-accent">
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
        <DialogContent class="max-w-md rounded-xl p-0 overflow-hidden border-none shadow-none ">
            <DialogHeader class="bg-muted/10 px-6 py-5 border-b border-slate-200">
                <DialogTitle class="text-[16px] font-bold flex items-center gap-2">
                    <Settings2 class="h-4 w-4 text-accent" />
                    Penyesuaian Stok Ledger
                </DialogTitle>
                <DialogDescription class="text-[12px] pt-1">
                    Sesuaikan saldo fisik secara manual untuk item <span class="font-bold text-foreground">{{ selectedProduct?.name }}</span>.
                </DialogDescription>
            </DialogHeader>

            <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
                <!-- Status Comparison -->
                <div class="grid grid-cols-2 gap-px bg-border/20 rounded-xl overflow-hidden border border-slate-200">
                    <div class="bg-white p-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground block mb-1">Stok System</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl font-bold font-mono tracking-tighter">{{ systemQtySelectedUnit.toLocaleString('id-ID') }}</span>
                            <span class="text-xs font-medium text-muted-foreground uppercase">{{ units.find(s => String(s.id) === String(adjustmentForm.unit_id))?.name }}</span>
                        </div>
                    </div>
                    <div class="bg-white p-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-muted-foreground block mb-1">Variance (Diff)</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-bold font-mono tracking-tighter" :class="delta > 0 ? 'text-emerald-600' : delta < 0 ? 'text-destructive' : 'text-muted-foreground'">
                                {{ delta > 0 ? '+' : '' }}{{ delta.toLocaleString('id-ID') }}
                            </span>
                            <Badge v-if="delta !== 0" :variant="delta > 0 ? 'secondary' : 'destructive'" class="h-4 px-1 rounded text-xs font-bold uppercase transition-all" :class="delta > 0 ? 'bg-emerald-50 text-emerald-600 border-none' : ''">
                                {{ delta > 0 ? 'GAINED' : 'LOST' }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-xs font-bold uppercase tracking-widest text-muted-foreground ml-1">Unit Hitung</Label>
                        <Select v-model="adjustmentForm.unit_id">
                            <SelectTrigger class="h-11 rounded-xl border-slate-200 bg-secondary/20 font-medium text-[13px] shadow-none ">
                                <SelectValue placeholder="Pilih Unit" />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl shadow-none ">
                                <SelectItem v-for="s in units" :key="s.id" :value="String(s.id)" class="rounded-lg">
                                    {{ s.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label class="text-xs font-bold uppercase tracking-widest text-muted-foreground ml-1">Kuantitas Fisik Riil</Label>
                        <div class="relative group">
                             <Input 
                                type="number" 
                                step="any" 
                                v-model="adjustmentForm.physical_qty" 
                                class="h-14 text-2xl font-black font-mono tracking-tighter rounded-xl border-accent/20 bg-accent/[0.02] focus:ring-accent/10 pr-16 transition-all" 
                            />
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-accent uppercase tracking-widest opacity-40 group-focus-within:opacity-100 transition-opacity">
                                Units
                            </div>
                        </div>
                    </div>



                    <div class="flex flex-col gap-1.5 pt-1">
                        <Label class="text-xs font-bold uppercase tracking-widest text-muted-foreground ml-1">Catatan Opname</Label>
                        <Textarea 
                            v-model="adjustmentForm.notes"
                            placeholder="Contoh: Barang rusak, selisih hitung gudang..." 
                            class="min-h-[80px] rounded-xl border-slate-200 bg-white text-[13px] h-20 resize-none shadow-none focus:ring-accent/10" 
                        />
                    </div>
                </div>
            </div>

            <div class="bg-muted/5 px-6 py-5 flex items-center justify-between border-t border-slate-200 transition-all" :class="adjustmentForm.processing ? 'opacity-50 grayscale' : ''">
                <Button variant="ghost" @click="isAdjustmentOpen = false" class="text-muted-foreground hover:bg-secondary rounded-xl px-5 text-xs font-bold uppercase tracking-widest">
                    Batal
                </Button>
                <Button @click="submitAdjustment" :disabled="adjustmentForm.processing" class="bg-accent hover:bg-accent/90 text-white rounded-xl h-11 px-6 text-xs font-bold uppercase tracking-widest shadow-none shadow-accent/20 gap-2">
                    <Loader2 v-if="adjustmentForm.processing" class="h-3.5 w-3.5 animate-spin" />
                    Commit Update
                </Button>
            </div>
        </DialogContent>
    </Dialog>

    <!-- Export Mutation Dialog -->
    <Dialog :open="isExportDialogOpen" @update:open="isExportDialogOpen = $event">
        <DialogContent class="max-w-md rounded-2xl p-0 overflow-hidden border-none shadow-2xl">
            <DialogHeader class="bg-muted/10 px-6 py-5 border-b border-slate-200">
                <DialogTitle class="text-[16px] font-bold flex items-center gap-2">
                    <Printer class="h-4 w-4 text-primary" />
                    Cetak Laporan Mutasi Stok
                </DialogTitle>
                <DialogDescription class="text-[12px] pt-1">
                    Pilih periode laporan mutasi yang ingin Anda cetak ke dalam format PDF.
                </DialogDescription>
            </DialogHeader>

            <div class="px-8 py-10 bg-white">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <Label class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Periode Laporan</Label>
                        <DateRangePicker v-model="exportDateRange" />
                    </div>
                    
                    <div class="bg-blue-50/50 border border-blue-100/50 rounded-2xl p-4 flex gap-3">
                        <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                            <Printer class="h-3 w-3 text-blue-600" />
                        </div>
                        <p class="text-[11px] leading-relaxed text-blue-900/70 font-medium">
                            Klik tombol di bawah untuk memulai pembuatan laporan. Proses ini mungkin memakan waktu beberapa detik.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-muted/5 px-6 py-5 flex items-center justify-between border-t border-slate-200">
                <Button variant="ghost" @click="isExportDialogOpen = false" class="text-muted-foreground hover:bg-secondary rounded-xl px-5 text-xs font-bold uppercase tracking-widest h-11">
                    Batal
                </Button>
                <Button @click="exportPdf" :disabled="isExporting" class="bg-primary hover:bg-primary/90 text-primary-foreground rounded-xl h-11 px-6 text-xs font-bold uppercase tracking-widest shadow-none gap-2">
                    <Loader2 v-if="isExporting" class="h-3.5 w-3.5 animate-spin" />
                    <Printer v-else class="h-3.5 w-3.5" />
                    Mulai Generate PDF
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
