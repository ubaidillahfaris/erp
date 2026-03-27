<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, CheckCircle2, History } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    opname: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: '/stock-opname' },
    { title: 'Detail Hasil', href: '#' },
];

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusVariant = (status: string) => {
    switch (status) {
        case 'completed': return 'default';
        case 'draft': return 'secondary';
        default: return 'outline';
    }
};
</script>

<template>
<Head title="Detail Stock Opname" />

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 flex flex-col gap-10">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-6 border-muted rounded-none">
            <div class="flex items-center gap-4">
                <Link href="/stock-opname">
                    <Button variant="ghost" size="icon" class="rounded-none">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Detail Stock Opname</h1>
                    <p class="text-sm text-muted-foreground mt-1">Hasil perbandingan stok fisik dan stok sistem.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Link v-if="opname.status === 'draft'" :href="`/stock-opname/${opname.id}/edit`">
                    <Button class="rounded-none">
                        Lanjutkan Draft
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Vertical Content Stack -->
        <div class="flex flex-col gap-10">
            <!-- 1. Ringkasan -->
            <section class="space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">1. Ringkasan</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-1">
                        <div class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">TANGGAL</div>
                        <div class="text-sm font-medium">{{ formatDate(opname.tanggal) }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">STATUS</div>
                        <div>
                            <Badge :variant="getStatusVariant(opname.status)" class="rounded-none text-[10px] px-2 py-0">
                                {{ opname.status.toUpperCase() }}
                            </Badge>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[10px] font-bold text-muted-foreground/60 uppercase tracking-widest">KETERANGAN</div>
                        <div class="text-sm font-medium">{{ opname.keterangan || '-' }}</div>
                    </div>
                </div>
            </section>

            <!-- 2. Daftar Perbandingan Stok -->
            <section class="space-y-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground/60">2. Daftar Perbandingan Stok</h3>
                <div class="overflow-x-auto">
                    <Table class="rounded-none border-none">
                        <TableHeader>
                            <TableRow class="hover:bg-transparent border-b border-muted">
                                <TableHead class="h-12 px-0 text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Barang</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Stok Sistem</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Stok Fisik</TableHead>
                                <TableHead class="h-12 px-0 text-right text-xs font-bold uppercase tracking-wider text-muted-foreground/50">Selisih</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in opname.items" :key="item.id" class="hover:bg-muted/30 border-b border-muted/50 group transition-colors">
                                <TableCell class="px-0 py-4">
                                    <div class="font-bold text-sm tracking-tight capitalize">{{ item.produk?.nama }}</div>
                                    <div class="text-[10px] font-mono text-muted-foreground/60 uppercase tracking-tighter mt-0.5">{{ item.produk?.sku }}</div>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right text-sm font-medium">
                                    {{ parseFloat(item.system_qty) }} <span class="text-[10px] text-muted-foreground uppercase">{{ item.satuan?.nama }}</span>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right text-sm font-medium">
                                    {{ parseFloat(item.physical_qty) }} <span class="text-[10px] text-muted-foreground uppercase">{{ item.satuan?.nama }}</span>
                                </TableCell>
                                <TableCell class="px-0 py-4 text-right">
                                    <div 
                                        class="text-sm font-bold"
                                        :class="parseFloat(item.physical_qty) - parseFloat(item.system_qty) === 0 ? 'text-muted-foreground/40' : (parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0 ? 'text-primary' : 'text-destructive')"
                                    >
                                        <span v-if="parseFloat(item.physical_qty) - parseFloat(item.system_qty) > 0">+</span>
                                        {{ parseFloat(item.physical_qty) - parseFloat(item.system_qty) }}
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </section>
        </div>
    </div>
</AppLayout>
</template>
