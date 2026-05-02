<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Plus, Search, Edit2, Trash2, Package,
    MoreHorizontal, ShieldCheck, Box,
    Settings, LayoutGrid, ArrowLeft, Pencil
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { useConfirm } from '@/composables/useConfirm';
import { index, destroy, toggle } from '@/actions/App/Http/Controllers/Admin/ModuleManagerController';
import ModuleModal from './ModuleModal.vue';

const props = defineProps<{
    modules: {
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
    { title: 'System Management', href: '/admin/system' },
    { title: 'Module Registry', href: '/admin/system/modules' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.modules.per_page));

const columns = [
    { key: 'module_name', label: 'Module Identity', sortable: false },
    { key: 'slug', label: 'Slug / Registry Key', sortable: false },
    { key: 'version_info', label: 'Version', sortable: false, width: '120px' },
    { key: 'priority', label: 'Order', sortable: false, width: '100px', align: 'center' },
    { key: 'status', label: 'Status', sortable: false, width: '100px', align: 'center' },
] as const;

watch([search, perPage], debounce(() => {
    router.get(index().url, {
        search: search.value || undefined,
        per_page: perPage.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();
const isModalOpen = ref(false);
const selectedModule = ref(null);

const openCreateModal = () => {
    selectedModule.value = null;
    isModalOpen.value = true;
};

const openEditModal = (module: any) => {
    selectedModule.value = module;
    isModalOpen.value = true;
};

const toggleModule = (module: any) => {
    if (module.slug === 'platform') return;
    router.post(toggle({ module: module.id }).url, {}, {
        preserveScroll: true,
    });
};

const deleteModule = async (module: any) => {
    if (module.slug === 'platform') return;
    
    if (await confirmDialog('Delete Module?', `Are you sure you want to delete module "${module.name}"? This action cannot be undone.`)) {
        router.delete(destroy({ module: module.id }).url);
    }
};

const getVersionVariant = (version: string) => {
    if (!version) return 'bg-slate-50 text-slate-500 border-slate-200';
    const parts = version.split('.');
    if (parts[2] !== '0') return 'bg-emerald-50 text-emerald-700 border-emerald-100'; // Patch
    if (parts[1] !== '0') return 'bg-blue-50 text-blue-700 border-blue-100';          // Minor
    return 'bg-amber-50 text-amber-700 border-amber-100';                         // Major
};
</script>

<template>
<Head title="Module Registry" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <!-- HEADER -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/admin/system">
                    <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Module Registry</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Global configuration for application modules and platform core features.</p>
                </div>
            </div>
            
            <Button @click="openCreateModal" class="h-9 px-4 bg-accent hover:bg-accent/90 text-white font-bold">
                <Plus class="h-4 w-4 mr-2" />
                Register Module
            </Button>
        </div>

        <!-- TABLE AREA -->
        <div class="w-full">
            <DataTable 
                :data="modules" 
                :columns="columns" 
                v-model:search="search" 
                v-model:perPage="perPage"
                search-placeholder="Search modules..." 
                toolbar-title="Global Registry" 
                :title="'Module List'"
                :total-count="modules.total"
            >
                <!-- Cell: Module Identity -->
                <template #cell(module_name)="{ row }">
                    <div class="flex items-center gap-3">
                        <div :class="[
                            'h-9 w-9 shrink-0 rounded-lg flex items-center justify-center transition-colors group-hover:bg-accent group-hover:text-white',
                            row.slug === 'platform' ? 'bg-primary/10 text-primary' : 'bg-secondary/50 text-muted-foreground'
                        ]">
                            <ShieldCheck v-if="row.slug === 'platform'" class="h-4 w-4" />
                            <Package v-else class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-bold text-foreground truncate max-w-[200px] leading-none">{{ row.name }}</p>
                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1.5 opacity-70">
                                {{ row.slug === 'platform' ? 'System Core' : 'Application Module' }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Cell: Slug -->
                <template #cell(slug)="{ row }">
                    <span class="text-[11px] font-mono font-bold text-foreground/80 tracking-tight bg-slate-100 px-1.5 py-0.5 rounded w-fit italic">
                        #{{ row.slug }}
                    </span>
                </template>

                <!-- Cell: Version -->
                <template #cell(version_info)="{ row }">
                    <Badge :class="['text-[10px] uppercase font-black tracking-widest px-1.5 h-4 rounded-sm border shadow-none', getVersionVariant(row.version)]">
                        v{{ row.version || '1.0.0' }}
                    </Badge>
                </template>

                <!-- Cell: Priority -->
                <template #cell(priority)="{ row }">
                    <span class="text-[12px] font-mono font-bold text-slate-400">#{{ row.order_priority }}</span>
                </template>

                <!-- Cell: Status -->
                <template #cell(status)="{ row }">
                    <div class="flex items-center justify-center gap-2">
                        <Switch 
                            :checked="row.is_active" 
                            @update:checked="toggleModule(row)"
                            :disabled="row.slug === 'platform'"
                        />
                    </div>
                </template>

                <!-- Actions -->
                <template #actions="{ row }">
                    <div class="flex items-center justify-end">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="openEditModal(row)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Pencil class="h-3.5 w-3.5" /> Edit Module
                                </DropdownMenuItem>
                                
                                <DropdownMenuItem 
                                    @click="deleteModule(row)"
                                    :disabled="row.slug === 'platform'"
                                    class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <Trash2 class="h-3.5 w-3.5" /> 
                                    {{ row.slug === 'platform' ? 'System Locked' : 'Delete Module' }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <!-- Empty State -->
                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                        <LayoutGrid class="h-12 w-12 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Registry Empty</p>
                            <p class="text-xs text-muted-foreground mt-1">Register a new module to begin.</p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>

    <!-- MODAL -->
    <ModuleModal 
        v-model:open="isModalOpen" 
        :module="selectedModule" 
    />
</AppLayout>
</template>
