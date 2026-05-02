<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Users, Phone, Mail, MapPin } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import quickCustomerAction from '@/actions/App/Http/Controllers/QuickCreateCustomerController';

const props = defineProps<{
    open: boolean;
    initialName?: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'created', customer: { id: number; name: string }): void;
}>();

const isOpen = ref(props.open);
const isSubmitting = ref(false);

watch(() => props.open, (newVal) => {
    isOpen.value = newVal;
    if (newVal) {
        resetForm();
        if (props.initialName) {
            formData.value.name = props.initialName;
        }
    }
});

watch(isOpen, (newVal) => {
    emit('update:open', newVal);
});

const formData = ref({
    name: '',
    address: '',
    phone: '',
    email: '',
    errors: {} as Record<string, string[]>
});

const resetForm = () => {
    formData.value = {
        name: '',
        address: '',
        phone: '',
        email: '',
        errors: {}
    };
    isSubmitting.value = false;
};

const submit = async () => {
    try {
        isSubmitting.value = true;
        formData.value.errors = {};

        const response = await axios.post(quickCustomerAction().url, {
            name: formData.value.name,
            address: formData.value.address,
            phone: formData.value.phone,
            email: formData.value.email,
        });

        toast.success(response.data.message || 'Customer added successfully');
        emit('created', response.data.customer);
        isOpen.value = false;
        resetForm();
    } catch (error: any) {
        if (error.response?.status === 422) {
            formData.value.errors = error.response.data.errors;
            toast.error('Gagal menyimpan. Silakan periksa isian form.');
        } else {
            toast.error('Terjadi kesalahan yang tidak diketahui.');
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
<Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[450px] rounded-3xl border-none shadow-2xl overflow-hidden p-0">
        <div class="bg-primary/5 p-6 border-b border-primary/10">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2 text-primary">
                    <Users class="h-5 w-5" />
                    Tambah Pelanggan Cepat
                </DialogTitle>
                <DialogDescription class="text-xs font-medium text-slate-500">
                    Buat profil pelanggan baru sekarang untuk langsung digunakan di transaksi ini.
                </DialogDescription>
            </DialogHeader>
        </div>

        <div class="p-6">
            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="grid gap-2">
                    <Label for="quick-customer-nama"
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                        Nama Pelanggan <span class="text-destructive">*</span>
                    </Label>
                    <Input id="quick-customer-nama" v-model="formData.name" placeholder="Nama Lengkap"
                        class="h-11 rounded-xl bg-slate-50 border-slate-200 focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all font-semibold"
                        :class="{ 'border-destructive': formData.errors.name }" required />
                    <p v-if="formData.errors.name" class="text-[10px] font-medium text-destructive">{{
                        formData.errors.name[0] }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="quick-customer-telepon"
                            class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            Telepon
                        </Label>
                        <div class="relative">
                            <Phone class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input id="quick-customer-telepon" v-model="formData.phone" placeholder="Nomor HP"
                                class="h-11 pl-10 rounded-xl bg-slate-50 border-slate-200 focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all font-medium" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="quick-customer-email"
                            class="text-[10px] font-bold uppercase tracking-widest text-slate-400">
                            Email
                        </Label>
                        <div class="relative">
                            <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <Input id="quick-customer-email" v-model="formData.email" type="email"
                                placeholder="Alamat Email"
                                class="h-11 pl-10 rounded-xl bg-slate-50 border-slate-200 focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all font-medium" />
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-customer-alamat"
                        class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Alamat</Label>
                    <div class="relative">
                        <MapPin class="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
                        <Textarea id="quick-customer-alamat" v-model="formData.address"
                            placeholder="Alamat lengkap pelanggan..." rows="3"
                            class="pl-10 rounded-xl bg-slate-50 border-slate-200 focus:bg-white focus:ring-4 focus:ring-primary/5 transition-all font-medium resize-none" />
                    </div>
                </div>
            </form>
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
            <Button variant="ghost" @click="isOpen = false" :disabled="isSubmitting"
                class="h-11 px-6 rounded-xl font-bold uppercase tracking-widest text-[10px]">
                Batal
            </Button>
            <Button @click="submit" :disabled="!formData.name || isSubmitting"
                class="h-11 px-8 rounded-xl bg-primary hover:bg-primary/90 text-primary-foreground font-bold uppercase tracking-widest text-[10px] shadow-lg shadow-primary/20 transition-all active:scale-[0.98]">
                {{ isSubmitting ? 'Menyimpan...' : 'Simpan Pelanggan' }}
            </Button>
        </div>
    </DialogContent>
</Dialog>
</template>
