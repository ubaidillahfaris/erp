<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { index, bulkDestroy, destroy } from '@/actions/App/Http/Controllers/CustomerController';
import debounce from 'lodash/debounce';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, User, Phone, Mail, MapPin, Info, ChevronRight, Tag, Activity } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';

import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    customers: {
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
    { title: 'Master Customer', href: '/customers' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.customers.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');

const columns = [
    { key: 'profil', label: 'Profil Customer', sortKey: 'name' },
    { key: 'kontak', label: 'Kontak', sortable: false },
    { key: 'tipe_status', label: 'Tipe & Status', sortable: false },
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
    if (await confirmDialog('Hapus Customer Terpilih?', `Are you sure you want to delete ${ids.length} customer yang dipilih? Data yang memiliki riwayat transaksi akan dilewati.`)) {
        router.post(bulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        });
    }
};

const { confirmDialog } = useConfirm();

const deleteCustomer = async (id: number) => {
    if (await confirmDialog('Hapus Customer?', 'Are you sure you want to delete customer ini? Data yang terhapus tidak bisa dikembalikan.')) {
        router.delete(destroy({ customer: id }).url);
    }
};
</script>

<template>
<Head title="Customer" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Customer & Pelanggan" 
        description="Manajemen database pelanggan setia." 
        back-href="/dashboard" 
        :count="customers.total" 
    />
    
    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="customers" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari customer..." 
            toolbar-title="Database Pelanggan" 
            :title="'Customer & Pelanggan'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            @bulk-delete="handleBulkDelete"
            :total-count="customers.total"
        >
            <template #header-actions>
                <Link href="/customers/create">
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Tambah Customer
                    </Button>
                </Link>
            </template>
            <template #cell(profil)="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <User class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[13px] font-bold text-foreground capitalize truncate leading-none">{{ row.name }}</p>
                        <p v-if="row.email"
                            class="text-[11px] text-muted-foreground font-mono mt-1.5 flex items-center gap-1.5 tracking-tight">
                            <Mail class="h-3 w-3 opacity-70" /> {{ row.email }}
                        </p>
                    </div>
                </div>
            </template>

            <template #cell(kontak)="{ row }">
                <div class="flex flex-col gap-1">
                    <div v-if="row.phone"
                        class="flex items-center text-[11px] font-bold text-foreground tracking-tight">
                        <Phone class="mr-2 h-3 w-3 text-accent" />
                        {{ row.phone }}
                    </div>
                    <div v-if="row.address"
                        class="flex items-start text-[10px] text-muted-foreground max-w-[180px] leading-relaxed pl-5">
                        <span class="line-clamp-2 italic">{{ row.address }}</span>
                    </div>
                </div>
            </template>

            <template #cell(tipe_status)="{ row }">
                <div class="flex flex-col gap-1.5">
                    <div v-if="row.type" class="flex items-center gap-2">
                        <Tag class="h-3.5 w-3.5 text-muted-foreground" />
                        <span class="text-xs font-medium">{{ row.type.name }}</span>
                    </div>
                    <div v-if="row.status" class="flex items-center gap-2">
                        <Activity class="h-3.5 w-3.5 text-muted-foreground" />
                        <Badge variant="outline" class="text-[10px] font-bold uppercase py-0 px-1.5 h-4.5">
                            {{ row.status.name }}
                        </Badge>
                    </div>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/customers/${row.id}/edit`" class="block w-full h-full flex items-center justify-center">
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
                                Opsi Customer</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link :href="`/customers/${row.id}/edit`"
                                    class="flex items-center w-full rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                    <Edit2 class="h-3.5 w-3.5 text-muted-foreground" /> Edit Detail
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteCustomer(row.id)"
                                class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive font-medium focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Customer
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <User class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Customer tidak ditemukan
                    </p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
