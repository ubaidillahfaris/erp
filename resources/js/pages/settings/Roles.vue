<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    Plus, Shield, ChevronRight, Check, 
    Trash2, Edit2, Loader2, Info
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Button } from '@/components/ui/button';
import { 
    Dialog, DialogContent, DialogDescription, 
    DialogFooter, DialogHeader, DialogTitle
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { 
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { 
    Alert, AlertDescription, AlertTitle 
} from '@/components/ui/alert';
import { store, update, destroy as destroyRoleRoute } from '@/routes/roles';
import type { BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';

interface MenuData {
    id: number;
    name: string;
    permission_name: string | null;
    children?: MenuData[];
}

interface RoleData {
    id: number;
    name: string;
    permissions: string[];
    menu_ids: number[];
}

const props = defineProps<{
    roles: RoleData[];
    menus: MenuData[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Role Management', href: '/settings/roles' },
];

const isDialogOpen = ref(false);
const isEditing = ref(false);
const editingRoleId = ref<number | null>(null);

const form = useForm({
    name: '',
    menu_ids: [] as number[],
});

const openCreateDialog = () => {
    isEditing.value = false;
    editingRoleId.value = null;
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
};

const openEditDialog = (role: RoleData) => {
    isEditing.value = true;
    editingRoleId.value = role.id;
    form.reset();
    form.clearErrors();
    form.name = role.name;
    form.menu_ids = [...role.menu_ids];
    isDialogOpen.value = true;
};

const toggleMenu = (id: number, checked: boolean) => {
    const ids = new Set(form.menu_ids);
    if (checked) {
        ids.add(id);
    } else {
        ids.delete(id);
    }
    form.menu_ids = Array.from(ids);
};

const isChecked = (id: number) => form.menu_ids.includes(id);

const submit = () => {
    if (isEditing.value && editingRoleId.value) {
        form.put(update(editingRoleId.value).url, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('Role berhasil diperbarui');
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('Role berhasil ditambahkan');
            },
        });
    }
};

const deleteRole = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus role ini?')) {
        form.delete(destroyRoleRoute(id).url, {
            onSuccess: () => toast.success('Role berhasil dihapus'),
            onError: (err: Record<string, any>) => {
                if (err.error) toast.error(err.error as string);
            }
        });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Role Management" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Role Management"
                        description="Define user roles and map them to specific application menus."
                    />
                    <Button @click="openCreateDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        Tambah Role
                    </Button>
                </div>

                <Alert>
                    <Info class="h-4 w-4" />
                    <AlertTitle>Informasi Mapping</AlertTitle>
                    <AlertDescription>
                        Mengaktifkan akses menu otomatis memberikan permission Spatie yang sesuai ke role tersebut.
                    </AlertDescription>
                </Alert>

                <div class="rounded-md border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Role</TableHead>
                                <TableHead>Akses Menu</TableHead>
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="role in roles" :key="role.id">
                                <TableCell class="font-medium">{{ role.name }}</TableCell>
                                <TableCell>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="menuId in role.menu_ids.slice(0, 5)" :key="menuId" variant="outline" class="text-[10px]">
                                            {{ menus.find(m => m.id === menuId)?.name || 'Menu #' + menuId }}
                                        </Badge>
                                        <Badge v-if="role.menu_ids.length > 5" variant="outline" class="text-[10px]">
                                            +{{ role.menu_ids.length - 5 }} lainnya
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="ghost" size="icon" @click="openEditDialog(role)">
                                            <Edit2 class="h-4 w-4" />
                                        </Button>
                                        <Button 
                                            v-if="!['superadmin', 'cashier'].includes(role.name)"
                                            variant="ghost" 
                                            size="icon" 
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteRole(role.id)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </SettingsLayout>

        <!-- Role Mapping Dialog -->
        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-[600px] max-h-[80vh] overflow-y-auto">
                <form @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>{{ isEditing ? 'Edit Role & Akses' : 'Tambah Role' }}</DialogTitle>
                        <DialogDescription>
                            Atur izin akses menu untuk role ini.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-6 py-6">
                        <div class="grid gap-2">
                            <Label for="role_name">Nama Role</Label>
                            <Input 
                                id="role_name" 
                                v-model="form.name" 
                                placeholder="Manager" 
                                :disabled="isEditing && ['superadmin', 'cashier'].includes(form.name)"
                            />
                            <span v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-4">
                            <Label>Akses Menu (Permission Matrix)</Label>
                            <div class="rounded-md border p-4 space-y-4">
                                <div v-for="menu in menus" :key="menu.id" class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox 
                                            :id="'menu-' + menu.id" 
                                            :checked="isChecked(menu.id)"
                                            @update:checked="(val) => toggleMenu(menu.id, !!val)"
                                        />
                                        <label 
                                            :for="'menu-' + menu.id"
                                            class="text-sm font-semibold leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            {{ menu.name }}
                                        </label>
                                    </div>

                                    <!-- Nested Children -->
                                    <div v-if="menu.children && menu.children.length > 0" class="ml-6 grid grid-cols-2 gap-2">
                                        <div v-for="child in menu.children" :key="child.id" class="flex items-center space-x-2">
                                            <Checkbox 
                                                :id="'menu-' + child.id" 
                                                :checked="isChecked(child.id)"
                                                @update:checked="(val) => toggleMenu(child.id, !!val)"
                                            />
                                            <label 
                                                :for="'menu-' + child.id"
                                                class="text-sm leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                            >
                                                {{ child.name }}
                                            </label>
                                        </div>
                                    </div>
                                    <Separator class="my-2" v-if="menu !== menus[menus.length-1]" />
                                </div>
                            </div>
                            <span v-if="form.errors.menu_ids" class="text-sm text-destructive">{{ form.errors.menu_ids }}</span>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="isDialogOpen = false">
                            Batal
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Simpan Perubahan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
