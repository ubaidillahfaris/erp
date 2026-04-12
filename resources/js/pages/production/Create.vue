<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';
import { watch, ref } from 'vue';
import bomIndexAction from '@/actions/App/Http/Controllers/BOMController';
import { store, index } from '@/actions/App/Http/Controllers/ProductionController';
import CreatableSelect from '@/components/CreatableSelect.vue';
import FormActionButtons from '@/components/FormActionButtons.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    boms: Array<any>;
    satuans: Array<any>;
    conversions: Array<any>;
    reproduceFrom?: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produksi', href: index().url },
    { title: 'Mulai Produksi', href: '#' },
];

const form = useForm({
    sku: '',
    tanggal: new Date().toISOString().split('T')[0],
    bom_id: '',
    produk_id: '',
    target_yield: 0,
    items: [] as any[] });

// Server-side search state
const bomOptions = ref<any[]>([]);
const loadingBoms = ref(false);
const nextPage = ref(1);
const hasMore = ref(true);
let searchCurrent = '';
let searchTimeout: any = null;
const targetYieldUnit = ref('');

// Handle Reproduction (Clone) Initialization
if (props.reproduceFrom) {
    // 1. Pre-fill basic form data (except SKU and keep today's date)
    form.bom_id = props.reproduceFrom.bom_id;
    form.produk_id = props.reproduceFrom.produk_id;
    form.target_yield = props.reproduceFrom.target_yield;
    targetYieldUnit.value = props.reproduceFrom.produk?.satuan?.nama || '';

    // 2. Ensure the BOM is in the selection options
    if (props.reproduceFrom.bom) {
        bomOptions.value = [props.reproduceFrom.bom];
    }

    // 3. Pre-fill items with display metadata
    form.items = (props.reproduceFrom.items || []).map((item: any) => ({
        produk_id: item.produk_id,
        satuan_id: item.satuan_id,
        planned_qty: item.planned_qty,
        _produk_nama: item.produk?.nama,
        _satuan_nama: item.satuan?.nama || item.produk?.satuan?.nama
    }));
}

const handleSearchBom = (search: string) => {
    searchCurrent = search;
    if (searchTimeout) clearTimeout(searchTimeout);

    loadingBoms.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(bomIndexAction.index().url, {
                params: { search, page: 1 },
                headers: { 'Accept': 'application/json' }
            });
            // Laravel pagination returns data in .data.data
            bomOptions.value = response.data.data;
            nextPage.value = response.data.current_page + 1;
            hasMore.value = response.data.current_page < response.data.last_page;
        } catch (error) {
            console.error('Gagal mencari BOM:', error);
        } finally {
            loadingBoms.value = false;
        }
    }, 300);
};

const handleLoadMoreBom = async () => {
    if (loadingBoms.value || !hasMore.value) return;

    loadingBoms.value = true;
    try {
        const response = await axios.get(bomIndexAction.index().url, {
            params: { search: searchCurrent, page: nextPage.value },
            headers: { 'Accept': 'application/json' }
        });

        bomOptions.value = [...bomOptions.value, ...response.data.data];
        nextPage.value = response.data.current_page + 1;
        hasMore.value = response.data.current_page < response.data.last_page;
    } catch (error) {
        console.error('Gagal memuat lebih banyak BOM:', error);
    } finally {
        loadingBoms.value = false;
    }
};

// Watch for BOM selection changes to auto-fill items and target yield
watch(() => form.bom_id, (newBomId) => {
    if (!newBomId) return;

    const selectedBom = bomOptions.value.find(b => b.id === newBomId);
    if (selectedBom) {
        form.produk_id = selectedBom.produk_id;
        form.target_yield = selectedBom.expected_yield || 1;
        targetYieldUnit.value = selectedBom.produk?.satuan?.nama || '';

        form.items = (selectedBom.items || []).map((item: any) => ({
            produk_id: item.produk_id,
            satuan_id: item.satuan_id,
            planned_qty: item.jumlah,
            // Provide names for UI display
            _produk_nama: item.produk?.nama,
            _satuan_nama: item.satuan ? item.satuan.nama : item.produk?.satuan?.nama
        }));
    }
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
<Head title="Mulai Produksi Baru" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <div class="flex items-center gap-4">
            <Link :href="index().url">
                <Button variant="outline" size="icon" class="h-8 w-8 border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
            </Link>
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Mulai Produksi</h1>
                <p class="text-sm text-slate-400 mt-0.5">Pilih Resep (BOM) untuk memulai proses produksi.</p>
            </div>
        </div>

        <Card class="border border-slate-200 rounded-xl bg-white shadow-none">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-sm font-semibold text-slate-900 leading-none">Pilih Resep</h3>
                <p class="text-xs text-slate-400 mt-1">
                    Pilih BOM, sistem akan otomatis menyiapkan bahan baku yang dibutuhkan.
                </p>
            </div>
            <div class="p-6">
                <form @submit.prevent="submit" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="sku">No. Referensi (Opsional)</Label>
                            <Input id="sku" v-model="form.sku" placeholder="Auto-generate jika kosong" />
                            <InputError :message="form.errors.sku" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="tanggal">Tanggal Produksi</Label>
                            <Input id="tanggal" type="date" v-model="form.tanggal" required />
                            <InputError :message="form.errors.tanggal" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div class="flex flex-col gap-2">
                            <CreatableSelect v-model="form.bom_id" :options="bomOptions" label="Resep (BOM)"
                                placeholder="Pilih BOM yang akan diproduksi" display-expr="produk.nama" value-expr="id"
                                :error="form.errors.bom_id" :loading="loadingBoms" @search="handleSearchBom"
                                @load-more="handleLoadMoreBom" @focus="handleSearchBom(searchCurrent)" />
                        </div>
                    </div>

                    <div v-if="form.bom_id" class="p-4 border rounded-xl bg-muted/20">
                        <h3 class="font-medium mb-4">Estimasi Kebutuhan Bahan (Planned Qtys)</h3>
                        <div class="space-y-2">
                            <div v-for="(item, idx) in form.items" :key="idx"
                                class="flex justify-between text-sm py-1 border-b last:border-0 border-border">
                                <span>{{ item._produk_nama }}</span>
                                <span class="font-mono">{{ item.planned_qty }} {{ item._satuan_nama }}</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t flex flex-col gap-2">
                            <Label for="target_yield">Target Hasil Jadi</Label>
                            <div class="flex items-center gap-2">
                                <Input id="target_yield" type="number" step="any" v-model="form.target_yield"
                                    class="w-32" lang="en-US" inputmode="decimal" />
                                <span v-if="targetYieldUnit" class="font-medium">{{ targetYieldUnit }}</span>
                                <span class="text-sm text-muted-foreground">(Bisa diubah jika hasil produksi berbeda
                                    dari resep standar)</span>
                            </div>
                            <InputError :message="form.errors.target_yield" />
                        </div>
                    </div>

                    <FormActionButtons :processing="form.processing" show-cancel @cancel="router.visit(index().url)"
                        @save="submit" />
                </form>
            </div>
        </Card>
    </div>
</AppLayout>
</template>
