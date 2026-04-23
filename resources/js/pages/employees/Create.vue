<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ArrowLeft, User, Briefcase, CreditCard, 
    Save, X, Info, Camera, MapPin, 
    Phone, Mail, Calendar, Building2
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { 
    Select, 
    SelectContent, 
    SelectItem, 
    SelectTrigger, 
    SelectValue 
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Separator } from '@/components/ui/separator';

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pegawai', href: '/employees' },
    { title: 'Tambah Pegawai', href: '#' },
];

// Mock Form
const form = {
    name: '',
    nik: '',
    email: '',
    phone: '',
    address: '',
    position: '',
    department: '',
    join_date: '',
    employment_type: 'Tetap',
    basic_salary: '',
    bank_name: '',
    bank_account: '',
    create_user: false,
    role: 'Staff'
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tambah Pegawai Baru" />

        <div class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans text-slate-700">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/employees">
                        <Button variant="outline" size="icon" class="btn-secondary h-8 w-8">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">Tambah Pegawai Baru</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Input data personal dan informasi kontrak kerja.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link href="/employees">
                        <Button variant="outline" class="rounded-full px-6 font-normal">Batal</Button>
                    </Link>
                    <Button primary class="rounded-full px-6 font-normal gap-2 shadow-none">
                        <Save class="h-4 w-4" />
                        Simpan Data
                    </Button>
                </div>
            </div>

            <div class="w-full max-w-7xl mx-auto grid grid-cols-12 gap-8">
                <!-- Left: Form Sections -->
                <div class="col-span-12 lg:col-span-8 space-y-8">
                    
                    <!-- Section 1: Personal Information -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white overflow-hidden p-0 gap-0">
                        <div class="px-8 py-6 border-b border-border/40 bg-slate-50/50 flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-primary/5 flex items-center justify-center text-primary">
                                <User class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900">Informasi Pribadi</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nama Lengkap Sesuai KTP</Label>
                                    <Input placeholder="Masukkan nama lengkap..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">NIK (Nomor Induk Kependudukan)</Label>
                                    <Input placeholder="16 digit NIK..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 font-mono" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nomor WhatsApp</Label>
                                    <div class="relative">
                                        <Phone class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input placeholder="0812..." class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Email Kerja / Personal</Label>
                                    <div class="relative">
                                        <Mail class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input placeholder="email@example.com" class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                </div>
                                <div class="col-span-2 space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Alamat Domisili</Label>
                                    <Textarea placeholder="Alamat lengkap..." class="rounded-2xl min-h-[100px] border-border/60 bg-white shadow-none focus-visible:ring-primary/20 resize-none" />
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Section 2: Employment Details -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white overflow-hidden p-0 gap-0">
                        <div class="px-8 py-6 border-b border-border/40 bg-slate-50/50 flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-blue-500/5 flex items-center justify-center text-blue-600">
                                <Briefcase class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900">Detail Pekerjaan</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Jabatan / Posisi</Label>
                                    <Select>
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih jabatan..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="manager">Manager</SelectItem>
                                            <SelectItem value="kasir">Kasir</SelectItem>
                                            <SelectItem value="cook">Cook</SelectItem>
                                            <SelectItem value="server">Server</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Departemen</Label>
                                    <Select>
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih departemen..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="ops">Operasional</SelectItem>
                                            <SelectItem value="kitchen">Dapur</SelectItem>
                                            <SelectItem value="admin">Administrasi</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Tanggal Mulai Kerja</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input type="date" class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Status Kepegawaian</Label>
                                    <Select>
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih status..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="tetap">Karyawan Tetap</SelectItem>
                                            <SelectItem value="kontrak">Kontrak</SelectItem>
                                            <SelectItem value="harian">Harian Lepas</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Section 3: Financial Information -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white overflow-hidden p-0 gap-0">
                        <div class="px-8 py-6 border-b border-border/40 bg-slate-50/50 flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-emerald-500/5 flex items-center justify-center text-emerald-600">
                                <CreditCard class="h-4 w-4" />
                            </div>
                            <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900">Informasi Finansial</h3>
                        </div>
                        <div class="p-8 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Gaji Pokok (Monthly)</Label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3 text-[13px] font-bold text-muted-foreground/60">Rp</span>
                                        <Input type="number" placeholder="0" class="rounded-xl h-11 pl-12 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 tabular-nums" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Metode Pembayaran</Label>
                                    <Select>
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih metode..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="transfer">Transfer Bank</SelectItem>
                                            <SelectItem value="tunai">Tunai / Cash</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nama Bank</Label>
                                    <Input placeholder="Misal: BCA, Mandiri, BRI" class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nomor Rekening</Label>
                                    <Input placeholder="Nomor rekening..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 font-mono" />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right: Side Config -->
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <!-- Photo Upload -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white p-10 flex flex-col items-center text-center space-y-4">
                        <div class="h-32 w-32 rounded-3xl bg-slate-50 border-2 border-dashed border-border/60 flex flex-col items-center justify-center text-muted-foreground group cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-all">
                            <Camera class="h-8 w-8 mb-2 opacity-20 group-hover:opacity-100 transition-opacity" />
                            <span class="text-[10px] font-normal uppercase tracking-widest">Upload Foto</span>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-normal text-slate-900">Foto Profil</h4>
                            <p class="text-[11px] text-muted-foreground font-normal">Format JPG, PNG (Max 2MB)</p>
                        </div>
                    </Card>

                    <!-- System Access -->
                    <Card class="rounded-[2rem] border-0 shadow-none bg-slate-900 text-white p-10 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mr-4 -mt-4 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>
                        <div class="flex items-center justify-between mb-10">
                            <div class="flex items-center gap-3">
                                <ShieldCheck class="h-5 w-5 text-blue-400" />
                                <h3 class="text-[11px] font-normal uppercase tracking-widest text-white/70">Akses Sistem</h3>
                            </div>
                            <Switch class="data-[state=checked]:bg-blue-500 border-white/20" />
                        </div>
                        
                        <div class="space-y-8">
                            <div class="space-y-2">
                                <Label class="text-[10px] uppercase tracking-widest text-white/50 font-normal">System Role</Label>
                                <Select disabled>
                                    <SelectTrigger class="rounded-xl h-11 border-white/10 bg-white/5 text-white/40 shadow-none">
                                        <SelectValue placeholder="Pilih Role" />
                                    </SelectTrigger>
                                </Select>
                            </div>
                            <div class="flex items-start gap-3">
                                <Info class="h-4 w-4 text-blue-400 shrink-0 mt-0.5" />
                                <p class="text-[11px] text-white/60 leading-relaxed font-normal">Aktifkan switch di atas jika pegawai memerlukan akses login ke aplikasi ini.</p>
                            </div>
                        </div>
                    </Card>

                    <!-- Info Tip -->
                    <div class="px-6 py-2 flex items-start gap-3">
                        <Info class="h-4 w-4 text-slate-400 shrink-0 mt-1" />
                        <p class="text-[12px] text-slate-500 leading-relaxed font-normal italic">
                            Pastikan data NIK dan Nomor Rekening sudah benar untuk keperluan verifikasi data dan payroll bulanan.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
