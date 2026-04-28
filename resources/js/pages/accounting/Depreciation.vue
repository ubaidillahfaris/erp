<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Calculator, Calendar, CheckCircle2, AlertCircle, 
    PlayCircle, ArrowLeft, History, FileSpreadsheet
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
    periods: any[];
}>();

const { confirmDialog } = useConfirm();

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value / 100 || 0);
};

const getMonthName = (month: number) => {
    return new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date(2024, month - 1, 1));
};

const handlePost = async (month: number, year: number) => {
    const monthName = getMonthName(month);
    if (await confirmDialog('Proses Penyusutan?', `Anda akan memproses penyusutan untuk periode ${monthName} ${year}. Tindakan ini akan membuat entri jurnal otomatis.`)) {
        router.post('/accounting/depreciation/post', {
            month,
            year
        }, {
            onSuccess: () => {
                // Success handled by backend flash
            }
        });
    }
};
</script>

<template>
    <Head title="Kelola Penyusutan" />

    <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
        <PageHeader 
            title="Depreciation Management" 
            description="Posting penyusutan bulanan ke buku besar" 
            back-href="/fixed-assets"
        />

        <div class="w-full max-w-5xl mx-auto flex flex-col gap-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="border-none shadow-sm overflow-hidden group">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Total Penyusutan Terjadwal</span>
                                <span class="text-2xl font-black text-foreground mt-1 tabular-nums">
                                    {{ periods.filter(p => p.status === 'scheduled').length }} <span class="text-[10px] text-muted-foreground">Periode</span>
                                </span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-orange-50 flex items-center justify-center text-orange-500 border border-orange-100/50 transition-all group-hover:scale-110">
                                <History class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-sm overflow-hidden group">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Total Posting (Journaled)</span>
                                <span class="text-2xl font-black text-foreground mt-1 tabular-nums">
                                    {{ periods.filter(p => p.status === 'posted').length }} <span class="text-[10px] text-muted-foreground">Periode</span>
                                </span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-500 border border-emerald-100/50 transition-all group-hover:scale-110">
                                <CheckCircle2 class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-none shadow-sm overflow-hidden group">
                    <CardContent class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Metode Perhitungan</span>
                                <span class="text-2xl font-black text-foreground mt-1 tracking-tight">GARIS LURUS</span>
                            </div>
                            <div class="h-12 w-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500 border border-blue-100/50 transition-all group-hover:scale-110">
                                <FileSpreadsheet class="h-6 w-6" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Periods Table -->
            <Card class="border-none shadow-sm">
                <CardHeader class="bg-white border-b py-4">
                    <div class="flex items-center gap-2">
                        <div class="p-1.5 rounded-md bg-peach-50 text-peach-500 border border-peach-100/50">
                            <Calendar class="h-4 w-4" />
                        </div>
                        <div>
                            <CardTitle class="text-sm font-bold">Riwayat & Jadwal Periode</CardTitle>
                            <CardDescription class="text-[11px]">Daftar periode yang perlu diproses atau telah selesai</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-slate-50/50 hover:bg-slate-50/50 border-b">
                                    <TableHead class="text-[10px] font-black uppercase tracking-widest pl-8">Periode</TableHead>
                                    <TableHead class="text-[10px] font-black uppercase tracking-widest text-center">Jumlah Aset</TableHead>
                                    <TableHead class="text-[10px] font-black uppercase tracking-widest text-right">Total Nominal</TableHead>
                                    <TableHead class="text-[10px] font-black uppercase tracking-widest text-center">Status</TableHead>
                                    <TableHead class="text-[10px] font-black uppercase tracking-widest text-right pr-8">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="period in periods" :key="period.period_month + '-' + period.period_year" class="group transition-colors hover:bg-slate-50/80 border-b last:border-0">
                                    <TableCell class="py-4 pl-8">
                                        <div class="flex flex-col">
                                            <span class="text-[13px] font-bold text-foreground">{{ getMonthName(period.period_month) }} {{ period.period_year }}</span>
                                            <span class="text-[9px] font-mono text-muted-foreground uppercase tracking-widest">ID: DEP-{{ period.period_year }}{{ String(period.period_month).padStart(2, '0') }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4 text-center">
                                        <span class="text-[12px] font-bold text-slate-600">{{ period.asset_count }} Aset</span>
                                    </TableCell>
                                    <TableCell class="py-4 text-right tabular-nums text-[13px] font-black text-slate-800">
                                        {{ formatCurrency(period.total_amount) }}
                                    </TableCell>
                                    <TableCell class="py-4 text-center">
                                        <div class="flex justify-center">
                                            <Badge v-if="period.status === 'posted'" variant="outline" class="h-6 text-[9px] font-black gap-1.5 bg-emerald-50 text-emerald-600 border-emerald-100 px-3">
                                                <CheckCircle2 class="h-3 w-3" /> POSTED
                                            </Badge>
                                            <Badge v-else variant="outline" class="h-6 text-[9px] font-black gap-1.5 bg-orange-50 text-orange-600 border-orange-100 px-3">
                                                <AlertCircle class="h-3 w-3" /> READY TO POST
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell class="py-4 text-right pr-8">
                                        <Button 
                                            v-if="period.status !== 'posted'" 
                                            size="sm" 
                                            class="h-8 text-[10px] font-bold gap-2 px-4 shadow-sm"
                                            @click="handlePost(period.period_month, period.period_year)"
                                        >
                                            <PlayCircle class="h-3.5 w-3.5" />
                                            POST DEPRECIATION
                                        </Button>
                                        <span v-else class="text-[10px] font-bold text-muted-foreground italic">No further action</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Disclaimer -->
            <div class="p-4 rounded-xl bg-orange-50 border border-orange-100 flex items-start gap-4">
                <AlertCircle class="h-5 w-5 text-orange-500 shrink-0 mt-0.5" />
                <div class="flex flex-col gap-1">
                    <p class="text-[12px] font-bold text-orange-800 uppercase tracking-tight">Perhatian</p>
                    <p class="text-[11px] text-orange-700 leading-relaxed font-medium">
                        Proses penyusutan tidak dapat dibatalkan (immutable). Pastikan data aset dan mapping akun sudah benar sebelum melakukan posting. 
                        Sistem juga menghormati <strong>Period Lock</strong>, jika periode dikunci maka posting akan gagal.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-peach-50 { background-color: #fff5f0; }
.text-peach-500 { color: #ff6b35; }
.border-peach-100\/50 { border-color: rgba(255, 107, 53, 0.1); }
</style>
