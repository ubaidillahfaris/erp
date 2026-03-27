<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Save, CheckCircle, ArrowLeft, Search, AlertCircle, Loader2 } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
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
    };
    filters: {
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
    { title: 'Mulai Baru', href: '#' },
];

const searchTerm = ref(props.filters.search || '');
const isSearching = ref(false);

const form = useForm({
    tanggal: new Date().toISOString().split('T')[0],
    keterangan: '',
    status: 'draft',
    // Key: produk_id, Value: item details
    items_map: {} as Record<number, any>
});

// Sync current page products to items_map
watch(() => props.produks.data, (newProduks) => {
    newProduks.forEach(p => {
        if (!form.items_map[p.id]) {
            form.items_map[p.id] = {
                produk_id: p.id,
                nama: p.nama,
                sku: p.sku,
                satuan_id: p.satuan_id,
                satuan_nama: p.satuan?.nama,
                satuan_simbol: p.satuan?.simbol,
                system_qty: parseFloat(p.stock?.balance || 0),
                physical_qty: parseFloat(p.stock?.balance || 0),
            };
        }
    });
}, { immediate: true });

// Server-side search
watch(searchTerm, debounce((value) => {
    isSearching.value = true;
    router.get('/stock-opname/create',
        { search: value },
        { preserveState: true, preserveScroll: true, onFinish: () => isSearching.value = false }
    );
}, 300));

const calculateDiff = (item: any) => {
    return (item.physical_qty || 0) - (item.system_qty || 0);
};

const submit = (status: 'draft' | 'completed') => {
    form.status = status;
    const msg = status === 'completed'
        ? 'Apakah Anda yakin ingin menyelesaikan opname ini? Stok sistem akan disesuaikan otomatis.'
        : 'Simpan sebagai draft?';

    if (confirm(msg)) {
        // Transform items_map to items array for backend
        form.transform((data) => ({
            ...data,
            items: Object.values(data.items_map),
        })).post('/stock-opname');
    }
};

const totalDiscrepancies = computed(() => {
    return Object.values(form.items_map).filter(item => Math.abs(calculateDiff(item)) > 0.000001).length;
});
</script>

<template>
<Head title="Mulai Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div class="flex items-center gap-4">
                <Link href="/stock-opname">
                    <Button variant="ghost" size="icon" class="rounded-none">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Mulai Stock Opname</h1>
                    <p class="text-sm text-muted-foreground mt-1">Input stok fisik untuk penyesuaian inventori.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <Button variant="outline" @click="submit('draft')" :disabled="form.processing"
                    class="rounded-none border-muted">
                    <Save class="mr-2 h-4 w-4" />
                    Draft
                </Button>
                <Button @click="submit('completed')" :disabled="form.processing"
                    class="rounded-none bg-primary hover:bg-primary/90">
                    <CheckCircle class="mr-2 h-4 w-4" />
                    Selesaikan
                </Button>
            </div>
        </div>

        <!-- Vertical Content Stack -->
        <div class="flex flex-col gap-10">
            <!-- 1. Informasi Umum -->
            <section class="space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">1. Informasi Umum</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <Label for="tanggal" class="text-xs font-medium">TANGGAL OPNAME</Label>
                        <Input id="tanggal" v-model="form.tanggal" type="date"
                            class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none px-0 focus-visible:ring-0 focus-visible:border-primary transition-colors" />
                    </div>
                    <div class="space-y-2">
                        <Label for="keterangan" class="text-xs font-medium">KETERANGAN / CATATAN</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" placeholder="Misal: Opname Bulanan"
                            class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none px-0 min-h-[40px] resize-none focus-visible:ring-0 focus-visible:border-primary transition-colors" />
                    </div>
                </div>

                <div v-if="totalDiscrepancies > 0"
                    class="flex items-center gap-3 py-3 border-y border-yellow-500/20 bg-yellow-500/5 px-2">
                    <AlertCircle class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                    <span class="text-xs font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-tight">
                        {{ totalDiscrepancies }} barang terdeteksi memiliki selisih stok.
                    </span>
                </div>
            </section>

            <!-- 2. Daftar Barang -->
            <section class="space-y-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">2. Daftar Barang
                    </h3>
                    <div class="relative w-full max-w-sm">
                        <Search v-if="!isSearching" class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Loader2 v-else class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40 animate-spin" />
                        <Input v-model="searchTerm" placeholder="Cari barang..."
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <Table class="rounded-none border-none">
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-b border-muted">
                                <TableHead
                                    class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">
                                    Barang</TableHead>
                                <TableHead
                                    class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">
                                    Stok Sistem</TableHead>
                                <TableHead
                                    class="h-12 px-0 w-[160px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">
                                    Stok Fisik</TableHead>
                                <TableHead
                                    class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">
                                    Selisih</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="produk in produks.data" :key="produk.id"
                                class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                                <TableCell class="px-0 py-4">
                                    <div class="font-bold text-sm tracking-tight capitalize">{{ produk.nama }}</div>
                                    <div
                                        class="text-[10px] font-mono text-muted-foreground/60 uppercase tracking-tighter mt-0.5">
                                        {{ produk.sku }}</div>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right text-sm font-medium">
                                    {{ form.items_map[produk.id]?.system_qty }} <span
                                        class="text-[10px] text-muted-foreground uppercase">{{ produk.satuan?.nama
                                        }}</span>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Input v-if="form.items_map[produk.id]"
                                            v-model="form.items_map[produk.id].physical_qty" type="number" step="any"
                                            class="w-24 text-right h-8 rounded-none border-muted focus-visible:ring-0 focus-visible:border-primary bg-transparent text-sm p-1" />
                                        <span
                                            class="text-[10px] font-bold text-muted-foreground w-8 text-left uppercase">{{
                                            produk.satuan?.simbol }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right">
                                    <div v-if="form.items_map[produk.id]" class="text-sm font-bold"
                                        :class="calculateDiff(form.items_map[produk.id]) === 0 ? 'text-muted-foreground/40' : (calculateDiff(form.items_map[produk.id]) > 0 ? 'text-primary' : 'text-destructive')">
                                        <span v-if="calculateDiff(form.items_map[produk.id]) > 0">+</span>
                                        {{ calculateDiff(form.items_map[produk.id]) }}
                                    </div>
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="produks.data.length === 0">
                                <TableCell colspan="4"
                                    class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                    Barang tidak ditemukan.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <DataTablePagination :paginator="produks" class="mt-8 border-t pt-4 border-muted rounded-none" />
            </section>
        </div>
    </div>
</AppLayout>
</template>
