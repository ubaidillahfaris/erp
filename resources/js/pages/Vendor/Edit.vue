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

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#FDFDFD] min-h-[calc(100vh-64px)] overflow-y-auto font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <PageHeader 
            title="Edit Vendor" 
            :description="`Update informasi rekanan ID: #${vendor.id}`"
            backHref="/vendors" 
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
            
            <!-- LEFT: FORM -->
            <div class="lg:col-span-5 bg-white rounded-md border border-border/60 h-fit overflow-hidden">
                <SectionHeader 
                    title="Detail Data Rekanan" 
                    class="p-5 border-b border-border/40" 
                />
                
                <form @submit.prevent="submit" class="p-6 flex flex-col gap-5">
                    <div class="flex flex-col gap-2">
                        <Label class="text-[12px] font-medium text-foreground px-0.5">Nama Vendor / Supplier</Label>
                        <Input v-model="form.nama" required placeholder="Nama Perusahaan" class="h-10 rounded-md border-border/40 shadow-none focus-visible:ring-1 focus-visible:ring-primary/50 font-medium" />
                        <div v-if="form.errors.nama" class="text-xs text-destructive">{{ form.errors.nama }}</div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label class="text-[12px] font-medium text-foreground px-0.5">Alamat Lengkap</Label>
                        <Textarea v-model="form.alamat" rows="2" placeholder="Alamat Operasional..." class="rounded-md border-border/40 shadow-none focus-visible:ring-1 focus-visible:ring-primary/50 resize-none p-3 text-[13px]" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label class="text-[12px] font-medium text-foreground px-0.5">Telepon</Label>
                            <Input v-model="form.telepon" placeholder="Nomor Telepon" class="h-10 rounded-md border-border/40 shadow-none focus-visible:ring-1 focus-visible:ring-primary/50 text-[13px]" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <Label class="text-[12px] font-medium text-foreground px-0.5">Email</Label>
                            <Input v-model="form.email" type="email" placeholder="Email (Opsional)" class="h-10 rounded-md border-border/40 shadow-none focus-visible:ring-1 focus-visible:ring-primary/50 text-[13px]" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label class="text-[12px] font-medium text-foreground px-0.5">Keterangan</Label>
                        <Textarea v-model="form.keterangan" rows="2" placeholder="Catatan tambahan..." class="rounded-md border-border/40 shadow-none focus-visible:ring-1 focus-visible:ring-primary/50 resize-none p-3 text-[13px]" />
                    </div>

                    <div class="mt-4 pt-5 border-t border-border/40">
                        <Button type="submit" :disabled="form.processing" class="w-full h-11 text-[13px] font-medium bg-primary text-white rounded-md shadow-none gap-2 hover:bg-primary/90 transition-all border-none">
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
                
                <div class="h-[550px] w-full rounded-md overflow-hidden border border-border/40 shadow-none relative z-0">
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
