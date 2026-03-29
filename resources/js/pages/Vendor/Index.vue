<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, Building2, Phone, Mail, MapPin, Info, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { useConfirm } from '@/composables/useConfirm';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

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
    { title: 'Master Vendor', href: '/vendors' },
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

const { confirmDialog } = useConfirm();

const deleteVendor = async (id: number) => {
    if (await confirmDialog('Hapus Vendor?', 'Apakah Anda yakin ingin menghapus vendor ini? Data pemasok yang terhapus tidak bisa dikembalikan.')) {
        router.delete(`/vendors/${id}`);
    }
};
</script>

<template>
    <Head title="Master Vendor" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Supply Chain</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Master Vendor</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Vendor & Supplier</h1>
                <Button @click="openCreateModal" class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                    <Plus class="h-4 w-4" />
                    Tambah Vendor
                </Button>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar -->
            <div class="flex items-center justify-between border-b border-border/40 pb-px h-12">
                <div class="flex items-center gap-8 h-full">
                     <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/40 px-1">Database Rekanan</h3>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari vendor..." 
                            class="pl-9 h-9 rounded-lg w-[240px] border-border/40 bg-white text-[13px] font-medium shadow-none focus:ring-accent/10 transition-all" 
                        />
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-border/40 overflow-hidden">
                <Table>
                    <TableHeader class="bg-muted/5">
                        <TableRow class="hover:bg-transparent border-none">
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Profil Vendor</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Kontak & Alamat</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Info Tambahan</TableHead>
                            <TableHead class="h-11 px-6 w-[80px] text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="vendor in vendors.data" :key="vendor.id" class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10">
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                                        <Building2 class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ vendor.nama }}</p>
                                        <p v-if="vendor.email" class="text-[11px] text-muted-foreground/60 flex items-center gap-1.5 mt-0.5">
                                            <Mail class="h-3 w-3" /> {{ vendor.email }}
                                        </p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <div class="flex flex-col gap-1">
                                    <div v-if="vendor.telepon" class="flex items-center text-[11px] font-semibold text-muted-foreground/80">
                                        <Phone class="mr-2 h-3.5 w-3.5 text-accent/50" />
                                        {{ vendor.telepon }}
                                    </div>
                                    <div v-if="vendor.alamat" class="flex items-start text-[11px] text-muted-foreground/60 max-w-[200px] leading-relaxed">
                                        <MapPin class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground/30" />
                                        <span class="line-clamp-2">{{ vendor.alamat }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <div v-if="vendor.keterangan" class="flex items-start text-[11px] text-muted-foreground/50 italic leading-snug max-w-[180px]">
                                    <Info class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground/20" />
                                    <span class="line-clamp-2">{{ vendor.keterangan }}</span>
                                </div>
                                <span v-else class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/20 italic">No notes</span>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEditModal(vendor)" class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                        <ChevronRight class="h-4 w-4" />
                                    </button>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40">
                                            <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5">Opsi Vendor</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="openEditModal(vendor)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                                <Edit2 class="h-3.5 w-3.5 text-muted-foreground/60" /> Edit Detail
                                            </DropdownMenuItem>
                                            <DropdownMenuItem @click="deleteVendor(vendor.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive font-medium focus:text-destructive focus:bg-destructive/5">
                                                <Trash2 class="h-3.5 w-3.5" /> Hapus Vendor
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="vendors.data.length === 0">
                            <TableCell colspan="4" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <Building2 class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Vendor tidak ditemukan</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="vendors" v-model:perPage="perPage" />
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[540px] rounded-2xl border-none p-0 overflow-hidden shadow-2xl">
            <DialogHeader class="px-8 pt-8 pb-6 bg-accent text-white">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                        <Building2 class="h-6 w-6" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <DialogTitle class="text-xl font-bold">{{ editingVendor ? 'Edit Profil Vendor' : 'Tambah Vendor Baru' }}</DialogTitle>
                        <DialogDescription class="text-white/60 text-[11px] font-bold uppercase tracking-widest">
                            Informasi Rekanan Bisnis
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <form @submit.prevent="submitForm" class="p-8 flex flex-col gap-6 bg-white">
                <div class="flex flex-col gap-2">
                    <Label for="nama" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-1">Nama Perusahaan / Personal *</Label>
                    <Input 
                        id="nama" 
                        v-model="form.nama" 
                        required 
                        placeholder="Contoh: PT. Sumber Makmur"
                        class="h-12 rounded-xl border-border/40 bg-[#F8F9FA] focus:border-accent/30 shadow-none transition-all font-medium text-[13px]"
                        :disabled="form.processing"
                    />
                    <div v-if="form.errors.nama" class="text-[11px] font-semibold text-destructive px-1">{{ form.errors.nama }}</div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div class="flex flex-col gap-2">
                        <Label for="telepon" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-1">Nomor Telepon</Label>
                        <Input 
                            id="telepon" 
                            v-model="form.telepon" 
                            placeholder="08..."
                            class="h-12 rounded-xl border-border/40 bg-[#F8F9FA] focus:border-accent/30 shadow-none transition-all font-medium text-[13px]"
                            :disabled="form.processing"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label for="email" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-1">Email</Label>
                        <Input 
                            id="email" 
                            type="email"
                            v-model="form.email" 
                            placeholder="vendor@mail.com"
                            class="h-12 rounded-xl border-border/40 bg-[#F8F9FA] focus:border-accent/30 shadow-none transition-all font-medium text-[13px]"
                            :disabled="form.processing"
                        />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="alamat" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-1">Alamat Lengkap</Label>
                    <Textarea 
                        id="alamat" 
                        v-model="form.alamat" 
                        rows="3"
                        placeholder="Jl. Raya No. 123..."
                        class="rounded-xl border-border/40 bg-[#F8F9FA] focus:border-accent/30 shadow-none transition-all resize-none font-medium text-[13px] p-4"
                        :disabled="form.processing"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="keterangan" class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/60 px-1">Catatan Tambahan</Label>
                    <Textarea 
                        id="keterangan" 
                        v-model="form.keterangan" 
                        rows="2"
                        placeholder="Syarat pembayaran, PIC, dll..."
                        class="rounded-xl border-border/40 bg-[#F8F9FA] focus:border-accent/30 shadow-none transition-all resize-none font-medium text-[13px] p-4"
                        :disabled="form.processing"
                    />
                </div>

                <DialogFooter class="mt-4 pt-6 border-t border-border/10 flex items-center justify-end gap-3">
                    <Button type="button" variant="ghost" class="text-xs font-bold uppercase tracking-widest h-11 px-6 rounded-lg" @click="showModal = false" :disabled="form.processing">
                        Batal
                    </Button>
                    <Button type="submit" class="h-11 px-10 text-xs font-bold uppercase tracking-widest bg-accent text-white hover:bg-accent/90 rounded-lg shadow-lg shadow-accent/10 transition-all" :disabled="form.processing">
                        {{ editingVendor ? 'Update Profile' : 'Simpan Vendor' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
