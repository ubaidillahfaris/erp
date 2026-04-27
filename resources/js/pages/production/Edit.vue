<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle } from 'lucide-vue-next';
import { update, index } from '@/actions/App/Http/Controllers/ProductionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    production: any;
    units: Array<any>;
    conversions: Array<any>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Productsi', href: index().url },
    { title: 'Selesaikan Productsi', href: '#' },
];

const form = useForm({
    actual_yield: props.production.target_yield, // Default actual to target
    items: props.production.items.map((item: any) => ({
        id: item.id,
        product_id: item.product_id,
        unit_id: item.unit_id,
        planned_qty: item.planned_qty,
        actual_qty: item.planned_qty, // Default actual to planned
        
        // For display
        _product_name: item.product?.name,
        _satuan_name: item.unit ? item.unit.name : item.product?.unit?.name
    })) });

const submit = () => {
    form.put(update({ production: props.production.id }).url);
};
</script>

<template>
<Head title="Selesaikan Productsi" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="outline" size="icon" class="btn-secondary h-8 w-8">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Selesaikan Productsi</h1>
                <p class="text-sm text-slate-400 mt-0.5">{{ props.production.sku }} - {{ props.production.bom?.name }}</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Input Aktual Lapangan</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Masukkan jumlah bahan baku yang riil terpakai dan jumlah hasil jadi sebenarnya (Actual Yield).
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit" class="flex flex-col gap-8">
                    
                    <div class="border rounded-xl p-4 bg-white">
                        <h3 class="font-semibold mb-4 text-lg border-b pb-2">Raw Materials (Actual vs Planned)</h3>
                        <div class="flex flex-col gap-4">
                            <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                <div class="col-span-1">
                                    <Label class="text-muted-foreground">{{ item._product_nama }}</Label>
                                    <div class="text-sm mt-1">Rencana: <span class="font-mono">{{ item.planned_qty }} {{ item._satuan_nama }}</span></div>
                                </div>
                                
                                <div class="col-span-2 flex gap-2 w-full md:max-w-xs">
                                    <div class="flex-1">
                                        <Label>Aktual Terpakai</Label>
                                        <div class="flex items-center gap-2 mt-1">
                                            <Input type="number" step="any" v-model="item.actual_qty" required lang="en-US" inputmode="decimal" />
                                            <span class="text-sm text-muted-foreground">{{ item._satuan_nama }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.items" />
                        </div>
                    </div>

                    <div class="border rounded-xl p-4 bg-primary/5 border-primary/20">
                        <h3 class="font-semibold mb-4 text-lg border-b pb-2">Hasil Aktual (Actual Yield)</h3>
                        <div class="flex flex-col gap-2 max-w-sm">
                            <Label for="actual_yield">Berapa {{ props.production.product?.unit?.name }} {{ props.production.product?.name }} yang berhasil dibuat?</Label>
                            <div class="flex items-center gap-2 mt-1">
                                <Input id="actual_yield" type="number" step="any" v-model="form.actual_yield" required class="text-lg font-bold" lang="en-US" inputmode="decimal" />
                                <span class="text-sm font-medium">{{ props.production.product?.unit?.name }}</span>
                            </div>
                            <p class="text-xs text-muted-foreground">Target resep: {{ props.production.target_yield }} {{ props.production.product?.unit?.name }}</p>
                            <InputError :message="form.errors.actual_yield" />
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end">
                        <Button type="button" variant="outline" @click="router.get(index().url)" class="btn-secondary">Cancel</Button>
                        <Button type="submit" :disabled="form.processing" class="btn-primary">
                            <CheckCircle class="w-4 h-4 mr-2" />
                            Selesaikan & Hitung HPP
                        </Button>
                    </div>
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
