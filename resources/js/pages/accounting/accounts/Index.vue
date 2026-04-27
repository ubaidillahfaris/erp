<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { 
    Plus, Lock, Unlock, 
    X, Trash2, Edit2, LayoutGrid,
    ArrowLeft, Search
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuItem, 
    DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { MoreHorizontal, Pencil, HistoryIcon } from 'lucide-vue-next';

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
] as const;

watch([search, type, is_active, perPage, sort, direction], debounce(() => {
    router.get('/accounting/accounts', {
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

    if (confirm('Deaktivasi akun ini?')) {
        router.delete(`/accounting/accounts/${id}`, {
            onSuccess: () => {}
        });
    }
};
</script>

<template>
<Head title="Chart of Accounts" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Link href="/dashboard">
                    <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Chart of Accounts</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Kelola daftar akun untuk pelaporan finansial dan audit internal.</p>
                </div>
            </div>
            
            <Link href="/accounting/accounts/create">
                <Button class="h-9 px-4 bg-accent hover:bg-accent/90">
                    <Plus class="h-4 w-4 mr-2" />
                    Tambah Akun
                </Button>
            </Link>
        </div>
        
        <div class="w-full">
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
                            <SelectTrigger class="h-9 w-[150px] text-xs font-medium bg-white rounded-lg border-slate-200">
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
                            <SelectTrigger class="h-9 w-[130px] text-xs font-medium bg-white rounded-lg border-slate-200">
                                <SelectValue placeholder="Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Status</SelectItem>
                                <SelectItem value="1">Active</SelectItem>
                                <SelectItem value="0">Nonaktif</SelectItem>
                            </SelectContent>
                        </Select>

                        <Button v-if="hasActiveFilters" variant="ghost" size="sm" class="h-9 px-2 text-xs text-muted-foreground hover:text-foreground" @click="resetFilters">
                            <X class="h-3 w-3 mr-1" /> Reset
                        </Button>
                    </div>
                </template>

                <template #cell(code)="{ row }">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 shrink-0 rounded-lg bg-secondary/50 flex items-center justify-center text-muted-foreground transition-colors group-hover:bg-accent group-hover:text-white">
                            <LayoutGrid class="h-4 w-4" />
                        </div>
                        <span class="text-[13px] font-bold text-foreground font-mono tracking-tighter leading-none">{{ row.code }}</span>
                    </div>
                </template>

                <template #cell(name)="{ row }">
                    <div class="flex flex-col gap-1">
                        <span class="text-[13px] font-bold text-foreground leading-none">{{ row.name }}</span>
                        <div v-if="row.journal_items_count > 0" class="flex items-center gap-1 mt-0.5">
                            <Lock class="h-2.5 w-2.5 text-amber-500" />
                            <span class="text-[10px] text-amber-600 font-black uppercase tracking-tighter">Immutable</span>
                        </div>
                    </div>
                </template>

                <template #cell(type_badge)="{ row }">
                    <Badge :class="['text-[10px] uppercase font-black tracking-widest px-1.5 h-4 rounded-sm border shadow-none', getTypeVariant(row.type)]">
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
                    <Badge variant="outline" class="h-5 px-1.5 text-[10px] font-bold font-mono border-slate-200 text-slate-500">
                        {{ row.journal_items_count }}
                    </Badge>
                </template>

                <template #cell(status)="{ row }">
                    <div class="flex justify-center">
                        <div v-if="row.is_active" class="h-2 w-2 rounded-full bg-emerald-500"></div>
                        <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                    </div>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-44 shadow-none border-slate-200 font-sans">
                                <DropdownMenuItem @click="router.visit(`/accounting/accounts/${row.id}/edit`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Pencil class="h-3.5 w-3.5" /> Edit Akun
                                </DropdownMenuItem>
                                
                                <DropdownMenuItem @click="router.visit(`/accounting/journal?account_id=${row.id}`)" class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <HistoryIcon class="h-3.5 w-3.5" /> Riwayat Jurnal
                                </DropdownMenuItem>

                                <DropdownMenuItem 
                                    @click="deleteAccount(row.id, row.journal_items_count > 0)"
                                    :disabled="row.journal_items_count > 0"
                                    class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <Trash2 class="h-3.5 w-3.5" /> 
                                    {{ row.journal_items_count > 0 ? 'Immutable (Ada Jurnal)' : 'Hapus Akun' }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-20 text-center">
                        <LayoutGrid class="h-12 w-12 text-muted-foreground" />
                        <div>
                            <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada akun</p>
                            <p class="text-xs text-muted-foreground mt-1">Gunakan tombol 'Tambah Akun' untuk memulai</p>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</AppLayout>
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
