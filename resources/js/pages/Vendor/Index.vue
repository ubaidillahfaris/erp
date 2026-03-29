<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
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
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Vendor', href: '/vendors' },
];

const search = ref(props.filters.search || '');
const perPage = ref(String(props.vendors.per_page));

const columns = [
    { key: 'profil', label: 'Profil Vendor' },
    { key: 'kontak', label: 'Kontak & Alamat' },
    { key: 'info', label: 'Info Tambahan' },
];

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/vendors', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const deleteVendor = async (id: number) => {
    if (await confirmDialog('Hapus Vendor?', 'Apakah Anda yakin ingin menghapus vendor ini? Data pemasok yang terhapus tidak bisa dikembalikan.')) {
        router.delete(`/vendors/${id}`);
    }
};
</script>

<template>
<Head title="Vendor" />

<div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">

    <PageHeader title="Vendor" description="Database Rekanan" back-href="/dashboard" />
    
    <!-- ====== CONTENT AREA ====== -->
    <div class="max-w-7xl mx-auto w-full">
        <DataTable :data="vendors" :columns="columns" v-model:search="search" v-model:perPage="perPage"
            search-placeholder="Cari vendor..." toolbar-title="Database Rekanan" :title="'Vendor & Supplier'"
            :total-count="vendors.total">
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
                        class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[14px] font-bold text-foreground capitalize truncate">{{ row.nama }}</p>
                        <p v-if="row.email"
                            class="text-[11px] text-muted-foreground/60 flex items-center gap-1.5 mt-0.5">
                            <Mail class="h-3 w-3" /> {{ row.email }}
                        </p>
                    </div>
                </div>
            </template>

            <template #cell(kontak)="{ row }">
                <div class="flex flex-col gap-1">
                    <div v-if="row.telepon"
                        class="flex items-center text-[11px] font-semibold text-muted-foreground/80">
                        <Phone class="mr-2 h-3.5 w-3.5 text-accent/50" />
                        {{ row.telepon }}
                    </div>
                    <div v-if="row.alamat"
                        class="flex items-start text-[11px] text-muted-foreground/60 max-w-[200px] leading-relaxed">
                        <MapPin class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground/30" />
                        <span class="line-clamp-2">{{ row.alamat }}</span>
                    </div>
                </div>
            </template>

            <template #cell(info)="{ row }">
                <div v-if="row.keterangan"
                    class="flex items-start text-[11px] text-muted-foreground/50 italic leading-snug max-w-[180px]">
                    <Info class="mr-2 h-3.5 w-3.5 mt-0.5 shrink-0 text-muted-foreground/20" />
                    <span class="line-clamp-2">{{ row.keterangan }}</span>
                </div>
                <span v-else class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/20 italic">No
                    notes</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1 px-2">
                    <Button variant="ghost" size="icon" as-child
                        class="h-8 w-8 text-muted-foreground/30 hover:bg-secondary hover:text-foreground rounded-lg">
                        <Link :href="`/vendors/${row.id}/edit`">
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40">
                            <DropdownMenuLabel
                                class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5">
                                Opsi Vendor</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="`/vendors/${row.id}/edit`"
                                    class="flex items-center w-full rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <Edit2 class="h-3.5 w-3.5 text-muted-foreground/60" /> Edit Detail
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
