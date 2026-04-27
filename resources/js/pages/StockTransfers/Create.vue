<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    Plus, ArrowRightLeft, Warehouse as WarehouseIcon, 
    Trash2, AlertCircle, Loader2, Save, Send,
    Info, Search, Package
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { 
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue 
} from '@/components/ui/select';
import { 
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { toast } from 'vue-sonner';
import { index as indexRoute } from '@/routes/stock-transfers';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    warehouses: any[];
    products: any[];
    units: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Persediaan', href: '/stock' },
    { title: 'Transfer Stok', href: '/stock-transfers' },
    { title: 'Baru', href: '/stock-transfers/create' },
];

const form = useForm({
    from_warehouse_id: '',
    to_warehouse_id: '',
    notes: '',
    items: [] as any[],
});

const selectedProduct = ref('');
const quantity = ref(1);

const availableProducts = computed(() => {
    return props.products.filter(p => !form.items.some(item => item.product_id === p.id));
});

const addItem = () => {
    if (!selectedProduct.value) return;
    
    const product = props.products.find(p => p.id === parseInt(selectedProduct.value));
    if (!product) return;

    form.items.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        unit_id: product.unit_id,
        unit_name: product.unit?.name || 'pcs',
        quantity_requested: quantity.value,
        current_stock: product.stock?.[0]?.balance || 0,
    });

    selectedProduct.value = '';
    quantity.value = 1;
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const submit = () => {
    if (form.items.length === 0) {
        toast.error('Tambahkan minimal satu item untuk ditransfer');
        return;
    }

    form.post('/stock-transfers', {
        onSuccess: () => {
            toast.success('Transfer stok berhasil dibuat sebagai Draft');
        },
    });
};
</script>

<template>
    <Head title="Buat Transfer Stok" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <PageHeader 
            title="Create Stock Transfer" 
            description="Pindahkan stok barang antar lokasi gudang" 
            back-href="/stock-transfers"
        />

        <div class="w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Left: Transfer Configuration -->
            <div class="lg:col-span-1 flex flex-col gap-6 animate-in fade-in slide-in-from-left-4 duration-500">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col gap-5 shadow-sm">
                    <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 flex items-center gap-2">
                        <WarehouseIcon class="h-3.5 w-3.5 text-accent" /> Konfigurasi Lokasi
                    </h3>

                    <div class="space-y-4">
                        <div class="grid gap-2">
                            <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Dari Gudang (Asal)</Label>
                            <Select v-model="form.from_warehouse_id">
                                <SelectTrigger class="h-10 border-slate-200 bg-slate-50/30">
                                    <SelectValue placeholder="Pilih gudang asal..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="w in warehouses" :key="w.id" :value="w.id.toString()">
                                        {{ w.name }} ({{ w.code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.from_warehouse_id" class="text-[10px] text-rose-500 font-bold uppercase">{{ form.errors.from_warehouse_id }}</p>
                        </div>

                        <div class="flex justify-center -my-2 relative z-10">
                            <div class="h-8 w-8 rounded-full bg-accent text-white flex items-center justify-center shadow-md shadow-accent/20 border-2 border-white">
                                <ArrowRightLeft class="h-3.5 w-3.5 rotate-90 lg:rotate-0" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Ke Gudang (Tujuan)</Label>
                            <Select v-model="form.to_warehouse_id">
                                <SelectTrigger class="h-10 border-slate-200 bg-slate-50/30">
                                    <SelectValue placeholder="Pilih gudang tujuan..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="w in warehouses" :key="w.id" :value="w.id.toString()" :disabled="w.id.toString() === form.from_warehouse_id">
                                        {{ w.name }} ({{ w.code }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.to_warehouse_id" class="text-[10px] text-rose-500 font-bold uppercase">{{ form.errors.to_warehouse_id }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2 pt-2">
                        <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Catatan</Label>
                        <Textarea v-model="form.notes" placeholder="Tulis catatan transfer di sini..." class="min-h-[100px] border-slate-200 text-sm" />
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                    <Info class="h-4 w-4 text-amber-600 mt-0.5 shrink-0" />
                    <p class="text-[11px] text-amber-700 leading-relaxed font-medium">
                        Transfer yang baru dibuat akan berstatus <strong class="font-bold">Draft</strong>. 
                        Stok <strong class="font-bold">belum berkurang</strong> dari gudang asal hingga Anda menekan tombol "Kirim Barang" pada halaman detail.
                    </p>
                </div>
            </div>

            <!-- Right: Item Selection & Table -->
            <div class="lg:col-span-2 flex flex-col gap-6 animate-in fade-in slide-in-from-right-4 duration-700">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col gap-6">
                    <div class="flex flex-col sm:flex-row gap-4 items-end bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                        <div class="flex-1 grid gap-2">
                            <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Pilih Produk</Label>
                            <Select v-model="selectedProduct">
                                <SelectTrigger class="h-10 border-slate-200 bg-white">
                                    <SelectValue placeholder="Cari produk..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in availableProducts" :key="p.id" :value="p.id.toString()">
                                        {{ p.name }} ({{ p.sku }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-32 grid gap-2">
                            <Label class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground ml-1">Jumlah</Label>
                            <Input v-model="quantity" type="number" step="0.0001" class="h-10 border-slate-200 bg-white" />
                        </div>
                        <Button @click="addItem" type="button" variant="outline" class="h-10 px-6 font-bold uppercase tracking-widest text-xs border-accent text-accent hover:bg-accent hover:text-white transition-all">
                            <Plus class="h-4 w-4 mr-2" /> Tambah
                        </Button>
                    </div>

                    <div class="rounded-xl border border-slate-100 overflow-hidden">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50/30">
                                    <TableHead class="pl-4">Produk</TableHead>
                                    <TableHead class="text-right">Jumlah</TableHead>
                                    <TableHead class="text-center">Satuan</TableHead>
                                    <TableHead class="text-right pr-4">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item, index) in form.items" :key="item.product_id" class="group transition-colors hover:bg-slate-50/50">
                                    <TableCell class="pl-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-accent/10 group-hover:text-accent transition-colors">
                                                <Package class="h-3.5 w-3.5" />
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-700">{{ item.name }}</span>
                                                <span class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ item.sku }}</span>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Input v-model="item.quantity_requested" type="number" step="0.0001" class="h-8 w-24 ml-auto text-right text-xs border-slate-200 focus:ring-accent" />
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge variant="outline" class="text-[10px] font-bold uppercase tracking-tight h-5 px-1.5">{{ item.unit_name }}</Badge>
                                    </TableCell>
                                    <TableCell class="text-right pr-4">
                                        <Button variant="ghost" size="icon" @click="removeItem(index)" class="h-8 w-8 text-slate-300 hover:text-rose-500 transition-colors">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="form.items.length === 0">
                                    <TableCell colspan="4" class="py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 opacity-30">
                                            <ArrowRightLeft class="h-8 w-8 text-slate-400" />
                                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Belum ada item ditambahkan</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <Link :href="indexRoute.url()">
                            <Button variant="ghost" class="h-10 text-xs font-bold uppercase tracking-widest text-slate-400">Batal</Button>
                        </Link>
                        <Button 
                            @click="submit" 
                            class="h-10 px-8 bg-accent text-white font-bold uppercase tracking-widest text-xs shadow-lg shadow-accent/20"
                            :disabled="form.processing || form.items.length === 0"
                        >
                            <Loader2 v-if="form.processing" class="h-3 w-3 mr-2 animate-spin" />
                            <Save class="h-4 w-4 mr-2" v-else />
                            Simpan Draft Transfer
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
