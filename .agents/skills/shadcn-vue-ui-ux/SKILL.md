---
name: shadcn-vue-ui-ux
description: Develops, refactors, and polishes UI/UX using Shadcn Vue and Tailwind CSS. Activates when the user asks for UI/UX improvements, frontend refactoring, or building interfaces with Shadcn Vue components.
---

# Shadcn Vue UI/UX Expert Agent

This skill ensures the AI acts as an expert UI/UX developer specifically for Vue 3 projects utilizing **Shadcn Vue** and **Tailwind CSS**. 

## Core Responsibilities
- **Refactoring & Polishing**: Transform basic or messy Vue templates into clean, modern, and professional interfaces using Shadcn Vue components.
- **Component Reusability**: Prioritize checking `resources/js/Components/ui/` for existing Shadcn components before creating custom ones.
- **Responsive & Accessible**: Ensure all UI elements are fully accessible (ARIA compliant via Shadcn) and perfectly responsive using Tailwind's `sm:`, `md:`, `lg:` breakpoints.
- **Dark Mode Compatibility**: Always consider dark mode. Use semantic Tailwind colors like `bg-background`, `text-foreground`, `border-border`, and `muted-foreground` to ensure native compatibility with Shadcn's layout.

## Rules & Best Practices

1. **Component Installation**: 
   - Before using a new Shadcn component (e.g., Dialog, Command, Select), check if it already exists in the `Components/ui` directory.
   - If a component is missing, YOU MUST tell the user to install it via shell command: `npx shadcn-vue@latest add [component-name]`, OR use your terminal tool to install it if you have permission.

2. **Form Layouts**:
   - Use Shadcn's Form components (`Form`, `FormField`, `FormItem`, `FormLabel`, `FormControl`, `FormMessage`) to build predictable and validatable forms.
   - Implement error handling gracefully using Toast notifications or inline Form messages.

3. **Loading, Alerts, & Empty States**:
   - UX involves feedback! Always implement **Skeletons** (`Skeleton` component) or spinning icons (Lucide Vue `Loader2`) during data fetching phases.
   - Use visually appealing "Empty States" if a table or list has no data (e.g., a subtle dashed border box with an illustration/icon and a call-to-action button).
   - **DIAGRAM/DIALOG/ALERTS**: DILARANG KERAS menggunakan fungsi native browser seperti `alert()`, `confirm()`, atau `prompt()`. Semua pesan peringatan atau konfirmasi aksi destruktif harus menggunakan Shadcn `AlertDialog`, `Dialog`, atau Toast (`sonner`). 
   - Gunakan `sonner` untuk menampilkan flash message atau feedback yang tidak membutuhkan respons blocking, dan `AlertDialog` untuk konfirmasi hapus data.

4. **Icons**:
   - Use `lucide-vue-next` for iconography. Maintain consistent sizing (e.g., `w-4 h-4` or `w-5 h-5`) and stroke widths.

5. **Interactivity**:
   - Add subtle hover states (`hover:bg-accent`, `hover:text-accent-foreground`) and transitions (`transition-colors duration-200`) to clickable elements.
   - Ensure Dropdowns, Dialogs, and Popovers have clear triggers and focus management.

6. **Typography**:
   - Utilize standard Tailwind typography. Use `text-primary` for emphasis, `text-muted-foreground` for subtext or descriptions.
   - Keep heading hierarchies (`h1`, `h2`, `h3`) clear using classes like `text-2xl font-bold tracking-tight`.

7. **Blue Chips Chicago Aesthetic & Guidelines**:
   - **Prinsip Desain Utama**: Sophisticated Cleanliness, Data Clarity, and Layered Depth.
   - **Layout & Structure**:
     - Gunakan layout dua kolom. Sidebar sempit (~20%), Main Dashboard luas (~80%).
     - JANGAN ADA ELEMENT YANG MEPET. Gunakan system spacing konsisten (8px grid: 16px, 24px, 32px).
   - **Layering & Depth**:
     - Gunakan bayangan super halus (*soft drop shadow*) pada card konten utama agar terlihat "mengambang" di atas background dashboard yang bersih.
   - **Color Palette**:
     - **Dashboard Background**: Light Grey-White (`#F8F9FA`) - 60% penggunaan.
     - **Main Card/Primary**: Pure White (`#FFFFFF`) - 30% penggunaan.
     - **Secondary/Active**: Sage Green (`#84A59D`) - 7% (Button, Active Menu, Indicators).
     - **Accent/CTA**: Electric Blue (`#0062FF`) - 3% (CTA Upgrade, Link, Highlights).
   - **Typography (Inter/Poppins/Lato)**:
     - Judul Halaman: Semi-Bold, besar (~24px).
     - Main Data (Figures): Bold, extra besar (~32px+).
     - Labels/Sub-text: Regular, kecil (~12px).
   - **Data Visualization**:
     - Gunakan tipe chart modern: Pyramid (Age/Gender), Radar (Interest), atau Simplified/Dot-matrix Map.
     - Minimalkan penggunaan warna dalam chart; hanya gunakan palette yang telah ditentukan.
   - **Sidebar CTA**: Tambahkan kartu promo/fitur premium dengan gradasi biru yang mencolok di area sidebar.

## Example Activation
When executing formatting or visual enhancements, always start by analyzing the current DOM/Vue Template structure, then replace standard HTML semantic tags like `<button>` with `<Button>`, `<input>` with `<Input>`, and wrap sections in `<Card><CardHeader>...<CardContent>...</Card>` for an instant modern facelift.
