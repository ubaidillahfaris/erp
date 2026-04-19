<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    LayoutGrid, Save, ArrowLeft, Info, 
    CheckCircle2, Sparkles 
} from 'lucide-vue-next';
import { watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

// Persistent Layout
defineOptions({ layout: AppLayout });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Chart of Accounts', href: '/accounts' },
    { title: 'Tambah Akun Baru', href: '/accounts/create' },
];

const form = useForm({
    code: '',
    name: '',
    type: 'asset',
    balance_type: 'debit',
    is_active: true,
});

// Autosuggest balance type based on account type
watch(() => form.type, (newType) => {
    if (newType === 'asset' || newType === 'expense') {
        form.balance_type = 'debit';
    } else {
        form.balance_type = 'credit';
    }
});

const submit = () => {
    form.post('/accounts', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
<Head title="Tambah Akun Baru" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <div class="flex items-center justify-between max-w-3xl mx-auto w-full">
        <div class="flex items-center gap-4">
            <Button as-child variant="ghost" size="sm" class="h-9 w-9 p-0 rounded-full bg-white shadow-sm hover:bg-slate-100">
                <Link href="/accounts">
                    <ArrowLeft class="h-4 w-4 text-slate-600" />
                </Link>
            </Button>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight">Tambah Akun</h1>
                <p class="text-xs text-slate-500 font-medium">Buat metrik finansial baru dalam Chart of Accounts</p>
            </div>
        </div>
    </div>

    <div class="w-full max-w-3xl mx-auto">
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-8 flex flex-col gap-8">
                <!-- Header Icon -->
                <div class="flex items-center gap-4 pb-6 border-b border-slate-100">
                    <div class="h-12 w-12 rounded-xl bg-accent/10 flex items-center justify-center">
                        <LayoutGrid class="h-6 w-6 text-accent" />
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-widest">Identitas Akun</h2>
                        <p class="text-[11px] text-slate-400 font-medium italic">Pastikan kode akun mengikuti standar akuntansi perusahaan</p>
                    </div>
                </div>

                <!-- Form Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                    <!-- Code -->
                    <div class="space-y-2">
                        <Label for="code" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                            Kode Akun
                            <span class="text-rose-500">*</span>
                        </Label>
                        <div class="relative">
                            <Input 
                                id="code" 
                                v-model="form.code" 
                                placeholder="Contoh: 1101" 
                                class="h-10 bg-slate-50/50 border-slate-200 focus-visible:ring-accent font-mono font-bold" 
                            />
                        </div>
                        <p v-if="form.errors.code" class="text-[10px] font-bold text-rose-500 animate-in fade-in slide-in-from-top-1">{{ form.errors.code }}</p>
                    </div>

                    <!-- Type -->
                    <div class="space-y-2">
                        <Label for="type" class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Tipe Akun</Label>
                        <Select v-model="form.type">
                            <SelectTrigger class="h-10 bg-slate-50/50 border-slate-200 font-medium">
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

                    <!-- Name -->
                    <div class="space-y-2 md:col-span-2">
                        <Label for="name" class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                            Nama Akun
                            <span class="text-rose-500">*</span>
                        </Label>
                        <Input 
                            id="name" 
                            v-model="form.name" 
                            placeholder="Contoh: Kas Utama" 
                            class="h-10 bg-slate-50/50 border-slate-200 focus-visible:ring-accent font-bold" 
                        />
                        <p v-if="form.errors.name" class="text-[10px] font-bold text-rose-500 animate-in fade-in slide-in-from-top-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Balance Type -->
                    <div class="space-y-4 md:col-span-2 pt-4">
                        <div class="flex items-center justify-between">
                            <Label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Saldo Normal</Label>
                            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-100 animate-pulse">
                                <Sparkles class="h-3 w-3 text-emerald-500" />
                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-tight">Smart Suggestion Active</span>
                            </div>
                        </div>
                        
                        <RadioGroup v-model="form.balance_type" class="grid grid-cols-2 gap-4">
                            <div>
                                <RadioGroupItem value="debit" id="debit" class="peer sr-only" />
                                <Label
                                    for="debit"
                                    class="flex flex-col items-center justify-between rounded-xl border-2 border-slate-100 bg-white p-4 hover:bg-slate-50 peer-data-[state=checked]:border-emerald-500 peer-data-[state=checked]:bg-emerald-50/50 cursor-pointer transition-all"
                                >
                                    <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center mb-2">
                                        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-widest text-slate-800">DEBIT</span>
                                </Label>
                            </div>

                            <div>
                                <RadioGroupItem value="credit" id="credit" class="peer sr-only" />
                                <Label
                                    for="credit"
                                    class="flex flex-col items-center justify-between rounded-xl border-2 border-slate-100 bg-white p-4 hover:bg-slate-50 peer-data-[state=checked]:border-rose-500 peer-data-[state=checked]:bg-rose-50/50 cursor-pointer transition-all"
                                >
                                    <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center mb-2">
                                        <CheckCircle2 class="h-4 w-4 text-rose-600" />
                                    </div>
                                    <span class="text-xs font-bold uppercase tracking-widest text-slate-800">KREDIT</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>
                </div>

                <!-- Guidance Alert -->
                <Alert class="bg-amber-50/50 border-amber-200">
                    <Info class="h-4 w-4 text-amber-600" />
                    <AlertTitle class="text-[11px] font-black uppercase text-amber-800 tracking-wider">Accounting Guard</AlertTitle>
                    <AlertDescription class="text-[11px] text-amber-700 font-medium leading-relaxed mt-1">
                        Setelah akun ini digunakan dalam jurnal, bidang <b>Kode, Tipe,</b> dan <b>Saldo Normal</b> akan dikunci untuk menjaga integritas laporan finansial. Periksa kembali sebelum menyimpan.
                    </AlertDescription>
                </Alert>
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
                    Simpan Akun
                </Button>
            </div>
        </form>
    </div>
</div>
</template>
