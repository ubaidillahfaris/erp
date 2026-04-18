<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Save, User, Phone, Mail, MapPin, Tag, Activity } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SectionHeader from '@/components/SectionHeader.vue';
import PageHeader from '@/components/PageHeader.vue';
import type { BreadcrumbItem } from '@/types';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    customer: {
        id: number;
        name: string;
        phone: string;
        email: string;
        address: string;
        customer_type_id: number;
        customer_status_id: number;
    };
    customerTypes: { id: number; name: string }[];
    customerStatuses: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Master Customer', href: '/customers' },
    { title: 'Edit Customer', href: `/customers/${props.customer.id}/edit` },
];

const form = useForm({
    name: props.customer.name,
    phone: props.customer.phone || '',
    email: props.customer.email || '',
    address: props.customer.address || '',
    customer_type_id: props.customer.customer_type_id,
    customer_status_id: props.customer.customer_status_id,
});

const submit = () => {
    form.put(`/customers/${props.customer.id}`, {
        onSuccess: () => {},
    });
};
</script>

<template>
    <Head title="Edit Customer" />

    <div class="px-6 py-8 flex flex-col gap-6 bg-slate-50 min-h-[calc(100vh-64px)] overflow-y-auto font-sans">
        <!-- ====== PAGE HEADER ====== -->
        <PageHeader 
            title="Edit Customer" 
            :description="`Update informasi pelanggan ID: #${customer.id}`"
            backHref="/customers" 
        />

        <!-- ====== CONTENT AREA ====== -->
        <div class="max-w-7xl mx-auto w-full">
            
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <SectionHeader 
                    title="Detail Data Pelanggan" 
                    class="px-5 py-4 border-b border-slate-100" 
                />
                
                <form @submit.prevent="submit" class="p-6 flex flex-col gap-6">
                    <!-- Basic Info Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2 md:col-span-2">
                            <Label for="name" class="text-sm font-medium text-foreground px-0.5">Nama Lengkap</Label>
                            <Input id="name" v-model="form.name" required placeholder="Nama Pelanggan" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 font-medium" :aria-invalid="!!form.errors.name" />
                            <div v-if="form.errors.name" class="text-xs text-destructive flex items-center gap-1 mt-1">
                                ⚠️ {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="phone" class="text-sm font-medium text-foreground px-0.5">Nomor Telepon</Label>
                            <Input id="phone" v-model="form.phone" placeholder="Contoh: 08123456789" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm" :aria-invalid="!!form.errors.phone" />
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="email" class="text-sm font-medium text-foreground px-0.5">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="Email (Opsional)" class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm" :aria-invalid="!!form.errors.email" />
                        </div>
                    </div>

                    <!-- Category & Status Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-2">
                            <Label for="customer_type_id" class="text-sm font-medium text-foreground px-0.5">Tipe Customer</Label>
                            <Select :model-value="String(form.customer_type_id || '')" @update:model-value="(val) => form.customer_type_id = Number(val)">
                                <SelectTrigger class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm">
                                    <SelectValue placeholder="Pilih Tipe Customer" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in customerTypes" :key="type.id" :value="String(type.id)">
                                        {{ type.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.customer_type_id" class="text-xs text-destructive flex items-center gap-1 mt-1">
                                ⚠️ {{ form.errors.customer_type_id }}
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="customer_status_id" class="text-sm font-medium text-foreground px-0.5">Status Filter</Label>
                            <Select :model-value="String(form.customer_status_id || '')" @update:model-value="(val) => form.customer_status_id = Number(val)">
                                <SelectTrigger class="h-10 rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 text-sm">
                                    <SelectValue placeholder="Pilih Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="status in customerStatuses" :key="status.id" :value="String(status.id)">
                                        {{ status.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.customer_status_id" class="text-xs text-destructive flex items-center gap-1 mt-1">
                                ⚠️ {{ form.errors.customer_status_id }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="address" class="text-sm font-medium text-foreground px-0.5">Alamat Lengkap</Label>
                        <Textarea id="address" v-model="form.address" rows="3" placeholder="Alamat pengiriman atau alamat domisili..." class="rounded-xl border-slate-200 shadow-none focus-visible:ring-2 focus-visible:ring-slate-900/10 resize-none p-3 text-sm" :aria-invalid="!!form.errors.address" />
                    </div>

                    <div class="mt-4 pt-5 border-t border-slate-200">
                        <Button type="submit" :disabled="form.processing" class="w-full h-11 text-sm font-semibold bg-slate-900 text-white rounded-xl gap-2 hover:bg-slate-800 transition-colors">
                            <Save class="h-4 w-4" />
                            Simpan Perubahan
                        </Button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>
