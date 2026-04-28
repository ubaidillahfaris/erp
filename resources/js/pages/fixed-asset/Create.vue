<script setup lang="ts">
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Save, ArrowLeft, Building2, Calendar, Wallet, Layers, Clock, ShieldCheck } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import FormActionButtons from '@/components/FormActionButtons.vue';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    accounts: any[];
    asset_accounts: any[];
    expense_accounts: any[];
}>();

const form = useForm({
    name: '',
    description: '',
    category: '',
    acquisition_date: new Date().toISOString().split('T')[0],
    acquisition_cost: 0,
    useful_life_months: 48,
    salvage_value: 0,
    asset_account_id: '',
    depreciation_account_id: '',
    expense_account_id: '',
});

// Format currency for display
const costDisplay = ref('');
const salvageDisplay = ref('');

const updateCost = (val: string) => {
    const numeric = parseInt(val.replace(/\D/g, '')) || 0;
    form.acquisition_cost = numeric * 100; // Store as cents
    costDisplay.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(numeric);
};

const updateSalvage = (val: string) => {
    const numeric = parseInt(val.replace(/\D/g, '')) || 0;
    form.salvage_value = numeric * 100; // Store as cents
    salvageDisplay.value = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(numeric);
};

const monthlyDepreciation = computed(() => {
    if (form.useful_life_months <= 0) return 0;
    const depreciable = form.acquisition_cost - form.salvage_value;
    return Math.floor(depreciable / form.useful_life_months);
});

const submit = () => {
    form.post('/fixed-assets', {
        preserveScroll: true,
    });
};
</script>

<template>
<Head title="Tambah Aset Tetap" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6">
    <PageHeader title="Register New Asset" description="Tambahkan aset tetap baru ke dalam sistem"
        back-href="/fixed-assets" />

    <form @submit.prevent="submit" class="w-full  mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left Column: Basic Info -->
        <div class="md:col-span-2 flex flex-col gap-6">
            <Card class="border-none shadow-sm overflow-hidden">
                <CardHeader class="bg-white border-b pb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-md bg-peach-50 text-peach-500">
                            <Building2 class="h-4 w-4" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-bold">Informasi Umum</CardTitle>
                            <CardDescription class="text-[11px]">Detail identitas aset</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-6 grid grid-cols-2 gap-4">
                    <div class="col-span-2 space-y-2">
                        <Label for="name" class="text-[11px] font-bold uppercase tracking-wider">Nama Aset</Label>
                        <Input id="name" v-model="form.name" placeholder="Contoh: Laptop MacBook Pro M3" required />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="category" class="text-[11px] font-bold uppercase tracking-wider">Kategori</Label>
                        <Select v-model="form.category" required>
                            <SelectTrigger id="category">
                                <SelectValue placeholder="Pilih kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="Peralatan">Peralatan Kantor</SelectItem>
                                <SelectItem value="Mesin">Mesin Produksi</SelectItem>
                                <SelectItem value="Kendaraan">Kendaraan</SelectItem>
                                <SelectItem value="Bangunan">Bangunan</SelectItem>
                                <SelectItem value="Lainnya">Lain-lain</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.category" class="text-xs text-destructive">{{ form.errors.category }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="acquisition_date" class="text-[11px] font-bold uppercase tracking-wider">Tanggal
                            Perolehan</Label>
                        <div class="relative">
                            <Calendar class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input id="acquisition_date" type="date" v-model="form.acquisition_date" class="pl-10"
                                required />
                        </div>
                        <p v-if="form.errors.acquisition_date" class="text-xs text-destructive">{{
                            form.errors.acquisition_date }}</p>
                    </div>

                    <div class="col-span-2 space-y-2">
                        <Label for="description" class="text-[11px] font-bold uppercase tracking-wider">Catatan /
                            Spesifikasi</Label>
                        <Textarea id="description" v-model="form.description"
                            placeholder="Serial number, kondisi awal, dll..." rows="3" />
                    </div>
                </CardContent>
            </Card>

            <Card class="border-none shadow-sm overflow-hidden">
                <CardHeader class="bg-white border-b pb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-md bg-blue-50 text-blue-500">
                            <Wallet class="h-4 w-4" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-bold">Nilai & Masa Manfaat</CardTitle>
                            <CardDescription class="text-[11px]">Parameter perhitungan penyusutan</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-6 grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="acquisition_cost" class="text-[11px] font-bold uppercase tracking-wider">Harga
                            Perolehan</Label>
                        <Input :value="costDisplay" @input="updateCost($event.target.value)" placeholder="Rp 0"
                            required />
                        <p v-if="form.errors.acquisition_cost" class="text-xs text-destructive">{{
                            form.errors.acquisition_cost }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="salvage_value" class="text-[11px] font-bold uppercase tracking-wider">Nilai Residu
                            (Salvage)</Label>
                        <Input :value="salvageDisplay" @input="updateSalvage($event.target.value)" placeholder="Rp 0"
                            required />
                        <p v-if="form.errors.salvage_value" class="text-xs text-destructive">{{
                            form.errors.salvage_value }}</p>
                    </div>

                    <div class="space-y-4 col-span-2">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label class="text-[11px] font-bold uppercase tracking-wider">Metode / Tarif (Tahunan)</Label>
                                <Select :model-value="String(form.useful_life_months)" @update:model-value="(val) => val && (form.useful_life_months = parseInt(String(val)))">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pilih tarif penyusutan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="12">1 Tahun (Tarif 100%)</SelectItem>
                                        <SelectItem value="24">2 Tahun (Tarif 50%)</SelectItem>
                                        <SelectItem value="36">3 Tahun (Tarif 33.3%)</SelectItem>
                                        <SelectItem value="48">4 Tahun (Tarif 25%)</SelectItem>
                                        <SelectItem value="60">5 Tahun (Tarif 20%)</SelectItem>
                                        <SelectItem value="96">8 Tahun (Tarif 12.5%)</SelectItem>
                                        <SelectItem value="120">10 Tahun (Tarif 10%)</SelectItem>
                                        <SelectItem value="240">20 Tahun (Tarif 5%)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-2">
                                <Label for="useful_life" class="text-[11px] font-bold uppercase tracking-wider">Masa Manfaat (Bulan)</Label>
                                <div class="relative">
                                    <Clock class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input id="useful_life" type="number" v-model="form.useful_life_months" class="pl-10" required />
                                </div>
                            </div>
                        </div>
                        <p v-if="form.errors.useful_life_months" class="text-xs text-destructive">{{ form.errors.useful_life_months }}</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg flex flex-col justify-center border border-dashed">
                        <span class="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Estimasi
                            Penyusutan / Bulan</span>
                        <span class="text-lg font-black text-foreground">
                            {{ new Intl.NumberFormat('id-ID', {
                                style: 'currency', currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(monthlyDepreciation / 100) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Right Column: Accounting -->
        <div class="flex flex-col gap-6">
            <Card class="border-none shadow-sm overflow-hidden">
                <CardHeader class="bg-white border-b pb-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-md bg-emerald-50 text-emerald-500">
                            <Layers class="h-4 w-4" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-bold">Pemetaan Akun</CardTitle>
                            <CardDescription class="text-[11px]">Integrasi buku besar (GL)</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-6 space-y-4">
                    <div class="space-y-2">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Akun Aset</Label>
                        <Select v-model="form.asset_account_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun aset" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="acc in asset_accounts" :key="acc.id" :value="String(acc.id)">
                                    {{ acc.code }} - {{ acc.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Akun Akumulasi
                            Penyusutan</Label>
                        <Select v-model="form.depreciation_account_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun akumulasi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="acc in asset_accounts" :key="acc.id" :value="String(acc.id)">
                                    {{ acc.code }} - {{ acc.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label class="text-[10px] font-bold uppercase text-muted-foreground">Akun Beban
                            Penyusutan</Label>
                        <Select v-model="form.expense_account_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih akun beban" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="acc in expense_accounts" :key="acc.id" :value="String(acc.id)">
                                    {{ acc.code }} - {{ acc.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <div class="bg-peach-50 border border-peach-100 p-4 rounded-xl flex items-start gap-3">
                <ShieldCheck class="h-5 w-5 text-peach-500 shrink-0 mt-0.5" />
                <p class="text-[11px] text-peach-700 leading-relaxed font-medium">
                    Sistem akan otomatis menghasilkan jadwal penyusutan selama <strong>{{ form.useful_life_months }}
                        bulan</strong> ke depan berdasarkan metode garis lurus.
                </p>
            </div>

            <div class="mt-auto">
                <FormActionButtons :processing="form.processing" @cancel="router.get('/fixed-assets')" save-label="Register Asset" />
            </div>
        </div>
    </form>
</div>
</template>

<style scoped>
.bg-peach-50 {
    background-color: #fff5f0;
}

.text-peach-500 {
    color: #ff6b35;
}

.text-peach-700 {
    color: #843b1d;
}

.border-peach-100 {
    border-color: #fee5d9;
}
</style>
