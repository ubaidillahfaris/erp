<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { 
    Plus, Trash2, ShieldCheck,
    Clock, CheckCircle2, XCircle
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import Combobox from '@/components/ui/combobox/Combobox.vue';
import { Pagination, type BreadcrumbItem, type TableColumn } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { storeOverride, destroyOverride, bulkDestroyOverride } from '@/actions/App/Http/Controllers/Admin/TenantManagerController';

const props = defineProps<{
    company: any;
    overrides: Pagination<any>;
    availableFeatures: Record<string, any[]>;
    filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Tenant Manager', href: '/admin/system/tenants' },
    { title: `Add-ons: ${props.company.name}`, href: '#' },
];

const form = useForm({
    feature_key: '',
    is_enabled: true,
    expires_at: '',
});

// Flatten features for Combobox with Grouping
const featureOptions = computed(() => {
    const options: { value: string; label: string; group: string }[] = [];
    Object.entries(props.availableFeatures).forEach(([moduleName, features]) => {
        features.forEach(feature => {
            options.push({
                value: feature.feature_key,
                label: `${feature.name} (${feature.feature_key})`,
                group: moduleName
            });
        });
    });
    return options;
});

const submit = () => {
    form.post(storeOverride({ company: props.company.id }).url, {
        onSuccess: () => {
            form.reset('feature_key', 'expires_at');
        }
    });
};

const deleteOverride = (id: number) => {
    if (confirm('Remove this override? The feature will revert to tier defaults.')) {
        router.delete(destroyOverride({ company: props.company.id, override: id }).url);
    }
};

const bulkDeleteOverrides = (ids: (string | number)[]) => {
    if (confirm(`Remove ${ids.length} selected overrides?`)) {
        router.delete(bulkDestroyOverride({ company: props.company.id }).url, {
            data: { ids },
        });
    }
};

const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Lifetime';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};

const isExpired = (dateString: string | null) => {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
};

const columns: TableColumn[] = [
    {
        key: 'feature_name',
        label: 'FEATURE / MENU',
        align: 'left',
    },
    {
        key: 'status',
        label: 'STATUS',
        align: 'center',
        width: '120px',
    },
    {
        key: 'expiry',
        label: 'EXPIRATION',
        align: 'left',
        width: '180px',
    },
    {
        key: 'actions',
        label: '',
        align: 'right',
        width: '80px',
    }
];
</script>

<template>
<Head :title="`Add-ons - ${company.name}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-8 font-sans">
        
        <PageHeader 
            title="Tenant Add-ons" 
            :description="`Override tier limits or provision specific modules for ${company.name}.`"
            back-href="/admin/system/tenants"
        >
            <template #actions>
                <Badge variant="outline" class="h-7 px-3 text-[10px] font-semibold border-slate-200 text-slate-500 uppercase tracking-wider bg-white shadow-sm">
                    Tenant: {{ company.name }}
                </Badge>
            </template>
        </PageHeader>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 max-w-7xl mx-auto w-full">
            <!-- LEFT: ADD OVERRIDE -->
            <div class="lg:col-span-1 flex flex-col gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h2 class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                            <Plus class="h-3.5 w-3.5" />
                            Provision Add-on
                        </h2>
                    </div>
                    <div class="p-6 flex flex-col gap-5">
                        <div class="grid gap-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 ml-1">Search Menu / Feature</Label>
                            
                            <Combobox 
                                v-model="form.feature_key"
                                :options="featureOptions"
                                placeholder="Search a menu or module..."
                                search-placeholder="Type to search..."
                                class="h-11"
                            />
                            
                            <div v-if="form.errors.feature_key" class="text-xs text-destructive font-medium">{{ form.errors.feature_key }}</div>
                        </div>

                        <div class="grid gap-2">
                            <Label class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 ml-1">Expiration (Optional)</Label>
                            <div class="relative">
                                <Input type="date" v-model="form.expires_at" class="h-11 border-slate-200 rounded-xl font-medium text-[13px] shadow-none bg-white" />
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="space-y-0.5">
                                <Label class="text-[11px] font-semibold text-slate-700 uppercase tracking-wide">Override Value</Label>
                                <p class="text-[10px] text-slate-400 font-medium">{{ form.is_enabled ? 'Force Grant Access' : 'Force Deny Access' }}</p>
                            </div>
                            <Switch 
                                :checked="form.is_enabled" 
                                @update:checked="form.is_enabled = $event" 
                                class="data-[state=checked]:bg-emerald-500 shadow-sm"
                            />
                        </div>

                        <Button @click="submit" class="w-full bg-accent hover:bg-accent/90 text-white font-semibold uppercase tracking-wider text-[11px] h-12 rounded-xl shadow-lg shadow-accent/20 mt-2" :disabled="form.processing">
                            Apply Add-on
                        </Button>
                    </div>
                </div>
            </div>

            <!-- RIGHT: ACTIVE OVERRIDES -->
            <div class="lg:col-span-2">
                <DataTable 
                    :data="overrides"
                    :columns="columns"
                    :per-page="filters.per_page"
                    show-selection
                    row-id-key="id"
                    @bulk-delete="bulkDeleteOverrides"
                >
                    <template #cell-feature_name="{ row }">
                        <div class="flex items-center gap-3">
                            <div :class="[
                                'h-8 w-8 rounded-lg flex items-center justify-center border',
                                row.original.is_enabled ? 'bg-emerald-50 border-emerald-100 text-emerald-600' : 'bg-rose-50 border-rose-100 text-rose-600'
                            ]">
                                <CheckCircle2 v-if="row.original.is_enabled" class="h-4 w-4" />
                                <XCircle v-else class="h-4 w-4" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-slate-900 tracking-tight">{{ row.original.feature_name }}</span>
                                <span class="text-[9px] font-mono font-medium text-slate-400 uppercase tracking-tighter">{{ row.original.feature_key }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <Badge :variant="row.original.is_enabled ? 'default' : 'destructive'" class="h-6 text-[10px] font-semibold tracking-wider px-2 shadow-none uppercase">
                            {{ row.original.is_enabled ? 'ENABLED' : 'DISABLED' }}
                        </Badge>
                    </template>

                    <template #cell-expiry="{ row }">
                        <div class="flex items-center gap-1.5">
                            <Clock class="h-3 w-3 text-slate-300" />
                            <span :class="[
                                'text-[11px] font-medium uppercase tracking-wider',
                                isExpired(row.original.expires_at) ? 'text-rose-500' : 'text-slate-500'
                            ]">
                                {{ isExpired(row.original.expires_at) ? 'Expired' : (row.original.expires_at ? formatDate(row.original.expires_at) : 'Permanent') }}
                            </span>
                        </div>
                    </template>

                    <template #cell-actions="{ row }">
                        <Button variant="ghost" size="icon" @click="deleteOverride(row.original.id)" class="h-8 w-8 text-slate-300 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </template>

                    <template #empty>
                        <div class="flex flex-col items-center justify-center py-20 text-center opacity-30">
                            <ShieldCheck class="h-12 w-12 text-slate-400 mb-4" />
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">No Custom Overrides Found</p>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </div>
</AppLayout>
</template>
