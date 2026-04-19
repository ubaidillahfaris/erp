<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, ArrowLeft, Lock, AlertTriangle } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Card } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';

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
    { title: 'Chart of Accounts', href: '/accounting/accounts' },
    { title: 'Edit Akun', href: '#' },
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
    form.put(`/accounting/accounts/${props.account.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
<Head :title="`Edit Akun: ${account.code}`" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        
        <div class="flex items-center gap-4">
            <Link href="/accounting/accounts">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                    Edit Akun: {{ account.code }}
                    <Lock v-if="isLocked" class="h-4 w-4 text-amber-500" />
                </h1>
                <p class="text-sm text-slate-400 mt-0.5">Update parameter akun atau sesuaikan nama komponen keuangan.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none max-w-3xl">
            <div v-if="isLocked" class="bg-amber-50 px-6 py-3 border-b border-amber-100 flex items-center gap-3">
                <AlertTriangle class="h-4 w-4 text-amber-600" />
                <span class="text-xs font-semibold text-amber-800 uppercase tracking-wider">Hard Guard Active: Mutable Data Blocked</span>
            </div>

            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Identitas Akun</h3>
                <p class="text-xs text-slate-400 mt-1">
                    {{ isLocked ? 'Beberapa bidang dikunci karena memiliki riwayat jurnal.' : 'Lengkapi detail informasi akun di bawah ini.' }}
                </p>
            </div>
            
            <div class="p-6">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Code -->
                        <div class="flex flex-col gap-2">
                            <Label for="code">Kode Akun</Label>
                            <div class="relative">
                                <Input 
                                    id="code" 
                                    v-model="form.code" 
                                    :disabled="isLocked"
                                    class="h-10 bg-white border-slate-200 focus-visible:ring-accent font-mono font-bold disabled:bg-slate-50 disabled:text-slate-400" 
                                />
                                <Lock v-if="isLocked" class="absolute right-3 top-3 h-4 w-4 text-slate-300" />
                            </div>
                            <InputError :message="form.errors.code" />
                        </div>

                        <!-- Type -->
                        <div class="flex flex-col gap-2">
                            <Label for="type">Tipe Akun</Label>
                            <Select v-model="form.type" :disabled="isLocked">
                                <SelectTrigger class="h-10 bg-white border-slate-200 disabled:bg-slate-50 disabled:text-slate-400">
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
                        <Input 
                            id="name" 
                            v-model="form.name" 
                            class="h-10 bg-white border-slate-200 focus-visible:ring-accent font-bold" 
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Normal Balance -->
                        <div class="flex flex-col gap-2">
                            <Label for="balance_type">Saldo Normal</Label>
                            <Select v-model="form.balance_type" :disabled="isLocked">
                                <SelectTrigger class="h-10 bg-white border-slate-200 disabled:bg-slate-50 disabled:text-slate-400">
                                    <SelectValue placeholder="Pilih Saldo" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="debit">DEBIT</SelectItem>
                                    <SelectItem value="credit">KREDIT</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex flex-col gap-2">
                            <Label for="is_active">Status Aktif</Label>
                            <div class="flex items-center h-10 px-3 rounded-md border border-slate-200 bg-white">
                                <Switch 
                                    id="is_active" 
                                    :checked="form.is_active" 
                                    @update:checked="(val: boolean) => form.is_active = val"
                                />
                                <span class="ml-3 text-xs font-semibold text-slate-500 italic">{{ form.is_active ? 'Akun Aktif' : 'Nonaktif' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <Button as-child variant="ghost" class="h-10 px-6 text-slate-500 font-bold hover:bg-slate-100">
                            <Link href="/accounting/accounts">Batal</Link>
                        </Button>
                        <Button 
                            type="submit" 
                            :disabled="form.processing"
                            class="h-10 px-8 bg-accent hover:bg-accent/90 text-white font-bold transition-all gap-2"
                        >
                            <Save class="h-4 w-4" />
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
