<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { store } from '@/routes/onboarding';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import InputError from '@/components/InputError.vue';

const form = useForm({
    name: '',
    business_type: '',
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <AuthBase
        title="Setup Perusahaan Anda"
        description="Satu langkah lagi untuk mulai mengelola bisnis Anda."
    >
        <Head title="Onboarding" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Nama Perusahaan / Toko</Label>
                    <Input
                        id="name"
                        type="text"
                        v-model="form.name"
                        required
                        autofocus
                        placeholder="Contoh: Laundry Berkah Jaya"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="business_type">Jenis Usaha</Label>
                    <select
                        id="business_type"
                        v-model="form.business_type"
                        required
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled>Pilih Jenis Usaha</option>
                        <option value="retail">Retail / Toko Kelontong</option>
                        <option value="warkop">Warkop / Cafe</option>
                        <option value="laundry">Laundry</option>
                        <option value="bengkel">Bengkel</option>
                        <option value="service">Jasa / Service</option>
                        <option value="other">Lainnya</option>
                    </select>
                    <InputError :message="form.errors.business_type" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :disabled="form.processing"
                >
                    <Spinner v-if="form.processing" />
                    Simpan & Mulai
                </Button>
            </div>
        </form>
    </AuthBase>
</template>
