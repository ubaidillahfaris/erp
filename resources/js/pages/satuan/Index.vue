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

import DataTable from '@/components/DataTable.vue';

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

const columns = [
    { key: 'nama_simbol', label: 'Nama & Simbol' },
    { key: 'deskripsi', label: 'Deskripsi' },
] as const;

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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
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
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="satuans"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari satuan..."
                toolbar-title="Daftar Master Satuan"
            >
                <template #cell(nama_simbol)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                            <Ruler class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-bold text-foreground capitalize">{{ row.nama }}</p>
                            <p class="text-[10px] font-mono font-bold text-muted-foreground/40 uppercase tracking-widest mt-0.5">{{ row.simbol }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(deskripsi)="{ row }">
                    <span class="text-[13px] text-muted-foreground/60">{{ row.deskripsi || 'Tidak ada deskripsi' }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="satuanEdit(row.id).url">
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
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-lg border-border/40 font-sans">
                                <DropdownMenuItem @click="confirmDelete(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Satuan
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <Ruler class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada satuan</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
