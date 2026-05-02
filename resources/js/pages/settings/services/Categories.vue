<script setup lang="ts">
// Refactored to use standard DataTable component
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    Plus, ArrowLeft, MoreHorizontal, Pencil, Trash2, 
    Tag, Info, Layers, PackageOpen, Search
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { toast } from 'vue-sonner';

interface Category {
    id: number;
    name: string;
    description: string | null;
    services_count: number;
}

const props = defineProps<{
    categories: {
        data: Category[];
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
    { title: 'Katalog Jasa', href: '/settings/services' },
    { title: 'Kelola Kategori', href: '#' },
];

const categoryOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref<number | null>(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    name: '',
    description: '',
});

const columns = [
    { key: 'category', label: 'Nama Kategori', sortable: false },
    { key: 'description', label: 'Deskripsi', sortable: false },
    { key: 'total', label: 'Total Jasa', align: 'center', sortable: false },
] as const;

watch(search, debounce((newSearch) => {
    router.get('/settings/service-categories', {
        search: newSearch || undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const openCreate = () => {
    isEditing.value = false;
    selectedId.value = null;
    form.reset();
    categoryOpen.value = true;
};

const openEdit = (cat: Category) => {
    isEditing.value = true;
    selectedId.value = cat.id;
    form.name = cat.name;
    form.description = cat.description || '';
    categoryOpen.value = true;
};

const submit = () => {
    if (isEditing.value && selectedId.value) {
        form.put(`/settings/service-categories/${selectedId.value}`, {
            onSuccess: () => {
                categoryOpen.value = false;
                toast.success('Kategori diperbarui');
            }
        });
    } else {
        form.post('/settings/service-categories', {
            onSuccess: () => {
                categoryOpen.value = false;
                form.reset();
                toast.success('Kategori baru ditambahkan');
            }
        });
    }
};

const { confirmDialog } = useConfirm();

const deleteCategory = async (cat: Category) => {
    if (cat.services_count > 0) {
        toast.error('Kategori ini masih digunakan oleh ' + cat.services_count + ' jasa.');
        return;
    }

    if (await confirmDialog('Hapus Kategori?', `Anda yakin ingin menghapus kategori "${cat.name}"?`)) {
        router.delete(`/settings/service-categories/${cat.id}`, {
            onSuccess: () => toast.success('Kategori dihapus')
        });
    }
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Hapus Massal?', `Anda yakin ingin menghapus ${ids.length} kategori terpilih?`)) {
        router.delete('/settings/service-categories/bulk-destroy', {
            data: { ids },
            onSuccess: () => {
                toast.success('Kategori terpilih berhasil dihapus');
            }
        });
    }
};
</script>

<template>
    <Head title="Kelola Kategori Jasa" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
            <PageHeader 
                title="Kategori Jasa" 
                description="Kelompokkan layanan jasa Anda agar lebih terorganisir" 
                back-href="/settings/services"
                :count="categories.total"
            />

            <div class="w-full max-w-7xl mx-auto">
                <DataTable 
                    :data="categories" 
                    :columns="columns" 
                    v-model:search="search" 
                    search-placeholder="Cari nama kategori..." 
                    title="Daftar Kategori"
                    :total-count="categories.total"
                    show-selection
                    @bulk-delete="handleBulkDelete"
                >
                    <template #header-actions>
                        <Button @click="openCreate" primary size="sm" class="gap-2 text-[11px] font-semibold uppercase tracking-wider">
                            <Plus class="h-3.5 w-3.5" /> Tambah Kategori
                        </Button>
                    </template>

                    <template #cell(category)="{ row }">
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-lg bg-primary/5 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                                <Tag class="h-4 w-4" />
                            </div>
                            <span class="text-[13px] font-semibold text-slate-900 leading-none">{{ row.name }}</span>
                        </div>
                    </template>

                    <template #cell(description)="{ row }">
                        <span class="text-[12px] font-medium text-slate-500 line-clamp-1 italic">
                            {{ row.description || 'Tidak ada deskripsi' }}
                        </span>
                    </template>

                    <template #cell(total)="{ row }">
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-[13px] font-semibold text-foreground tabular-nums leading-none">
                                {{ row.services_count }}
                            </span>
                            <span class="text-[10px] font-semibold uppercase opacity-60 leading-none tracking-widest">Jasa</span>
                        </div>
                    </template>

                    <template #actions="{ row }">
                        <div class="flex items-center justify-end gap-1">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 text-slate-400 hover:bg-secondary hover:text-foreground">
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-40 shadow-xl border-slate-100 font-sans">
                                    <DropdownMenuItem @click="openEdit(row)">
                                        <Pencil class="h-3.5 w-3.5 mr-2" /> Edit Kategori
                                    </DropdownMenuItem>
                                    <DropdownMenuItem @click="deleteCategory(row)" class="text-rose-600 focus:text-rose-600 focus:bg-rose-50">
                                        <Trash2 class="h-3.5 w-3.5 mr-2" /> Hapus Kategori
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </template>

                    <template #empty>
                        <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                            <PackageOpen class="h-10 w-10 text-muted-foreground" />
                            <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Belum ada kategori</p>
                        </div>
                    </template>
                </DataTable>

                <div class="mt-6 p-4 bg-blue-50/50 rounded-2xl border border-blue-100 flex items-start gap-3 max-w-2xl">
                    <Info class="h-4 w-4 text-blue-500 shrink-0 mt-0.5" />
                    <p class="text-[11px] text-blue-700 leading-relaxed font-medium">
                        Kategori digunakan untuk mempermudah pencarian dan pengelompokkan layanan di menu POS Kasir. Anda dapat membuat kategori seperti "Laundry Kiloan", "Cuci Sepatu", atau "Reparasi Gadget".
                    </p>
                </div>
            </div>
        </div>

        <!-- Dialog Create/Edit -->
        <Dialog v-model:open="categoryOpen">
            <DialogContent class="rounded-2xl border-slate-100 shadow-2xl max-w-sm font-sans">
                <DialogHeader>
                    <DialogTitle class="text-lg font-bold uppercase tracking-tight text-slate-900">
                        {{ isEditing ? 'Edit Kategori' : 'Kategori Baru' }}
                    </DialogTitle>
                    <DialogDescription class="text-xs font-medium text-slate-400">
                        Tentukan nama dan deskripsi kategori jasa Anda.
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submit" class="space-y-5 py-4">
                    <div class="space-y-2">
                        <Label for="name" class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Nama Kategori</Label>
                        <Input id="name" v-model="form.name" placeholder="Misal: Laundry Kiloan" class="h-11 rounded-xl font-semibold border-slate-200 focus:border-primary/30" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="description" class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Deskripsi (Optional)</Label>
                        <Textarea id="description" v-model="form.description" placeholder="Jelaskan jenis jasa dalam kategori ini..." class="rounded-xl min-h-[100px] border-slate-200 focus:border-primary/30 font-medium text-sm" />
                    </div>
                </form>
                <DialogFooter>
                    <Button @click="submit" :disabled="form.processing" primary class="w-full h-12 rounded-xl font-bold uppercase tracking-widest text-[11px] shadow-lg shadow-primary/20">
                        {{ isEditing ? 'Simpan Perubahan' : 'Buat Kategori' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
