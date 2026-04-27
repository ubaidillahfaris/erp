<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    History, Search, User as UserIcon, 
    Clock, Database, Globe, Filter, X,
    Plus, Edit, Trash2, ArrowRight, Eye
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    logs: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    events: string[];
    users: any[];
    filters: {
        search?: string;
        event?: string;
        user_id?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'System', href: '#' },
    { title: 'Audit Log', href: '/system/audit-log' },
];

const search = ref(props.filters.search || '');
const event = ref(props.filters.event || 'all');
const user_id = ref(props.filters.user_id || 'all');
const perPage = ref(props.filters.per_page || String(props.logs.per_page));

const columns = [
    { key: 'timestamp', label: 'Timestamp' },
    { key: 'user', label: 'User' },
    { key: 'event_badge', label: 'Event', align: 'center' },
    { key: 'model', label: 'Model' },
    { key: 'record_id', label: 'Record ID' },
    { key: 'ip', label: 'IP Address', align: 'right' },
] as const;

watch([search, event, user_id, perPage], debounce(() => {
    router.get('/system/audit-log', {
        search: search.value || undefined,
        event: event.value === 'all' ? undefined : event.value,
        user_id: user_id.value === 'all' ? undefined : user_id.value,
        per_page: perPage.value
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const getModelName = (type: string) => {
    if (!type) return '--';
    const parts = type.split('\\');
    return parts[parts.length - 1];
};

const getEventStyles = (event: string) => {
    switch (event) {
        case 'created':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'updated':
            return 'bg-blue-50 text-blue-600 border-blue-100';
        case 'deleted':
            return 'bg-rose-50 text-rose-600 border-rose-100';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-100';
    }
};

const getEventIcon = (event: string) => {
    switch (event) {
        case 'created': return Plus;
        case 'updated': return Edit;
        case 'deleted': return Trash2;
        default: return History;
    }
};

const getModelRoute = (type: string, id: number | string) => {
    const model = getModelName(type).toLowerCase();
    switch (model) {
        case 'sale': return `/sales/${id}`;
        case 'product': return `/product/${id}`;
        case 'production': return `/production/${id}`;
        case 'purchase': return `/purchasing/${id}`;
        case 'restock': return `/restock/${id}`;
        case 'vendor': return `/vendors/${id}`;
        case 'customer': return `/customers/${id}`;
        case 'bom': return `/bom/${id}`;
        default: return null;
    }
};

const resetFilters = () => {
    search.value = '';
    event.value = 'all';
    user_id.value = 'all';
};

const hasActiveFilters = computed(() => {
    return search.value || event.value !== 'all' || user_id.value !== 'all';
});
</script>

<template>
<Head title="Audit Log" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader 
        title="System Audit Log" 
        description="Pantau seluruh aktivitas perubahan data di dalam sistem" 
        back-href="/dashboard"
        :count="logs.total"
    />

    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="logs" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari audit log..." 
            toolbar-title="Activity Stream" 
            :title="'Audit Log'"
            :total-count="logs.total"
            expandable
            :showSelection="false"
        >
            <template #toolbar-prefix>
                <div class="flex items-center gap-3">
                    <Select v-model="event">
                        <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="Event" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Event</SelectItem>
                            <SelectItem v-for="e in events" :key="e" :value="e">{{ e.toUpperCase() }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="user_id">
                        <SelectTrigger class="h-9 w-[180px] text-xs font-medium bg-white rounded-lg border-slate-200">
                            <SelectValue placeholder="User" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua User</SelectItem>
                            <SelectItem v-for="u in users" :key="u.id" :value="String(u.id)">{{ u.name }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(timestamp)="{ row }">
                <div class="flex items-center gap-2">
                    <Clock class="h-3.5 w-3.5 text-muted-foreground/60" />
                    <span class="text-xs font-medium text-slate-500 tabular-nums">{{ formatDate(row.created_at) }}</span>
                </div>
            </template>

            <template #cell(user)="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200/50">
                        <UserIcon class="h-3 w-3 text-slate-400" />
                    </div>
                    <span class="text-xs font-bold text-foreground/80 leading-none">{{ row.user?.name || 'System' }}</span>
                </div>
            </template>

            <template #cell(event_badge)="{ row }">
                <Badge :class="getEventStyles(row.event)" class="text-[10px] uppercase font-bold px-1.5 h-5">
                    <component :is="getEventIcon(row.event)" class="h-2.5 w-2.5 mr-1" />
                    {{ row.event }}
                </Badge>
            </template>

            <template #cell(model)="{ row }">
                <div class="flex items-center gap-2">
                    <Database class="h-3.5 w-3.5 text-muted-foreground/40" />
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-tighter">{{ getModelName(row.auditable_type) }}</span>
                </div>
            </template>

            <template #cell(record_id)="{ row }">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono font-bold text-muted-foreground">#{{ row.auditable_id }}</span>
                    <a v-if="getModelRoute(row.auditable_type, row.auditable_id)" :href="getModelRoute(row.auditable_type, row.auditable_id)" class="text-accent hover:underline">
                        <Eye class="h-3 w-3" />
                    </a>
                </div>
            </template>

            <template #cell(ip)="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <Globe class="h-3 w-3 text-muted-foreground/40" />
                    <span class="text-[10px] font-mono text-slate-400">{{ row.ip_address || '0.0.0.0' }}</span>
                </div>
            </template>

            <template #expanded="{ row }">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Changes Table -->
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Data Changes</h4>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-1.5">
                                    <div class="h-2 w-2 rounded-full bg-rose-500"></div>
                                    <span class="text-[9px] font-bold uppercase text-muted-foreground">Old</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                                    <span class="text-[9px] font-bold uppercase text-muted-foreground">New</span>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-xl overflow-hidden bg-white shadow-sm">
                            <table class="w-full text-xs">
                                <thead class="bg-slate-50 border-b">
                                    <tr>
                                        <th class="px-4 py-2 text-left font-bold text-slate-400 uppercase tracking-tighter w-1/4">Field</th>
                                        <th class="px-4 py-2 text-left font-bold text-slate-400 uppercase tracking-tighter">Difference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-if="row.event === 'updated'">
                                        <tr v-for="(newVal, key) in row.new_values" :key="key" class="border-b last:border-0">
                                            <td class="px-4 py-3 font-mono font-bold text-slate-600 bg-slate-50/30">{{ key }}</td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-col gap-2">
                                                    <div v-if="row.old_values[key] !== undefined" class="flex items-center gap-2">
                                                        <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 line-through opacity-70">{{ row.old_values[key] }}</span>
                                                        <ArrowRight class="h-3 w-3 text-slate-300" />
                                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600 font-bold">{{ newVal }}</span>
                                                    </div>
                                                    <div v-else class="flex items-center gap-2">
                                                        <span class="px-1.5 py-0.5 rounded bg-slate-50 text-slate-400 italic">No previous value</span>
                                                        <ArrowRight class="h-3 w-3 text-slate-300" />
                                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600 font-bold">{{ newVal }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="row.event === 'created'">
                                        <tr v-for="(val, key) in row.new_values" :key="key" class="border-b last:border-0">
                                            <td class="px-4 py-3 font-mono font-bold text-slate-600 bg-slate-50/30">{{ key }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-600 font-bold">{{ val }}</span>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else-if="row.event === 'deleted'">
                                        <tr v-for="(val, key) in row.old_values" :key="key" class="border-b last:border-0">
                                            <td class="px-4 py-3 font-mono font-bold text-slate-600 bg-slate-50/30">{{ key }}</td>
                                            <td class="px-4 py-3">
                                                <span class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 line-through">{{ val }}</span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Meta Info -->
                    <div class="flex flex-col gap-4">
                        <h4 class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">Request Metadata</h4>
                        <div class="grid grid-cols-1 gap-3">
                            <div class="p-4 rounded-xl border bg-white shadow-sm space-y-3">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">URL Target</span>
                                    <span class="text-xs font-mono text-slate-600 break-all">{{ row.url }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-[10px] font-bold uppercase text-slate-400">User Agent</span>
                                    <span class="text-[11px] text-slate-500 leading-relaxed">{{ row.user_agent }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                    <History class="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">No activities recorded</p>
                        <p class="text-xs text-muted-foreground mt-1">Audit logs will appear here as users interact with the system</p>
                    </div>
                </div>
            </template>
        </DataTable>
    </div>
</div>
</template>
