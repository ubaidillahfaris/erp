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
    X, // Added
    Plus,
    ListFilter,
    Check,
    Search,
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
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
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

interface FilterOption {
    readonly value: string;
    readonly label: string;
}

interface FilterCategory {
    readonly key: string;
    readonly label: string;
    readonly options: readonly FilterOption[];
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
    filterOptions?: readonly FilterCategory[];
    activeFilters?: Record<string, any>;
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
    (e: 'addNew'): void;
    (e: 'update:active-filters', value: Record<string, any>): void;
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

const hasActiveFilters = computed(() => {
    return Object.keys(props.activeFilters || {}).length > 0;
});

const getActiveFilterCount = (key: string) => {
    const filter = props.activeFilters?.[key];
    if (Array.isArray(filter)) return filter.length;
    return filter ? 1 : 0;
};

const isFilterActive = (key: string, value: string) => {
    const filter = props.activeFilters?.[key];
    if (Array.isArray(filter)) return filter.includes(value);
    return filter === value;
};

const toggleFilter = (key: string, value: string) => {
    const current = { ...(props.activeFilters || {}) };
    let values = Array.isArray(current[key]) ? [...current[key]] : (current[key] ? [current[key]] : []);

    if (values.includes(value)) {
        values = values.filter(v => v !== value);
    } else {
        values.push(value);
    }

    if (values.length === 0) {
        delete current[key];
    } else {
        current[key] = values;
    }

    emit('update:active-filters', current);
};

const clearFilters = () => {
    emit('update:active-filters', {});
};

const clearFilterCategory = (key: string) => {
    const current = { ...(props.activeFilters || {}) };
    delete current[key];
    emit('update:active-filters', current);
};
</script>

<template>
<div class="w-full space-y-4">
    <!-- TABS (Optional) -->
    <DataTableTabs v-if="tabs && tabs.length > 0" v-model:activeTab="internalActiveTab" :tabs="tabs" />

    <!-- TOOLBAR -->
    <div class="h-10 flex items-center justify-end gap-3">
        <!-- Bulkl/Selection Toolbar -->
        <div v-if="selectedRowsCount > 0"
            class="flex-1 flex items-center justify-between px-3 bg-accent/5 border border-accent/20 rounded-md h-full animate-in fade-in slide-in-from-top-1 duration-200">
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
                    <Button variant="destructive" size="sm"
                        class="h-8 gap-2 px-3 text-xs font-bold uppercase tracking-wider" @click="handleBulkDelete">
                        <Trash2 class="h-3.5 w-3.5" />
                        Hapus
                    </Button>
                </slot>
            </div>
        </div>

        <!-- Standard Toolbar -->
        <template v-else>
            <!-- FILTERS & OTHER ACTIONS -->
            <div class="flex items-center gap-2">
                <div v-if="$slots['toolbar-actions']" class="flex items-center gap-2">
                    <slot name="toolbar-actions"></slot>
                </div>

                <div v-if="filterOptions && filterOptions.length > 0" class="flex items-center gap-2">
                    <template v-for="filter in filterOptions" :key="filter.key">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" size="sm" class="h-9 border rounded-full px-4">
                                    <ListFilter class="mr-2 h-4 w-4" />
                                    {{ filter.label }}
                                    <template v-if="getActiveFilterCount(filter.key) > 0">
                                        <Separator orientation="vertical" class="mx-2 h-4" />
                                        <Badge variant="secondary" class="rounded-sm px-1 font-normal lg:hidden">
                                            {{ getActiveFilterCount(filter.key) }}
                                        </Badge>
                                        <div class="hidden space-x-1 lg:flex">
                                            <Badge v-if="getActiveFilterCount(filter.key) > 2" variant="secondary"
                                                class="rounded-sm px-1 font-normal">
                                                {{ getActiveFilterCount(filter.key) }} selected
                                            </Badge>
                                            <template v-else>
                                                <Badge
                                                    v-for="option in filter.options.filter(o => isFilterActive(filter.key, o.value))"
                                                    :key="option.value" variant="secondary"
                                                    class="rounded-sm px-1 font-normal">
                                                    {{ option.label }}
                                                </Badge>
                                            </template>
                                        </div>
                                    </template>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-[200px]">
                                <DropdownMenuLabel>{{ filter.label }}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuCheckboxItem v-for="option in filter.options" :key="option.value"
                                    :checked="isFilterActive(filter.key, option.value)"
                                    @select.prevent="toggleFilter(filter.key, option.value)"
                                    :class="[
                                        'cursor-pointer transition-colors',
                                        isFilterActive(filter.key, option.value) ? 'bg-accent/5 font-semibold text-accent' : ''
                                    ]">
                                    <div class="flex items-center justify-between w-full gap-2">
                                        <span>{{ option.label }}</span>
                                        <Check v-if="isFilterActive(filter.key, option.value)"
                                            class="h-3.5 w-3.5 animate-in zoom-in-50 duration-300" />
                                    </div>
                                </DropdownMenuCheckboxItem>
                                <template v-if="getActiveFilterCount(filter.key) > 0">
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="clearFilterCategory(filter.key)"
                                        class="justify-center text-center text-xs font-semibold text-muted-foreground hover:text-destructive py-2 transition-colors cursor-pointer border-t mt-1">
                                        RESET FILTER
                                    </DropdownMenuItem>
                                </template>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </template>

                    <Button v-if="hasActiveFilters" variant="ghost" class="h-9 px-2 lg:px-4 rounded-full" @click="clearFilters">
                        Reset
                        <X class="ml-2 h-4 w-4" />
                    </Button>
                </div>
            </div>

            <!-- SEARCH & CREATE -->
            <div class="flex items-center gap-2">
                <div class="relative group">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground transition-colors group-focus-within:text-accent" />
                    <Input v-model="internalSearch" :placeholder="searchPlaceholder"
                        class="h-9 w-[150px] lg:w-[250px] bg-white border-input shadow-none focus-visible:ring-accent/5 rounded-full transition-all pl-9" />
                </div>
                <slot name="header-actions"></slot>
            </div>
        </template>
    </div>

    <!-- TABLE AREA -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <Table>
            <TableHeader class="bg-slate-50 border-b border-slate-200">
                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                    <template v-for="header in headerGroup.headers" :key="header.id">
                        <!-- Selection Header -->
                        <TableHead v-if="header.column.id === 'select'" class="w-[40px] px-4 align-middle">
                            <input type="checkbox" :checked="table.getIsAllPageRowsSelected()"
                                :indeterminate="table.getIsSomePageRowsSelected() && !table.getIsAllPageRowsSelected()"
                                @change="table.toggleAllPageRowsSelected(($event.target as HTMLInputElement).checked)"
                                class="h-4 w-4 rounded border-input text-accent accent-accent focus:ring-accent/20 cursor-pointer transition-all bg-white" />
                        </TableHead>

                        <!-- Expand Header -->
                        <TableHead v-else-if="header.column.id === 'expand'" class="w-[40px]"></TableHead>

                        <!-- Actions Header -->
                        <TableHead v-else-if="header.column.id === 'actions'" class="w-[80px] px-4 text-right">
                        </TableHead>

                        <!-- Data Headers -->
                        <TableHead v-else @click="handleSortToggle(header.column)" :class="[
                            'px-4 text-xs font-medium text-muted-foreground h-10 select-none',
                            header.column.getCanSort() ? 'cursor-pointer hover:text-foreground transition-colors group/head' : '',
                            (header.column.columnDef.meta as any)?.align === 'center' ? 'text-center' : (header.column.columnDef.meta as any)?.align === 'right' ? 'text-right' : 'text-left',
                            (header.column.columnDef.meta as any)?.class
                        ]" :style="{ width: (header.column.columnDef.meta as any)?.width }">
                            <div class="flex items-center gap-2"
                                :class="(header.column.columnDef.meta as any)?.align === 'center' ? 'justify-center' : (header.column.columnDef.meta as any)?.align === 'right' ? 'justify-end' : 'justify-start'">
                                {{ header.column.columnDef.header }}

                                <template v-if="header.column.getCanSort()">
                                    <div class="flex items-center">
                                        <ArrowUp v-if="header.column.getIsSorted() === 'asc'"
                                            class="h-3.5 w-3.5 text-accent animate-in fade-in zoom-in-50 duration-300" />
                                        <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'"
                                            class="h-3.5 w-3.5 text-accent animate-in fade-in zoom-in-50 duration-300" />
                                        <ArrowUpDown v-else
                                            class="h-3.5 w-3.5 opacity-20 group-hover/head:opacity-60 transition-opacity" />
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
                        <TableRow :data-state="row.getIsSelected() && 'selected'"
                            class="group transition-colors data-[state=selected]:bg-accent/[0.03] hover:bg-slate-50/80"
                            @click="emit('rowClick', row.original)">
                            <template v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <!-- Selection Cell -->
                                <TableCell v-if="cell.column.id === 'select'" class="px-4 py-3 align-middle">
                                    <input type="checkbox" :checked="row.getIsSelected()"
                                        @change="row.toggleSelected(($event.target as HTMLInputElement).checked)"
                                        @click.stop
                                        class="h-4 w-4 rounded border-input text-accent accent-accent focus:ring-accent/20 cursor-pointer transition-all bg-white"
                                        :class="row.getIsSelected() ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 transition-opacity'" />
                                </TableCell>

                                <!-- Expand Cell -->
                                <TableCell v-else-if="cell.column.id === 'expand'"
                                    class="p-0 pl-1 w-[40px] align-middle">
                                    <button @click.stop="row.toggleExpanded()"
                                        class="h-8 w-8 rounded-md flex items-center justify-center hover:bg-muted transition-all">
                                        <ChevronRight
                                            :class="['h-4 w-4 text-muted-foreground transition-transform duration-300', row.getIsExpanded() ? 'rotate-90 text-accent' : '']" />
                                    </button>
                                </TableCell>

                                <!-- Actions Cell -->
                                <TableCell v-else-if="cell.column.id === 'actions'"
                                    class="px-4 py-3 text-right align-middle">
                                    <div v-if="$slots.actions" @click.stop>
                                        <slot name="actions" :row="row.original"></slot>
                                    </div>
                                </TableCell>

                                <!-- Data Cell -->
                                <TableCell v-else :class="[
                                    'px-4 py-3.5 align-middle text-sm text-foreground/80',
                                    (cell.column.columnDef.meta as any)?.align === 'center' ? 'text-center' : (cell.column.columnDef.meta as any)?.align === 'right' ? 'text-right' : 'text-left',
                                    (cell.column.columnDef.meta as any)?.class
                                ]">
                                    <slot :name="`cell(${cell.column.id})`" :row="row.original"
                                        :column="cell.column.columnDef">
                                        {{ row.original[cell.column.id] }}
                                    </slot>
                                </TableCell>
                            </template>
                        </TableRow>

                        <!-- Expandable Content -->
                        <TableRow v-if="row.getIsExpanded()">
                            <TableCell :colspan="row.getVisibleCells().length"
                                class="p-0 border-t bg-muted/5 tracking-normal">
                                <div class="px-8 py-5 animate-in slide-in-from-top-1 duration-300">
                                    <slot name="expanded" :row="row.original"></slot>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </template>

                <template v-else>
                    <TableRow>
                        <TableCell :colspan="tableColumns.length"
                            class="h-48 text-center text-muted-foreground text-sm py-12">
                            <slot name="empty">No results found.</slot>
                        </TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>

        <div class="border-t border-slate-100">
            <slot name="add-new">
                <button @click="emit('addNew')"
                    class="w-full flex items-center gap-2 px-6 py-3 text-sm text-muted-foreground hover:text-foreground hover:bg-slate-50 transition-colors">
                    <Plus class="h-4 w-4" />
                    New
                </button>
            </slot>
        </div>

        <!-- PAGINATION -->
        <DataTablePagination :paginator="data" v-model:perPage="internalPerPage" :selectedCount="selectedRowsCount"
            class="border-t bg-muted/10" />
    </div>
</div>
</template>
