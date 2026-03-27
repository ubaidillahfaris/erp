<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
import { ArrowLeft, CheckCircle } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import { update, index } from '@/actions/App/Http/Controllers/ProductionController';

const props = defineProps<{
    production: any;
    satuans: Array<any>;
    conversions: Array<any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produksi', href: index().url },
    { title: 'Selesaikan Produksi', href: '#' },
];

const form = useForm({
    actual_yield: props.production.target_yield, // Default actual to target
    items: props.production.items.map((item: any) => ({
        id: item.id,
        produk_id: item.produk_id,
        satuan_id: item.satuan_id,
        planned_qty: item.planned_qty,
        actual_qty: item.planned_qty, // Default actual to planned
        
        // For display
        _produk_nama: item.produk?.nama,
        _satuan_nama: item.satuan ? item.satuan.nama : item.produk?.satuan?.nama
    })),
});

const submit = () => {
    form.put(update({ production: props.production.id }).url);
};
</script>

<template>
<Head title="Selesaikan Produksi" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-6">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="ghost" size="icon">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Selesaikan Produksi</h1>
                <p class="text-muted-foreground">{{ props.production.sku }} - {{ props.production.bom?.nama }}</p>
            </div>
        </div>

        <Card class="border-0 rounded-none shadow-none bg-transparent">
            <CardHeader class="px-0">
                <CardTitle>Input Aktual Lapangan</CardTitle>
                <CardDescription>
                    Masukkan jumlah bahan baku yang riil terpakai dan jumlah hasil jadi sebenarnya (Actual Yield).
                </CardDescription>
            </CardHeader>
            <CardContent class="px-0">
                <form @submit.prevent="submit" class="flex flex-col gap-8">
                    
                    <div class="border rounded-md p-4 bg-card">
                        <h3 class="font-semibold mb-4 text-lg border-b pb-2">Bahan Baku (Actual vs Planned)</h3>
                        <div class="flex flex-col gap-4">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                <div class="col-span-1">
                                    <Label class="text-muted-foreground">{{ item._produk_nama }}</Label>
                                    <div class="text-sm mt-1">Rencana: <span class="font-mono">{{ item.planned_qty }} {{ item._satuan_nama }}</span></div>
                                </div>
                                
                                <div class="col-span-2 flex gap-2 w-full md:max-w-xs">
                                    <div class="flex-1">
                                        <Label>Aktual Terpakai</Label>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Input type="number" step="any" v-model="item.actual_qty" required />
                                            <span class="text-sm text-muted-foreground">{{ item._satuan_nama }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.items" />
                        </div>
                    </div>

                    <div class="border rounded-md p-4 bg-primary/5 border-primary/20">
                        <h3 class="font-semibold mb-4 text-lg border-b pb-2">Hasil Aktual (Actual Yield)</h3>
                        <div class="flex flex-col gap-2 max-w-sm">
                            <Label for="actual_yield">Berapa {{ props.production.produk?.satuan?.nama }} {{ props.production.produk?.nama }} yang berhasil dibuat?</Label>
                            <div class="flex items-center gap-2 mt-1">
                                <Input id="actual_yield" type="number" step="any" v-model="form.actual_yield" required class="text-lg font-bold" />
                                <span class="text-sm font-medium">{{ props.production.produk?.satuan?.nama }}</span>
                            </div>
                            <p class="text-xs text-muted-foreground">Target resep: {{ props.production.target_yield }} {{ props.production.produk?.satuan?.nama }}</p>
                            <InputError :message="form.errors.actual_yield" />
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="router.get(index().url)">Batal</Button>
                        <Button type="submit" :disabled="form.processing">
                            <CheckCircle class="w-4 h-4 mr-2" />
                            Selesaikan & Hitung HPP
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</AppLayout>
</template>
