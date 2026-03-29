<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Plus, MoreHorizontal, User as UserIcon, 
    Mail, Calendar, Shield, Trash2, Edit2, Loader2 
} from 'lucide-vue-next';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Button } from '@/components/ui/button';
import { 
    Dialog, DialogContent, DialogDescription, 
    DialogFooter, DialogHeader, DialogTitle
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { 
    Select, SelectContent, SelectItem, 
    SelectTrigger, SelectValue 
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { 
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, 
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import { store, update, destroy as destroyUserRoute } from '@/routes/users';
import type { BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';

interface UserData {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const props = defineProps<{
    users: UserData[];
    roles: string[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'User Management', href: '/settings/users' },
];

const isDialogOpen = ref(false);
const isEditing = ref(false);
const editingUserId = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
});

const openCreateDialog = () => {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
};

const openEditDialog = (user: UserData) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    isDialogOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingUserId.value) {
        form.put(update(editingUserId.value).url, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('User berhasil diperbarui');
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('User berhasil ditambahkan');
            },
        });
    }
};

import { useConfirm } from '@/composables/useConfirm';

const { confirmDialog } = useConfirm();

const deleteUser = async (id: number) => {
    if (await confirmDialog('Hapus User?', 'Apakah Anda yakin ingin menghapus user ini? Akses loginnya akan segera dicabut.')) {
        form.delete(destroyUserRoute(id).url, {
            onSuccess: () => toast.success('User berhasil dihapus'),
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="User Management" />

        <SettingsLayout>
            <div class="space-y-6">
                <PageHeader 
                    title="User Management" 
                    description="Kelola pengguna aplikasi dan role mereka" 
                    :count="users.length"
                >
                    <template #actions>
                        <Button primary @click="openCreateDialog">
                            <Plus class="h-4 w-4" />
                            Tambah User
                        </Button>
                    </template>
                </PageHeader>

                <div class="rounded-md border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>User</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Dibuat Pada</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users" :key="user.id">
                                <TableCell>
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ user.name }}</span>
                                        <span class="text-sm text-muted-foreground">{{ user.email }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="user.role === 'superadmin' ? 'default' : 'secondary'">
                                        {{ user.role }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ user.created_at }}
                                </TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuLabel>Aksi</DropdownMenuLabel>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem @click="openEditDialog(user)">
                                                <Edit2 class="mr-2 h-4 w-4" /> Edit
                                            </DropdownMenuItem>
                                            <DropdownMenuItem 
                                                class="text-destructive focus:text-destructive" 
                                                @click="deleteUser(user.id)"
                                            >
                                                <Trash2 class="mr-2 h-4 w-4" /> Hapus
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </SettingsLayout>

        <!-- Upsert Dialog -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[425px]">
                <form @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edit User' : 'Tambah User' }}</DialogTitle>
                        <DialogDescription>
                            {{ isEditing ? 'Perbarui informasi user di bawah ini.' : 'Isi form di bawah ini untuk menambahkan user baru.' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name">Nama</Label>
                            <Input id="name" v-model="form.name" placeholder="John Doe" />
                            <span v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="john@example.com" />
                            <span v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="role">Role</Label>
                            <Select v-model="form.role">
                                <SelectTrigger id="role">
                                    <SelectValue placeholder="Pilih Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="role in roles" :key="role" :value="role">
                                        {{ role }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</span>
                        </div>
                        <div class="grid gap-2">
                            <Label for="password">{{ isEditing ? 'Password (Kosongkan jika tidak diubah)' : 'Password' }}</Label>
                            <Input id="password" v-model="form.password" type="password" />
                            <span v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</span>
                        </div>
                        <div class="grid gap-2" v-if="!isEditing || form.password">
                            <Label for="password_confirmation">Konfirmasi Password</Label>
                            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isDialogOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
