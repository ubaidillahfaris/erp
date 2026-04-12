<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, ArrowLeft } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import { index as satuanIndex, update as satuanUpdate } from '@/actions/App/Http/Controllers/SatuanController';
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
    satuan: {
        id: number;
        nama: string;
        simbol: string;
        deskripsi: string | null;
        conversions: Array<{
            id?: number;
            to_satuan_id: string | number;
            rasio: number;
        }>;
    };
    allSatuans: Array<{
        id: number;
        nama: string;
        simbol: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Satuan Barang', href: satuanIndex().url },
    { title: 'Edit Satuan', href: '#' },
];

const form = useForm({
    nama: props.satuan.nama,
    simbol: props.satuan.simbol,
    deskripsi: props.satuan.deskripsi || '',
    conversions: props.satuan.conversions.map(c => ({
        to_satuan_id: c.to_satuan_id.toString(),
        rasio: Number(c.rasio)
    })) || []
});

let oldNama = props.satuan.nama;

watch(() => form.nama, (newNama) => {
    if (form.simbol === oldNama.substring(0, 3).toLowerCase()) {
        form.simbol = newNama.substring(0, 3).toLowerCase();
    }
    oldNama = newNama;
});

const addConversion = () => {
    form.conversions.push({ to_satuan_id: '', rasio: 1 });
};

const removeConversion = (index: number) => {
    form.conversions.splice(index, 1);
};

const submit = () => {
    form.put(satuanUpdate(props.satuan.id).url);
};
</script>

<template>
<Head title="Edit Satuan Barang" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="satuanIndex().url">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Edit Satuan Barang</h1>
                <p class="text-sm text-slate-400 mt-0.5">Ubah detail satuan barang dalam sistem.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Ubah Informasi Satuan</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Lengkapi detail informasi form di bawah.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
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

                    <FormActionButtons :processing="form.processing" @save="submit" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
