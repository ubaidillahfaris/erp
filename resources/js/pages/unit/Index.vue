<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Plus, Search, Edit2, Trash2, MoreHorizontal, Ruler, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import { index as unitIndex, destroy as unitDestroy, bulkDestroy as unitBulkDestroy } from '@/actions/App/Http/Controllers/UnitController';
import { create as unitCreate, edit as unitEdit } from '@/actions/App/Http/Controllers/UnitController';
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
    units: {
        data: Array<{
            id: number;
            name: string;
            symbol: string;
            description: string | null;
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
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Units', href: unitIndex().url },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.units.per_page));
const sort = ref(props.filters.sort || '');
const direction = ref(props.filters.direction || '');

const columns = [
    { key: 'nama_simbol', label: 'Name & Symbol', sortKey: 'name' },
    { key: 'description', label: 'Description' },
] as const;

watch(
    [search, perPage, sort, direction],
    debounce(([newSearch, newPerPage, newSort, newDirection]) => {
        router.get(
            unitIndex().url,
            { 
                search: newSearch || undefined, 
                per_page: newPerPage,
                sort: newSort || undefined,
                direction: newSort ? (newDirection || 'asc') : undefined
            },
            { preserveState: true, replace: true, preserveScroll: true }
        );
    }, 300)
);

const { confirmDialog } = useConfirm();

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const handleBulkDelete = async (ids: (string | number)[]) => {
    if (await confirmDialog('Delete Selected Units?', `Are you sure you want to delete ${ids.length} selected units?`)) {
        router.post(unitBulkDestroy().url, {
            _method: 'DELETE',
            ids: ids
        }, {
            onSuccess: () => {
                // Flash messages handled by server
            }
        });
    }
};

const confirmDelete = async (id: number) => {
    if (await confirmDialog('Delete Product Unit?', 'Are you sure you want to delete this unit? This action cannot be undone.')) {
        router.delete(unitDestroy(id).url);
    }
};
</script>

<template>
    <Head title="Product Units" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Units & Measurements" 
            description="Manage Product Units of Measure" 
            back-href="/dashboard"
            :count="units.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="units"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                :sort="sort"
                :direction="direction as any"
                @sort-change="handleSortChange"
                @bulk-delete="handleBulkDelete"
                search-placeholder="Search units..."
                toolbar-title="Unit Master List"
            >
                <template #header-actions>
                    <Link :href="unitCreate().url">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Add Unit
                        </Button>
                    </Link>
                </template>
                <template #cell(nama_simbol)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                            <Ruler class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-bold text-foreground capitalize leading-none">{{ row.name }}</p>
                            <p class="text-[11px] font-mono font-bold text-muted-foreground uppercase tracking-widest mt-1.5">{{ row.symbol }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(description)="{ row }">
                    <span class="text-[13px] text-muted-foreground">{{ row.description || 'No description' }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="unitEdit(row.id).url">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                <ChevronRight class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="confirmDelete(row.id)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5" /> Delete Unit
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <Ruler class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">No units found</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
