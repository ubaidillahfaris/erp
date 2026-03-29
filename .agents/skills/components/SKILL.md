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
