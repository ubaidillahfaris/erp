<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    Lock, Unlock, Calendar, History, 
    AlertCircle, CheckCircle2, Ban, 
    Plus, Loader2, Info
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Table, TableBody, TableCell, 
    TableHead, TableHeader, TableRow 
} from '@/components/ui/table';
import { 
    Dialog, DialogContent, DialogDescription, 
    DialogFooter, DialogHeader, DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'vue-sonner';
import { useConfirm } from '@/composables/useConfirm';
import type { BreadcrumbItem } from '@/types';

// Persistent Layout Fix
defineOptions({ layout: AppLayout });

const props = defineProps<{
    periods: any[];
    suggestions: any[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Accounting', href: '/accounting/journal' },
    { title: 'Period Lock', href: '/accounting/periods' },
];

const { confirmDialog } = useConfirm();
const isAddDialogOpen = ref(false);

const addForm = useForm({
    period_month: '',
    period_year: '',
});

const now = new Date();
const currentMonth = now.getMonth() + 1;
const currentYear = now.getFullYear();

const isCurrentOrFuture = (month: number, year: number) => {
    if (year > currentYear) return true;
    if (year === currentYear && month >= currentMonth) return true;
    return false;
};

const formatMonth = (month: number) => {
    return new Date(2000, month - 1, 1).toLocaleDateString('id-ID', { month: 'long' });
};

const formatDate = (dateString: string) => {
    if (!dateString) return '--';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const handleAddPeriod = () => {
    addForm.post('/accounting/periods', {
        onSuccess: () => {
            isAddDialogOpen.value = false;
            addForm.reset();
            toast.success('Periode berhasil ditambahkan dan dikunci');
        },
    });
};

const toggleLock = async (period: any) => {
    const action = period.is_locked ? 'Unlock' : 'Lock';
    const monthName = formatMonth(period.period_month);
    
    if (!period.is_locked) {
        const confirmed = await confirmDialog(
            `${action} Period: ${monthName} ${period.period_year}`,
            `Locking ${monthName} ${period.period_year} will prevent all transactions in this period. Continue?`
        );
        if (!confirmed) return;
    }

    router.put(`/accounting/periods/${period.id}`, {
        is_locked: !period.is_locked
    }, {
        onSuccess: () => {
            toast.success(`Periode ${monthName} ${period.period_year} berhasil ${period.is_locked ? 'dibuka' : 'dikunci'}`);
        }
    });
};

const deletePeriod = async (period: any) => {
    const monthName = formatMonth(period.period_month);
    const confirmed = await confirmDialog(
        `Hapus Kontrol Periode: ${monthName} ${period.period_year}`,
        `Apakah Anda yakin ingin menghapus periode ini dari daftar kontrol? Ini tidak akan menghapus data transaksi.`
    );
    
    if (confirmed) {
        router.delete(`/accounting/periods/${period.id}`, {
            onSuccess: () => {
                toast.success('Kontrol periode berhasil dihapus');
            }
        });
    }
};

const getSuggestionLabel = (suggestion: any) => {
    return `${formatMonth(suggestion.month)} ${suggestion.year}`;
};

const handleSuggestionSelect = (value: string | any) => {
    if (!value) return;
    const [month, year] = value.split('-');
    addForm.period_month = month;
    addForm.period_year = year;
};
</script>

<template>
<Head title="Manajemen Periode Akuntansi" />

<div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans">
    
    <PageHeader 
        title="Period Lock Management" 
        description="Kelola penguncian periode akuntansi untuk mencegah perubahan data historis" 
        back-href="/accounting/journal"
    >
        <template #actions>
            <Dialog v-model:open="isAddDialogOpen">
                <DialogTrigger as-child>
                    <Button class="h-9 text-xs font-bold uppercase tracking-widest bg-accent hover:bg-accent/90 text-white">
                        <Plus class="h-4 w-4 mr-2" /> Tambah Periode
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-[425px]">
                    <form @submit.prevent="handleAddPeriod">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5 text-accent" />
                                Tambah & Kunci Periode
                            </DialogTitle>
                            <DialogDescription>
                                Pilih periode yang ingin didaftarkan ke sistem penguncian.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-widest text-muted-foreground ml-1">Pilih Periode</label>
                                <Select @update:model-value="handleSuggestionSelect">
                                    <SelectTrigger class="w-full border-slate-200">
                                        <SelectValue placeholder="Pilih bulan & tahun..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem 
                                            v-for="s in suggestions" 
                                            :key="`${s.month}-${s.year}`" 
                                            :value="`${s.month}-${s.year}`"
                                            :disabled="isCurrentOrFuture(s.month, s.year)"
                                        >
                                            {{ getSuggestionLabel(s) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="addForm.errors.period_month || addForm.errors.period_year" class="text-xs text-destructive font-medium ml-1">
                                    {{ addForm.errors.period_month || addForm.errors.period_year }}
                                </p>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="ghost" @click="isAddDialogOpen = false" class="text-xs font-bold uppercase tracking-widest">
                                Batal
                            </Button>
                            <Button 
                                type="submit" 
                                class="bg-accent text-white text-xs font-bold uppercase tracking-widest px-6"
                                :disabled="addForm.processing || !addForm.period_month"
                            >
                                <Loader2 v-if="addForm.processing" class="h-3 w-3 mr-2 animate-spin" />
                                Kunci Periode
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </template>
    </PageHeader>

    <div class="w-full max-w-7xl mx-auto flex flex-col gap-6">
        
        <!-- Info Banner -->
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 flex items-start gap-4 animate-in fade-in slide-in-from-top-4 duration-500">
            <div class="h-10 w-10 shrink-0 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shadow-sm border border-blue-200/50">
                <Info class="h-5 w-5" />
            </div>
            <div class="flex flex-col gap-1">
                <p class="text-sm font-bold text-blue-900 uppercase tracking-tight">Informasi Otomatisasi</p>
                <p class="text-[13px] text-blue-700 leading-relaxed font-medium">
                    Sistem secara otomatis akan mengunci periode bulan sebelumnya setiap tanggal 1 pada bulan berjalan untuk menjaga integritas data laporan keuangan.
                </p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-none animate-in fade-in slide-in-from-bottom-4 duration-700">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-xs font-bold uppercase tracking-widest text-muted-foreground flex items-center gap-2">
                    <History class="h-3.5 w-3.5 text-accent" /> Daftar Periode
                </h3>
                <Badge variant="outline" class="text-[10px] font-mono font-bold">{{ periods.length }} Records</Badge>
            </div>
            
            <Table>
                <TableHeader>
                    <TableRow class="bg-slate-50/30">
                        <TableHead class="pl-6 w-1/3">Periode</TableHead>
                        <TableHead class="text-center">Status</TableHead>
                        <TableHead class="text-center">Terakhir Diperbarui</TableHead>
                        <TableHead class="text-right pr-6">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="period in periods" :key="period.id" class="group transition-colors hover:bg-slate-50/50">
                        <TableCell class="pl-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-accent group-hover:text-white transition-colors">
                                    <Calendar class="h-4 w-4" />
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-sm font-bold text-foreground">{{ formatMonth(period.period_month) }} {{ period.period_year }}</p>
                                    <p class="text-[10px] text-muted-foreground uppercase tracking-widest font-bold">Accounting Period</p>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="text-center">
                            <Badge 
                                :class="period.is_locked 
                                    ? 'bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-50' 
                                    : 'bg-emerald-50 text-emerald-600 border-emerald-100 hover:bg-emerald-50'"
                                class="text-[10px] uppercase font-bold px-2 h-6"
                            >
                                <component :is="period.is_locked ? Lock : Unlock" class="h-3 w-3 mr-1.5" />
                                {{ period.is_locked ? 'Locked' : 'Open' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-center">
                            <span class="text-[12px] font-medium text-slate-500 tabular-nums">
                                {{ formatDate(period.updated_at) }}
                            </span>
                        </TableCell>
                        <TableCell class="text-right pr-6">
                            <div class="flex items-center justify-end gap-2">
                                <Button 
                                    variant="outline" 
                                    size="sm"
                                    class="h-8 text-[10px] font-bold uppercase tracking-widest"
                                    :class="period.is_locked ? 'hover:bg-emerald-50 hover:text-emerald-600 border-emerald-100' : 'hover:bg-rose-50 hover:text-rose-600 border-rose-100'"
                                    :disabled="isCurrentOrFuture(period.period_month, period.period_year)"
                                    @click="toggleLock(period)"
                                >
                                    <component :is="period.is_locked ? Unlock : Lock" class="h-3 w-3 mr-1.5" />
                                    {{ period.is_locked ? 'Unlock' : 'Lock' }}
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    size="icon"
                                    class="h-8 w-8 text-slate-400 hover:text-rose-600"
                                    @click="deletePeriod(period)"
                                >
                                    <Ban class="h-4 w-4" />
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                    <TableRow v-if="periods.length === 0">
                        <TableCell colspan="4" class="py-20 text-center">
                            <div class="flex flex-col items-center gap-3 opacity-20">
                                <Calendar class="h-12 w-12 text-muted-foreground" />
                                <div>
                                    <p class="text-sm font-bold uppercase tracking-widest text-muted-foreground">Belum ada periode terdaftar</p>
                                    <p class="text-xs text-muted-foreground mt-1">Gunakan tombol 'Tambah Periode' untuk memulai</p>
                                </div>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>
</div>
</template>
