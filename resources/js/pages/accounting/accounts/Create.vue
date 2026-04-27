<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, ArrowLeft } from 'lucide-vue-next';
import { watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Card } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Chart of Accounts', href: '/accounting/accounts' },
    { title: 'Tambah Akun baru', href: '#' },
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
    form.post('/accounting/accounts', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
<Head title="Tambah Akun Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">

        <div class="flex items-center gap-4">
            <Link href="/accounting/accounts">
                <Button variant="outline" size="icon"
                    class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Tambah Akun Baru</h1>
                <p class="text-sm text-slate-400 mt-0.5">Lengkapi detail akun untuk ditambahkan ke dalam sistem
                    akuntansi.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none max-w-7xl">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Parameter Akun</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Pastikan kode akun mengikuti standar akuntansi perusahaan.
                </p>
            </div>

            <div class="p-6">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Code -->
                        <div class="flex flex-col gap-2">
                            <Label for="code">Kode Akun</Label>
                            <Input id="code" v-model="form.code" placeholder="Contoh: 1101"
                                class="h-10 bg-white border-slate-200 focus-visible:ring-accent font-mono font-bold" />
                            <InputError :message="form.errors.code" />
                        </div>

                        <!-- Type -->
                        <div class="flex flex-col gap-2">
                            <Label for="type">Tipe Akun</Label>
                            <Select v-model="form.type">
                                <SelectTrigger class="h-10 bg-white border-slate-200">
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
                            <InputError :message="form.errors.type" />
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="flex flex-col gap-2">
                        <Label for="name">Nama Akun</Label>
                        <Input id="name" v-model="form.name" placeholder="Contoh: Kas Utama"
                            class="h-10 bg-white border-slate-200 focus-visible:ring-accent font-bold" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Balance Type -->
                    <div class="flex flex-col gap-4 pt-2">
                        <Label>Saldo Normal</Label>

                        <RadioGroup v-model="form.balance_type" class="grid grid-cols-2 gap-4">
                            <div>
                                <RadioGroupItem value="debit" id="debit" class="peer sr-only" />
                                <Label for="debit"
                                    class="flex flex-col items-center justify-between rounded-xl border-2 border-slate-100 bg-white p-4 hover:bg-slate-50 peer-data-[state=checked]:border-emerald-500 peer-data-[state=checked]:bg-emerald-50/50 cursor-pointer transition-all">
                                    <span class="text-xs font-bold text-slate-800">DEBIT</span>
                                </Label>
                            </div>

                            <div>
                                <RadioGroupItem value="credit" id="credit" class="peer sr-only" />
                                <Label for="credit"
                                    class="flex flex-col items-center justify-between rounded-xl border-2 border-slate-100 bg-white p-4 hover:bg-slate-50 peer-data-[state=checked]:border-rose-500 peer-data-[state=checked]:bg-rose-50/50 cursor-pointer transition-all">
                                    <span class="text-xs font-bold text-slate-800">KREDIT</span>
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <Button as-child variant="ghost" class="h-10 px-6 text-slate-500 font-bold hover:bg-slate-100">
                            <Link href="/accounting/accounts">Cancel</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing"
                            class="h-10 px-8 bg-accent hover:bg-accent/90 text-white font-bold transition-all gap-2">
                            <Save class="h-4 w-4" />
                            Simpan Akun
                        </Button>
                    </div>
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
