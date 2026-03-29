<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { Plus, Eye, Search, Trash2, Edit2, MoreHorizontal, ClipboardList, ChevronRight } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DataTable from '@/components/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    opnames: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.opnames.per_page));

const columns = [
    { key: 'date', label: 'Tanggal' },
    { key: 'info', label: 'Keterangan' },
    { key: 'status', label: 'Status', align: 'center' },
    { key: 'items', label: 'Jumlah Barang', align: 'center' },
] as const;

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/stock-opname', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const { confirmDialog } = useConfirm();

const deleteOpname = async (id: number) => {
    if (await confirmDialog('Hapus Data Opname?', 'Apakah Anda yakin ingin menghapus data opname ini? Data yang terhapus tidak dapat dikembalikan.')) {
        router.delete(`/stock-opname/${id}`);
    }
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusStyles = (status: string) => {
    switch (status) {
        case 'completed': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'draft': return 'bg-secondary/40 text-muted-foreground/60 border-transparent';
        default: return 'bg-muted/50 text-muted-foreground/40 border-transparent';
    }
};

const formatStatus = (status: string) => {
    return status.toUpperCase();
};
</script>

<template>
    <Head title="Stock Opname" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
        <PageHeader 
            title="Stock Opname" 
            description="Audit Stok & Penyesuaian" 
            back-href="/dashboard"
            :count="opnames.total"
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="opnames"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari keterangan..."
                toolbar-title="Riwayat Opname"
                :title="'Stock Opname'"
                :total-count="opnames.total"
            >
                <template #header-actions>
                    <Link href="/stock-opname/create">
                        <Button primary>
                            <Plus class="h-4 w-4" />
                            Mulai Opname Baru
                        </Button>
                    </Link>
                </template>
                <template #cell(date)="{ row }">
                    <div class="flex items-center gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-xl bg-secondary/50 flex items-center justify-center text-muted-foreground/40 transition-colors group-hover:bg-accent group-hover:text-white">
                            <ClipboardList class="h-5 w-5" />
                        </div>
                        <span class="text-[14px] font-bold text-foreground capitalize tabular-nums">{{ formatDate(row.tanggal) }}</span>
                    </div>
                </template>

                <template #cell(info)="{ row }">
                    <p class="text-[13px] text-muted-foreground/70 max-w-[200px] line-clamp-1 leading-relaxed tracking-tight">{{ row.keterangan || '-' }}</p>
                </template>

                <template #cell(status)="{ row }">
                    <Badge variant="secondary" class="h-5 px-1.5 rounded-md text-[9px] font-bold uppercase tracking-widest transition-all" :class="getStatusStyles(row.status)">
                        {{ formatStatus(row.status) }}
                    </Badge>
                </template>

                <template #cell(items)="{ row }">
                    <div class="flex flex-col items-center">
                        <span class="text-[14px] font-bold text-foreground tabular-nums">{{ row.items_count }}</span>
                        <span class="text-[9px] font-black uppercase tracking-tighter text-muted-foreground/30">SKUs Audited</span>
                    </div>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1 px-2">
                        <Link :href="`/stock-opname/${row.id}`">
                            <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                <Eye class="h-4 w-4" />
                            </button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button class="h-8 w-8 flex items-center justify-center rounded-lg text-muted-foreground/30 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="rounded-xl p-1.5 w-48 shadow-lg border-border/40 font-sans">
                                <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground/40 px-2 py-1.5 text-center text-xs">Aksi Cepat</DropdownMenuLabel>
                                <DropdownMenuSeparator />

                                <Link :href="`/stock-opname/${row.id}`">
                                    <DropdownMenuItem class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                        <Eye class="h-3.5 w-3.5 text-muted-foreground/60" /> Lihat Detail
                                    </DropdownMenuItem>
                                </Link>

                                <Link v-if="row.status === 'draft'" :href="`/stock-opname/${row.id}/edit`">
                                    <DropdownMenuItem class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] font-medium">
                                        <Edit2 class="h-3.5 w-3.5 text-muted-foreground/60" /> Lanjutkan Draft
                                    </DropdownMenuItem>
                                </Link>

                                <DropdownMenuItem
                                    v-if="row.status === 'draft'"
                                    class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5 font-medium"
                                    @click="deleteOpname(row.id)">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Draft
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>

                <template #empty>
                    <div class="flex flex-col items-center gap-3 opacity-20 py-12">
                        <ClipboardList class="h-10 w-10 text-muted-foreground" />
                        <p class="text-xs font-bold uppercase tracking-widest text-muted-foreground">Belum ada riwayat opname</p>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>
