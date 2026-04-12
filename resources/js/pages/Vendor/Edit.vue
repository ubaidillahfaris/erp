<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import Map from '@/components/Map.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { BreadcrumbItem } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    vendor: {
        id: number;
        nama: string;
        alamat: string;
        latitude: number | null;
        longitude: number | null;
        telepon: string;
        email: string;
        keterangan: string;
    }
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Vendor', href: '/vendors' },
    { title: 'Edit Vendor', href: `/vendors/${props.vendor.id}/edit` },
];

const form = useForm({
    nama: props.vendor.nama,
    alamat: props.vendor.alamat,
    latitude: props.vendor.latitude ?? undefined,
    longitude: props.vendor.longitude ?? undefined,
    telepon: props.vendor.telepon,
    email: props.vendor.email,
    keterangan: props.vendor.keterangan || '',
});

const submit = () => {
    form.put(`/vendors/${props.vendor.id}`, {
        onSuccess: () => {},
    });
};

const updateLocation = (loc: { lat: number, lng: number }) => {
    form.latitude = loc.lat;
    form.longitude = loc.lng;
};
</script>

<template>
    <Head title="Edit Vendor" />

    <div class="px-6 py-8 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] overflow-y-auto font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <PageHeader 
            title="Edit Vendor" 
            :description="`Update informasi rekanan ID: #${vendor.id}`"
            backHref="/vendors" 
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- LEFT: FORM -->
            <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 overflow-hidden">
                <SectionHeader 
                    title="Detail Data Rekanan" 
                    class="px-5 py-4 border-b border-slate-100" 
                />
                
                <form @submit.prevent="submit" class="p-6 flex flex-col gap-4">
                    <div class="flex flex-col gap-2">
                        <Label for="nama" class="text-sm font-medium text-foreground px-0.5">Nama Vendor / Supplier</Label>
                        <Input id="nama" v-model="form.nama" required placeholder="Nama Perusahaan" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 font-medium" :aria-invalid="!!form.errors.nama" />
                        <div v-if="form.errors.nama" class="text-xs text-destructive flex items-center gap-1 mt-1">
                            ⚠️ {{ form.errors.nama }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="alamat" class="text-sm font-medium text-foreground px-0.5">Alamat Lengkap</Label>
                        <Textarea id="alamat" v-model="form.alamat" rows="2" placeholder="Alamat Operasional..." class="rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 resize-none p-3 text-sm" :aria-invalid="!!form.errors.alamat" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="telepon" class="text-sm font-medium text-foreground px-0.5">Telepon</Label>
                            <Input id="telepon" v-model="form.telepon" placeholder="Nomor Telepon" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm" :aria-invalid="!!form.errors.telepon" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label for="email" class="text-sm font-medium text-foreground px-0.5">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="Email (Opsional)" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm" :aria-invalid="!!form.errors.email" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="keterangan" class="text-sm font-medium text-foreground px-0.5">Keterangan</Label>
                        <Textarea id="keterangan" v-model="form.keterangan" rows="2" placeholder="Catatan tambahan..." class="rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 resize-none p-3 text-sm" />
                    </div>

                    <div class="mt-4 pt-5 border-t border-slate-200">
                        <Button type="submit" :disabled="form.processing" class="w-full h-10 text-sm font-semibold bg-slate-900 text-white rounded-lg gap-2 hover:bg-slate-800 transition-colors">
                            <Save class="h-4 w-4" />
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </div>

            <!-- RIGHT: MAP PICKER -->
            <div class="lg:col-span-7 flex flex-col gap-6 overflow-hidden">
                <SectionHeader 
                    title="Titik Koordinat Vendor"
                    description="Klik atau geser marker untuk menyesuaikan lokasi."
                    class="px-1"
                />
                
                <div class="h-[550px] w-full rounded-xl overflow-hidden border border-slate-200 shadow-none relative z-0">
                    <Map 
                        :center="form.latitude && form.longitude ? [form.latitude, form.longitude] : [-6.200000, 106.816666]" 
                        :zoom="15" 
                        isPicker
                        @update:location="updateLocation"
                    />
                </div>

                <SectionHeader 
                    title="Update Logistik"
                    description="Perubahan lokasi akan berpengaruh pada jangkauan distribusi dan perhitungan logistik di sistem Warung ERP."
                    class="px-1"
                />
            </div>

        </div>
    </div>
</template>
