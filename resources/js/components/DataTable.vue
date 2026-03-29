<script setup lang="ts">
import { 
    getCoreRowModel, 
    getExpandedRowModel,
    getSortedRowModel, // Added
    useVueTable, 
    type ColumnDef,
    type SortingState, // Added
} from '@tanstack/vue-table';
import { 
    ChevronDown, 
    ChevronRight, 
    ArrowUpDown,
    ArrowUp,
    ArrowDown,
    Trash2, // Added
    X // Added
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import DataTableTabs from '@/components/DataTableTabs.vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
// Removed Checkbox import

interface Column {
    readonly key: string;
    readonly label: string;
    readonly align?: 'left' | 'center' | 'right';
    readonly width?: string;
    readonly class?: string;
    readonly sortable?: boolean;
    readonly sortKey?: string; // Added
}

interface Tab {
    readonly value: string;
    readonly label: string;
    readonly count?: number | string;
}

const props = withDefaults(defineProps<{
    data: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    columns: readonly Column[];
    title?: string;
    totalCount?: number | string;
    search?: string;
    searchPlaceholder?: string;
    tabs?: readonly Tab[];
    activeTab?: string;
    perPage?: string;
    sort?: string;
    direction?: 'asc' | 'desc';
    expandable?: boolean;
    rowIdKey?: string;
    toolbarTitle?: string;
    showSelection?: boolean;
}>(), {
    searchPlaceholder: 'Filter...',
    rowIdKey: 'id',
    expandable: false,
    showSelection: true,
});

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:activeTab', value: string): void;
    (e: 'update:perPage', value: string): void;
    (e: 'rowClick', row: any): void;
    (e: 'bulkDelete', selectedIds: (string | number)[]): void;
    (e: 'sortChange', payload: { key: string, direction: 'asc' | 'desc' | null }): void;
}>();

// --- STATE SYNC ---
const internalSearch = computed({
    get: () => props.search || '',
    set: (val) => emit('update:search', val),
});

const internalActiveTab = computed({
    get: () => props.activeTab || '',
    set: (val) => emit('update:activeTab', val),
});

const internalPerPage = computed({
    get: () => props.perPage || String(props.data.per_page),
    set: (val) => emit('update:perPage', val),
});

// --- TANSTACK TABLE ---
const sorting = ref<SortingState>(
    props.sort ? [{ id: props.sort, desc: props.direction === 'desc' }] : []
);
const rowSelection = ref({});

// Watch sorting state and emit
watch(sorting, (newSorting) => {
    if (newSorting.length > 0) {
        const { id, desc } = newSorting[0];
        // Find the column to see if it has a sortKey
        const column = table.getColumn(id);
        const sortKey = (column?.columnDef.meta as any)?.sortKey || id;
        
        if (sortKey !== props.sort || (desc ? 'desc' : 'asc') !== props.direction) {
            emit('sortChange', { key: sortKey, direction: desc ? 'desc' : 'asc' });
        }
    } else if (props.sort) {
        emit('sortChange', { key: '', direction: null });
    }
}, { deep: true });

// Sync props back to sorting
watch(() => [props.sort, props.direction], ([newSort, newDir]) => {
    // Find the column ID that matches this sort key
    const column = tableColumns.value.find(c => 
        (c.meta as any)?.sortKey === newSort || c.id === newSort
    );
    
    const targetId = column?.id || (newSort as string);
    const targetDesc = newDir === 'desc';

    const currentSort = sorting.value[0]?.id;
    const currentDesc = sorting.value[0]?.desc;

    if (targetId !== currentSort || targetDesc !== currentDesc) {
        sorting.value = targetId ? [{ id: targetId, desc: targetDesc }] : [];
    }
});

const tableColumns = computed<ColumnDef<any>[]>(() => {
    const cols: ColumnDef<any>[] = [];

    // Selection Column
    if (props.showSelection) {
        cols.push({
            id: 'select',
            header: () => null,
            cell: () => null,
            size: 40,
            enableHiding: false,
            enableSorting: false,
        });
    }

    // Expand Toggle Column
    if (props.expandable) {
        cols.push({
            id: 'expand',
            header: () => null,
            cell: () => null,
            size: 40,
            enableHiding: false,
            enableSorting: false,
        });
    }

    // Data Columns
    props.columns.forEach(col => {
        cols.push({
            id: col.key,
            accessorKey: col.key,
            header: col.label,
            enableSorting: col.sortable !== false,
            meta: {
                align: col.align,
                width: col.width,
                class: col.class,
                sortable: col.sortable,
                sortKey: col.sortKey, // Added
            }
        });
    });

    // Actions Column (Always at the end)
    cols.push({
        id: 'actions',
        header: () => null,
        cell: () => null,
        size: 80,
        enableHiding: false,
        enableSorting: false,
    });

    return cols;
});

const table = useVueTable({
    get data() { return props.data.data },
    get columns() { return tableColumns.value },
    getCoreRowModel: getCoreRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getRowId: (row) => row[props.rowIdKey],
    manualPagination: true,
    manualSorting: true,
    state: {
        get sorting() { return sorting.value },
        get rowSelection() { return rowSelection.value },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater;
    },
});

// --- HELPER ---
const selectedRowsCount = computed(() => table.getFilteredSelectedRowModel().rows.length);

const handleBulkDelete = () => {
    const selectedIds = table.getFilteredSelectedRowModel().rows.map(row => row.id);
    emit('bulkDelete', selectedIds);
};

const clearSelection = () => {
    rowSelection.value = {};
};

const handleSortToggle = (column: any) => {
    if (!column.getCanSort()) return;
    const current = column.getIsSorted();
    if (!current) {
        column.toggleSorting(false); // Set to ASC
    } else if (current === 'asc') {
        column.toggleSorting(true); // Set to DESC
    } else {
        column.clearSorting(); // Back to NEUTRAL
    }
};
</script>

<template>
<div class="w-full space-y-4">
    <!-- TABS (Optional) -->
    <DataTableTabs v-if="tabs && tabs.length > 0" v-model:activeTab="internalActiveTab" :tabs="tabs" />

    <!-- TOOLBAR (Standard vs Selection) -->
    <div class="h-10 flex items-center justify-between gap-4">
        <!-- Bulkl/Selection Toolbar -->
        <div v-if="selectedRowsCount > 0" class="flex-1 flex items-center justify-between px-3 bg-accent/5 border border-accent/20 rounded-md h-full animate-in fade-in slide-in-from-top-1 duration-200">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="sm" class="h-7 w-7 p-0 hover:bg-accent/10" @click="clearSelection">
                    <X class="h-4 w-4" />
                </Button>
                <p class="text-sm font-semibold text-accent leading-none">
                    {{ selectedRowsCount }} items selected
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <slot name="bulk-actions" :selected-ids="table.getFilteredSelectedRowModel().rows.map(r => r.id)">
                    <Button 
                        variant="destructive" 
                        size="sm" 
                        class="h-8 gap-2 px-3 text-xs font-bold uppercase tracking-wider"
                        @click="handleBulkDelete"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        Hapus
                    </Button>
                </slot>
            </div>
        </div>

        <!-- Standard Toolbar -->
        <template v-else>
            <div class="flex items-center gap-2">
                <div class="relative group">
                    <Input
                        v-model="internalSearch"
                        :placeholder="searchPlaceholder"
                        class="h-9 w-[150px] lg:w-[250px] shadow-sm focus:ring-accent/10 transition-all pl-3"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <slot name="header-actions"></slot>
            </div>
        </template>
    </div>

    <!-- TABLE AREA -->
    <div class="rounded-md border border-border/60 bg-white overflow-hidden shadow-sm">
        <Table>
            <TableHeader class="bg-muted/30">
                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                    <template v-for="header in headerGroup.headers" :key="header.id">
                        <!-- Selection Header -->
                        <TableHead v-if="header.column.id === 'select'" class="w-[40px] px-4 align-middle">
                            <input 
                                type="checkbox"
                                :checked="table.getIsAllPageRowsSelected()"
                                :indeterminate="table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected()"
                                @change="table.toggleAllPageRowsSelected(($event.target as HTMLInputElement).checked)"
                                class="h-4 w-4 rounded border-border/40 text-accent accent-accent focus:ring-accent/20 cursor-pointer transition-all bg-white"
                            />
                        </TableHead>

                        <!-- Expand Header -->
                        <TableHead v-else-if="header.column.id === 'expand'" class="w-[40px]"></TableHead>

                        <!-- Actions Header -->
                        <TableHead v-else-if="header.column.id === 'actions'" class="w-[80px] px-4 text-right"></TableHead>

                        <!-- Data Headers -->
                        <TableHead 
                            v-else
                            @click="handleSortToggle(header.column)"
                            :class="[
                                'px-4 text-xs font-bold uppercase tracking-wider text-muted-foreground/70 h-11 select-none',
                                header.column.getCanSort() ? 'cursor-pointer hover:bg-muted/50 transition-colors group/head' : '',
                                (header.column.columnDef.meta as any)?.align === 'center' ? 'text-center' : (header.column.columnDef.meta as any)?.align === 'right' ? 'text-right' : 'text-left',
                                (header.column.columnDef.meta as any)?.class
                            ]"
                            :style="{ width: (header.column.columnDef.meta as any)?.width }"
                        >
                            <div class="flex items-center gap-2"
                                :class="(header.column.columnDef.meta as any)?.align === 'center' ? 'justify-center' : (header.column.columnDef.meta as any)?.align === 'right' ? 'justify-end' : 'justify-start'">
                                {{ header.column.columnDef.header }}
                                
                                <template v-if="header.column.getCanSort()">
                                    <div class="flex items-center">
                                        <ArrowUp v-if="header.column.getIsSorted() === 'asc'" class="h-3.5 w-3.5 text-accent animate-in fade-in zoom-in-50 duration-300" />
                                        <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="h-3.5 w-3.5 text-accent animate-in fade-in zoom-in-50 duration-300" />
                                        <ArrowUpDown v-else class="h-3.5 w-3.5 opacity-20 group-hover/head:opacity-60 transition-opacity" />
                                    </div>
                                </template>
                            </div>
                        </TableHead>
                    </template>
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-if="table.getRowModel().rows?.length">
                    <template v-for="row in table.getRowModel().rows" :key="row.id">
                        <TableRow 
                            :data-state="row.getIsSelected() && 'selected'"
                            class="group transition-colors data-[state=selected]:bg-accent/[0.03] hover:bg-muted/20"
                            @click="emit('rowClick', row.original)"
                        >
                            <template v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <!-- Selection Cell -->
                                <TableCell v-if="cell.column.id === 'select'" class="px-4 py-3 align-middle">
                                    <input 
                                        type="checkbox"
                                        :checked="row.getIsSelected()" 
                                        @change="row.toggleSelected(($event.target as HTMLInputElement).checked)"
                                        @click.stop
                                        class="h-4 w-4 rounded border-border/40 text-accent accent-accent focus:ring-accent/20 cursor-pointer transition-all bg-white"
                                    />
                                </TableCell>

                                <!-- Expand Cell -->
                                <TableCell v-else-if="cell.column.id === 'expand'" class="p-0 pl-1 w-[40px] align-middle">
                                    <button @click.stop="row.toggleExpanded()" class="h-8 w-8 rounded-md flex items-center justify-center hover:bg-muted transition-all">
                                        <ChevronRight :class="['h-4 w-4 text-muted-foreground/40 transition-transform duration-300', row.getIsExpanded() ? 'rotate-90 text-accent' : '']" />
                                    </button>
                                </TableCell>

                                <!-- Actions Cell -->
                                <TableCell v-else-if="cell.column.id === 'actions'" class="px-4 py-3 text-right align-middle">
                                    <div v-if="$slots.actions" @click.stop>
                                        <slot name="actions" :row="row.original"></slot>
                                    </div>
                                </TableCell>

                                <!-- Data Cell -->
                                <TableCell 
                                    v-else
                                    :class="[
                                        'px-4 py-3 align-middle text-sm font-medium text-foreground/80',
                                        (cell.column.columnDef.meta as any)?.align === 'center' ? 'text-center' : (cell.column.columnDef.meta as any)?.align === 'right' ? 'text-right' : 'text-left',
                                        (cell.column.columnDef.meta as any)?.class
                                    ]"
                                >
                                    <slot :name="`cell(${cell.column.id})`" :row="row.original" :column="cell.column.columnDef">
                                        {{ row.original[cell.column.id] }}
                                    </slot>
                                </TableCell>
                            </template>
                        </TableRow>

                        <!-- Expandable Content -->
                        <TableRow v-if="row.getIsExpanded()">
                            <TableCell :colspan="row.getVisibleCells().length" class="p-0 border-t bg-muted/5 tracking-normal">
                                <div class="px-12 py-6 animate-in slide-in-from-top-1 duration-300">
                                    <slot name="expanded" :row="row.original"></slot>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </template>

                <template v-else>
                    <TableRow>
                        <TableCell :colspan="tableColumns.length" class="h-32 text-center text-muted-foreground/40 text-sm italic py-12">
                            <slot name="empty">No results found.</slot>
                        </TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>

        <!-- PAGINATION -->
        <DataTablePagination 
            :paginator="data" 
            v-model:perPage="internalPerPage" 
            :selectedCount="selectedRowsCount"
            class="border-t bg-muted/10"
        />
    </div>
</div>
</template>
