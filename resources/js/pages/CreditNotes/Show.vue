<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { 
    Calendar, User, FileText, 
    ArrowLeft, CheckCircle2, Ban,
    Package, Info, Loader2, Send,
    ExternalLink, History
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    creditNote: any;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Daftar Nota Kredit', href: '/credit-notes' },
    { title: `Detail #${props.creditNote.credit_note_number}`, href: `/credit-notes/${props.creditNote.id}` },
];

const postForm = useForm({});

const handlePost = () => {
    postForm.post(`/credit-notes/${props.creditNote.id}/post`, {
        onSuccess: () => {
            toast.success('Nota Kredit berhasil diposting. Stok dan Jurnal telah diperbarui.');
        },
        onError: (errors: any) => {
            toast.error(errors.error || 'Gagal memposting nota kredit');
        }
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};

const formatDate = (dateString: string, includeTime = false) => {
    if (!dateString) return '--';
    const options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return new Date(dateString).toLocaleDateString('id-ID', options);
};
</script>

<template>
<Head :title="`Nota Kredit - ${creditNote.credit_note_number}`" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <PageHeader 
        :title="`Nota Kredit #${creditNote.credit_note_number}`" 
        :description="`Dibuat oleh ${creditNote.creator?.name} pada ${formatDate(creditNote.created_at, true)}`"
        back-href="/credit-notes"
    >
        <template #actions>
            <div class="flex items-center gap-2">
                <Badge 
                    v-if="creditNote.status === 'posted'" 
                    class="bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <CheckCircle2 class="h-3.5 w-3.5 mr-1.5" /> Posted
                </Badge>
                <Badge 
                    v-else-if="creditNote.status === 'draft'" 
                    class="bg-amber-50 text-amber-600 border-amber-100 hover:bg-amber-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <Info class="h-3.5 w-3.5 mr-1.5" /> Draft
                </Badge>
                <Badge 
                    v-else 
                    class="bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50 text-[11px] uppercase font-bold px-3 h-8"
                >
                    <Ban class="h-3.5 w-3.5 mr-1.5" /> Voided
                </Badge>

                <Button 
                    v-if="creditNote.status === 'draft'" 
                    variant="default" 
                    class="h-8 text-[11px] font-black uppercase tracking-widest bg-accent hover:bg-accent/90"
                    @click="handlePost"
                    :disabled="postForm.processing"
                >
                    <Send v-if="!postForm.processing" class="h-3.5 w-3.5 mr-2" />
                    <Loader2 v-else class="h-3.5 w-3.5 mr-2 animate-spin" />
                    Posting Return
                </Button>
            </div>
        </template>
    </PageHeader>

    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
        
        <!-- Posted Alert -->
        <div v-if="creditNote.status === 'posted'" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="flex items-start gap-4">
                <div class="h-10 w-10 shrink-0 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <CheckCircle2 class="h-5 w-5" />
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-bold text-emerald-900 uppercase tracking-tight">Return Berhasil Diposting</p>
                    <p class="text-[13px] text-emerald-700 leading-relaxed font-medium">Stok barang telah ditambahkan kembali dan jurnal pembalikan telah dicatat secara otomatis.</p>
                </div>
            </div>
            <div class="bg-white/50 border border-emerald-200 px-4 py-2 rounded-xl flex items-center gap-2">
                <Calendar class="h-3.5 w-3.5 text-emerald-400" />
                <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-widest">Diposting pada {{ formatDate(creditNote.posted_at, true) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            
            <!-- Info Panel -->
            <div class="lg:col-span-1 flex flex-col gap-6 animate-in fade-in slide-in-from-left-4 duration-700">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <FileText class="h-3.5 w-3.5 text-accent" /> Informasi Penjualan Asli
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Invoice Asal</label>
                            <Link :href="`/sales/${creditNote.sale.id}`" class="flex items-center gap-2 text-sm font-bold text-accent hover:underline">
                                #{{ creditNote.sale.invoice_number }}
                                <ExternalLink class="h-3 w-3" />
                            </Link>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Pelanggan</label>
                            <p class="text-sm font-bold text-foreground">{{ creditNote.sale.sale_customer?.customer?.name || 'Walk-in' }}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">Alasan Retur</label>
                            <p class="text-xs text-muted-foreground leading-relaxed font-medium italic">"{{ creditNote.reason }}"</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Panel -->
            <div class="lg:col-span-2 flex flex-col gap-6 animate-in fade-in slide-in-from-right-4 duration-700">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                            <Package class="h-3.5 w-3.5 text-accent" /> Item yang Dikembalikan
                        </h3>
                    </div>
                    
                    <Table>
                        <TableHeader>
                            <TableRow class="bg-slate-50/30">
                                <TableHead class="pl-6 w-full">Produk</TableHead>
                                <TableHead class="text-center px-4">Qty Retur</TableHead>
                                <TableHead class="text-right px-4">Harga Satuan</TableHead>
                                <TableHead class="text-right pr-6">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in creditNote.items" :key="item.id">
                                <TableCell class="pl-6 py-4">
                                    <div class="flex flex-col gap-0.5 min-w-0">
                                        <p class="text-sm font-bold text-foreground truncate">{{ item.product.name }}</p>
                                        <p class="text-[10px] text-muted-foreground uppercase tracking-tighter font-mono">#{{ item.product.sku || '--' }}</p>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center font-bold text-foreground text-[13px]">
                                    {{ item.quantity_returned }} <span class="text-[10px] font-normal text-muted-foreground uppercase ml-0.5">{{ item.sale_item?.unit?.symbol || 'Unit' }}</span>
                                </TableCell>
                                <TableCell class="text-right text-[13px] font-medium tabular-nums">{{ formatCurrency(item.unit_price) }}</TableCell>
                                <TableCell class="text-right pr-6 font-bold tabular-nums text-foreground">{{ formatCurrency(item.subtotal) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="p-8 bg-slate-50/50 border-t border-slate-100 flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-bold uppercase tracking-[0.2em] text-foreground">Total Nota Kredit</span>
                            <span class="text-3xl font-black text-amber-600 tabular-nums tracking-tighter">
                                {{ formatCurrency(creditNote.total_amount) }}
                            </span>
                        </div>
                        <p v-if="creditNote.status === 'draft'" class="text-[10px] font-bold text-muted-foreground uppercase text-right opacity-60">
                            * Dana ini akan dikreditkan kembali ke pelanggan atau kas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
