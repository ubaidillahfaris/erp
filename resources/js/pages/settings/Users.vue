<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import {
    Plus, MoreHorizontal, User as UserIcon,
    Mail, Calendar, Shield, Trash2, Edit2, Loader2,
    Pencil, Filter
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
import DataTable from '@/components/DataTable.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import debounce from 'lodash/debounce';
import { router } from '@inertiajs/vue3';

interface UserData {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
}

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'User Management', href: '/settings/users' },
];

const props = defineProps<{
    users: any;
    roles: string[];
    filters: any;
}>();

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.users.per_page));
const sort = ref(props.filters.sort || 'created_at');
const direction = ref(props.filters.direction || 'desc');
const activeFilters = ref(props.filters.active_filters || {});

const columns = [
    { key: 'user', label: 'User Details', sortKey: 'name' },
    { key: 'role', label: 'Role', sortKey: 'role' },
    { key: 'created_at', label: 'Dibuat Pada', sortKey: 'created_at' },
];

const filterOptions = computed(() => [
    {
        key: 'role',
        label: 'Role',
        options: props.roles.map(r => ({ value: r, label: r.charAt(0).toUpperCase() + r.slice(1) }))
    }
]);

watch([search, perPage, sort, direction, activeFilters], debounce(([s, p, st, d, f]) => {
    router.get('/settings/users', {
        search: s || undefined,
        per_page: p,
        sort: st || undefined,
        direction: st ? (d || 'asc') : undefined,
        active_filters: Object.keys(f).length > 0 ? f : undefined
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300), { deep: true });

const handleSortChange = (payload: { key: string, direction: 'asc' | 'desc' | null }) => {
    sort.value = payload.key || '';
    direction.value = payload.direction || '';
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getRoleBadgeColor = (role: string) => {
    const r = role.toLowerCase();
    if (r.includes('super')) return 'bg-purple-50 text-purple-700 border-purple-100';
    if (r.includes('admin')) return 'bg-blue-50 text-blue-700 border-blue-100';
    if (r.includes('kasir')) return 'bg-emerald-50 text-emerald-700 border-emerald-100';
    if (r.includes('owner')) return 'bg-amber-50 text-amber-700 border-amber-100';
    return 'bg-slate-100 text-slate-600 border-slate-200';
};

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
                toast.success('User updated successfully');
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                isDialogOpen.value = false;
                toast.success('User added successfully');
            },
        });
    }
};

import { useConfirm } from '@/composables/useConfirm';

const { confirmDialog } = useConfirm();

const deleteUser = async (id: number) => {
    if (await confirmDialog('Hapus User?', 'Are you sure you want to delete user ini? Akses loginnya akan segera dicabut.')) {
        form.delete(destroyUserRoute(id).url, {
            onSuccess: () => toast.success('User deleted successfully'),
        });
    }
};
</script>

<template>
<AppLayout :breadcrumbs="breadcrumbItems">

    <Head title="User Management" />

    <SettingsLayout>
        <div class="space-y-6">
            <DataTable :data="users" :columns="columns" v-model:search="search" v-model:perPage="perPage"
                search-placeholder="Search name atau email..." toolbar-title="Daftar Pengguna" :sort="sort"
                :direction="direction as any" :filter-options="filterOptions" v-model:active-filters="activeFilters"
                @sort-change="handleSortChange" :total-count="users.total">
                <template #header-actions>
                    <Button primary @click="openCreateDialog">
                        <Plus class="h-4 w-4" />
                        Tambah User
                    </Button>
                </template>

            <template #cell(user)="{ row }">
                <div class="flex items-center gap-3">
                    <div
                        class="h-9 w-9 shrink-0 rounded-full bg-accent/10 flex items-center justify-center text-accent font-bold text-xs border border-accent/20">
                        {{ row.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-foreground leading-none">{{ row.name }}</p>
                        <p class="text-[11px] text-muted-foreground font-mono mt-1 tracking-tight">{{ row.email }}</p>
                    </div>
                </div>
            </template>

                <template #cell(role)="{ row }">
                    <Badge :class="[
                        getRoleBadgeColor(row.role),
                        'text-[10px] uppercase font-bold px-2 h-5 tracking-widest border-none shadow-none'
                    ]">
                        {{ row.role }}
                    </Badge>
                </template>

                <template #cell(created_at)="{ row }">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[12px] font-medium text-foreground">{{ formatDate(row.created_at) }}</span>
                        <span class="text-[10px] text-muted-foreground uppercase tracking-widest font-bold">Terdaftar</span>
                    </div>
                </template>

                <template #actions="{ row }">
                    <div class="flex justify-end pr-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all">
                                    <MoreHorizontal class="h-4 w-4 text-muted-foreground" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-48 shadow-none rounded-xl p-1.5 border-slate-200">
                                <DropdownMenuLabel class="text-[10px] uppercase font-bold text-muted-foreground px-2.5 py-1.5">
                                    User Actions
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="openEditDialog(row)"
                                    class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px]">
                                    <Edit2 class="h-3.5 w-3.5" /> Edit Info User
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    class="rounded-lg h-9 px-2.5 gap-2.5 cursor-pointer text-[12px] text-destructive focus:text-destructive focus:bg-destructive/5"
                                    @click="deleteUser(row.id)">
                                    <Trash2 class="h-3.5 w-3.5" /> Hapus Akses User
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>
            </DataTable>
        </div>
    </SettingsLayout>

    <!-- Upsert Dialog -->
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
            <form @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Edit User' : 'Tambah User' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEditing ? `Perbarui informasi user di bawah ini.` : `Isi form di bawah ini untuk
                        menambahkan
                        user baru.` }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4 py-4">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
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
                        <Label for="password">{{ isEditing ? 'Password (Kosongkan jika tidak diubah)' : 'Password'
                        }}</Label>
                        <Input id="password" v-model="form.password" type="password" />
                        <span v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password
                        }}</span>
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
