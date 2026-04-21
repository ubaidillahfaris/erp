---
description: Merefaktor komponen Vue untuk sistem ERP tingkat enterprise yang memprioritaskan integritas data, efisiensi operasional, kepatuhan WCAG 2.2, dan mengadopsi struktur visual Geometric Grid berlapis aksen Oranye-Persik (Peach-Orange).
---

# Workflow: Enterprise UI/UX Architect V2 (Visual Reference Edition)

Ini adalah rilis terbaru workflow "The Architect V2". Didesain khusus untuk sistem ERP tingkat enterprise yang mensinergikan kerapatan informasi (Information Density), operasional logis, dan referensi desain visual yang canggih (Aksen Oranye-Persik).

## Panduan Visual & Tata Letak

### 1. Grid Geometris yang Bersih
*   Adopsi tata letak kartu yang berbentuk persegi panjang dibulatkan (*rounded rectangular cards*).
*   Jaga konsistensi jarak (*gap*) antar komponen secara presisi.
*   Buat hierarki informasi yang logis menggunakan garis pembatas yang sangat halus (*muted borders* / abu-abu muda).

### 2. Aksen Oranye-Persik (Peach-Orange) Terarah
*   Gunakan warna oranye-persik lembut sebagai aksen utama.
*   **Fokus Penggunaan**: Jangan gunakan secara acak. Warna hanya disorot pada:
    *   Tombol aksi utama (e.g., "Generate Report", "New Invoice").
    *   Metrik sangat kritis atau *highlight* pembeda.
    *   Teks fungsional penentu seperti "Edit" atau penanda status aksi.
    *   Proporsi diagram/grafik untuk memandu mata (*guiding focus*).

### 3. Abstraksi Warna (Semantic CSS)
*   **Dilarang *hardcode*** nilai HEX oranye secara langsung ke komponen.
*   Wajib memetakannya melalui variabel CSS (contoh: `--brand-primary`, `--brand-accent`), sehingga *Dark Mode* tetap konsisten secara inheren tanpa perlu menambahkan utility duplikat.

---

## Langkah Kerja Agent (Systematic Protocol)

### 1. Pre-Refactor: Logic & Data Mapping (Integritas Kode)
Sebelum mengubah HTML:
*   Identifikasi `v-model`, `props`, `emits`. Patenkan event asli saat mengganti wrapper Shadcn.
*   Pastikan tabel menangani padding dengan arsitektur mikro-font (misal teks `11px` tanpa memotong string data kritikal seperti ID Invoice).

### 2. Accessibility Audit (WCAG 2.2 Compliance)
*   **Perceivable**: Teks fungsional harus jelas dan teks `11px` di-*boost* rasio kontrasnya ke WCAG AAA.
*   **Operable**: Navigasi keyboard harus terpetakan logis (*Filter -> Summary -> Table -> Actions*), dengan `focus-visible`.
*   **Semantic**: Elemen button wajib menjadi `<button>`, bukan `div`.

### 3. ERP-Grade Refactoring (Shadcn Implementation)
*   Ubah layout agar **High Information Density**. Jangan pakai `text-xs` jika *horizontal scrolling* terjadi; bergeser ke sizing khusus `11px` jika diperlukan, namun kompensasi warnanya.
*   **Semantic Color Abstraction**: Integrasikan `--brand-primary` di Tailwind / CSS. Ganti aksen konfeti visual (beragam warna warni per komponen) dengan fokus aksen Oranye-Persik + *grayscale* berkelas untuk fungsi non-primer.
*   Pasang Shadcn `<Select>`, `<Card>`, `<Skeleton>`, dsb.

### 4. Technical Validation & QA
*   Sajikan simulasi kerapatan tabel *(Density Mapping)*.
*   Lampirkan bukti integritas event.
*   Definisikan *Color Mapping* di Implementation Plan terlebih dahulu sebelum *write code*.

## Cara Memanggil:
Gunakan perintah:
`/architect resources/js/Pages/[NamaFile].vue`
