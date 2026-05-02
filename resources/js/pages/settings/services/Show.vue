<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, Plus, Trash2, Settings2, Save,
    CheckCircle2, Clock, AlertCircle, 
    Weight, Hash, Layers, Pencil, Tag,
    ChevronRight, ListOrdered, DollarSign
} from 'lucide-vue-next';
import { cn, fmtIdr } from '@/lib/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input, InputCurrency } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { 
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { 
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from 'vue-sonner';
import servicesRoutes from '@/routes/settings/services';
import serviceTypesRoutes from '@/routes/settings/service-types';
import servicePricingsRoutes from '@/routes/settings/service-pricings';
import type { BreadcrumbItem } from '@/types';

interface Pricing {
    id: number;
    pricing_basis: 'per_kg' | 'per_item' | 'per_unit';
    unit_name: string;
    unit_price: number;
    min_quantity: number;
    max_quantity: number | null;
    discount_pct: number;
}

interface ServiceType {
    id: number;
    code: string;
    name: string;
    description: string | null;
    pricings: Pricing[];
}

interface Status {
    id: number;
    status_code: string;
    status_name: string;
    sequence_order: number;
    is_default_start: boolean;
    is_final: boolean;
}

interface Service {
    id: number;
    code: string;
    name: string;
    description: string | null;
    service_category: string;
    category?: {
        id: number;
        name: string;
    };
    is_active: boolean;
    service_types: ServiceType[];
}

const props = defineProps<{
    service: Service;
    production_steps: Status[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Katalog Jasa', href: servicesRoutes.index.url() },
    { title: props.service.name, href: '#' },
];

const typeOpen = ref(false);
const pricingOpen = ref(false);
const selectedTypeId = ref<number | null>(null);
const selectedPricingId = ref<number | null>(null);
const isEditingPricing = ref(false);

const typeForm = useForm({
    code: '',
    name: '',
    description: '',
});

const pricingForm = useForm({
    pricing_basis: 'per_kg' as 'per_kg' | 'per_item' | 'per_unit',
    unit_name: 'kg',
    unit_price: 0,
    min_quantity: 0,
    max_quantity: null as number | null,
    discount_pct: 0,
});

const statusForm = useForm({
    statuses: props.production_steps.map(s => ({ 
        id: s.id,
        code: s.status_code || (s as any).code,
        name: s.status_name || (s as any).name,
        sequence_order: s.sequence_order,
        is_start: s.is_default_start || (s as any).is_start,
        is_final: s.is_final
    }))
});

const addType = () => {
    typeForm.post(servicesRoutes.storeType.url(props.service.id), {
        onSuccess: () => {
            typeOpen.value = false;
            typeForm.reset();
            toast.success("Tipe layanan berhasil ditambahkan");
        }
    });
};

const openPricingDialog = (typeId: number, pricing?: Pricing) => {
    selectedTypeId.value = typeId;
    if (pricing) {
        selectedPricingId.value = pricing.id;
        isEditingPricing.value = true;
        pricingForm.pricing_basis = pricing.pricing_basis;
        pricingForm.unit_name = pricing.unit_name;
        pricingForm.unit_price = pricing.unit_price / 100;
        pricingForm.min_quantity = pricing.min_quantity;
        pricingForm.max_quantity = pricing.max_quantity;
        pricingForm.discount_pct = pricing.discount_pct;
    } else {
        selectedPricingId.value = null;
        isEditingPricing.value = false;
        pricingForm.reset();
    }
    pricingOpen.value = true;
};

const savePricing = () => {
    if (!selectedTypeId.value) return;
    
    if (isEditingPricing.value && selectedPricingId.value) {
        pricingForm.put(servicePricingsRoutes.update.url(selectedPricingId.value), {
            onSuccess: () => {
                pricingOpen.value = false;
                pricingForm.reset();
                toast.success("Aturan harga diperbarui");
            }
        });
    } else {
        pricingForm.post(serviceTypesRoutes.storePricing.url(selectedTypeId.value), {
            onSuccess: () => {
                pricingOpen.value = false;
                pricingForm.reset();
                toast.success("Aturan harga ditambahkan");
            }
        });
    }
};

const deletePricing = (pricingId: number) => {
    if (confirm("Hapus aturan harga ini?")) {
        router.delete(servicePricingsRoutes.destroy.url(pricingId), {
            onSuccess: () => {
                toast.success("Aturan harga dihapus");
            }
        });
    }
};

const syncStatuses = () => {
    statusForm.post(servicesRoutes.syncStatuses.url(props.service.id), {
        onSuccess: () => toast.success("Alur kerja diperbarui")
    });
};

const addStatusRow = () => {
    statusForm.statuses.push({
        id: 0,
        code: '',
        name: '',
        sequence_order: statusForm.statuses.length + 1,
        is_start: statusForm.statuses.length === 0,
        is_final: false
    } as any);
};

const removeStatusRow = (index: number) => {
    statusForm.statuses.splice(index, 1);
};

const formatPricingValue = (p: Pricing) => {
    return `${fmtIdr(p.unit_price)} / ${p.unit_name}`;
};
</script>

<template>
    <Head :title="`Builder: ${service.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="servicesRoutes.index.url()">
                        <Button variant="outline" size="icon" class="h-8 w-8 rounded-lg border-slate-200">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ service.name }}</h1>
                        <p class="text-sm text-slate-400 mt-0.5 font-medium">Service Builder / SKU: <span class="font-mono font-semibold">{{ service.code }}</span></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :class="service.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-rose-50 text-rose-600 border-rose-100'" class="h-7 px-3 font-semibold uppercase text-[10px]">
                        <CheckCircle2 v-if="service.is_active" class="w-3 h-3 mr-1.5" />
                        {{ service.is_active ? 'Aktif' : 'Non-Aktif' }}
                    </Badge>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Main Builder Area -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Tipe & Varian Section -->
                    <Card class="border-slate-200 shadow-none overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                            <div class="flex items-center gap-2">
                                <Layers class="w-4 h-4 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-tight">Varian & Tipe Layanan</h3>
                            </div>
                            <Button @click="typeOpen = true" variant="outline" size="sm" class="h-8 gap-2 text-[11px] font-semibold uppercase tracking-wider">
                                <Plus class="h-3.5 w-3.5" /> Tambah Tipe
                            </Button>
                        </div>
                        <div class="p-6 space-y-6">
                            <div v-for="type in service.service_types" :key="type.id" class="border border-slate-100 rounded-xl overflow-hidden bg-white">
                                <div class="px-4 py-3 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <Badge variant="outline" class="bg-white font-mono text-[10px] font-semibold">{{ type.code }}</Badge>
                                        <span class="text-sm font-semibold text-slate-900">{{ type.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Button @click="openPricingDialog(type.id)" variant="ghost" size="sm" class="h-7 px-2 text-[10px] font-semibold uppercase text-primary">
                                            <Plus class="h-3 w-3 mr-1" /> Harga
                                        </Button>
                                        <Button variant="ghost" size="icon" class="h-7 w-7 text-slate-300 hover:text-rose-500">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                                <div class="p-4 bg-white">
                                    <div v-if="type.pricings.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div v-for="pricing in type.pricings" :key="pricing.id" class="p-3 rounded-lg border border-slate-100 bg-slate-50/30 flex items-center justify-between group">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center text-slate-400">
                                                    <Weight v-if="pricing.pricing_basis === 'per_kg'" class="h-4 w-4" />
                                                    <Tag v-else class="h-4 w-4" />
                                                </div>
                                                <div>
                                                    <p class="text-[13px] font-semibold text-slate-900 tabular-nums leading-none">{{ fmtIdr(pricing.unit_price) }}</p>
                                                    <p class="text-[10px] font-semibold text-muted-foreground uppercase mt-1">per {{ pricing.unit_name }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Button @click="openPricingDialog(type.id, pricing)" variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-primary">
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </Button>
                                                <Button @click="deletePricing(pricing.id)" variant="ghost" size="icon" class="h-7 w-7 text-slate-400 hover:text-rose-500">
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="py-6 text-center border border-dashed border-slate-200 rounded-xl">
                                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest italic">Belum ada aturan harga</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="service.service_types.length === 0" class="py-12 text-center">
                                <Layers class="h-10 w-10 text-slate-200 mx-auto mb-3" />
                                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest italic">Belum ada tipe layanan yang dikonfigurasi</p>
                            </div>
                        </div>
                    </Card>

                    <!-- Workflow Section -->
                    <Card class="border-slate-200 shadow-none overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                            <div class="flex items-center gap-2">
                                <ListOrdered class="w-4 h-4 text-primary" />
                                <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-tight">Workflow / Alur Status</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button @click="addStatusRow" variant="outline" size="sm" class="h-8 gap-2 text-[11px] font-semibold uppercase tracking-wider">
                                    <Plus class="h-3.5 w-3.5" /> Status
                                </Button>
                                <Button @click="syncStatuses" :disabled="statusForm.processing" primary size="sm" class="h-8 gap-2 text-[11px] font-semibold uppercase tracking-wider">
                                    <Save class="h-3.5 w-3.5" /> Simpan Alur
                                </Button>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div v-for="(status, index) in statusForm.statuses" :key="index" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50 group">
                                    <div class="w-6 text-center font-semibold text-slate-300 text-[11px]">#{{ index + 1 }}</div>
                                    <div class="flex-1 grid grid-cols-2 gap-3">
                                        <Input v-model="status.code" placeholder="KODE" class="h-9 text-[11px] font-semibold uppercase tracking-widest rounded-lg" />
                                        <Input v-model="status.name" placeholder="Nama Status" class="h-9 text-[12px] font-semibold rounded-lg" />
                                    </div>
                                    <div class="flex items-center gap-4 px-2">
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" v-model="status.is_start" class="h-3.5 w-3.5 rounded border-slate-200 text-primary" />
                                            <span class="text-[9px] font-semibold uppercase tracking-tight text-slate-500">Mulai</span>
                                        </label>
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" v-model="status.is_final" class="h-3.5 w-3.5 rounded border-slate-200 text-primary" />
                                            <span class="text-[9px] font-semibold uppercase tracking-tight text-slate-500">Final</span>
                                        </label>
                                    </div>
                                    <Button @click="removeStatusRow(index)" variant="ghost" size="icon" class="h-8 w-8 text-slate-300 hover:text-rose-500">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                                <div v-if="statusForm.statuses.length === 0" class="py-12 text-center">
                                    <Clock class="h-10 w-10 text-slate-200 mx-auto mb-3" />
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest italic">Belum ada alur kerja status</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right Sidebar: Info & Stats -->
                <div class="space-y-6">
                    <Card class="border-slate-200 shadow-none overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-white">
                            <div class="flex items-center gap-2">
                                <Tag class="w-4 h-4 text-primary" />
                                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Informasi Utama</h3>
                            </div>
                        </div>
                        <Table>
                            <TableBody>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 w-1/2 font-medium text-slate-500 text-[11px] uppercase tracking-tight">ID Jasa / SKU</TableCell>
                                    <TableCell class="font-mono font-semibold text-[12px] uppercase">{{ service.code }}</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500 text-[11px] uppercase tracking-tight">Kategori</TableCell>
                                    <TableCell>
                                        <Badge variant="outline" class="font-semibold py-0 h-5 text-[10px]">{{ service.category?.name || service.service_category }}</Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500 text-[11px] uppercase tracking-tight">Total Tipe</TableCell>
                                    <TableCell class="font-semibold tabular-nums">{{ service.service_types.length }} Tipe</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell class="bg-slate-50/50 font-medium text-slate-500 text-[11px] uppercase tracking-tight">Order Masuk</TableCell>
                                    <TableCell class="font-semibold text-primary tabular-nums">0 Order</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </Card>

                    <Card v-if="service.description" class="border-slate-200 shadow-none bg-slate-50/50">
                        <div class="p-6">
                            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">Deskripsi Layanan</h3>
                            <p class="text-[13px] text-slate-600 leading-relaxed font-medium">{{ service.description }}</p>
                        </div>
                    </Card>

                    <div class="p-4 bg-amber-50 rounded-xl border border-amber-100 flex items-start gap-3">
                        <AlertCircle class="h-4 w-4 text-amber-500 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <p class="text-[11px] font-semibold text-amber-900 uppercase">Petunjuk Konfigurasi</p>
                            <p class="text-[10px] text-amber-700 leading-tight">
                                Pastikan Anda memiliki minimal 1 tipe layanan dan 1 status bertanda "Mulai" agar jasa ini dapat muncul di menu POS Kasir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialogs -->
        <Dialog v-model:open="typeOpen">
            <DialogContent class="rounded-2xl border-slate-200 shadow-2xl">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold uppercase tracking-tight">Tambah Tipe Layanan</DialogTitle>
                    <DialogDescription class="text-xs font-medium">Misal: Reguler, Express, Kilat 4 Jam.</DialogDescription>
                </DialogHeader>
                <div class="space-y-4 py-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1 space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Kode</Label>
                            <Input v-model="typeForm.code" placeholder="REG" class="h-10 rounded-lg font-semibold uppercase" />
                        </div>
                        <div class="col-span-2 space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Nama Tipe</Label>
                            <Input v-model="typeForm.name" placeholder="Reguler (3 Hari)" class="h-10 rounded-lg font-semibold" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Deskripsi Singkat</Label>
                        <Textarea v-model="typeForm.description" class="rounded-lg min-h-[80px] text-sm" />
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="addType" :disabled="typeForm.processing" primary class="w-full h-11 font-semibold uppercase tracking-widest text-[11px]">Simpan Tipe</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="pricingOpen">
            <DialogContent class="rounded-2xl border-slate-200 shadow-2xl max-w-sm">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold uppercase tracking-tight">
                        {{ isEditingPricing ? 'Edit Aturan Harga' : 'Aturan Harga Baru' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium">Tentukan basis perhitungan biaya.</DialogDescription>
                </DialogHeader>
                <div class="space-y-5 py-4">
                    <div class="space-y-2">
                        <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Basis Harga</Label>
                        <Select v-model="pricingForm.pricing_basis">
                            <SelectTrigger class="h-10 rounded-lg font-semibold">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent class="rounded-xl border-slate-100">
                                <SelectItem value="per_kg" class="text-sm font-medium">Berat (KG)</SelectItem>
                                <SelectItem value="per_item" class="text-sm font-medium">Per Item / Helai</SelectItem>
                                <SelectItem value="per_unit" class="text-sm font-medium">Per Unit / Satuan</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Nama Satuan</Label>
                            <Input v-model="pricingForm.unit_name" placeholder="kg" class="h-10 rounded-lg font-semibold" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Harga Unit</Label>
                            <InputCurrency v-model="pricingForm.unit_price" class="h-10 rounded-lg font-semibold tabular-nums" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Min. Kuantitas</Label>
                            <Input type="number" v-model="pricingForm.min_quantity" class="h-10 rounded-lg font-semibold" />
                        </div>
                        <div class="space-y-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Diskon (%)</Label>
                            <Input type="number" v-model="pricingForm.discount_pct" class="h-10 rounded-lg font-semibold" />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button @click="savePricing" :disabled="pricingForm.processing" primary class="w-full h-11 font-semibold uppercase tracking-widest text-[11px]">
                        {{ isEditingPricing ? 'Simpan Perubahan' : 'Simpan Harga' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
