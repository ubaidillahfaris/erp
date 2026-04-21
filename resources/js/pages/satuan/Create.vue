<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { index as satuanIndex, store as satuanStore } from '@/actions/App/Http/Controllers/SatuanController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    allSatuans: Array<{
        id: number;
        nama: string;
        simbol: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Satuan Barang', href: satuanIndex().url },
    { title: 'Tambah Satuan', href: '#' },
];

const form = useForm({
    nama: '',
    simbol: '',
    deskripsi: '',
    add_another: false,
    conversions: [] as Array<{ to_satuan_id: string | number, rasio: number }>
});

watch(() => form.nama, (newNama) => {
    if (!form.simbol || form.simbol === oldNama.substring(0, 3).toLowerCase()) {
        form.simbol = newNama.substring(0, 3).toLowerCase();
    }
    oldNama = newNama;
});

let oldNama = '';

const addConversion = () => {
    form.conversions.push({ to_satuan_id: '', rasio: 1 });
};

const removeConversion = (index: number) => {
    form.conversions.splice(index, 1);
};

const submit = (addAnother = false) => {
    form.add_another = addAnother;
    form.post(satuanStore().url, {
        onSuccess: () => {
            if (addAnother) {
                form.reset();
                document.getElementById('nama')?.focus();
            }
        }
    });
};
</script>

<template>
<Head title="Tambah Satuan Barang" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="satuanIndex().url">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Tambah Satuan Barang</h1>
                <p class="text-muted-foreground">Tambahkan satuan barang baru untuk sistem warung (misal: Kilogram,
                    Pcs).</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Informasi Satuan</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Lengkapi detail informasi form di bawah.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit(false)" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="nama">Nama Satuan</Label>
                            <Input id="nama" v-model="form.nama" placeholder="Contoh: Kilogram" required />
                            <InputError :message="form.errors.nama" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="simbol">Simbol</Label>
                            <Input id="simbol" v-model="form.simbol" placeholder="Contoh: kg" required />
                            <InputError :message="form.errors.simbol" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="deskripsi">Deskripsi (Optional)</Label>
                        <textarea id="deskripsi" v-model="form.deskripsi"
                            class="flex min-h-[80px] w-full rounded-xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Keterangan tambahan..."></textarea>
                        <InputError :message="form.errors.deskripsi" />
                    </div>

                    <!-- Unit Conversions -->
                    <div class="flex flex-col gap-4 border-t pt-6 mt-4">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <h3 class="text-lg font-medium">Satuan Persamaan (Konversi)</h3>
                                <p class="text-sm text-muted-foreground">Misal: 1 {{ form.simbol || 'Unit' }} = 12 Pcs</p>
                            </div>
                            <Button type="button" variant="outline" size="sm" @click="addConversion">
                                <Plus class="mr-2 h-4 w-4" />
                                Tambah Persamaan
                            </Button>
                        </div>

                        <div v-if="form.conversions.length > 0" class="border rounded-xl overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead width="50" class="text-center">1 {{ form.simbol }}</TableHead>
                                        <TableHead width="50" class="text-center">=</TableHead>
                                        <TableHead width="200">Jumlah di Satuan Dasar</TableHead>
                                        <TableHead>Satuan Pembanding</TableHead>
                                        <TableHead width="50"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="(conv, idx) in form.conversions" :key="idx">
                                        <TableCell class="text-center font-medium">1</TableCell>
                                        <TableCell class="text-center font-bold text-lg">=</TableCell>
                                        <TableCell>
                                            <Input type="number" step="0.0001" v-model="conv.rasio" required />
                                        </TableCell>
                                        <TableCell>
                                            <CreatableSelect 
                                                v-model="conv.to_satuan_id" 
                                                :options="allSatuans" 
                                                hide-label 
                                                hide-error
                                                placeholder="Pilih Satuan"
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Button type="button" variant="ghost" size="icon" @click="removeConversion(idx)">
                                                <Trash2 class="h-4 w-4 text-destructive" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div v-else class="text-center py-6 border border-dashed rounded-xl bg-muted/30">
                            <p class="text-sm text-muted-foreground italic">Belum ada konversi satuan yang didefinisikan.</p>
                        </div>
                        <InputError :message="form.errors.conversions" />
                    </div>

                    <FormActionButtons :processing="form.processing" show-add-another @save="submit(false)"
                        @save-and-add-another="submit(true)" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
