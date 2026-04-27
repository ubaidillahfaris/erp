<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus, ArrowRightLeft, Eye,
    Calendar, Building2, BadgeCheck, Clock, XCircle, ArrowRight, MoreHorizontal,
    Truck, CheckCircle2
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { create as createRoute, show as showRoute, index as indexRoute } from '@/routes/stock-transfers';
import type { BreadcrumbItem, Pagination } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    transfers: Pagination<any>;
    filters: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Persediaan', href: '/stock' },
    { title: 'Transfer Stok', href: indexRoute().url },
];

const search = ref(props.filters.search || '');

const columns = [
    { key: 'transfer_number', label: 'Nomor Transfer' },
    { key: 'route', label: 'Rute Gudang' },
    { key: 'date', label: 'Tanggal' },
    { key: 'status', label: 'Status', align: 'center' },
] as const;

watch(search, debounce((value) => {
    router.get(indexRoute().url, { search: value }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
}, 300));

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'draft':
            return { class: 'bg-slate-50 text-slate-500 border-slate-100', icon: Clock, label: 'Draft' };
        case 'in_transit':
            return { class: 'bg-blue-50 text-blue-600 border-blue-100', icon: Truck, label: 'In Transit' };
        case 'completed':
            return { class: 'bg-emerald-50 text-emerald-600 border-emerald-100', icon: CheckCircle2, label: 'Selesai' };
        case 'cancelled':
            return { class: 'bg-rose-50 text-rose-600 border-rose-100', icon: XCircle, label: 'Batal' };
        default:
            return { class: 'bg-slate-50 text-slate-500 border-slate-100', icon: Clock, label: status };
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Transfer Stok" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <PageHeader title="Stock Transfer" description="Riwayat perpindahan stok antar gudang"
            back-href="/stock" :count="transfers.total" />

        <div class="w-full max-w-7xl mx-auto">
            <DataTable
                :data="transfers"
                :columns="columns"
                v-model:search="search"
                search-placeholder="Cari nomor transfer..."
                title="Riwayat Transfer"
                :total-count="transfers.total"
            >
                <template #header-actions>
                    <Link :href="createRoute.url()">
                        <Button class="h-9 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white shadow-lg shadow-accent/20">
                            <Plus class="h-4 w-4 mr-2" /> Transfer Baru
                        </Button>
                    </Link>
                </template>

                <template #cell(transfer_number)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-accent group-hover:text-white transition-all duration-300">
                            <ArrowRightLeft class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <p class="text-sm font-bold text-foreground">{{ row.transfer_number }}</p>
                            <p class="text-[10px] text-muted-foreground uppercase tracking-widest font-bold">Ref: #{{ row.id }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(route)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col min-w-[100px]">
                            <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-tighter leading-none mb-1">Dari</span>
                            <span class="text-xs font-bold text-slate-700">{{ row.from_warehouse?.name }}</span>
                        </div>
                        <ArrowRight class="h-3.5 w-3.5 text-slate-300" />
                        <div class="flex flex-col min-w-[100px]">
                            <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-tighter leading-none mb-1">Ke</span>
                            <span class="text-xs font-bold text-slate-700">{{ row.to_warehouse?.name }}</span>
                        </div>
                    </div>
                </template>

                <template #cell(date)="{ row }">
                    <div class="flex items-center gap-2 text-slate-500">
                        <Calendar class="h-3.5 w-3.5 opacity-50" />
                        <span class="text-xs font-medium tabular-nums">{{ formatDate(row.created_at) }}</span>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge :class="getStatusBadge(row.status).class" class="text-[10px] uppercase font-bold px-2 h-6">
                        <component :is="getStatusBadge(row.status).icon" class="h-3 w-3 mr-1.5" />
                        {{ getStatusBadge(row.status).label }}
                    </Badge>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="router.visit(showRoute.url(row.id))" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Eye class="h-3.5 w-3.5 text-slate-400" /> Detail Transfer
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
