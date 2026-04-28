<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { 
    ArrowLeft, Building2, Calendar, Wallet, Layers, Clock, 
    History, CheckCircle2, AlertCircle, ExternalLink, Ban 
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useConfirm } from '@/composables/useConfirm';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    asset: any;
}>();

const { confirmDialog } = useConfirm();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value / 100 || 0);
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateString));
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'active': return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'disposed': return 'bg-slate-50 text-slate-600 border-slate-100';
        case 'fully_depreciated': return 'bg-orange-50 text-orange-600 border-orange-100';
        default: return 'bg-secondary text-secondary-foreground';
    }
};

const getStatusLabel = (status: string) => {
    switch (status) {
        case 'active': return 'AKTIF';
        case 'disposed': return 'DIHENTIKAN';
        case 'fully_depreciated': return 'PENYUSUTAN PENUH';
        default: return status.toUpperCase();
    }
};

const handleDispose = async () => {
    if (await confirmDialog('Hentikan Penggunaan Aset?', 'Tindakan ini akan menghentikan jadwal penyusutan di masa depan. Pastikan Anda telah mencatat pelepasan aset di buku besar jika diperlukan.')) {
        router.post(`/fixed-assets/${props.asset.id}/dispose`, {
            disposal_date: new Date().toISOString().split('T')[0],
            notes: 'Manual disposal action.'
        });
    }
};
</script>

<template>
    <Head :title="asset.name" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            :title="asset.name" 
            :description="'Asset ID: ' + asset.asset_code" 
            back-href="/fixed-assets"
        />

        <div class="w-full max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar: Asset Summary -->
            <div class="lg:col-span-1 flex flex-col gap-6">
                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <Badge :class="['w-fit text-[9px] font-black px-2 py-0.5 rounded-full border', getStatusColor(asset.status)]">
                            {{ getStatusLabel(asset.status) }}
                        </Badge>
                        <CardTitle class="text-sm font-bold mt-2">{{ asset.name }}</CardTitle>
                        <CardDescription class="text-[11px] uppercase tracking-widest font-bold text-muted-foreground">{{ asset.category }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-2">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">Harga Perolehan</span>
                            <span class="text-lg font-black text-foreground">{{ formatCurrency(asset.acquisition_cost) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-bold text-muted-foreground uppercase tracking-widest">Nilai Buku Saat Ini</span>
                            <span class="text-lg font-black text-peach-600">{{ formatCurrency(asset.current_book_value) }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t">
                            <div class="flex flex-col">
                                <span class="text-[8px] font-bold text-muted-foreground uppercase">Tgl Perolehan</span>
                                <span class="text-[11px] font-bold text-slate-600">{{ formatDate(asset.acquisition_date) }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[8px] font-bold text-muted-foreground uppercase">Masa Manfaat</span>
                                <span class="text-[11px] font-bold text-slate-600">{{ asset.useful_life_months }} Bulan</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-sm">
                    <CardHeader class="pb-2">
                        <CardTitle class="text-[11px] font-black uppercase tracking-widest flex items-center gap-2">
                            <Layers class="h-3 w-3" />
                            Mapping Akun
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 pt-2">
                        <div class="p-2 bg-slate-50 rounded border border-slate-100 flex flex-col gap-0.5">
                            <span class="text-[8px] font-bold text-muted-foreground uppercase tracking-tighter">Akun Aset</span>
                            <span class="text-[10px] font-bold text-slate-700 truncate" v-if="asset.asset_account">{{ asset.asset_account.code }} - {{ asset.asset_account.name }}</span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded border border-slate-100 flex flex-col gap-0.5">
                            <span class="text-[8px] font-bold text-muted-foreground uppercase tracking-tighter">Akumulasi Penyusutan</span>
                            <span class="text-[10px] font-bold text-slate-700 truncate" v-if="asset.depreciation_account">{{ asset.depreciation_account.code }} - {{ asset.depreciation_account.name }}</span>
                        </div>
                        <div class="p-2 bg-slate-50 rounded border border-slate-100 flex flex-col gap-0.5">
                            <span class="text-[8px] font-bold text-muted-foreground uppercase tracking-tighter">Beban Penyusutan</span>
                            <span class="text-[10px] font-bold text-slate-700 truncate" v-if="asset.expense_account">{{ asset.expense_account.code }} - {{ asset.expense_account.name }}</span>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="asset.status === 'active'" class="mt-4">
                    <Button variant="destructive" outline class="w-full h-11 text-xs font-bold gap-2" @click="handleDispose">
                        <Ban class="h-4 w-4" />
                        HENTIKAN PENGGUNAAN
                    </Button>
                </div>
            </div>

            <!-- Main Content: Schedule Table -->
            <div class="lg:col-span-3 flex flex-col gap-6">
                <Card class="border-none shadow-sm">
                    <CardHeader class="bg-white border-b flex flex-row items-center justify-between py-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-md bg-orange-50 text-orange-500">
                                <History class="h-4 w-4" />
                            </div>
                            <div>
                                <CardTitle class="text-sm font-bold">Jadwal Penyusutan</CardTitle>
                                <CardDescription class="text-[11px]">Rincian penyusutan bulanan (Straight-line)</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow class="bg-slate-50/50 hover:bg-slate-50/50 border-b">
                                        <TableHead class="text-[10px] font-black uppercase tracking-widest pl-6">Periode</TableHead>
                                        <TableHead class="text-[10px] font-black uppercase tracking-widest text-right">Penyusutan</TableHead>
                                        <TableHead class="text-[10px] font-black uppercase tracking-widest text-right">Nilai Buku</TableHead>
                                        <TableHead class="text-[10px] font-black uppercase tracking-widest text-center">Status</TableHead>
                                        <TableHead class="text-[10px] font-black uppercase tracking-widest text-right pr-6">Referensi GL</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in asset.schedules" :key="item.id" class="group transition-colors hover:bg-slate-50/80 border-b last:border-0">
                                        <TableCell class="py-3 pl-6 font-mono text-[11px] font-bold text-slate-700">
                                            {{ item.period_month }}/{{ item.period_year }}
                                        </TableCell>
                                        <TableCell class="py-3 text-right tabular-nums text-[12px] font-bold text-destructive">
                                            -{{ formatCurrency(item.depreciation_amount) }}
                                        </TableCell>
                                        <TableCell class="py-3 text-right tabular-nums text-[12px] font-bold text-slate-800">
                                            {{ formatCurrency(item.book_value_after) }}
                                        </TableCell>
                                        <TableCell class="py-3 text-center">
                                            <div class="flex justify-center">
                                                <Badge v-if="item.status === 'posted'" variant="outline" class="h-5 text-[8px] font-black gap-1 bg-emerald-50 text-emerald-600 border-emerald-100">
                                                    <CheckCircle2 class="h-2.5 w-2.5" /> POSTED
                                                </Badge>
                                                <Badge v-else variant="outline" class="h-5 text-[8px] font-black gap-1 bg-slate-100 text-slate-400 border-slate-200">
                                                    <Clock class="h-2.5 w-2.5" /> SCHEDULED
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="py-3 text-right pr-6">
                                            <Link v-if="item.journal_entry" :href="'/journal?ref=' + item.journal_entry.ref_number" class="text-[9px] font-black text-blue-600 hover:underline flex items-center justify-end gap-1 uppercase tracking-tighter">
                                                {{ item.journal_entry.ref_number }}
                                                <ExternalLink class="h-2.5 w-2.5" />
                                            </Link>
                                            <span v-else class="text-[10px] font-bold text-slate-300 italic">-</span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

<style scoped>
.text-peach-600 { color: #f25c27; }
</style>
