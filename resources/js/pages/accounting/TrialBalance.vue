<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    LayoutGrid, ArrowLeft, RefreshCw, AlertCircle, 
    CheckCircle2, XCircle, Info, Calculator 
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

// Persistent Layout
defineOptions({ layout: AppLayout });

interface Account {
    id: number;
    code: string;
    name: string;
    type: string;
    balance_type: 'debit' | 'credit';
    journal_items_sum_debit?: number;
    journal_items_sum_credit?: number;
}

const props = defineProps<{
    accounts: Account[];
    totals: {
        debit: number;
        credit: number;
    };
    is_balanced: boolean;
    generated_at: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Accounting', href: '#' },
    { title: 'Trial Balance', href: '/accounting/trial-balance' },
];

const formatCurrency = (cents: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(cents / 100);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }) + ', ' + new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Grouping Logic
const groupedAccounts = computed(() => {
    const groups: Record<string, { label: string, accounts: Account[], totalDebit: number, totalCredit: number }> = {
        asset: { label: 'Asset (Harta)', accounts: [], totalDebit: 0, totalCredit: 0 },
        liability: { label: 'Liability (Kewajiban)', accounts: [], totalDebit: 0, totalCredit: 0 },
        equity: { label: 'Equity (Modal)', accounts: [], totalDebit: 0, totalCredit: 0 },
        income: { label: 'Income (Pendapatan)', accounts: [], totalDebit: 0, totalCredit: 0 },
        expense: { label: 'Expense (Beban)', accounts: [], totalDebit: 0, totalCredit: 0 },
    };

    props.accounts.forEach(acc => {
        const type = acc.type.toLowerCase();
        if (groups[type]) {
            groups[type].accounts.push(acc);
            groups[type].totalDebit += (acc.journal_items_sum_debit || 0);
            groups[type].totalCredit += (acc.journal_items_sum_credit || 0);
        }
    });

    return Object.entries(groups).filter(g => g[1].accounts.length > 0);
});

const calculateSaldo = (account: Account) => {
    const dr = account.journal_items_sum_debit || 0;
    const cr = account.journal_items_sum_credit || 0;
    
    if (account.balance_type === 'debit') {
        return dr - cr;
    } else {
        return cr - dr;
    }
};

const isAbnormal = (account: Account) => {
    return calculateSaldo(account) < 0;
};
</script>

<template>
<Head title="Trial Balance" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 max-w-7xl mx-auto w-full">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <Badge variant="outline" class="bg-white border-slate-200 text-slate-400 font-bold px-1.5 uppercase tracking-tighter text-[9px]">Reports</Badge>
                <div class="h-1 w-1 rounded-full bg-slate-300"></div>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Per: {{ new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}</span>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                Trial Balance
                <Calculator class="h-6 w-6 text-accent" />
            </h1>
            <p class="text-xs text-slate-500 font-medium mt-1 flex items-center gap-1.5">
                <RefreshCw class="h-3 w-3 text-slate-400" />
                Diperbarui: {{ formatDate(generated_at) }} 
                <span class="text-[10px] text-slate-300 ml-1">(Cache TTL: 5m)</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div :class="['flex items-center gap-3 px-4 py-2 rounded-xl border-2 transition-all shadow-sm', is_balanced ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200 animate-pulse']">
                <div :class="['h-8 w-8 rounded-full flex items-center justify-center', is_balanced ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white shadow-lg shadow-rose-200']">
                    <CheckCircle2 v-if="is_balanced" class="h-5 w-5" />
                    <XCircle v-else class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest leading-none" :class="is_balanced ? 'text-emerald-700' : 'text-rose-700'">Ledger Integrity</p>
                    <p class="text-xs font-bold" :class="is_balanced ? 'text-emerald-900' : 'text-rose-900'">
                        {{ is_balanced ? 'BALANCED ✓' : 'OUT OF BALANCE ✗' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alert if Out of Balance -->
    <Alert v-if="!is_balanced" variant="destructive" class="max-w-7xl mx-auto w-full border-rose-200 bg-rose-50/50">
        <AlertCircle class="h-4 w-4" />
        <AlertTitle class="text-[11px] font-black uppercase tracking-widest">CRITICAL DISCREPANCY DETECTED</AlertTitle>
        <AlertDescription class="text-xs font-medium">
            Total Debit dan Kredit tidak seimbang. Selisih sebesar <b>{{ formatCurrency(Math.abs(totals.debit - totals.credit)) }}</b>. Segera hubungi administrator atau lakukan audit manual.
        </AlertDescription>
    </Alert>

    <div class="w-full max-w-7xl mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-12">
        <table class="w-full text-left border-collapse">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-500">Kode</th>
                    <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-500">Nama Akun</th>
                    <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-500 text-right">Debit Harian</th>
                    <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-500 text-right">Kredit Harian</th>
                    <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-widest text-slate-500 text-right">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <template v-for="[type, group] in groupedAccounts" :key="type">
                    <!-- Group Header -->
                    <tr class="bg-slate-50/30">
                        <td colspan="5" class="px-6 py-3 border-y border-slate-100">
                            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">{{ group.label }}</span>
                        </td>
                    </tr>

                    <!-- Group Rows -->
                    <tr v-for="acc in group.accounts" :key="acc.id" class="group hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3.5 text-xs font-mono font-bold text-slate-400 group-hover:text-accent transition-colors">{{ acc.code }}</td>
                        <td class="px-6 py-3.5">
                            <div class="flex flex-col">
                                <span class="text-[13px] font-bold text-slate-800">{{ acc.name }}</span>
                                <span class="text-[10px] text-slate-400 font-medium italic opacity-0 group-hover:opacity-100 transition-opacity uppercase tracking-tighter">Norm: {{ acc.balance_type }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3.5 text-right font-mono text-[13px] text-slate-600">
                            {{ (acc.journal_items_sum_debit || 0) > 0 ? formatCurrency(acc.journal_items_sum_debit || 0) : '-' }}
                        </td>
                        <td class="px-6 py-3.5 text-right font-mono text-[13px] text-slate-600">
                            {{ (acc.journal_items_sum_credit || 0) > 0 ? formatCurrency(acc.journal_items_sum_credit || 0) : '-' }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <div class="flex flex-col items-end">
                                <span :class="['font-black font-mono text-[13px]', isAbnormal(acc) ? 'text-rose-500' : 'text-slate-900']">
                                    {{ formatCurrency(Math.abs(calculateSaldo(acc))) }}
                                </span>
                                <div v-if="isAbnormal(acc)" class="flex items-center gap-1 mt-0.5">
                                    <AlertCircle class="h-3 w-3 text-rose-500" />
                                    <span class="text-[9px] font-black uppercase text-rose-500 tracking-tighter">Abnormal Balance</span>
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Group Subtotal -->
                    <tr class="bg-slate-50/30 border-y border-slate-100">
                        <td colspan="2" class="px-6 py-3 text-right">
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 italic">Subtotal {{ type }}</span>
                        </td>
                        <td class="px-6 py-3 text-right font-black font-mono text-[13px] text-slate-500">
                            {{ formatCurrency(group.totalDebit) }}
                        </td>
                        <td class="px-6 py-3 text-right font-black font-mono text-[13px] text-slate-500">
                            {{ formatCurrency(group.totalCredit) }}
                        </td>
                        <td class="px-6 py-3 border-l bg-slate-50"></td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="bg-slate-900 text-white border-t-4 border-accent">
                <tr>
                    <td colspan="2" class="px-6 py-6 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-400">Grand Total</span>
                            <span class="text-xs font-bold text-accent italic">Adjusted Operational Ledger</span>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-lg font-black font-mono tracking-tighter">{{ formatCurrency(totals.debit) }}</span>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">DR</span>
                        </div>
                    </td>
                    <td class="px-6 py-6 text-right">
                        <div class="flex flex-col items-end">
                            <span class="text-lg font-black font-mono tracking-tighter">{{ formatCurrency(totals.credit) }}</span>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">CR</span>
                        </div>
                    </td>
                    <td class="px-6 py-6 border-l border-white/10 flex items-center justify-center">
                        <div :class="['h-12 w-12 rounded-full flex items-center justify-center shadow-lg', is_balanced ? 'bg-emerald-500 shadow-emerald-500/20' : 'bg-rose-500 animate-bounce shadow-rose-500/20']">
                            <CheckCircle2 v-if="is_balanced" class="h-6 w-6 text-white" />
                            <ArrowRightLeft v-else class="h-6 w-6 text-white" />
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</template>

<style scoped>
.tracking-widest {
    letter-spacing: 0.1em;
}
.tracking-tighter {
    letter-spacing: -0.05em;
}
</style>
