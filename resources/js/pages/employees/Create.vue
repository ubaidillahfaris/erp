<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    ArrowLeft, User, Briefcase, CreditCard, 
    Save, X, Info, Camera, MapPin, 
    Phone, Mail, Calendar, Building2,
    ShieldCheck
} from 'lucide-vue-next';
import { index, store, update } from '@/actions/App/Http/Controllers/EmployeeController';
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
import { ref, onMounted } from 'vue';

const props = defineProps<{
    employee?: any;
    roles: string[];
    isEditing?: boolean;
    app_debug?: boolean;
}>();

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pegawai', href: index().url },
    { title: props.isEditing ? 'Edit Pegawai' : 'Tambah Pegawai', href: '#' },
];

const form = useForm({
    name: props.employee?.name ?? '',
    nik: props.employee?.nik ?? '',
    email: props.employee?.email ?? '',
    phone: props.employee?.phone ?? '',
    address: props.employee?.address ?? '',
    position: props.employee?.position ?? '',
    department: props.employee?.department ?? '',
    join_date: props.employee?.join_date ?? '',
    employment_type: props.employee?.employment_type ?? 'Tetap',
    status: props.employee?.status ?? 'active',
    basic_salary: props.employee?.basic_salary ?? '',
    bank_name: props.employee?.bank_name ?? '',
    bank_account: props.employee?.bank_account ?? '',
    create_user: !!props.employee?.user_id,
    role: props.employee?.user?.roles?.[0]?.name ?? 'staff',
    password: '',
    photo: null as File | null,
    documents: [] as File[],
    documents_meta: '',
});

onMounted(() => {
    if (props.app_debug && !props.isEditing) {
        form.name = 'Budi Santoso';
        form.nik = '3273000000000001';
        form.email = 'budi@warung.com';
        form.phone = '08123456789';
        form.address = 'Jl. Merdeka No. 123, Bandung';
        form.position = 'Staf Produksi';
        form.department = 'Produksi';
        form.join_date = new Date().toISOString().split('T')[0];
        form.employment_type = 'Tetap';
        form.basic_salary = '5000000';
        form.status = 'active';
        form.create_user = true;
        form.role = 'staff';
        form.password = 'password';
    }
});

const photoPreview = ref(props.employee?.photo_path ? `/storage/${props.employee.photo_path}` : null);
const photoInput = ref<HTMLInputElement | null>(null);
const docInputs = ref<HTMLInputElement[]>([]);

const handlePhotoChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const additionalDocuments = ref<{ type: string; file: File | null; preview: string | null }[]>([]);

const addDocumentRow = () => {
    additionalDocuments.value.push({ type: 'KTP', file: null, preview: null });
};

const removeDocumentRow = (index: number) => {
    additionalDocuments.value.splice(index, 1);
};

const handleDocumentFileChange = (index: number, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        additionalDocuments.value[index].file = file;
        if (file.type.startsWith('image/')) {
            additionalDocuments.value[index].preview = URL.createObjectURL(file);
        } else {
            additionalDocuments.value[index].preview = null;
        }
    }
};

const submit = () => {
    // Prep documents
    form.documents = additionalDocuments.value.map(d => d.file).filter(f => f !== null) as File[];
    form.documents_meta = JSON.stringify(additionalDocuments.value.filter(d => d.file !== null).map(d => ({ type: d.type })));

    if (props.isEditing) {
        // Use POST with _method: PUT for file upload support in updates
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(update(props.employee.id).url);
    } else {
        form.post(store().url);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEditing ? 'Edit Pegawai' : 'Tambah Pegawai Baru'" />

        <form @submit.prevent="submit" class="px-6 py-8 bg-slate-50 min-h-[calc(100vh-64px)] flex flex-col gap-6 font-sans text-slate-700">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button type="button" variant="outline" size="icon" class="btn-secondary h-8 w-8">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ isEditing ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru' }}</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Input data personal dan informasi kontrak kerja.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="index().url">
                        <Button type="button" variant="outline" class="rounded-full px-6 font-normal">Batal</Button>
                    </Link>
                    <Button type="submit" primary :disabled="form.processing" class="rounded-full px-6 font-normal gap-2 shadow-none">
                        <Save class="h-4 w-4" />
                        {{ isEditing ? 'Update Data' : 'Simpan Data' }}
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
                                    <Input v-model="form.name" placeholder="Masukkan nama lengkap..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    <p v-if="form.errors.name" class="text-[10px] text-red-500 ml-1">{{ form.errors.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">NIK (Nomor Induk Kependudukan)</Label>
                                    <Input v-model="form.nik" placeholder="16 digit NIK..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 font-mono" />
                                    <p v-if="form.errors.nik" class="text-[10px] text-red-500 ml-1">{{ form.errors.nik }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nomor WhatsApp</Label>
                                    <div class="relative">
                                        <Phone class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input v-model="form.phone" placeholder="0812..." class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                    <p v-if="form.errors.phone" class="text-[10px] text-red-500 ml-1">{{ form.errors.phone }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Email Kerja / Personal</Label>
                                    <div class="relative">
                                        <Mail class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input v-model="form.email" placeholder="email@example.com" class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                    <p v-if="form.errors.email" class="text-[10px] text-red-500 ml-1">{{ form.errors.email }}</p>
                                </div>
                                <div class="col-span-2 space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Alamat Domisili</Label>
                                    <Textarea v-model="form.address" placeholder="Alamat lengkap..." class="rounded-2xl min-h-[100px] border-border/60 bg-white shadow-none focus-visible:ring-primary/20 resize-none" />
                                    <p v-if="form.errors.address" class="text-[10px] text-red-500 ml-1">{{ form.errors.address }}</p>
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
                                    <Select v-model="form.position">
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih jabatan..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="Manager">Manager</SelectItem>
                                            <SelectItem value="Kasir">Kasir</SelectItem>
                                            <SelectItem value="Cook">Cook</SelectItem>
                                            <SelectItem value="Server">Server</SelectItem>
                                            <SelectItem value="Admin">Admin</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.position" class="text-[10px] text-red-500 ml-1">{{ form.errors.position }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Departemen</Label>
                                    <Select v-model="form.department">
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih departemen..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="Operasional">Operasional</SelectItem>
                                            <SelectItem value="Dapur">Dapur</SelectItem>
                                            <SelectItem value="Administrasi">Administrasi</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.department" class="text-[10px] text-red-500 ml-1">{{ form.errors.department }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Tanggal Mulai Kerja</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3.5 top-3.5 h-4 w-4 text-muted-foreground/40" />
                                        <Input v-model="form.join_date" type="date" class="rounded-xl h-11 pl-10 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    </div>
                                    <p v-if="form.errors.join_date" class="text-[10px] text-red-500 ml-1">{{ form.errors.join_date }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Status Kepegawaian</Label>
                                    <Select v-model="form.employment_type">
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih status..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="Tetap">Karyawan Tetap</SelectItem>
                                            <SelectItem value="Kontrak">Kontrak</SelectItem>
                                            <SelectItem value="Harian">Harian Lepas</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.employment_type" class="text-[10px] text-red-500 ml-1">{{ form.errors.employment_type }}</p>
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
                                        <Input v-model="form.basic_salary" type="number" placeholder="0" class="rounded-xl h-11 pl-12 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 tabular-nums" />
                                    </div>
                                    <p v-if="form.errors.basic_salary" class="text-[10px] text-red-500 ml-1">{{ form.errors.basic_salary }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Status</Label>
                                    <Select v-model="form.status">
                                        <SelectTrigger class="rounded-xl h-11 border-border/60 bg-white shadow-none">
                                            <SelectValue placeholder="Pilih status..." />
                                        </SelectTrigger>
                                        <SelectContent class="rounded-xl shadow-none">
                                            <SelectItem value="active">Aktif</SelectItem>
                                            <SelectItem value="inactive">Non-Aktif</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.status" class="text-[10px] text-red-500 ml-1">{{ form.errors.status }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nama Bank</Label>
                                    <Input v-model="form.bank_name" placeholder="Misal: BCA, Mandiri, BRI" class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20" />
                                    <p v-if="form.errors.bank_name" class="text-[10px] text-red-500 ml-1">{{ form.errors.bank_name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-[11px] uppercase tracking-widest text-muted-foreground font-normal ml-1">Nomor Rekening</Label>
                                    <Input v-model="form.bank_account" placeholder="Nomor rekening..." class="rounded-xl h-11 border-border/60 bg-white shadow-none focus-visible:ring-primary/20 font-mono" />
                                    <p v-if="form.errors.bank_account" class="text-[10px] text-red-500 ml-1">{{ form.errors.bank_account }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Right: Side Config -->
                <div class="col-span-12 lg:col-span-4 space-y-6">
                    <!-- Photo Upload -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white p-10 flex flex-col items-center text-center space-y-4">
                        <div 
                            class="h-32 w-32 rounded-3xl bg-slate-50 border-2 border-dashed border-border/60 flex flex-col items-center justify-center text-muted-foreground group cursor-pointer hover:border-primary/40 hover:bg-primary/5 transition-all overflow-hidden relative"
                            @click="photoInput?.click()"
                        >
                            <img v-if="photoPreview" :src="photoPreview" class="absolute inset-0 w-full h-full object-cover" />
                            <div v-else class="flex flex-col items-center justify-center">
                                <Camera class="h-8 w-8 mb-2 opacity-20 group-hover:opacity-100 transition-opacity" />
                                <span class="text-[10px] font-normal uppercase tracking-widest">Upload Foto</span>
                            </div>
                            <input 
                                type="file" 
                                ref="photoInput" 
                                class="hidden" 
                                accept="image/*"
                                @change="handlePhotoChange"
                            />
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-sm font-normal text-slate-900">Foto Profil</h4>
                            <p class="text-[11px] text-muted-foreground font-normal">Format JPG, PNG (Max 2MB)</p>
                            <p v-if="form.errors.photo" class="text-[10px] text-red-500">{{ form.errors.photo }}</p>
                        </div>
                    </Card>

                    <!-- Documents Section -->
                    <Card class="rounded-[2rem] border-border/40 shadow-none bg-white overflow-hidden p-0">
                        <div class="px-8 py-6 border-b border-border/40 bg-slate-50/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-orange-500/5 flex items-center justify-center text-orange-600">
                                    <CreditCard class="h-4 w-4" />
                                </div>
                                <h3 class="text-sm font-bold uppercase tracking-widest text-slate-900">Dokumen Pendukung</h3>
                            </div>
                            <Button type="button" variant="outline" size="sm" class="h-8 rounded-full text-[10px] uppercase tracking-wider" @click="addDocumentRow">
                                Tambah
                            </Button>
                        </div>
                        <div class="p-0">
                            <div v-if="additionalDocuments.length === 0" class="text-center py-12 px-6">
                                <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center mx-auto mb-3 text-slate-300">
                                    <CreditCard class="h-6 w-6" />
                                </div>
                                <p class="text-[11px] text-slate-400 font-normal">Belum ada dokumen tambahan.</p>
                            </div>
                            
                            <div v-else class="divide-y divide-slate-100">
                                <!-- Table Header -->
                                <div class="px-6 py-3 flex items-center bg-slate-50/30 text-[10px] uppercase tracking-[0.15em] text-slate-400 font-bold">
                                    <div class="w-24">Tipe</div>
                                    <div class="flex-1 px-4">Berkas</div>
                                    <div class="w-10 text-right">Aksi</div>
                                </div>

                                <!-- Table Rows -->
                                <div v-for="(doc, index) in additionalDocuments" :key="index" class="px-6 py-4 flex items-center group hover:bg-slate-50/50 transition-colors">
                                    <!-- Type Selection -->
                                    <div class="w-24 shrink-0">
                                        <Select v-model="doc.type">
                                            <SelectTrigger class="h-8 rounded-lg text-[10px] uppercase tracking-wider border-0 bg-slate-100/50 hover:bg-slate-100 shadow-none ring-0 focus:ring-0">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="KTP">KTP</SelectItem>
                                                <SelectItem value="NPWP">NPWP</SelectItem>
                                                <SelectItem value="Kontrak">Kontrak</SelectItem>
                                                <SelectItem value="CV">CV / Resume</SelectItem>
                                                <SelectItem value="Lainnya">Lainnya</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <!-- File Preview/Info -->
                                    <div class="flex-1 px-4 flex items-center gap-3 overflow-hidden">
                                        <div 
                                            class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 bg-white flex items-center justify-center cursor-pointer overflow-hidden group/thumb"
                                            @click="docInputs[index]?.click()"
                                        >
                                            <img v-if="doc.preview" :src="doc.preview" class="h-full w-full object-cover" />
                                            <div v-else class="flex items-center justify-center text-slate-300 group-hover/thumb:text-primary transition-colors">
                                                <Camera class="h-4 w-4" />
                                            </div>
                                        </div>
                                        <div class="flex flex-col min-w-0">
                                            <p 
                                                class="text-[11px] font-bold text-slate-600 truncate cursor-pointer hover:text-primary transition-colors"
                                                @click="docInputs[index]?.click()"
                                            >
                                                {{ doc.file ? doc.file.name : 'Pilih file...' }}
                                            </p>
                                            <p v-if="doc.file" class="text-[9px] text-slate-400 font-normal uppercase tracking-tighter">
                                                {{ (doc.file.size / 1024).toFixed(1) }} KB
                                            </p>
                                        </div>
                                        <input 
                                            type="file" 
                                            :ref="(el) => { if (el) docInputs[index] = el as any }" 
                                            class="hidden" 
                                            @change="handleDocumentFileChange(index, $event)"
                                        />
                                    </div>

                                    <!-- Actions -->
                                    <div class="w-10 text-right">
                                        <Button 
                                            type="button" 
                                            variant="ghost" 
                                            size="icon" 
                                            class="h-8 w-8 rounded-full text-slate-300 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition-all" 
                                            @click="removeDocumentRow(index)"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Existing Documents (if editing) -->
                            <div v-if="isEditing && employee.documents?.length > 0" class="border-t border-slate-100 bg-slate-50/20">
                                <div class="px-6 py-4">
                                    <h4 class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 mb-4">Dokumen Tersimpan</h4>
                                    <div class="space-y-2">
                                        <div v-for="doc in employee.documents" :key="doc.id" class="flex items-center justify-between p-3 rounded-2xl bg-white border border-slate-100 hover:border-slate-200 transition-all shadow-sm shadow-slate-200/20">
                                            <div class="flex items-center gap-3">
                                                <div class="h-9 w-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                                                    <CreditCard class="h-4 w-4" />
                                                </div>
                                                <div class="overflow-hidden">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 leading-none">{{ doc.type }}</span>
                                                        <Badge class="h-4 px-1 rounded text-[8px] font-normal border-0 bg-blue-50 text-blue-500 shadow-none uppercase">{{ doc.file_type.split('/')[1] }}</Badge>
                                                    </div>
                                                    <p class="text-[11px] font-bold text-slate-700 truncate mt-1">{{ doc.file_name }}</p>
                                                </div>
                                            </div>
                                            <a :href="`/storage/${doc.file_path}`" target="_blank" class="shrink-0 ml-4">
                                                <Button variant="ghost" size="sm" class="h-8 rounded-lg text-[10px] uppercase tracking-wider font-bold text-blue-500 hover:text-blue-600 hover:bg-blue-50">Lihat</Button>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <Switch 
                                :checked="form.create_user" 
                                @update:checked="form.create_user = $event"
                                :disabled="isEditing && !!employee?.user_id"
                                class="data-[state=checked]:bg-blue-500 border-white/20" 
                            />
                        </div>
                        
                        <div v-if="form.create_user" class="space-y-8 animate-in fade-in slide-in-from-top-4 duration-300">
                            <div class="space-y-2">
                                <Label class="text-[10px] uppercase tracking-widest text-white/50 font-normal">System Role</Label>
                                <Select v-model="form.role">
                                    <SelectTrigger class="rounded-xl h-11 border-white/10 bg-white/5 text-white shadow-none focus:ring-blue-500/20">
                                        <SelectValue placeholder="Pilih Role" />
                                    </SelectTrigger>
                                    <SelectContent class="bg-slate-800 border-white/10 text-white">
                                        <SelectItem v-for="role in roles" :key="role" :value="role">{{ role }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.role" class="text-[10px] text-red-400">{{ form.errors.role }}</p>
                            </div>
                            <div v-if="!isEditing" class="space-y-2">
                                <Label class="text-[10px] uppercase tracking-widest text-white/50 font-normal">Password Awal</Label>
                                <Input v-model="form.password" type="password" placeholder="Minimal 8 karakter..." class="rounded-xl h-11 border-white/10 bg-white/5 text-white shadow-none focus-visible:ring-blue-500/20" />
                                <p v-if="form.errors.password" class="text-[10px] text-red-400">{{ form.errors.password }}</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <Info class="h-4 w-4 text-blue-400 shrink-0 mt-0.5" />
                                <p class="text-[11px] text-white/60 leading-relaxed font-normal">
                                    {{ isEditing ? 'Akun sistem sudah tertaut.' : 'User baru akan dibuat otomatis saat data pegawai disimpan.' }}
                                </p>
                            </div>
                        </div>
                        <div v-else class="flex items-start gap-3">
                            <Info class="h-4 w-4 text-blue-400 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-white/60 leading-relaxed font-normal">Aktifkan switch di atas jika pegawai memerlukan akses login ke aplikasi ini.</p>
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
        </form>
    </AppLayout>
</template>
