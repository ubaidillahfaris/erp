---
name: components
description: A collection of reusable UI components for the Warung ERP project.
---

# Project Components

This skill documents custom, high-level components developed for the Warung ERP system that are not part of the standard Shadcn UI library.

## Map Component (Leaflet)

A reactive geographic map component built with Leaflet for Vue 3.

### Usage

```vue
<script setup lang="ts">
import Map from '@/components/Map.vue';

const markers = [
    { position: [-6.200000, 106.816666], title: 'Jakarta HQ', content: 'Main hub' }
];
</script>

<template>
    <div class="h-[400px]">
        <Map :center="[-6.2, 106.8]" :zoom="13" :markers="markers" />
    </div>
</template>
```

### Implementation Details
- **Location**: `resources/js/components/Map.vue`
- **Dependency**: `leaflet`
- **Aesthetic**: Integrated with the "Blue Chips Chicago" design system (clean borders, subtle shadows).
- **Features**: Responsive resizing (`invalidateSize`), custom marker support, and fixed icon assets for Vite compatibility.

### Best Practices
- Always wrap the `<Map />` in a container with a defined height.
- Use `z-0` on the map container if it conflicts with overlapping UI like headers or dropdowns.
- For dynamic data, the component handles state re-rendering via Vue hooks.

## Standard Page Layout

The project uses a standardized high-density layout pattern for all main data pages. This pattern consists of a `PageHeader` (for identity and primary actions) and a `DataTable` (for data visualization and filtering).

### Usage

```vue
<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';

// ... logic ...
</script>

<template>
    <Head title="Modul Name" />

    <div class="px-8 py-10 flex flex-col gap-8 bg-[#F8F9FA] min-h-[calc(100vh-64px)] font-sans">
        <!-- Identity -->
        <PageHeader 
            title="Kelola Data" 
            description="Manajemen informasi utama" 
            back-href="/dashboard"
            :count="data.total"
        />

        <!-- Main Content Area & Primary Actions -->
        <div class="max-w-7xl mx-auto w-full">
            <DataTable
                :data="data"
                :columns="columns"
                v-model:search="search"
                v-model:perPage="perPage"
                search-placeholder="Cari data..."
                title="Daftar Informasi"
                :total-count="data.total"
            >
                <template #header-actions>
                    <Button primary>
                        <Plus class="h-4 w-4" />
                        Tambah Baru
                    </Button>
                </template>
                <!-- Slots for cells and actions -->
            </DataTable>
        </div>
    </div>
</template>
```

### Key Components
- **PageHeader**: Controls the page title, back navigation, and global record counts. It provides context for the current view.
- **DataTable**: Now handles the primary page actions via the `#header-actions` slot (Top Row), in addition to tabs, search, and pagination. This keeps actionable items within the context of the data they affect.
