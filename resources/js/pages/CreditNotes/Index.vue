<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    FileText, History, ChevronRight,
    ArrowLeftRight, Filter, Search, Plus
} from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { createGeneral as createGeneralRoute } from '@/actions/App/Http/Controllers/CreditNoteController';

const route = createGeneralRoute;

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    creditNotes: {
        data: any[];
        links: any[];
        total: number;
    };
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Daftar Nota Kredit', href: '/credit-notes' },
];

const columns = [
    { key: 'cn_number', label: 'Nota Kredit' },
    { key: 'sale_invoice', label: 'Invoice Asal' },
    { key: 'reason', label: 'Alasan' },
    { key: 'amount', label: 'Total Retur' },
    { key: 'status', label: 'Status' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
<Head title="Daftar Nota Kredit" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    <PageHeader 
        title="Nota Kredit & Retur" 
        description="Manajemen pengembalian barang pelanggan" 
        back-href="/dashboard" 
        :count="creditNotes.total" 
    >
        <template #actions>
            <Link :href="route('credit-notes.create-general').url">
                <Button class="bg-amber-600 hover:bg-amber-700 text-white font-bold gap-2">
                    <Plus class="h-4 w-4" />
                    Tambah Retur
                </Button>
            </Link>
        </template>
    </PageHeader>
    
    <div class="w-full max-w-7xl mx-auto">
        <DataTable 
            :data="creditNotes" 
            :columns="columns" 
            toolbar-title="Returns Ledger"
            :title="'Nota Kredit'"
        >
            <template #cell(cn_number)="{ row }">
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 shrink-0 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600">
                        <History class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-foreground font-mono leading-none">#{{ row.credit_note_number }}</p>
                        <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-tighter mt-1.5">{{ formatDate(row.created_at) }}</p>
                    </div>
                </div>
            </template>

            <template #cell(sale_invoice)="{ row }">
                <Link :href="`/sales/${row.sale.id}`" class="text-[12px] font-bold text-accent hover:underline">
                    #{{ row.sale.invoice_number }}
                </Link>
            </template>

            <template #cell(reason)="{ row }">
                <p class="text-[12px] font-medium text-muted-foreground line-clamp-1 max-w-[200px] italic">
                    "{{ row.reason }}"
                </p>
            </template>

            <template #cell(amount)="{ row }">
                <span class="text-[13px] font-bold text-foreground tabular-nums">
                    {{ formatCurrency(row.total_amount) }}
                </span>
            </template>

            <template #cell(status)="{ row }">
                <Badge 
                    v-if="row.status === 'posted'" 
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[10px] uppercase font-bold px-1.5 h-5"
                >
                    Posted
                </Badge>
                <Badge 
                    v-else-if="row.status === 'draft'" 
                    class="bg-amber-50 text-amber-600 border-amber-100 hover:bg-amber-50 text-[10px] uppercase font-bold px-1.5 h-5"
                >
                    Draft
                </Badge>
                <Badge 
                    v-else 
                    class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[10px] uppercase font-bold px-1.5 h-5"
                >
                    Voided
                </Badge>
            </template>

            <template #actions="{ row }">
                <Link :href="`/credit-notes/${row.id}`" class="flex items-center justify-center h-8 w-8 rounded-lg hover:bg-secondary transition-all">
                    <ChevronRight class="h-4 w-4 text-muted-foreground" />
                </Link>
            </template>
        </DataTable>
    </div>
</div>
</template>
