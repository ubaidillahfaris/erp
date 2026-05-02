<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import {
    Plus, Search, Edit2, Trash2, PackageOpen,
    MoreHorizontal, Settings2, History as HistoryIcon,
    ChevronRight, Pencil, Filter, Hash, Layers
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
// Use Wayfinder actions for consistency
import { index, store } from '@/actions/App/Http/Controllers/ServiceController';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuLabel, 
    DropdownMenuSeparator, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import servicesRoutes from '@/routes/settings/services';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

interface Service {
    id: number;
    code: string;
    name: string;
    description: string | null;
    service_category: string;
    category?: {
        id: number;
        name: string;
    };
    is_active: boolean;
    service_types_count: number;
    orders_count: number;
}

const props = defineProps<{
    services: {
        data: Service[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        category?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pengaturan', href: '/settings' },
    { title: 'Katalog Jasa', href: servicesRoutes.index.url() },
];

const search = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category || 'all');

const columns = [
    { key: 'identity', label: 'Kode & Kategori', sortable: false },
    { key: 'service', label: 'Nama Layanan', sortable: false },
    { key: 'variants', label: 'Tipe & Varian', align: 'center', sortable: false },
    { key: 'status', label: 'Status', align: 'center', sortable: false },
] as const;

watch([search, categoryFilter], debounce(([newSearch, newCategory]) => {
    router.get(servicesRoutes.index.url(), {
        search: newSearch || undefined,
        category: newCategory === 'all' ? undefined : newCategory
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const deleteService = async (id: number) => {
    // Assuming we'll add a destroy method later
    if (await confirmDialog('Apakah Anda yakin?', 'Anda akan menghapus layanan ini beserta seluruh konfigurasinya.')) {
        router.delete(`/settings/services/${id}`);
    }
};

const formatCount = (count: number, label: string) => {
    return `${count} ${label}`;
};
</script>

<template>
<Head title="Katalog Jasa" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader 
        title="Katalog Jasa" 
        description="Manajemen layanan, harga, dan alur kerja (BOM Jasa)" 
        back-href="/settings"
        :count="services.total" 
    />

    <!-- ====== CONTENT AREA ====== -->
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="services" 
            :columns="columns" 
            v-model:search="search" 
            search-placeholder="Cari nama atau kode jasa..." 
            :title="'Katalog Jasa'"
            :total-count="services.total"
        >
            <template #header-actions>
                <div class="flex items-center gap-2">
                    <Button variant="outline" @click="router.visit('/settings/service-categories')">
                        <Tag class="h-4 w-4" />
                        Kelola Kategori
                    </Button>
                    <Button primary @click="router.visit('/settings/services/create')">
                        <Plus class="h-4 w-4" />
                        Tambah Jasa
                    </Button>
                </div>
            </template>

            <template #cell(identity)="{ row }">
                <div class="flex flex-col gap-1">
                    <span class="text-[11px] font-mono font-semibold text-foreground/80 tracking-tight bg-slate-100 px-1.5 py-0.5 rounded w-fit">
                        #{{ row.code }}
                    </span>
                    <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-widest pl-0.5">
                        {{ row.category?.name || row.service_category }}
                    </span>
                </div>
            </template>

            <template #cell(service)="{ row }">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                        <Layers class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 pr-4">
                        <p class="text-[13px] font-semibold text-foreground truncate max-w-[300px] leading-none">{{ row.name }}</p>
                        <p class="text-[11px] font-semibold text-muted-foreground uppercase tracking-widest mt-1.5 opacity-70">
                            {{ row.description || 'Tidak ada deskripsi' }}
                        </p>
                    </div>
                </div>
            </template>

            <template #cell(variants)="{ row }">
                <div class="flex flex-col items-center gap-1">
                    <span class="text-[13px] font-semibold text-foreground tabular-nums leading-none">
                        {{ row.service_types_count }}
                    </span>
                    <span class="text-[10px] font-semibold uppercase opacity-60 leading-none tracking-widest">Varian</span>
                </div>
            </template>

            <template #cell(status)="{ row }">
                <Badge 
                    :class="[
                        'text-[10px] uppercase font-semibold px-1.5 h-5',
                        row.is_active 
                            ? 'bg-emerald-50 text-emerald-600 border-emerald-100' 
                            : 'bg-slate-100 text-slate-400 border-slate-200'
                    ]"
                >
                    {{ row.is_active ? 'Aktif' : 'Non-Aktif' }}
                </Badge>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                <MoreHorizontal class="h-4 w-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                            <DropdownMenuItem @click="router.visit(servicesRoutes.show.url(row.id))">
                                <Settings2 class="h-3.5 w-3.5" /> Konfigurasi Builder
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="deleteService(row.id)" class="text-destructive focus:text-destructive focus:bg-destructive/5">
                                <Trash2 class="h-3.5 w-3.5" /> Hapus Jasa
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                    <PackageOpen class="h-10 w-10 text-muted-foreground" />
                    <p class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Katalog Jasa Kosong</p>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
