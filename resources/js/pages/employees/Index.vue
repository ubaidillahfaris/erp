<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus, MoreHorizontal, Eye, Edit2, Trash2,
    Phone, Mail, UserPlus
} from 'lucide-vue-next';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { create, show, edit, destroy } from '@/actions/App/Http/Controllers/EmployeeController';

const props = defineProps<{
    employees: any;
    filters: any;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pegawai', href: '/employees' },
];

const columns = [
    { key: 'employee', label: 'Employee Details' },
    { key: 'position', label: 'Position' },
    { key: 'contact', label: 'Contact Info' },
    { key: 'status', label: 'Status' },
    { key: 'join_date', label: 'Join Date' },
];

const getStatusBadge = (status: string) => {
    return status === 'active'
        ? 'bg-emerald-500/10 text-emerald-600 border-0 shadow-none'
        : 'bg-slate-500/10 text-slate-600 border-0 shadow-none';
};

const getPositionBadge = (position: string) => {
    if (position.includes('Manager')) return 'bg-purple-500/10 text-purple-600 border-0 shadow-none';
    if (position.includes('Kasir')) return 'bg-blue-500/10 text-blue-600 border-0 shadow-none';
    if (position.includes('Cook')) return 'bg-orange-500/10 text-orange-600 border-0 shadow-none';
    return 'bg-slate-500/10 text-slate-600 border-0 shadow-none';
};
</script>

<template>
<Head title="Manajemen Pegawai" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans text-slate-700">
        <PageHeader title="Daftar Pegawai"
            description="Kelola data personal, kontrak, dan akses sistem seluruh staf valee." back-href="/dashboard"
            :count="employees.total" />

        <!-- ====== CONTENT AREA ====== -->
        <div class="w-full max-w-7xl mx-auto">
            <DataTable :data="employees" :columns="columns" search-placeholder="Search name, NIK..."
                toolbar-title="Daftar Pegawai" :total-count="employees.total">
                <template #header-actions>
                    <Link :href="create().url">
                        <Button primary
                            class="rounded-full px-6 font-normal gap-2 shadow-none transition hover:-translate-y-0.5">
                            <UserPlus class="h-4 w-4" />
                            Tambah Pegawai
                        </Button>
                    </Link>
                </template>
                <template #cell(employee)="{ row }">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-9 w-9 shrink-0 rounded-full bg-primary/5 flex items-center justify-center text-primary font-normal text-xs border border-primary/10">
                            {{row.name.split(' ').map((n: string) => n[0]).join('')}}
                        </div>
                        <div class="min-w-0 pr-4">
                            <p class="text-[13px] font-normal text-foreground leading-none">{{ row.name }}</p>
                            <p
                                class="text-[11px] font-normal text-muted-foreground uppercase tracking-widest mt-1.5 opacity-60">
                                {{ row.nik }}</p>
                        </div>
                    </div>
                </template>

                <template #cell(position)="{ row }">
                    <Badge
                        :class="['rounded-full px-2.5 py-0.5 font-normal text-[10px] uppercase tracking-widest border-0 shadow-none', getPositionBadge(row.position)]">
                        {{ row.position }}
                    </Badge>
                </template>

                <template #cell(contact)="{ row }">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-1.5 text-[12px] font-normal text-slate-600">
                            <Phone class="h-3 w-3 text-muted-foreground/50" />
                            {{ row.phone }}
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] font-normal text-muted-foreground">
                            <Mail class="h-3 w-3 text-muted-foreground/50" />
                            {{ row.email }}
                        </div>
                    </div>
                </template>

                <template #cell(status)="{ row }">
                    <Badge
                        :class="['rounded-full px-2.5 py-0.5 font-normal text-[10px] uppercase tracking-widest border-0 shadow-none', getStatusBadge(row.status)]">
                        {{ row.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </template>

                <template #cell(join_date)="{ row }">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[12px] font-normal text-foreground">
                            {{ format(new Date(row.join_date), 'dd MMM yyyy', { locale: id }) }}
                        </span>
                        <span
                            class="text-[10px] text-muted-foreground uppercase tracking-widest font-normal opacity-50">Terdaftar</span>
                    </div>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-end gap-1">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button
                                    class="h-8 w-8 flex items-center justify-center rounded-lg text-black/80 hover:bg-secondary hover:text-foreground transition-all">
                                    <MoreHorizontal class="h-4 w-4" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end"
                                class="rounded-xl p-1.5 w-48 shadow-none border-border/40 font-sans">
                                <DropdownMenuLabel
                                    class="text-[10px] font-normal uppercase tracking-widest text-muted-foreground px-2.5 py-1.5">
                                    Employee Actions</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem @click="router.visit(show(row.id).url)"
                                    class="text-[12px] font-normal h-9 rounded-lg">
                                    <Eye class="h-3.5 w-3.5 mr-2" /> Lihat Detail
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="router.visit(edit(row.id).url)"
                                    class="text-[12px] font-normal h-9 rounded-lg">
                                    <Edit2 class="h-3.5 w-3.5 mr-2" /> Edit Data
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="router.delete(destroy(row.id).url)"
                                    class="text-[12px] font-normal h-9 rounded-lg text-destructive focus:text-destructive focus:bg-destructive/5">
                                    <Trash2 class="h-3.5 w-3.5 mr-2" /> Hapus Pegawai
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</AppLayout>
</template>
