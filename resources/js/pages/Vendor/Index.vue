<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { index, bulkDestroy, destroy } from '@/actions/App/Http/Controllers/VendorController';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, Building2, Phone, Mail, MapPin, Info, ChevronRight, Map as MapIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import PageHeader from '@/components/PageHeader.vue';

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
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Vendor', href: '/vendors' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.vendors.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'profil', label: 'Profil Vendor', sortKey: 'nama' },
    { key: 'kontak', label: 'Kontak & Alamat', sortable: false },
    { key: 'info', label: 'Info Tambahan', sortKey: 'keterangan' },
];

watch([search, perPage, sort, direction], debounce(([newSearch, newPerPage, newSort, newDirection]) => {
    router.get(index().url, {
        search: newSearch || undefined,
        per_page: newPerPage,
        sort: newSort || undefined,
        direction: newSort ? (newDirection || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Hapus Vendor Terpilih?', `Apakah Anda yakin ingin menghapus ${ids.length} vendor yang dipilih? Data yang memiliki riwayat pembelian akan dilewati.`)) {
        router.post(bulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        });
    }
};

const { confirmDialog } = useConfirm();

const deleteVendor = async (id: number) => {
    if (await confirmDialog('Hapus Vendor?', 'Apakah Anda yakin ingin menghapus vendor ini? Data pemasok yang terhapus tidak bisa dikembalikan.')) {
        router.delete(destroy({ vendor: id }).url);
    }
};
</script>

<template>
<Head title="Vendor" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Vendor & Supplier" 
        description="Database Rekanan & Pemasok" 
        back-href="/dashboard" 
        :count="vendors.total" 
    />
    
    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="vendors" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari vendor..." 
            toolbar-title="Database Rekanan" 
            :title="'Vendor & Supplier'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            @bulk-delete="handleBulkDelete"
            :total-count="vendors.total"
        >
            <template #header-actions>
                <Link href="/vendors/create">
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Tambah Vendor
                    </Button>
                </Link>
            </template>
            <template #cell(profil)="{ row }">
                <div class="flex items-center gap-4">
                    <div
                        class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.nama }}</p>
                        <p v-if="row.email"
                            class="text-xs text-muted-foreground flex items-center gap-1.5 mt-0.5">
                            <Mail class="h-3 w-3" /> {{ row.email }}
                        </p>
                    </div>
                </div>
            </template>

            <template #cell(kontak)="{ row }">
                <div class="flex flex-col gap-1">
                    <div v-if="row.telepon"
                        class="flex items-center text-xs font-semibold text-muted-foreground">
                        <Phone class="mr-2 h-3.5 w-3.5 text-accent" />
                        {{ row.telepon }}
                    </div>
                    <div v-if="row.alamat"
                        class="flex items-start text-xs text-muted-foreground max-w-[200px] leading-relaxed">
                        <MapPin class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground" />
                        <span class="line-clamp-2">{{ row.alamat }}</span>
                    </div>
                </div>
            </template>

            <template #cell(info)="{ row }">
                <div v-if="row.keterangan"
                    class="flex items-start text-xs text-muted-foreground italic leading-snug max-w-[180px]">
                    <Info class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground" />
                    <span class="line-clamp-2">{{ row.keterangan }}</span>
                </div>
                <span v-else class="text-xs font-bold uppercase tracking-widest text-muted-foreground italic">No
                    notes</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/vendors/${row.id}/edit`" class="block w-full h-full flex items-center justify-center">
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-none border-slate-200">
                            <DropdownMenuLabel
                                class="text-xs font-bold uppercase tracking-widest text-muted-foreground px-2 py-1.5">
                                Opsi Vendor</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="`/vendors/${row.id}/edit`"
                                    class="flex items-center w-full rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <Edit2 class="h-3.5 w-3.5 text-muted-foreground" /> Edit Detail
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteVendor(row.id)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive font-medium focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Vendor
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <Building2 class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Vendor tidak ditemukan
                    </p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
