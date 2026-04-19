<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    LayoutGrid, Save, ArrowLeft, Lock, 
    Unlock, AlertTriangle, Info, HelpCircle
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Switch } from '@/components/ui/switch';
import { 
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";

// Persistent Layout
defineOptions({ layout: AppLayout });

const props = defineProps<{
    account: {
        id: number;
        code: string;
        name: string;
        type: string;
        balance_type: string;
        is_active: boolean;
        journal_items_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Chart of Accounts', href: '/accounts' },
    { title: 'Edit Akun', href: `/accounts/${props.account.id}/edit` },
];

const form = useForm({
    code: props.account.code,
    name: props.account.name,
    type: props.account.type,
    balance_type: props.account.balance_type,
    is_active: props.account.is_active,
});

const isLocked = props.account.journal_items_count > 0;

const submit = () => {
    form.put(`/accounts/${props.account.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
<Head :title="`Edit Akun: ${account.code}`" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <div class="flex items-center justify-between max-w-3xl mx-auto w-full">
        <div class="flex items-center gap-4">
            <Button as-child variant="ghost" size="sm" class="h-9 w-9 p-0 rounded-full bg-white shadow-sm hover:bg-slate-100">
                <Link href="/accounts">
                    <ArrowLeft class="h-4 w-4 text-slate-600" />
                </Link>
            </Button>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                    Edit Akun: {{ account.code }}
                    <Lock v-if="isLocked" class="h-5 w-5 text-amber-500" />
                </h1>
                <p class="text-xs text-slate-500 font-medium italic">ID Internal: #{{ account.id }} • {{ account.journal_items_count }} Riwayat Jurnal</p>
            </div>
        </div>
    </div>

    <div class="w-full max-w-3xl mx-auto">
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                
                <!-- Status Banner for Locked State -->
                <div v-if="isLocked" class="bg-amber-50 border-b border-amber-100 px-8 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <AlertTriangle class="h-4 w-4 text-amber-600" />
                        <span class="text-[11px] font-black uppercase text-amber-800 tracking-wider">Hard Guard Active: Mutable Data Blocked</span>
                    </div>
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger>
                                <HelpCircle class="h-4 w-4 text-amber-400 cursor-help" />
                            </TooltipTrigger>
                            <TooltipContent side="left" class="bg-slate-900 text-white max-w-[200px] text-[10px] font-bold p-2">
                                Karena akun ini memiliki history jurnal, Kode dan Tipe dikunci untuk menjaga integritas laporan.
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>

                <div class="p-8 flex flex-col gap-8">
                    <!-- Section header -->
                    <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
                        <div :class="['h-12 w-12 rounded-xl flex items-center justify-center', isLocked ? 'bg-amber-100/50' : 'bg-accent/10']">
                            <Lock v-if="isLocked" class="h-6 w-6 text-amber-600" />
                            <LayoutGrid v-else class="h-6 w-6 text-accent" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-widest">Parameter Akun</h2>
                            <p class="text-[11px] text-slate-400 font-medium italic">
                                {{ isLocked ? 'Beberapa bidang tidak dapat diubah (ReadOnly)' : 'Semua bidang dapat diubah secara bebas' }}
                            </p>
                        </div>
                    </div>

                    <!-- Form Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <!-- Code -->
                        <div class="space-y-2">
                            <Label for="code" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kode Akun</Label>
                            <div class="relative">
                                <Input 
                                    id="code" 
                                    v-model="form.code" 
                                    :disabled="isLocked"
                                    class="h-10 bg-slate-50/50 border-slate-200 focus-visible:ring-accent font-mono font-bold disabled:bg-slate-100 disabled:text-slate-400 disabled:opacity-75 shadow-none" 
                                />
                                <Lock v-if="isLocked" class="absolute right-3 top-3 h-4 w-4 text-slate-300" />
                            </div>
                            <p v-if="form.errors.code" class="text-[10px] font-bold text-rose-500">{{ form.errors.code }}</p>
                        </div>

                        <!-- Type -->
                        <div class="space-y-2">
                            <Label for="type" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tipe Akun</Label>
                            <Select v-model="form.type" :disabled="isLocked">
                                <SelectTrigger class="h-10 bg-slate-50/50 border-slate-200 font-medium disabled:bg-slate-100 disabled:text-slate-400 disabled:opacity-75 shadow-none">
                                    <SelectValue placeholder="Pilih Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="asset">Harta (Asset)</SelectItem>
                                    <SelectItem value="liability">Kewajiban (Liability)</SelectItem>
                                    <SelectItem value="equity">Modal (Equity)</SelectItem>
                                    <SelectItem value="income">Pendapatan (Income)</SelectItem>
                                    <SelectItem value="expense">Beban (Expense)</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.type" class="text-[10px] font-bold text-rose-500">{{ form.errors.type }}</p>
                        </div>

                        <!-- Name (Editable) -->
                        <div class="space-y-2 md:col-span-2">
                            <Label for="name" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama Akun (Cosmetic)</Label>
                            <Input 
                                id="name" 
                                v-model="form.name" 
                                class="h-10 bg-white border-slate-200 focus-visible:ring-accent font-bold" 
                            />
                            <p v-if="form.errors.name" class="text-[10px] font-bold text-rose-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Normal Balance (Editable only if not locked) -->
                        <div class="space-y-2">
                            <Label for="balance_type" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Saldo Normal</Label>
                            <Select v-model="form.balance_type" :disabled="isLocked">
                                <SelectTrigger class="h-10 bg-slate-50/50 border-slate-200 font-medium disabled:bg-slate-100 disabled:text-slate-400 shadow-none">
                                    <SelectValue placeholder="Pilih Saldo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="debit">DEBIT</SelectItem>
                                    <SelectItem value="credit">KREDIT</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Status Toggle (Always Editable) -->
                        <div class="space-y-2 flex flex-col justify-end">
                            <div class="flex items-center justify-between p-2 rounded-lg border border-slate-100 bg-slate-50/30">
                                <Label for="is_active" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Status Akun Aktif</Label>
                                <Switch 
                                    id="is_active" 
                                    :checked="form.is_active" 
                                    @update:checked="(val) => form.is_active = val"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Informational Warning -->
                    <div v-if="isLocked" class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex gap-4">
                        <div class="h-10 w-10 shrink-0 bg-white rounded-lg flex items-center justify-center border border-rose-200 shadow-sm">
                            <AlertTriangle class="h-5 w-5 text-rose-500" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-xs font-black text-rose-900 uppercase tracking-tight">Peringatan Integritas</p>
                            <p class="text-[11px] text-rose-700 leading-relaxed font-medium">
                                Akun ini sudah memiliki transaksi tercatat. Mengubah <b>Kode</b> atau <b>Tipe</b> akan merusak konsistensi laporan laba rugi dan neraca historis. Hanya perubahan nama kosmetik yang diijinkan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pb-20">
                <Button as-child variant="ghost" class="h-11 px-6 text-slate-500 font-bold hover:bg-slate-200 transition-all">
                    <Link href="/accounts">Batal</Link>
                </Button>
                <Button 
                    type="submit" 
                    :disabled="form.processing"
                    class="h-11 px-8 bg-accent hover:bg-accent/90 text-white font-black uppercase tracking-widest shadow-lg shadow-accent/20 transition-all gap-2"
                >
                    <Save class="h-4 w-4" />
                    Simpan Perubahan
                </Button>
            </div>
        </form>
    </div>
</div>
</template>
