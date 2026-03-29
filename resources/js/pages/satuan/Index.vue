<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, Ruler, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as satuanIndex, destroy as satuanDestroy } from '@/actions/App/Http/Controllers/SatuanController';
import { create as satuanCreate, edit as satuanEdit } from '@/actions/App/Http/Controllers/SatuanController';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    satuans: {
        data: Array<{
            id: number;
            nama: string;
            simbol: string;
            deskripsi: string | null;
        }>;
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Satuan Barang', href: satuanIndex().url },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.satuans.per_page));

watch(
    [search, perPage],
    debounce(([newSearch, newPerPage]) => {
        router.get(
            satuanIndex().url,
            { search: newSearch, per_page: newPerPage },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);

const { confirmDialog } = useConfirm();

const confirmDelete = async (id: number) => {
    if (await confirmDialog('Hapus Satuan Barang?', 'Apakah Anda yakin ingin menghapus satuan ini? Data yang terkait tidak dapat dikembalikan.')) {
        router.delete(satuanDestroy(id).url);
    }
};
</script>

<template>
    <Head title="Satuan Barang" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)]">
        <!-- ====== PAGE HEADER ====== -->
        <div class="flex flex-col gap-2 max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-[11px] font-bold text-muted-foreground uppercase tracking-widest bg-muted/20 w-fit px-2 py-0.5 rounded">
                <span>Konfigurasi</span>
                <ChevronRight class="h-3 w-3" />
                <span class="text-foreground/40">Satuan Barang</span>
            </div>
            <div class="flex items-end justify-between">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Unit & Satuan</h1>
                <Link :href="satuanCreate().url">
                    <Button class="h-10 px-5 text-xs font-bold rounded-lg bg-accent text-white hover:bg-accent/90 shadow-md shadow-accent/20 gap-2 transition-all">
                        <Plus class="h-4 w-4" />
                        Tambah Satuan
                    </Button>
                </Link>
            </div>
        </div>

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full flex flex-col gap-6">
            
            <!-- Table Toolbar -->
            <div class="flex items-center justify-between border-b border-border/40 pb-px h-12">
                <div class="flex items-center gap-8 h-full">
                     <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/40 px-1">Daftar Master Satuan</h3>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari satuan..." 
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
                            <TableHead class="h-11 px-6 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Nama & Simbol</TableHead>
                            <TableHead class="h-11 px-4 text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40">Deskripsi</TableHead>
                            <TableHead class="h-11 px-6 w-[80px] text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="satuan in satuans.data" :key="satuan.id" class="group transition-all duration-200 border-border/10 last:border-0 hover:bg-secondary/10">
                            <TableCell class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                                        <Ruler class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0 pr-4">
                                        <p class="text-[14px] font-bold text-foreground capitalize">{{ satuan.nama }}</p>
                                        <p class="text-[10px] font-mono font-bold text-muted-foreground/40 uppercase tracking-widest mt-0.5">{{ satuan.simbol }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="px-4 py-4">
                                <span class="text-[13px] text-muted-foreground/60">{{ satuan.deskripsi || 'Tidak ada deskripsi' }}</span>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="satuanEdit(satuan.id).url">
                                        <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                            <ChevronRight class="h-4 w-4" />
                                        </button>
                                    </Link>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-lg border-border/40">
                                            <DropdownMenuItem @click="confirmDelete(satuan.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                                <Trash2 class="h-3.5 w-3.5" /> Hapus Satuan
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="satuans.data.length === 0">
                            <TableCell colspan="3" class="px-10 py-24 text-center">
                                <div class="flex flex-col items-center gap-3 opacity-20">
                                    <Ruler class="h-10 w-10 text-muted-foreground" />
                                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada satuan</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div class="px-2">
                <DataTablePagination :paginator="satuans" v-model:perPage="perPage" />
            </div>
        </div>
    </div>
</template>
