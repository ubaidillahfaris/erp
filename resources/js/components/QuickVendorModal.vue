<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Store, Phone, Mail, MapPin } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import quickVendorAction from '@/actions/App/Http/Controllers/QuickCreateVendorController';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'created', vendor: { id: number; name: string }): void;
}>();

const isOpen = ref(props.open);
const isSubmitting = ref(false);

watch(() => props.open, (newVal) => {
    isOpen.value = newVal;
    if (newVal) resetForm();
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
        
        const response = await axios.post(quickVendorAction().url, {
            name: formData.value.name,
            address: formData.value.address,
            phone: formData.value.phone,
            email: formData.value.email,
        });
        
        toast.success(response.data.message || 'Vendor added successfully');
        emit('created', response.data.vendor);
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
        <DialogContent class="sm:max-w-[450px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Store class="h-5 w-5 text-primary" />
                    Tambah Vendor Cepat
                </DialogTitle>
                <DialogDescription>
                    Buat profil vendor baru sekarang untuk langsung digunakan di form transaksi.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="quick-vendor-nama" class="font-medium">
                        Nama Vendor <span class="text-destructive">*</span>
                    </Label>
                    <Input 
                        id="quick-vendor-nama" 
                        v-model="formData.name" 
                        placeholder="Contoh: PT. Sumber Makmur" 
                        :class="{'border-destructive': formData.errors.name}"
                        required
                    />
                    <p v-if="formData.errors.name" class="text-xs text-destructive">{{ formData.errors.name[0] }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-vendor-telepon" class="flex items-center gap-1">
                        Telepon
                    </Label>
                    <div class="relative">
                        <Phone class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input 
                            id="quick-vendor-telepon" 
                            v-model="formData.phone" 
                            placeholder="Nomor Telp/WA" 
                            class="pl-9"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-vendor-email" class="flex items-center gap-1">
                        Email
                    </Label>
                    <div class="relative">
                        <Mail class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input 
                            id="quick-vendor-email" 
                            v-model="formData.email" 
                            type="email"
                            placeholder="Email Perusahaan" 
                            class="pl-9"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="quick-vendor-alamat">Address</Label>
                    <Textarea 
                        id="quick-vendor-alamat" 
                        v-model="formData.address" 
                        placeholder="Alamat operasional vendor..." 
                        rows="2"
                        class="resize-none"
                    />
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" @click="isOpen = false" :disabled="isSubmitting">
                    Batal
                </Button>
                <Button @click="submit" :disabled="!formData.name || isSubmitting" class="gap-2">
                    Simpan Vendor
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
