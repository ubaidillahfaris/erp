<script setup lang="ts">
import { Search, ChevronDown, ChevronRight, MoreHorizontal, Filter, Grid, List, ArrowUpDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';

interface Column {
    readonly key: string;
    readonly label: string;
    readonly align?: 'left' | 'center' | 'right';
    readonly width?: string;
    readonly class?: string;
    readonly sortable?: boolean;
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
    expandable?: boolean;
    rowIdKey?: string;
    toolbarTitle?: string;
    showSelection?: boolean;
}>(), {
    searchPlaceholder: 'Search...',
    rowIdKey: 'id',
    expandable: false,
    showSelection: true,
});

const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:activeTab', value: string): void;
    (e: 'update:perPage', value: string): void;
    (e: 'rowClick', row: any): void;
}>();

const internalSearch = computed({
    get: () => props.search || '',
    set: (val) => emit('update:search', val),
});

const internalPerPage = computed({
    get: () => props.perPage || String(props.data.per_page),
    set: (val) => emit('update:perPage', val),
});

const expandedRows = ref<Set<any>>(new Set());

const toggleRow = (id: any) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};

const handleTabClick = (value: string) => {
    emit('update:activeTab', value);
};
</script>

<template>
<div class="flex flex-col gap-0 bg-white border border-border/40 rounded-sm overflow-hidden font-sans">


    <!-- ====== ROW 2: TABS NAVIGATION ====== -->
    <div v-if="tabs && tabs.length > 0" class="px-8 border-b border-border/10">
        <div class="flex items-center gap-8 h-12 overflow-x-auto no-scrollbar">
            <button v-for="tab in tabs" :key="tab.value" @click="handleTabClick(tab.value)" type="button" :class="[
                'relative h-12 text-[13px] font-semibold tracking-wider cursor-pointer transition-all flex items-center gap-2 whitespace-nowrap px-1 group',
                activeTab === tab.value
                    ? 'text-foreground'
                    : 'text-muted-foreground/40 hover:text-foreground'
            ]">
                {{ tab.label }}
                <span v-if="tab.count !== undefined" class="text-[10px] font-bold opacity-60 ml-0.5">
                    {{ tab.count }}
                </span>
                <!-- Active Indicator -->
                <div v-if="activeTab === tab.value"
                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-accent rounded-t-full transition-all"></div>
            </button>
        </div>
    </div>

    <!-- ====== ROW 3: TOOLBAR (SEARCH & FILTERS) ====== -->
    <div class="px-8 py-4 flex items-center justify-between border-b border-border/10 bg-white">
        <div class="flex items-center gap-4 flex-1">
            <div
                class="flex items-center border border-border/40 rounded-sm bg-white h-9 px-3 gap-2 group focus-within:ring-2 focus-within:ring-accent/10 transition-all">
                <Grid class="h-3.5 w-3.5 text-muted-foreground/30 group-hover:text-accent transition-colors" />
                <span class="text-[12px] font-bold text-muted-foreground/60">View</span>
                <ChevronDown class="h-3 w-3 text-muted-foreground/30" />
            </div>

            <div class="h-9 w-px bg-border/20 mx-1"></div>

            <div class="flex items-center gap-2">
                <button
                    class="h-9 px-3 flex items-center gap-2 border border-border/40 rounded-sm bg-white text-[12px] font-bold text-muted-foreground hover:bg-secondary/50 transition-all">
                    <Filter class="h-3.5 w-3.5 text-muted-foreground/30" />
                    All
                    <ChevronDown class="h-3 w-3 text-muted-foreground/30" />
                </button>

                <div class="relative group">
                    <Search
                        class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground/30 transition-colors group-focus-within:text-accent" />
                    <Input v-model="internalSearch" :placeholder="searchPlaceholder"
                        class="pl-9 h-9 rounded-sm w-[240px] border-border/40 bg-white text-[12px] font-medium shadow-none focus:ring-accent/10 transition-all" />
                </div>
            </div>
        </div>

        <!-- <div class="flex items-center gap-3">
            <slot name="toolbar-actions"></slot>
            <div class="flex items-center bg-secondary/30 p-1 rounded-sm gap-1 border border-border/10">
                <button class="p-1.5 rounded-md bg-white shadow-sm text-accent">
                    <Grid class="h-3.5 w-3.5" />
                </button>
                <button class="p-1.5 rounded-md text-muted-foreground/40 hover:text-foreground transition-all">
                    <List class="h-3.5 w-3.5" />
                </button>
            </div>
        </div> -->
    </div>

    <!-- ====== TABLE CONTAINER ====== -->
    <div class="overflow-hidden">
        <Table class="border-collapse">
            <TableHeader class="bg-[#F9FAFB] border-b border-border/10 no-hover">
                <TableRow class="hover:bg-transparent border-none">
                    <!-- Selection Checkbox -->
                    <TableHead v-if="showSelection" class="h-10 w-[60px] pl-8">
                        <Checkbox />
                    </TableHead>

                    <!-- Expand Toggle Column -->
                    <TableHead v-if="expandable" class="h-10 w-[40px]"></TableHead>

                    <!-- Dynamic Columns -->
                    <TableHead v-for="col in columns" :key="col.key" :class="[
                        'h-10 text-[10px] font-bold uppercase tracking-[0.1em] text-muted-foreground/40 px-4',
                        col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                        col.class
                    ]" :style="{ width: col.width }">
                        <div class="flex items-center gap-1.5 text-black/60"
                            :class="col.align === 'center' ? 'justify-center' : col.align === 'right' ? 'justify-end' : 'justify-start'">
                            {{ col.label }}
                            <ArrowUpDown v-if="col.sortable !== false" class="h-2.5 w-2.5 opacity-30" />
                        </div>
                    </TableHead>

                    <!-- Actions Column Slot -->
                    <TableHead v-if="$slots.actions" class="h-10 w-[80px] px-8 text-right"></TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-for="row in data.data" :key="row[rowIdKey]">
                    <TableRow :class="[
                        'group transition-all duration-200 border-border/10 last:border-0 hover:bg-accent/[0.02]',
                        expandedRows.has(row[rowIdKey]) ? 'bg-accent/[0.02] border-b-0' : ''
                    ]" @click="emit('rowClick', row)">
                        <!-- Selection Checkbox -->
                        <TableCell v-if="showSelection" class="pl-8 py-4 align-middle">
                            <Checkbox @click.stop />
                        </TableCell>

                        <!-- Expand Toggle -->
                        <TableCell v-if="expandable" class="p-0 pl-1 w-[40px] align-middle">
                            <button @click.stop="toggleRow(row[rowIdKey])"
                                class="h-6 w-6 rounded-md flex items-center justify-center transition-all hover:bg-white hover:shadow-sm">
                                <ChevronRight :class="[
                                    'h-3.5 w-3.5 text-muted-foreground/30 transition-transform duration-300',
                                    expandedRows.has(row[rowIdKey]) ? 'rotate-90 text-accent' : ''
                                ]" />
                            </button>
                        </TableCell>

                        <!-- Cells -->
                        <TableCell v-for="col in columns" :key="col.key" :class="[
                            'py-4 px-4 align-middle',
                            col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                            col.class
                        ]">
                            <slot :name="`cell(${col.key})`" :row="row" :column="col">
                                <span class="text-[13px] font-bold text-foreground">{{ row[col.key] }}</span>
                            </slot>
                        </TableCell>

                        <!-- Actions -->
                        <TableCell v-if="$slots.actions" class="px-8 py-4 text-right align-middle">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex justify-end">
                                <slot name="actions" :row="row"></slot>
                            </div>
                        </TableCell>
                    </TableRow>

                    <!-- Expandable Content -->
                    <TableRow v-if="expandable && expandedRows.has(row[rowIdKey])"
                        class="hover:bg-transparent bg-accent/[0.01] border-border/10">
                        <TableCell
                            :colspan="columns.length + (expandable ? 1 : 0) + ($slots.actions ? 1 : 0) + (showSelection ? 1 : 0)"
                            class="p-0 border-b border-border/10">
                            <div class="px-20 py-8 animate-in slide-in-from-top-2 duration-300">
                                <div class="relative pl-8 border-l border-border/40 space-y-8">
                                    <slot name="expanded" :row="row"></slot>
                                </div>
                            </div>
                        </TableCell>
                    </TableRow>
                </template>

                <!-- Empty State -->
                <TableRow v-if="data.data.length === 0">
                    <TableCell
                        :colspan="columns.length + (expandable ? 1 : 0) + ($slots.actions ? 1 : 0) + (showSelection ? 1 : 0)"
                        class="px-10 py-24 text-center">
                        <slot name="empty">
                            <div class="flex flex-col items-center gap-3 opacity-20">
                                <div class="h-14 w-14 rounded-full bg-muted flex items-center justify-center">
                                    <Grid class="h-6 w-6" />
                                </div>
                                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-muted-foreground mt-2">
                                    Data tidak
                                    ditemukan</h3>
                            </div>
                        </slot>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>
    </div>

    <!-- ====== PAGINATION ====== -->
    <div class="px-8 py-6 border-t border-border/10 bg-[#FAFAFB]">
        <DataTablePagination :paginator="data" v-model:perPage="internalPerPage" />
    </div>
</div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
