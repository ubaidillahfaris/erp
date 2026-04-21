<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Calculator, Hash, CheckCircle2, AlertCircle, 
    ArrowLeft, Printer, FileDown, RefreshCw
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import trialBalanceController from '@/actions/App/Http/Controllers/Accounting/TrialBalanceController';

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
    { title: 'Neraca Saldo', href: '/accounting/trial-balance' },
];

const formatCurrency = (cents: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(cents / 100);
};

// Group accounts by type for the report view
const groupedAccounts = computed(() => {
    const groups: Record<string, { label: string, accounts: Account[], totalDebit: number, totalCredit: number }> = {
        asset: { label: 'Asset (Harta)', accounts: [], totalDebit: 0, totalCredit: 0 },
        liability: { label: 'Liability (Kewajiban)', accounts: [], totalDebit: 0, totalCredit: 0 },
        equity: { label: 'Equity (Modal)', accounts: [], totalDebit: 0, totalCredit: 0 },
        income: { label: 'Income (Pendapatan)', accounts: [], totalDebit: 0, totalCredit: 0 },
        expense: { label: 'Expense (Beban)', accounts: [], totalDebit: 0, totalCredit: 0 },
    };

    if (Array.isArray(props.accounts)) {
        props.accounts.forEach(acc => {
            if (!acc || !acc.type) return;

            const type = acc.type.toLowerCase();
            if (groups[type]) {
                groups[type].accounts.push(acc);
                groups[type].totalDebit += (acc.journal_items_sum_debit || 0);
                groups[type].totalCredit += (acc.journal_items_sum_credit || 0);
            }
        });
    }

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
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">Trial Balance (Neraca Saldo)</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Audit saldo seluruh akun per {{ generated_at }}</p>
                </div>

                <form @submit.prevent="router.post(trialBalanceController.refresh().url)">
                    <Button 
                        type="submit" 
                        variant="outline" 
                        size="sm"
                        class="h-8 gap-2 text-slate-500 border-slate-200"
                    >
                        <RefreshCw class="h-3.5 w-3.5" />
                        Refresh Data
                    </Button>
                </form>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" class="h-9 border-slate-200 text-slate-600 font-bold gap-2">
                    <FileDown class="h-4 w-4" /> Export CSV
                </Button>
                <Button variant="outline" size="sm" class="h-9 border-slate-200 text-slate-600 font-bold gap-2" onclick="window.print()">
                    <Printer class="h-4 w-4" /> Print Report
                </Button>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Card class="p-6 border border-slate-200 rounded-xl bg-white shadow-none">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Debit Ledger</p>
                <h2 class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ formatCurrency(totals.debit) }}</h2>
            </Card>

            <Card class="p-6 border border-slate-200 rounded-xl bg-white shadow-none">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kredit Ledger</p>
                <h2 class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ formatCurrency(totals.credit) }}</h2>
            </Card>

            <Card :class="['p-6 border rounded-xl shadow-none', is_balanced ? 'bg-emerald-50/50 border-emerald-100' : 'bg-rose-50/50 border-rose-100']">
                <div class="flex items-center justify-between">
                    <div>
                        <p :class="['text-xs font-semibold uppercase tracking-wider', is_balanced ? 'text-emerald-600' : 'text-rose-600']">Integrity Status</p>
                        <h2 :class="['text-2xl font-bold mt-1', is_balanced ? 'text-emerald-900' : 'text-rose-900']">
                            {{ is_balanced ? 'BALANCED' : 'UNBALANCED' }}
                        </h2>
                    </div>
                    <div :class="['h-12 w-12 rounded-xl flex items-center justify-center', is_balanced ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600']">
                        <CheckCircle2 v-if="is_balanced" class="h-6 w-6" />
                        <AlertCircle v-else class="h-6 w-6" />
                    </div>
                </div>
            </Card>
        </div>

        <!-- Report Sheet -->
        <Card class="border border-slate-200 rounded-xl bg-white shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 w-32">Kode</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500">Nama Akun</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right w-48">Debit</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right w-48">Kredit</th>
                            <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 text-right w-48">Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template v-for="[type, group] in groupedAccounts" :key="type">
                            <tr class="bg-slate-50/30 font-bold uppercase text-slate-400">
                                <td colspan="5" class="px-6 py-2.5">
                                    <span class="text-[10px] tracking-widest">{{ group.label }}</span>
                                </td>
                            </tr>
                            <tr v-for="acc in group.accounts" :key="acc.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 font-mono text-[13px] font-bold text-slate-400">{{ acc.code }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-1.5 rounded-full" :class="acc.balance_type === 'debit' ? 'bg-emerald-400' : 'bg-rose-400'"></div>
                                        <span class="text-[13px] font-bold text-slate-800 leading-none">{{ acc.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums text-[13px] font-bold text-slate-600">
                                    {{ (acc.journal_items_sum_debit || 0) > 0 ? formatCurrency(acc.journal_items_sum_debit || 0) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right tabular-nums text-[13px] font-bold text-slate-600">
                                    {{ (acc.journal_items_sum_credit || 0) > 0 ? formatCurrency(acc.journal_items_sum_credit || 0) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span :class="['font-bold font-mono text-[13px]', isAbnormal(acc) ? 'text-rose-500' : 'text-slate-900']">
                                            {{ formatCurrency(Math.abs(calculateSaldo(acc))) }}
                                        </span>
                                        <span v-if="isAbnormal(acc)" class="text-[9px] font-bold uppercase text-rose-500 tracking-tighter">Abnormal Balance</span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="bg-slate-900 text-white">
                        <tr>
                            <td colspan="2" class="px-6 py-5 text-sm font-bold uppercase tracking-widest text-slate-400">Total Grand Ledger Summary</td>
                            <td class="px-6 py-5 text-right tabular-nums text-sm font-bold text-emerald-400 border-l border-white/5">
                                {{ formatCurrency(totals.debit) }}
                            </td>
                            <td class="px-6 py-5 text-right tabular-nums text-sm font-bold text-rose-400 border-l border-white/5">
                                {{ formatCurrency(totals.credit) }}
                            </td>
                            <td class="px-6 py-5 bg-slate-800 border-l border-white/5"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Card>

        <div v-if="!is_balanced" class="mt-4 p-4 rounded-xl bg-rose-50 border border-rose-100 flex gap-4">
            <AlertCircle class="h-5 w-5 text-rose-600 shrink-0" />
            <div>
                <p class="text-xs font-bold text-rose-900 uppercase">Attention Required: Trial Balance Mismatch</p>
                <p class="text-[11px] text-rose-700 font-medium mt-1 leading-relaxed">
                    Terdapat perbedaan sebesar <b class="tabular-nums">{{ formatCurrency(Math.abs(totals.debit - totals.credit)) }}</b> antara total debit dan kredit. 
                    Periksa kembali entri jurnal atau hubungi sistem administrator.
                </p>
            </div>
        </div>

        <div class="py-10 text-center opacity-30 select-none">
            <Calculator class="h-12 w-12 text-slate-400 mx-auto" />
            <p class="text-[10px] font-bold uppercase tracking-widest mt-2">End of Audit Trail Report</p>
        </div>
    </div>
</AppLayout>
</template>
