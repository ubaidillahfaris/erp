<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Plus, Search, Edit2, Trash2, MoreHorizontal } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { BreadcrumbItem } from '@/types';
import { index as satuanIndex, destroy as satuanDestroy } from '@/actions/App/Http/Controllers/SatuanController';
import { create as satuanCreate, edit as satuanEdit } from '@/actions/App/Http/Controllers/SatuanController';

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
    { title: 'Satuan Barang', href: '#' },
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

const confirmDelete = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus satuan ini?')) {
        router.delete(satuanDestroy(id).url);
    }
};
</script>

<template>
<Head title="Satuan Barang" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Satuan Barang</h1>
                <p class="text-sm text-muted-foreground mt-1">Kelola satuan (unit) barang seperti pcs, kg, dus, dll.</p>
            </div>
            <Link :href="satuanCreate().url">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Satuan
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Daftar Satuan</h3>
                <div class="flex items-center gap-4 w-full max-w-sm">
                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari satuan..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="satuans" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama Satuan</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Simbol</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Deskripsi</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="satuan in satuans.data" :key="satuan.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">{{ satuan.nama }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm font-mono uppercase text-muted-foreground">{{ satuan.simbol }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ satuan.deskripsi || '-' }}</TableCell>
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

                                        <Link :href="satuanEdit(satuan.id).url">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Edit2 class="mr-2 h-4 w-4" />
                                                <span>Edit Satuan</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="confirmDelete(satuan.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Satuan</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="satuans.data.length === 0">
                            <TableCell colspan="4" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Tidak ada data satuan.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="satuans" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
