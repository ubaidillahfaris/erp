<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Search, Plus, FileText, Lock, Unlock, 
    Filter, X, Trash2, Edit2, LayoutGrid,
    ArrowRight
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

// Persistent Layout
defineOptions({ layout: AppLayout });

const props = defineProps<{
    accounts: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        type?: string;
        is_active?: string;
        per_page?: string;
        sort?: string;
        direction?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Accounting', href: '#' },
    { title: 'Chart of Accounts', href: '/accounting/accounts' },
];

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || 'all');
const is_active = ref(props.filters.is_active || 'all');
const perPage = ref(props.filters.per_page || String(props.accounts.per_page));
const sort = ref(props.filters.sort || 'code');
const direction = ref(props.filters.direction || 'asc');

const columns = [
    { key: 'code', label: 'Code', sortable: true, width: '120px' },
    { key: 'name', label: 'Account Name', sortable: true },
    { key: 'type_badge', label: 'Type', sortable: false, width: '150px' },
    { key: 'balance', label: 'Balance Type', sortable: false, width: '150px' },
    { key: 'history', label: 'Journal Items', sortable: false, width: '130px', align: 'center' },
    { key: 'status', label: 'Status', sortable: false, width: '100px', align: 'center' },
];

watch([search, type, is_active, perPage, sort, direction], debounce(() => {
    router.get('/accounts', {
        search: search.value || undefined,
        type: type.value === 'all' ? undefined : type.value,
        is_active: is_active.value === 'all' ? undefined : is_active.value,
        per_page: perPage.value,
        sort: sort.value || undefined,
        direction: sort.value ? (direction.value || 'asc') : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const resetFilters = () => {
    search.value = '';
    type.value = 'all';
    is_active.value = 'all';
};

const hasActiveFilters = computed(() => {
    return search.value || type.value !== 'all' || is_active.value !== 'all';
});

const getTypeVariant = (type: string) => {
    switch (type) {
        case 'asset': return 'bg-blue-50 text-blue-700 border-blue-100 hover:bg-blue-50';
        case 'liability': return 'bg-rose-50 text-rose-700 border-rose-100 hover:bg-rose-50';
        case 'equity': return 'bg-purple-50 text-purple-700 border-purple-100 hover:bg-purple-50';
        case 'income': return 'bg-emerald-50 text-emerald-700 border-emerald-100 hover:bg-emerald-50';
        case 'expense': return 'bg-amber-50 text-amber-700 border-amber-100 hover:bg-amber-50';
        default: return 'bg-slate-50 text-slate-700 border-slate-100 hover:bg-slate-50';
    }
};

const deleteAccount = (id: number, hasHistory: boolean) => {
    if (hasHistory) {
        alert('Tidak dapat menghapus akun yang memiliki riwayat jurnal.');
        return;
    }

    if (confirm('Deaktivasi akun ini? (Gunakan ?force=true di API untuk hapus permanen jika diijinkan)')) {
        router.delete(`/accounts/${id}`, {
            onSuccess: () => {}
        });
    }
};
</script>

<template>
<Head title="Chart of Accounts" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

    <PageHeader 
        title="Chart of Accounts" 
        description="Kelola daftar akun untuk pelaporan finansial" 
        back-href="/dashboard" 
        :count="accounts.total" 
    >
        <template #actions>
            <Button as-child class="h-9 px-4 bg-accent hover:bg-accent/90 shadow-sm transition-all gap-2">
                <Link href="/accounts/create">
                    <Plus class="h-4 w-4" />
                    Tambah Akun
                </Link>
            </Button>
        </template>
    </PageHeader>
    
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="accounts" 
            :columns="columns" 
            v-model:search="search" 
            v-model:perPage="perPage"
            search-placeholder="Cari kode atau nama akun..." 
            toolbar-title="COA Ledger" 
            :title="'Daftar Akun'"
            :sort="sort"
            :direction="direction as any"
            @sort-change="handleSortChange"
            :total-count="accounts.total"
        >
            <template #toolbar-prefix>
                <div class="flex items-center gap-3 overflow-x-auto pb-1 no-scrollbar">
                    <Select v-model="type">
                        <SelectTrigger class="h-9 w-[150px] text-xs font-medium bg-white rounded-lg border-slate-200 shadow-sm">
                            <SelectValue placeholder="Semua Tipe" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Tipe</SelectItem>
                            <SelectItem value="asset">Harta (Asset)</SelectItem>
                            <SelectItem value="liability">Kewajiban (Liability)</SelectItem>
                            <SelectItem value="equity">Modal (Equity)</SelectItem>
                            <SelectItem value="income">Pendapatan (Income)</SelectItem>
                            <SelectItem value="expense">Beban (Expense)</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select v-model="is_active">
                        <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200 shadow-sm">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Status</SelectItem>
                            <SelectItem value="1">Aktif</SelectItem>
                            <SelectItem value="0">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                        <X class="h-3 w-3 mr-1" /> Reset
                    </Button>
                </div>
            </template>

            <template #cell(code)="{ row }">
                <div class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded bg-slate-100 flex items-center justify-center">
                        <LayoutGrid class="h-3.5 w-3.5 text-slate-400" />
                    </div>
                    <span class="text-[13px] font-bold text-slate-900 font-mono tracking-tight">{{ row.code }}</span>
                </div>
            </template>

            <template #cell(name)="{ row }">
                <div class="flex flex-col">
                    <span class="text-[13px] font-bold text-slate-800">{{ row.name }}</span>
                    <span v-if="row.journal_items_count > 0" class="flex items-center gap-1 mt-0.5">
                        <Lock class="h-3 w-3 text-amber-500" />
                        <span class="text-[10px] text-amber-600 font-medium italic">Immutable Data Locked</span>
                    </span>
                    <span v-else class="flex items-center gap-1 mt-0.5">
                        <Unlock class="h-3 w-3 text-slate-300" />
                        <span class="text-[10px] text-slate-400 font-medium italic">Fully Editable</span>
                    </span>
                </div>
            </template>

            <template #cell(type_badge)="{ row }">
                <Badge :class="['text-[10px] uppercase font-bold px-1.5 h-5 rounded-md border', getTypeVariant(row.type)]">
                    {{ row.type }}
                </Badge>
            </template>

            <template #cell(balance)="{ row }">
                <div class="flex items-center gap-1.5">
                    <div :class="['h-2 w-2 rounded-full', row.balance_type === 'debit' ? 'bg-emerald-500' : 'bg-rose-500']"></div>
                    <span class="text-[11px] font-bold uppercase text-slate-600 tracking-wider">{{ row.balance_type }}</span>
                </div>
            </template>

            <template #cell(history)="{ row }">
                <Badge variant="outline" class="h-5 px-1.5 text-[10px] font-bold font-mono">
                    {{ row.journal_items_count }}
                </Badge>
            </template>

            <template #cell(status)="{ row }">
                <div class="flex justify-center">
                    <div v-if="row.is_active" class="h-2 w-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                </div>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center gap-2 justify-end">
                    <TooltipProvider>
                        <Tooltip :delay-duration="100">
                            <TooltipTrigger as-child>
                                <Button as-child variant="ghost" size="sm" class="h-8 w-8 p-0 rounded-lg shadow-sm hover:translate-y-[-1px] transition-all">
                                    <Link :href="`/accounts/${row.id}/edit`">
                                        <Edit2 class="h-3.5 w-3.5 text-slate-600" />
                                    </Link>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top" class="bg-slate-900 text-white text-[10px] font-bold py-1 px-2">Edit Akun</TooltipContent>
                        </Tooltip>

                        <Tooltip :delay-duration="100">
                            <TooltipTrigger as-child>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    :class="['h-8 w-8 p-0 rounded-lg shadow-sm transition-all', row.journal_items_count > 0 ? 'opacity-30 grayscale cursor-not-allowed' : 'hover:bg-rose-50 hover:translate-y-[-1px]']"
                                    @click="deleteAccount(row.id, row.journal_items_count > 0)"
                                >
                                    <Trash2 :class="['h-3.5 w-3.5', row.journal_items_count > 0 ? 'text-slate-400' : 'text-rose-500']" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="top" class="bg-rose-600 text-white text-[10px] font-bold py-1 px-2">
                                {{ row.journal_items_count > 0 ? 'Akun memiliki history jurnal' : 'Hapus/Nonaktifkan Akun' }}
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            </template>

            <template #empty>
                <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                    <FileText class="h-12 w-12 text-muted-foreground" />
                    <div>
                        <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada akun</p>
                        <p class="text-xs text-muted-foreground mt-1">Gunakan tombol 'Tambah Akun' untuk memulai</p>
                    </div>
                </div>
            </template>
        </DataTable>
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
