<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/BOMController';
import { Package, Search, Plus, Trash2, FileText, Edit, Eye, BookOpen, MoreHorizontal } from 'lucide-vue-next';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps<{
    boms: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
    };
    filters: {
        search?: string;
        per_page?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bill of Materials', href: '#' },
];

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || String(props.boms.per_page));

watch([search, perPage], debounce(([newSearch, newPerPage]) => {
    router.get('/bom', {
        search: newSearch,
        per_page: newPerPage
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300));

const deleteBom = (id: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus resep BOM ini?')) {
        router.delete(destroy.url({ bom: id }));
    }
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};
</script>

<template>
<Head title="Bill of Materials" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Bill of Materials (BOM)</h1>
                <p class="text-sm text-muted-foreground mt-1">Kelola resep produksi dan kebutuhan bahan baku.</p>
            </div>
            <Link :href="create.url()">
                <Button class="rounded-none">
                    <Plus class="mr-2 h-4 w-4" />
                    Buat BOM Baru
                </Button>
            </Link>
        </div>

        <!-- content -->
        <div class="flex flex-col gap-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-4 border-muted">
                 <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">Daftar Resep</h3>
                <div class="flex items-center gap-4 w-full max-w-sm">
                    <div class="relative w-full">
                        <Search class="absolute left-0 top-3 h-4 w-4 text-muted-foreground/40" />
                        <Input 
                            v-model="search" 
                            placeholder="Cari resep..." 
                            class="pl-7 rounded-none border-t-0 border-x-0 border-b border-muted bg-transparent shadow-none focus-visible:ring-0 focus-visible:border-primary transition-colors h-10" 
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <DataTablePagination :paginator="boms" v-model:perPage="perPage" class="border-b pb-4 border-muted rounded-none" />

                <Table class="border-none">
                    <TableHeader>
                        <TableRow class="hover:bg-transparent border-b border-muted">
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">SKU BOM</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Barang Jadi</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Nama Resep</TableHead>
                            <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Estimasi HPP</TableHead>
                            <TableHead class="h-12 px-0 text-center text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Status</TableHead>
                            <TableHead class="h-12 px-0 w-[80px] text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="bom in boms.data" :key="bom.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                            <TableCell class="px-0 py-4 font-mono text-xs">{{ bom.sku }}</TableCell>
                            <TableCell class="px-0 py-4 font-bold text-sm tracking-tight capitalize">
                                {{ bom.produk.nama }}
                                <div class="text-[10px] text-muted-foreground font-mono uppercase mt-0.5 tracking-tighter">{{ bom.produk.sku }}</div>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-sm">{{ bom.nama || '-' }}</TableCell>
                            <TableCell class="px-0 py-4 text-sm font-bold">
                                {{ formatCurrency(bom.produk?.current_price?.purchase_price || 0) }}
                            </TableCell>
                            <TableCell class="px-0 py-4 text-center">
                                <Badge :variant="bom.is_active ? 'default' : 'secondary'" class="rounded-none text-[10px] px-2 py-0 uppercase">
                                    {{ bom.is_active ? 'Aktif' : 'Non-aktif' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="px-0 py-4 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="rounded-none">
                                        <DropdownMenuLabel class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Aksi Cepat</DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <Link :href="edit.url({ bom: bom.id.toString() })">
                                            <DropdownMenuItem class="cursor-pointer rounded-none">
                                                <Edit class="mr-2 h-4 w-4" />
                                                <span>Edit Resep</span>
                                            </DropdownMenuItem>
                                        </Link>

                                        <DropdownMenuItem
                                            class="cursor-pointer text-destructive focus:bg-destructive/10 focus:text-destructive rounded-none"
                                            @click="deleteBom(bom.id)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            <span>Hapus Resep</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="boms.data.length === 0">
                            <TableCell colspan="6" class="h-32 text-center text-xs text-muted-foreground uppercase tracking-widest">
                                Belum ada resep BOM.
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <DataTablePagination :paginator="boms" v-model:perPage="perPage" class="border-t mt-4 pt-4 border-muted rounded-none" />
            </div>
        </div>
    </div>
</AppLayout>
</template>
