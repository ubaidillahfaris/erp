<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import servicesRoutes from '@/routes/settings/services';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    available_categories: Array<{ id: number, name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Katalog Jasa', href: servicesRoutes.index.url() },
    { title: 'Tambah Jasa', href: '#' },
];

const form = useForm({
    code: '',
    name: '',
    description: '',
    service_category_id: props.available_categories[0]?.id || '',
    is_active: true,
    add_another: false
});

const submit = (addAnother = false) => {
    form.add_another = addAnother;
    form.post(servicesRoutes.store.url(), {
        onSuccess: () => {
            if (addAnother) {
                form.reset();
                document.getElementById('name')?.focus();
            }
        }
    });
};
</script>

<template>
<Head title="Tambah Jasa Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="servicesRoutes.index.url()">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8 shrink-0">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Tambah Jasa Baru</h1>
                <p class="text-sm text-slate-400 mt-0.5 font-medium">Buat kategori layanan jasa dasar Anda di sini.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none ">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none uppercase tracking-tight">Informasi Layanan
                </h3>
                <p class="text-xs text-slate-400 mt-1">
                    Detail harga dan alur kerja bisa diatur setelah jasa dibuat.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit(false)" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="code">ID Layanan / Kode</Label>
                            <Input id="code" v-model="form.code" placeholder="Misal: LND-01" class="font-semibold uppercase"
                                required />
                            <InputError :message="form.errors.code" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="service_category_id" class="font-medium">Kategori Jasa</Label>
                            <Select v-model="form.service_category_id">
                                <SelectTrigger class="w-full font-semibold">
                                    <SelectValue placeholder="Pilih Kategori" />
                                </SelectTrigger>
                                <SelectContent class="rounded-xl border-slate-100 shadow-xl">
                                    <SelectItem v-for="cat in available_categories" :key="cat.id" :value="cat.id.toString()">
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.service_category_id" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="name">Nama Layanan</Label>
                        <Input id="name" v-model="form.name" placeholder="Contoh: Cuci Satuan Premium" class="font-semibold"
                            required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="description">Deskripsi (Optional)</Label>
                        <textarea id="description" v-model="form.description"
                            class="flex min-h-[100px] w-full rounded-xl border border-slate-200 bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 font-medium"
                            placeholder="Jelaskan detail layanan ini..."></textarea>
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="flex items-center space-x-3 py-4 border-t border-slate-50">
                        <input type="checkbox" id="is_active" v-model="form.is_active"
                            class="h-4 w-4 rounded border-slate-200 bg-background accent-primary cursor-pointer" />
                        <div class="grid gap-1.5 leading-none">
                            <label for="is_active" class="text-sm font-semibold leading-none cursor-pointer">
                                Status Aktif
                            </label>
                            <p class="text-xs text-muted-foreground font-medium">
                                Jika tidak aktif, jasa ini tidak akan muncul di POS Kasir.
                            </p>
                        </div>
                    </div>

                    <FormActionButtons :processing="form.processing" show-add-another @save="submit(false)"
                        @save-and-add-another="submit(true)" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
