<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Search, History, Settings2, AlertTriangle, CheckCircle2, Loader2, Plus, Minus, MoreHorizontal, ShoppingCart, TestTube, SplitSquareHorizontal } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    { title: 'Stok Inventori', href: '/stock' },
];

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || 'all');
const perPage = ref(props.filters.per_page || String(props.produks.per_page));

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
    delta.value = adjustmentForm.physical_qty - systemQtySelectedUnit.value;
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

    if (balance <= 0) return { label: 'Habis', variant: 'destructive', icon: AlertTriangle };
    if (balance <= min) return { label: 'Menipis', variant: 'outline', icon: AlertTriangle, class: 'text-orange-500 border-orange-500' };
    return { label: 'Tersedia', variant: 'secondary', icon: CheckCircle2, class: 'text-green-600' };
};
</script>

<template>
<Head title="Stok Inventori" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Stok Inventori</h1>
                <p class="text-sm text-muted-foreground mt-1">Monitoring saldo stok barang dan historis mutasi.</p>
            </div>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Daftar Stok</h3>
                <div class="flex items-center gap-4 w-full max-w-2xl">
                    <Select v-model="type">
                        <SelectTrigger class="w-[200px] rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10">
                            <SelectValue placeholder="Semua Tipe" />
                        </SelectTrigger>
                        <SelectContent class="rounded-none">
                            <SelectGroup>
                                <SelectItem value="all">Semua Tipe</SelectItem>
                                <SelectItem value="raw_material">Raw Material</SelectItem>
                                <SelectItem value="intermediate_good">Intermediate</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>

                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari barang..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="produks" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">SKU</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama Produk</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Tipe</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Min. Stok</TableHead>
                            <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Saldo Saat Ini</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Satuan</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="produk in produks.data" :key="produk.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-mono text-xs">{{ produk.sku }}</TableCell>
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">{{ produk.nama }}</TableCell>
                            <TableCell class="px-0 py-4">
                                <Badge variant="outline" class="rounded-none text-[10px] px-2 py-0 uppercase">
                                    {{ produk.type?.replace('_', ' ') }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-medium text-muted-foreground">{{ produk.stok_minimal }}</TableCell>
                            <TableCell class="px-0 py-4 text-right text-sm font-bold">
                                <span class="text-lg font-black tracking-tighter" :class="parseFloat(produk.stock?.balance || 0) <= produk.stok_minimal ? 'text-destructive' : 'text-primary'">
                                    {{ parseFloat(produk.stock?.balance || 0).toLocaleString('id-ID') }}
                                </span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-xs font-bold text-muted-foreground uppercase">{{ produk.satuan?.nama }}</TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5" :class="getStockStatus(produk).class">
                                    <component :is="getStockStatus(produk).icon" class="h-3 w-3" />
                                    <span class="text-[10px] font-bold uppercase tracking-widest">
                                        {{ getStockStatus(produk).label }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="rounded-none">
                                        <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Aksi Cepat</DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <Link :href="`/restock/create?produk_id=${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <ShoppingCart class="mr-2 h-4 w-4" />
                                                <span>Restock (Belanja)</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <Link v-if="produk.type !== 'finished_good'"
                                            :href="`/production/create?produk_id=${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <TestTube class="mr-2 h-4 w-4" />
                                                <span>Gunakan Produksi</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem class="cursor-pointer rounded-none" @click="openAdjustment(produk)">
                                            <Settings2 class="mr-2 h-4 w-4" />
                                            <span>Adjust (Opname)</span>
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <Link :href="`/stock/${produk.id}`">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <History class="mr-2 h-4 w-4" />
                                                <span>History Mutasi</span>
                                            </DropdownMenuItem>
                                        </Link>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="produks.data.length === 0">
                            <TableCell colspan="8" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Tidak ada data stok ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="produks" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>

    <!-- Adjustment Dialog -->
    <Dialog :open="isAdjustmentOpen" @update:open="isAdjustmentOpen = $event">
        <DialogContent class="sm:max-w-[425px] rounded-none">
            <DialogHeader>
                <DialogTitle>Penyesuaian Stok (Opname)</DialogTitle>
                <DialogDescription>
                    Sesuaikan saldo stok untuk <strong>{{ selectedProduk?.nama }}</strong> secara manual.
                </DialogDescription>
            </DialogHeader>
            <div class="grid gap-4 py-4">
                <div class="bg-muted/50 p-4 rounded-lg flex flex-col gap-3 border">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-muted-foreground">Stok Sistem Saat Ini</span>
                        <span class="font-mono font-bold">{{ systemQtySelectedUnit.toLocaleString('id-ID') }} {{
                            satuans.find(s => String(s.id) === String(adjustmentForm.satuan_id))?.nama}}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm border-t pt-2"
                        v-if="adjustmentForm.physical_qty !== systemQtySelectedUnit">
                        <span class="text-muted-foreground">Selisih (Adjustment)</span>
                        <span class="font-bold" :class="delta > 0 ? 'text-green-600' : 'text-destructive'">
                            {{ delta > 0 ? '+' : '' }}{{ delta.toLocaleString('id-ID') }} {{satuans.find(s =>
                                String(s.id) === String(adjustmentForm.satuan_id))?.nama}}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="satuan" class="text-right font-medium">Satuan Hitung</Label>
                    <div class="col-span-3">
                        <Select v-model="adjustmentForm.satuan_id">
                            <SelectTrigger id="satuan">
                                <SelectValue placeholder="Pilih Satuan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem v-for="s in satuans" :key="s.id" :value="String(s.id)">
                                        {{ s.nama }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="physical_qty" class="text-right font-bold text-primary">Stok Fisik</Label>
                    <div class="col-span-3">
                        <Input id="physical_qty" type="number" step="any" v-model="adjustmentForm.physical_qty"
                            class="text-lg font-black bg-primary/5 border-primary/20 focus:ring-primary h-12"
                            placeholder="Input jumlah yang dihitung di gudang..." />
                    </div>
                </div>

                <div class="grid grid-cols-4 items-start gap-4 pt-2">
                    <Label for="keterangan" class="text-right pt-2 font-medium">Alasan/Catatan</Label>
                    <Textarea id="keterangan" v-model="adjustmentForm.keterangan"
                        placeholder="Keterangan opname (opsional)" class="col-span-3 h-16 text-sm" />
                </div>
                <p v-if="adjustmentForm.errors.keterangan" class="text-xs text-destructive text-right">
                    {{ adjustmentForm.errors.keterangan }}
                </p>
                <p v-if="adjustmentForm.errors.physical_qty" class="text-xs text-destructive text-right">
                    {{ adjustmentForm.errors.physical_qty }}
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="isAdjustmentOpen = false"
                    :disabled="adjustmentForm.processing">Batal</Button>
                <Button @click="submitAdjustment" :disabled="adjustmentForm.processing">
                    <Loader2 v-if="adjustmentForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Simpan Perubahan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</AppLayout>
</template>
