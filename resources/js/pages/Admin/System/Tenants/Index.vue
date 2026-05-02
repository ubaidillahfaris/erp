<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Users, Search, Edit2, Trash2, Building2,
    MoreHorizontal, ShieldCheck, Crown, Star,
    LayoutGrid, ArrowLeft, Pencil, ChevronRight,
    Puzzle
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index, updateTier } from '@/actions/App/Http/Controllers/Admin/TenantManagerController';

const props = defineProps<{
    tenants: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    tiers: any[];
    filters: {
        search?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'System Management', href: '/admin/system' },
    { title: 'Tenant Manager', href: '/admin/system/tenants' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.tenants.per_page));

const columns = [
    { key: 'tenant_info', label: 'Tenant Identity', sortable: false },
    { key: 'business', label: 'Business Type', sortable: false },
    { key: 'tier_selection', label: 'Active Plan / Tier', sortable: false, width: '200px' },
    { key: 'created', label: 'Registered At', sortable: false, width: '150px' },
] as const;

watch([search, perPage], debounce(() => {
    router.get(index().url, {
        search: search.value || undefined,
        per_page: perPage.value,
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const changeTier = (companyId: number, tierId: string) => {
    router.put(updateTier({ company: companyId }).url, {
        tier_id: tierId,
    }, {
        preserveScroll: true,
    });
};

const getTierIcon = (slug: string) => {
    switch (slug) {
        case 'enterprise': return Crown;
        case 'pro': return Star;
        default: return Building2;
    }
};

const getTierColor = (slug: string) => {
    switch (slug) {
        case 'enterprise': return 'text-amber-500 bg-amber-50 border-amber-100';
        case 'pro': return 'text-blue-500 bg-blue-50 border-blue-100';
        default: return 'text-slate-500 bg-slate-50 border-slate-100';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
<Head title="Tenant Manager" />

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
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Tenant Manager</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Manage multi-tenant company accounts, tiers, and granular feature overrides.</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <Badge variant="outline" class="h-9 px-4 border-slate-200 bg-white font-bold text-slate-500 uppercase tracking-widest text-[10px]">
                    Total Tenants: {{ tenants.total }}
                </Badge>
            </div>
        </div>

        <!-- TABLE AREA -->
        <div class="w-full">
            <DataTable 
                :data="tenants" 
                :columns="columns" 
                v-model:search="search" 
                v-model:perPage="perPage"
                search-placeholder="Search tenants by name or type..." 
                toolbar-title="Tenant Ecosystem" 
                :title="'Active Companies'"
                :total-count="tenants.total"
            >
                <!-- Cell: Tenant Identity -->
                <template #cell(tenant_info)="{ row }">
                    <div class="flex items-center gap-3">
                        <div :class="[
                            'h-10 w-10 shrink-0 rounded-xl flex items-center justify-center transition-all group-hover:scale-105',
                            getTierColor(row.tier?.slug)
                        ]">
                            <component :is="getTierIcon(row.tier?.slug)" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[14px] font-black text-foreground truncate max-w-[250px] leading-tight">{{ row.name }}</p>
                            <p class="text-[10px] font-bold text-muted-foreground uppercase tracking-[0.2em] mt-1 opacity-60">
                                ID: #TEN-{{ String(row.id).padStart(4, '0') }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Cell: Business -->
                <template #cell(business)="{ row }">
                    <div class="flex flex-col">
                        <span class="text-[13px] font-bold text-slate-600 capitalize">{{ row.business_type || 'General' }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Domain Industry</span>
                    </div>
                </template>

                <!-- Cell: Tier Selection -->
                <template #cell(tier_selection)="{ row }">
                    <Select :model-value="String(row.tier_id)" @update:model-value="(val) => changeTier(row.id, val as string)">
                        <SelectTrigger class="h-9 border-slate-200 bg-white rounded-lg text-xs font-bold shadow-none focus:ring-accent/5">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent class="font-sans">
                            <SelectItem v-for="tier in tiers" :key="tier.id" :value="String(tier.id)">
                                <div class="flex items-center gap-2">
                                    <div :class="['w-1.5 h-1.5 rounded-full', tier.slug === 'enterprise' ? 'bg-amber-500' : (tier.slug === 'pro' ? 'bg-blue-500' : 'bg-slate-400')]"></div>
                                    <span class="font-bold uppercase tracking-wider text-[10px]">{{ tier.name }}</span>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </template>

                <!-- Cell: Created -->
                <template #cell(created)="{ row }">
                    <div class="flex flex-col items-end">
                        <span class="text-[12px] font-mono font-bold text-slate-500 tracking-tighter">{{ formatDate(row.created_at) }}</span>
                        <span class="text-[10px] font-bold text-slate-300 uppercase">Onboarded</span>
                    </div>
                </template>

                <!-- Actions -->
                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <Link :href="`/admin/system/tenants/${row.id}/overrides`">
                            <Button variant="outline" size="sm" class="h-8 gap-2 border-slate-200 hover:bg-accent hover:text-white transition-all group/btn">
                                <Puzzle class="h-3.5 w-3.5 transition-transform group-hover/btn:rotate-12" />
                                <span class="text-[11px] font-bold uppercase tracking-wider">Add-ons</span>
                            </Button>
                        </Link>
                        
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="router.visit(`/admin/system/tenants/${row.id}/overrides`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Puzzle class="h-3.5 w-3.5" /> Manage Overrides
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="router.visit(`/companies/${row.id}/edit`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Building2 class="h-3.5 w-3.5" /> View Profile
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <!-- Empty State -->
                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                        <Building2 class="h-12 w-12 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">No Tenants Found</p>
                            <p class="text-xs text-muted-foreground mt-1">Tenant database is currently empty.</p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</AppLayout>
</template>
