<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    Plus, Warehouse as WarehouseIcon, Edit2, Trash2,
    MapPin, Hash, CheckCircle2, XCircle, Loader2, MoreHorizontal, Pencil
} from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog, DialogContent, DialogDescription,
    DialogFooter, DialogHeader, DialogTitle
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { toast } from 'vue-sonner';
import { useConfirm } from '@/composables/useConfirm';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    warehouses: any[];
}>();

// Wrap warehouses in a pagination-like object for DataTable if it's just an array
const warehouseData = computed(() => ({
    data: props.warehouses,
    links: [],
    current_page: 1,
    last_page: 1,
    per_page: props.warehouses.length,
    total: props.warehouses.length
}));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Persediaan', href: '/stock' },
    { title: 'Daftar Gudang', href: '/warehouses' },
];

const { confirmDialog } = useConfirm();
const isDialogOpen = ref(false);
const isEditing = ref(false);
const currentId = ref<number | null>(null);

const form = useForm({
    name: '',
    code: '',
    address: '',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Nama Gudang' },
    { key: 'code', label: 'Kode' },
    { key: 'address', label: 'Alamat' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

const openCreateDialog = () => {
    isEditing.value = false;
    currentId.value = null;
    form.reset();
    isDialogOpen.value = true;
};

const openEditDialog = (warehouse: any) => {
    isEditing.value = true;
    currentId.value = warehouse.id;
    form.name = warehouse.name;
    form.code = warehouse.code;
    form.address = warehouse.address || '';
    form.is_active = !!warehouse.is_active;
    isDialogOpen.value = true;
};

const handleSubmit = () => {
    if (isEditing.value && currentId.value) {
        form.put(`/warehouses/${currentId.value}`, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('Gudang berhasil diperbarui');
            },
        });
    } else {
        form.post('/warehouses', {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
                toast.success('Gudang berhasil ditambahkan');
            },
        });
    }
};

const deleteWarehouse = async (warehouse: any) => {
    if (warehouse.is_default) {
        toast.error('Gudang utama tidak dapat dihapus');
        return;
    }

    const confirmed = await confirmDialog(
        `Hapus Gudang: ${warehouse.name}`,
        `Apakah Anda yakin ingin menghapus gudang ini? Tindakan ini tidak dapat dibatalkan.`
    );

    if (confirmed) {
        router.delete(`/warehouses/${warehouse.id}`, {
            onSuccess: () => {
                toast.success('Gudang berhasil dihapus');
            },
            onError: (errors) => {
                if (errors.error) toast.error(errors.error);
            }
        });
    }
};
import { computed } from 'vue';
</script>

<template>
    <Head title="Manajemen Gudang" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <PageHeader title="Warehouse Management" description="Kelola daftar gudang dan lokasi penyimpanan stok"
            back-href="/stock" :count="warehouses.length" />

        <div class="w-full max-w-7xl mx-auto">
            <DataTable 
                :data="warehouseData" 
                :columns="columns" 
                title="Daftar Gudang"
                :total-count="warehouses.length"
            >
                <template #header-actions>
                    <Button @click="openCreateDialog" class="h-9 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white shadow-lg shadow-accent/20">
                        <Plus class="h-4 w-4 mr-2" /> Tambah Gudang
                    </Button>
                </template>

                <template #cell(name)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-accent group-hover:text-white transition-all duration-300">
                            <WarehouseIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-bold text-foreground">{{ row.name }}</p>
                                <Badge v-if="row.is_default" variant="secondary" class="h-4 text-[8px] uppercase tracking-tighter bg-amber-50 text-amber-600 border-amber-100 px-1">Default</Badge>
                            </div>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-widest font-bold">Storage Location</p>
                        </div>
                    </div>
                </template>

                <template #cell(code)="{ row }">
                    <code class="text-[11px] font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">
                        {{ row.code }}
                    </code>
                </template>

                <template #cell(address)="{ row }">
                    <div class="flex items-start gap-1.5 max-w-xs">
                        <MapPin class="h-3 w-3 text-slate-400 mt-0.5 shrink-0" />
                        <span class="text-xs text-slate-500 line-clamp-1 italic">{{ row.address || 'No address provided' }}</span>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge 
                        :class="row.is_active 
                            ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                            : 'bg-slate-50 text-slate-500 border-slate-100'"
                        class="text-[10px] uppercase font-bold px-2 h-6"
                    >
                        <component :is="row.is_active ? CheckCircle2 : XCircle" class="h-3 w-3 mr-1.5" />
                        {{ row.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="openEditDialog(row)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Pencil class="h-3.5 w-3.5 text-slate-400" /> Edit Gudang
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="!row.is_default" @click="deleteWarehouse(row)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Gudang
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Add/Edit Dialog -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <form @submit.prevent="handleSubmit">
                    <DialogHeader>
                        <DialogTitle class="flex items-center gap-2">
                            <WarehouseIcon class="h-5 w-5 text-accent" />
                            {{ isEditing ? 'Edit Gudang' : 'Tambah Gudang' }}
                        </DialogTitle>
                        <DialogDescription>
                            Lengkapi informasi gudang untuk manajemen penyimpanan stok.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-5 py-6">
                        <div class="grid gap-2">
                            <Label for="name" class="text-xs font-bold uppercase tracking-widest text-slate-500">Nama Gudang</Label>
                            <Input id="name" v-model="form.name" placeholder="Contoh: Gudang Pusat"
                                class="h-10 border-slate-200" required />
                            <p v-if="form.errors.name"
                                class="text-[10px] text-rose-500 font-bold uppercase tracking-tight">{{ form.errors.name
                                }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="code" class="text-xs font-bold uppercase tracking-widest text-slate-500">Kode Gudang</Label>
                            <div class="relative">
                                <Hash class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                                <Input id="code" v-model="form.code" placeholder="GDP" class="h-10 pl-9 border-slate-200"
                                    required />
                            </div>
                            <p v-if="form.errors.code"
                                class="text-[10px] text-rose-500 font-bold uppercase tracking-tight">{{ form.errors.code
                                }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="address" class="text-xs font-bold uppercase tracking-widest text-slate-500">Alamat (Opsional)</Label>
                            <Textarea id="address" v-model="form.address" placeholder="Jl. Raya Kemerdekaan No. 1..."
                                class="min-h-[80px] border-slate-200" />
                        </div>

                        <div class="flex items-center space-x-2 pt-2">
                            <Switch id="is_active" v-model:checked="form.is_active" />
                            <Label for="is_active" class="text-[13px] font-medium text-slate-600">Gudang Aktif</Label>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="isDialogOpen = false"
                            class="h-10 text-xs font-bold uppercase tracking-widest">
                            Batal
                        </Button>
                        <Button type="submit"
                            class="h-10 bg-accent text-white text-xs font-bold uppercase tracking-widest px-8"
                            :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="h-3 w-3 mr-2 animate-spin" />
                            {{ isEditing ? 'Simpan Perubahan' : 'Simpan Gudang' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>

