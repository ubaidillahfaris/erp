<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    ArrowLeft, Phone, Mail, MapPin, Briefcase, 
    Calendar, Building2, ShieldCheck, 
    Clock, TrendingUp, History, 
    Printer, Edit2, AlertCircle, User as UserIcon
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';

// Mock Data for a Single Employee
const employee = {
    id: 1,
    name: 'Ahmad Fauzi',
    nik: '3275012345670001',
    position: 'Manager Operasional',
    phone: '0812-3456-7890',
    email: 'ahmad.fauzi@warung.com',
    address: 'Jl. Raya Bogor No. 123, Cimanggis, Depok, Jawa Barat',
    status: 'active',
    join_date: '15 Jan 2023',
    employment_type: 'Karyawan Tetap',
    department: 'Manajemen',
    basic_salary: 7500000,
    bank_name: 'BCA',
    bank_account: '8830123456',
    photo: null,
    user: {
        id: 5,
        username: 'ahmad_fauzi',
        role: 'Admin',
        last_login: '2 jam yang lalu'
    }
};

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pegawai', href: '/employees' },
    { title: employee.name, href: '#' },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value || 0);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Pegawai - ${employee.name}`" />

        <div class="px-6 py-8 space-y-8 animate-fade-up font-sans text-slate-700 bg-slate-50 min-h-[calc(100vh-64px)]">
            
            <!-- Header Section -->
            <PageHeader 
                :title="employee.name" 
                :description="`NIK: ${employee.nik} • Terdaftar sejak ${employee.join_date}`"
                back-href="/employees"
                class="pb-6 border-b border-border/60"
            >
                <template #actions>
                    <div class="flex items-center gap-3">
                        <Badge class="rounded-full px-2.5 py-0.5 font-normal text-[10px] uppercase tracking-widest border-0 bg-emerald-500/10 text-emerald-600 shadow-none">
                            Aktif
                        </Badge>
                        <Button variant="outline" class="h-10 px-5 rounded-full text-sm font-normal flex items-center gap-2 transition hover:-translate-y-0.5 shadow-none border-border/60">
                            <Printer class="h-3.5 w-3.5 text-muted-foreground" />
                            Cetak
                        </Button>
                        <Link :href="`/employees/${employee.id}/edit`" class="inline-block">
                            <Button primary class="h-10 px-5 rounded-full text-sm font-normal flex items-center gap-2 transition hover:-translate-y-0.5 shadow-none">
                                <Edit2 class="h-3.5 w-3.5" />
                                Edit Pegawai
                            </Button>
                        </Link>
                    </div>
                </template>
            </PageHeader>

            <!-- Sticky Profile Mini-Bento -->
            <Card class="bg-card rounded-3xl p-6 shadow-none border border-border/40 flex flex-col md:flex-row items-center md:items-start gap-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mr-6 -mt-6 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                
                <Avatar class="h-28 w-28 rounded-2xl border border-border/40 shadow-sm shrink-0">
                    <AvatarFallback class="bg-primary/5 text-primary text-3xl font-normal rounded-2xl">
                        {{ employee.name.split(' ').map(n => n[0]).join('') }}
                    </AvatarFallback>
                </Avatar>

                <div class="space-y-4 text-center md:text-left flex-1">
                    <div class="space-y-1">
                        <h2 class="text-2xl font-normal tracking-tight text-foreground leading-tight">{{ employee.name }}</h2>
                        <p class="text-[11px] font-normal text-muted-foreground/60 uppercase tracking-[0.2em]">{{ employee.position }}</p>
                    </div>

                    <div class="flex flex-wrap justify-center md:justify-start items-center gap-y-3 gap-x-6">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-3.5 w-3.5 text-muted-foreground/50" />
                            <span class="text-[13px] font-normal text-muted-foreground leading-none">{{ employee.department }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Calendar class="h-3.5 w-3.5 text-muted-foreground/50" />
                            <span class="text-[13px] font-normal text-muted-foreground leading-none">Mulai: {{ employee.join_date }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Phone class="h-3.5 w-3.5 text-muted-foreground/50" />
                            <span class="text-[13px] font-normal text-muted-foreground leading-none">{{ employee.phone }}</span>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Tabs Section -->
            <Tabs default-value="overview" class="w-full space-y-6">
                <div class="flex justify-center md:justify-start">
                    <TabsList class="bg-slate-200/40 p-1 rounded-2xl h-11 border border-border/40 shadow-none">
                        <TabsTrigger value="overview" class="rounded-xl px-6 text-[12px] font-normal uppercase tracking-widest data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm">Overview</TabsTrigger>
                        <TabsTrigger value="personal" class="rounded-xl px-6 text-[12px] font-normal uppercase tracking-widest data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm">Personal</TabsTrigger>
                        <TabsTrigger value="employment" class="rounded-xl px-6 text-[12px] font-normal uppercase tracking-widest data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm">Employment</TabsTrigger>
                        <TabsTrigger value="financial" class="rounded-xl px-6 text-[12px] font-normal uppercase tracking-widest data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm">Financial</TabsTrigger>
                        <TabsTrigger value="system" class="rounded-xl px-6 text-[12px] font-normal uppercase tracking-widest data-[state=active]:bg-white data-[state=active]:text-foreground data-[state=active]:shadow-sm">System</TabsTrigger>
                    </TabsList>
                </div>

                <!-- Tab: Overview -->
                <TabsContent value="overview" class="mt-0 space-y-6">
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 md:col-span-8 grid grid-cols-2 gap-5">
                            <Card class="p-6 rounded-3xl border border-border/40 shadow-none bg-white relative overflow-hidden">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-[10px] font-normal text-muted-foreground uppercase tracking-[0.2em]">Salary Component</span>
                                    <div class="h-8 w-8 rounded-full bg-primary/5 flex items-center justify-center text-primary">
                                        <TrendingUp class="h-4 w-4" />
                                    </div>
                                </div>
                                <h3 class="text-3xl font-normal tracking-tighter tabular-nums text-foreground">{{ formatCurrency(employee.basic_salary) }}</h3>
                                <p class="text-[11px] font-normal text-muted-foreground/60 uppercase tracking-widest mt-2 leading-none">Basic Salary / Month</p>
                            </Card>

                            <Card class="p-6 rounded-3xl border border-border/40 shadow-none bg-white relative overflow-hidden">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-[10px] font-normal text-muted-foreground uppercase tracking-[0.2em]">Work Experience</span>
                                    <div class="h-8 w-8 rounded-full bg-blue-500/10 flex items-center justify-center text-blue-600">
                                        <Clock class="h-4 w-4" />
                                    </div>
                                </div>
                                <h3 class="text-3xl font-normal tracking-tighter tabular-nums text-foreground">1y 3m</h3>
                                <p class="text-[11px] font-normal text-muted-foreground/60 uppercase tracking-widest mt-2 leading-none">Tenure Duration</p>
                            </Card>

                            <Card class="col-span-2 p-6 rounded-3xl border border-border/40 shadow-none bg-white">
                                <h3 class="text-[10px] font-normal text-muted-foreground uppercase tracking-[0.2em] mb-6">Address & Location</h3>
                                <div class="flex items-start gap-3">
                                    <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center shrink-0">
                                        <MapPin class="h-4 w-4 text-slate-400" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[15px] font-normal leading-relaxed text-foreground/80">{{ employee.address }}</p>
                                        <p class="text-[11px] text-muted-foreground/40 font-normal uppercase tracking-widest">Domisili Utama</p>
                                    </div>
                                </div>
                            </Card>
                        </div>

                        <div class="col-span-12 md:col-span-4 space-y-5">
                            <Card class="p-6 rounded-3xl bg-slate-900 border-0 shadow-none text-white relative overflow-hidden">
                                <div class="absolute bottom-0 right-0 -mr-4 -mb-4 w-24 h-24 bg-white/5 rounded-full blur-2xl"></div>
                                <div class="flex items-center gap-3 mb-8">
                                    <div class="h-8 w-8 rounded-full bg-white/10 flex items-center justify-center">
                                        <ShieldCheck class="h-4 w-4 text-blue-300" />
                                    </div>
                                    <span class="text-[10px] font-normal uppercase tracking-widest text-white/40">Auth Credentials</span>
                                </div>
                                <div class="space-y-5">
                                    <div class="space-y-1">
                                        <p class="text-[10px] uppercase tracking-widest text-white/30 leading-none font-normal">Linked Account</p>
                                        <p class="text-lg font-normal text-white">@{{ employee.user.username }}</p>
                                    </div>
                                    <div class="flex items-center justify-between pt-4 border-t border-white/5">
                                        <Badge class="bg-white/10 text-white border-0 font-normal text-[9px] uppercase tracking-widest rounded-full">
                                            {{ employee.user.role }}
                                        </Badge>
                                        <span class="text-[10px] text-white/30 font-normal">{{ employee.user.last_login }}</span>
                                    </div>
                                </div>
                            </Card>

                            <Card class="p-6 rounded-3xl bg-white border border-border/40 shadow-none flex items-center gap-4 py-8">
                                <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center">
                                    <History class="h-5 w-5 text-slate-300" />
                                </div>
                                <p class="text-[12px] font-normal text-muted-foreground/50 uppercase tracking-tight italic">No recent logs found</p>
                            </Card>
                        </div>
                    </div>
                </TabsContent>

                <!-- Tab: Personal -->
                <TabsContent value="personal" class="mt-0">
                    <Card class="bg-card rounded-[2.5rem] p-8 md:p-12 shadow-none border border-border/40">
                        <div class="space-y-12">
                            <div class="flex items-center gap-3 pb-8 border-b border-border/40">
                                <div class="h-10 w-10 rounded-full bg-primary/5 flex items-center justify-center text-primary">
                                    <UserIcon class="h-5 w-5" />
                                </div>
                                <h3 class="text-xl font-normal tracking-tight text-foreground">Personal Identity</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-10 gap-x-16">
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground/60 font-normal">Full Name</label>
                                    <p class="text-lg font-normal text-foreground/90">{{ employee.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground/60 font-normal">Identity Number (NIK)</label>
                                    <p class="text-lg font-normal text-foreground/90 font-mono tracking-tight">{{ employee.nik }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground/60 font-normal">Phone Number</label>
                                    <p class="text-lg font-normal text-foreground/90">{{ employee.phone }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground/60 font-normal">Official Email</label>
                                    <p class="text-lg font-normal text-foreground/90">{{ employee.email }}</p>
                                </div>
                                <div class="col-span-2 space-y-2">
                                    <label class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground/60 font-normal">Address Details</label>
                                    <p class="text-lg font-normal text-foreground/90 leading-relaxed">{{ employee.address }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </TabsContent>

                <!-- Tab: Financial -->
                <TabsContent value="financial" class="mt-0">
                    <Card class="bg-card rounded-[2.5rem] p-8 md:p-12 shadow-none border border-border/40">
                        <div class="space-y-12">
                            <div class="flex items-center gap-3 pb-8 border-b border-border/40">
                                <div class="h-10 w-10 rounded-full bg-emerald-500/5 flex items-center justify-center text-emerald-600">
                                    <TrendingUp class="h-5 w-5" />
                                </div>
                                <h3 class="text-xl font-normal tracking-tight text-foreground">Payroll & Banking</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <div class="p-8 rounded-3xl bg-slate-50/50 border border-border/40">
                                    <p class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest mb-4">Gaji Pokok Terakhir</p>
                                    <h2 class="text-3xl font-normal tracking-tighter tabular-nums text-foreground">{{ formatCurrency(employee.basic_salary) }}</h2>
                                    <p class="text-[11px] text-muted-foreground mt-2 font-normal uppercase tracking-widest opacity-60">Status: Fixed / Monthly</p>
                                </div>
                                <div class="p-8 rounded-3xl bg-slate-50/50 border border-border/40 space-y-6">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest">Bank Entity</p>
                                        <p class="text-lg font-normal text-foreground">{{ employee.bank_name }}</p>
                                    </div>
                                    <div class="space-y-1 pt-4 border-t border-border/40">
                                        <p class="text-[10px] font-normal text-muted-foreground uppercase tracking-widest">Account Number</p>
                                        <p class="text-lg font-normal font-mono text-foreground tracking-tight">{{ employee.bank_account }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </TabsContent>
                
                <!-- Placeholders for other tabs -->
                <TabsContent v-for="t in ['employment', 'system']" :key="t" :value="t" class="mt-0">
                    <Card class="p-16 rounded-[2.5rem] border border-border/40 shadow-none bg-white flex flex-col items-center justify-center text-center opacity-40">
                        <AlertCircle class="h-10 w-10 text-muted-foreground mb-4 font-light" />
                        <p class="text-sm font-normal uppercase tracking-[0.2em] text-muted-foreground">Module under development</p>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
