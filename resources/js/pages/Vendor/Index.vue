<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, Building2, Phone, Mail, MapPin, Info } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    vendors: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Vendor', href: '/vendors' },
];

const search = ref(props.filters.search || '');
const perPage = ref(String(props.vendors.per_page));

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/vendors', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

// Form Logic
const showModal = ref(false);
const editingVendor = ref<any>(null);

const form = useForm({
    nama: '',
    alamat: '',
    telepon: '',
    email: '',
    keterangan: '',
});

const openCreateModal = () => {
    editingVendor.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (vendor: any) => {
    editingVendor.value = vendor;
    form.nama = vendor.nama;
    form.alamat = vendor.alamat;
    form.telepon = vendor.telepon;
    form.email = vendor.email;
    form.keterangan = vendor.keterangan;
    showModal.value = true;
};

const submitForm = () => {
    if (editingVendor.value) {
        form.put(`/vendors/${editingVendor.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post('/vendors', {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteVendor = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus vendor ini?')) {
        router.delete(`/vendors/${id}`);
    }
};
</script>

<template>
<Head title="Master Vendor" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Master Vendor</h1>
                <p class="text-sm text-muted-foreground mt-1">Kelola data supplier dan rekanan bisnis.</p>
            </div>
            <Button class="rounded-none" @click="openCreateModal">
                <Plus class="mr-2 h-4 w-4" />
                Tambah Vendor
            </Button>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Daftar Supplier</h3>
                <div class="flex items-center gap-4 w-full max-w-md">
                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari vendor..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="vendors" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama Vendor</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Kontak</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Alamat</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Keterangan</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="vendor in vendors.data" :key="vendor.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 bg-muted flex items-center justify-center rounded-none border border-muted-foreground/10 group-hover:bg-primary/10 transition-colors">
                                        <Building2 class="h-4 w-4 text-muted-foreground group-hover:text-primary" />
                                    </div>
                                    <span class="font-bold text-sm tracking-tight capitalize">{{ vendor.nama }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="vendor.telepon" class="flex items-center text-xs text-muted-foreground">
                                        <Phone class="mr-2 h-3 w-3 opacity-50" />
                                        {{ vendor.telepon }}
                                    </div>
                                    <div v-if="vendor.email" class="flex items-center text-xs text-muted-foreground">
                                        <Mail class="mr-2 h-3 w-3 opacity-50" />
                                        {{ vendor.email }}
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 py-4">
                                <div v-if="vendor.alamat" class="flex items-start text-xs text-muted-foreground line-clamp-2 max-w-xs">
                                    <MapPin class="mr-2 h-3 w-3 mt-0.5 shrink-0 opacity-50" />
                                    {{ vendor.alamat }}
                                </div>
                                <span v-else class="text-xs text-muted-foreground/40 italic">Tidak ada alamat</span>
                            </TableCell>
                            <TableCell class="px-0 py-4">
                                <div v-if="vendor.keterangan" class="flex items-start text-xs text-muted-foreground line-clamp-2 max-w-xs">
                                    <Info class="mr-2 h-3 w-3 mt-0.5 shrink-0 opacity-50" />
                                    {{ vendor.keterangan }}
                                </div>
                                <span v-else class="text-xs text-muted-foreground/40 italic">-</span>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="rounded-none">
                                        <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Opsi Vendor</DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem class="cursor-pointer rounded-none" @click="openEditModal(vendor)">
                                            <Edit2 class="mr-2 h-4 w-4" />
                                            <span>Edit Data</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="deleteVendor(vendor.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Vendor</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="vendors.data.length === 0">
                            <TableCell colspan="5" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Tidak ada vendor ditemukan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="vendors" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[500px] rounded-none border-none">
            <DialogHeader>
                <DialogTitle class="text-xl font-bold tracking-tight">{{ editingVendor ? 'Edit Vendor' : 'Tambah Vendor Baru' }}</DialogTitle>
                <DialogDescription class="text-xs text-muted-foreground/60 uppercase tracking-widest">
                    Informasi detail supplier
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="grid gap-6 py-4">
                <div class="grid gap-2">
                    <Label for="nama" class="text-xs font-bold uppercase tracking-wider text-muted-foreground/70">Nama Vendor *</Label>
                    <Input 
                        id="nama" 
                        v-model="form.nama" 
                        required 
                        class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors"
                        :disabled="form.processing"
                    />
                    <div v-if="form.errors.nama" class="text-xs text-destructive mt-1">{{ form.errors.nama }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="telepon" class="text-xs font-bold uppercase tracking-wider text-muted-foreground/70">Telepon</Label>
                        <Input 
                            id="telepon" 
                            v-model="form.telepon" 
                            class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors"
                            :disabled="form.processing"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="email" class="text-xs font-bold uppercase tracking-wider text-muted-foreground/70">Email</Label>
                        <Input 
                            id="email" 
                            type="email"
                            v-model="form.email" 
                            class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors"
                            :disabled="form.processing"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="alamat" class="text-xs font-bold uppercase tracking-wider text-muted-foreground/70">Alamat</Label>
                    <Textarea 
                        id="alamat" 
                        v-model="form.alamat" 
                        rows="2"
                        class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors resize-none"
                        :disabled="form.processing"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="keterangan" class="text-xs font-bold uppercase tracking-wider text-muted-foreground/70">Keterangan</Label>
                    <Textarea 
                        id="keterangan" 
                        v-model="form.keterangan" 
                        rows="2"
                        class="rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors resize-none"
                        :disabled="form.processing"
                    />
                </div>

                <DialogFooter class="flex flex-col gap-2">
                    <Button type="submit" class="w-full rounded-none" :disabled="form.processing">
                        {{ editingVendor ? 'Perbarui Data' : 'Simpan Vendor' }}
                    </Button>
                    <Button type="button" variant="ghost" class="w-full rounded-none text-xs" @click="showModal = false" :disabled="form.processing">
                        Batal
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</AppLayout>
</template>
