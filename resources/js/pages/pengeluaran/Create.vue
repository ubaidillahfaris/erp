<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import { index, store } from '@/actions/App/Http/Controllers/PengeluaranController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
const expenseTypes = ref([
    { id: 'Listrik', nama: 'Listrik' },
    { id: 'Air & Galon', nama: 'Air & Galon' },
    { id: 'Internet / WIFI', nama: 'Internet / WIFI' },
    { id: 'Gas', nama: 'Gas' },
    { id: 'Kebersihan', nama: 'Kebersihan (Sabun, Pel, dll)' },
    { id: 'Lain-lain', nama: 'Lain-lain (Tak Terduga)' },
]);

const handleCreateType = (nama: string) => {
    expenseTypes.value.push({ id: nama, nama });
    form.jenis_pengeluaran = nama;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Biaya Operasional', href: index.url() },
    { title: 'Catat Biaya', href: '#' },
];

const form = useForm({
    tanggal: new Date().toISOString().slice(0, 10),
    jenis_pengeluaran: '',
    nama_pengeluaran: '',
    nominal: 0,
    keterangan: '',
    add_another: false });

const submit = (addAnother = false) => {
    form.add_another = addAnother;
    form.post(store.url(), {
        onSuccess: () => {
            if (addAnother) {
                form.reset('nama_pengeluaran', 'nominal', 'keterangan');
                document.getElementById('nama_pengeluaran')?.focus();
            }
        } });
};
</script>

<template>
<Head title="Catat Biaya Operasional" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="index.url()">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Catat Biaya Operasional</h1>
                <p class="text-sm text-slate-400 mt-0.5">Masukkan rincian pengeluaran operasional warung non-bahan baku.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Rincian Pengeluaran</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Lengkapi form di bawah sesuai dengan tagihan atau kuitansi yang dibayarkan.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit(false)" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="tanggal">Tanggal Pengeluaran</Label>
                            <Input id="tanggal" type="date" v-model="form.tanggal" required />
                            <InputError :message="form.errors.tanggal" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <CreatableSelect 
                                v-model="form.jenis_pengeluaran" 
                                :options="expenseTypes" 
                                label="Jenis Pengeluaran"
                                placeholder="Pilih atau Ketik Baru..." 
                                :error="form.errors.jenis_pengeluaran"
                                @create="handleCreateType"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="nama_pengeluaran">Nama Pengeluaran</Label>
                            <Input id="nama_pengeluaran" v-model="form.nama_pengeluaran"
                                placeholder="Contoh: Token Listrik 100rb, atau Refill Galon Aqua" required />
                            <InputError :message="form.errors.nama_pengeluaran" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="nominal">Nominal (Rp)</Label>
                            <Input id="nominal" type="number" v-model="form.nominal" required min="0" />
                            <InputError :message="form.errors.nominal" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="keterangan">Keterangan (Optional)</Label>
                        <textarea id="keterangan" v-model="form.keterangan"
                            class="flex min-h-[80px] w-full rounded-xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Catatan tambahan..."></textarea>
                        <InputError :message="form.errors.keterangan" />
                    </div>

                    <FormActionButtons :processing="form.processing" show-add-another @save="submit(false)"
                        @save-and-add-another="submit(true)" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
