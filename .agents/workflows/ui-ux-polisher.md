---
description: Memoles dan memperbaiki UI/UX suatu halaman (Vue Component) menggunakan Shadcn Vue dan Tailwind CSS.
---

# Workflow: UI/UX Polisher dengan Shadcn Vue

Workflow ini menugaskan AI Agent sebagai **ahli UI/UX** untuk merefaktor komponen Vue yang ada agar memiliki tampilan yang jauh lebih modern, bersih, intuitif, dan responsif. AI akan memanfaatkan **Shadcn Vue** dan **Tailwind CSS**.

## Langkah Kerja AI Agent

Ketika workflow ini dijalankan, AI harus mengikuti prosedur berikut secara sistematis:

### 1. Analisis Komponen Saat Ini
- Periksa struktur HTML/Vue (`<template>`) yang ditunjuk oleh user atau yang terakhir diubah.
- Pahami tujuan dari halaman/komponen tersebut (misal: "Halaman Daftar Produk", "Form Pembuatan Pengguna baru", "Dashboard Card").
- Temukan titik lemah UI:
  - Apakah padding/margin tidak rapi atau tidak konsisten?
  - Apakah tombol tidak punya state `hover` atau feedback?
  - Apakah penggunaan warna tidak mengikuti standar theme (`text-primary`, `bg-background`, dll)?
  - Apakah input form biasa `input[type="text"]` bisa digantikan oleh Shadcn `<Input>`?

### 2. Identifikasi Komponen Shadcn Vue yang Diperlukan
- Evaluasi komponen Shadcn yang cocok untuk halaman tersebut:
  - Container / Wrapper: `<Card>`, `<CardHeader>`, `<CardTitle>`, `<CardContent>`.
  - Formulir: `<Input>`, `<Select>`, `<Checkbox>`, `<Switch>`, `<Label>`.
  - Tabel: `<Table>`, `<TableHeader>`, `<TableRow>`, `<TableHead>`, `<TableBody>`, `<TableCell>`.
  - Aksi/Interaksi: `<Button>`, `<DropdownMenu>`, `<Sheet>`, `<Dialog>`, `<Tooltip>`.
  - Status/Feedback: `<Badge>`, `<Skeleton>`, `<Alert>`, `<Toast>`.
- Jika ada komponen Shadcn yang belum tepasang di `resources/js/Components/ui/`, informasikan ke user atau jalankan perintah instalasi (`npx shadcn-vue@latest add [nama-komponen]`).

### 3. Implementasi (Refactoring Code)
- Lakukan refaktorisasi pada bagian `<script setup>` untuk mengimpor komponen Shadcn yang dibutuhkan. Pastikan path import benar (cth: `import { Button } from '@/components/ui/button'`).
- Rombak `<template>` menggunakan standar Shadcn Vue dan utility class Tailwind yang rapi:
  - Terapkan layout grid atau flex yang modern (`gap-4`, `grid-cols-1 md:grid-cols-2`).
  - Hapus style lama berupa CSS mentah dan ganti menjadi utility classes.
  - Perhatikan responsivitas (`sm:`, `md:`, `lg:`).
  - Terapkan ikonisasi menggunakan `lucide-vue-next`.
- Terapkan Empty State, Loading State, dan proper error validation handling.

### 4. Quality Assurance (Review Visual)
- Cek ulang penamaan kelas Tailwind agar tidak terjadi bentrok.
- Terangkan kepada user perubahan apa saja yang telah dilakukan.
- Berikan pratinjau hasil akhir dan minta user memastikannya di browser apakah sudah memuaskan atau perlu perbaikan estetika tambahan.

---

**Perintah Pengguna:** Saat kamu ingin AI memperbaiki UI dari file tertentu, kamu bisa mengetik: 
`/ui-ux-polisher resources/js/Pages/NamaFile.vue` atau men-@ file tersebut dan memanggil `/ui-ux-polisher`.
