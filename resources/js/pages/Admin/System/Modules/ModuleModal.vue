<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/Admin/ModuleManagerController';

interface Module {
    id?: number;
    name: string;
    slug: string;
    version: string;
    icon: string;
    is_active: boolean;
    order_priority: number;
}

const props = defineProps<{
    open: boolean;
    module: Module | null;
}>();

const emit = defineEmits(['update:open', 'success']);

const form = useForm({
    name: '',
    slug: '',
    version: '1.0.0',
    icon: 'package',
    is_active: true,
    order_priority: 0,
});

watch(() => props.module, (newModule) => {
    if (newModule) {
        form.name = newModule.name;
        form.slug = newModule.slug;
        form.version = newModule.version || '1.0.0';
        form.icon = newModule.icon || 'package';
        form.is_active = newModule.is_active;
        form.order_priority = newModule.order_priority;
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.module?.id) {
        form.put(update({ module: props.module.id }).url, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
            },
        });
    } else {
        form.post(store().url, {
            onSuccess: () => {
                emit('update:open', false);
                emit('success');
            },
        });
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[425px] font-sans">
            <DialogHeader>
                <DialogTitle class="text-xl font-bold tracking-tight">
                    {{ module ? 'Edit Module' : 'Register New Module' }}
                </DialogTitle>
                <DialogDescription class="text-slate-500">
                    Define the core module identity and operational status.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <div class="grid gap-2">
                    <Label for="name" class="text-xs font-bold uppercase tracking-widest text-slate-400">Module Name</Label>
                    <Input id="name" v-model="form.name" placeholder="e.g. Inventory Management" class="h-10 rounded-lg" />
                    <div v-if="form.errors.name" class="text-xs text-destructive font-bold">{{ form.errors.name }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="slug" class="text-xs font-bold uppercase tracking-widest text-slate-400">Slug</Label>
                    <Input id="slug" v-model="form.slug" placeholder="e.g. inventory" class="h-10 rounded-lg font-mono text-sm" :disabled="!!module" />
                    <div v-if="form.errors.slug" class="text-xs text-destructive font-bold">{{ form.errors.slug }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="version" class="text-xs font-bold uppercase tracking-widest text-slate-400">Version</Label>
                        <Input id="version" v-model="form.version" placeholder="1.0.0" class="h-10 rounded-lg font-mono text-sm" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="priority" class="text-xs font-bold uppercase tracking-widest text-slate-400">Priority</Label>
                        <Input id="priority" type="number" v-model="form.order_priority" class="h-10 rounded-lg" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="icon" class="text-xs font-bold uppercase tracking-widest text-slate-400">Icon (Lucide Name)</Label>
                    <Input id="icon" v-model="form.icon" placeholder="package" class="h-10 rounded-lg" />
                </div>

                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-100">
                    <div class="space-y-0.5">
                        <Label class="text-sm font-bold text-slate-700">Module Status</Label>
                        <p class="text-xs text-slate-400">Enable or disable this module system-wide.</p>
                    </div>
                    <Switch :checked="form.is_active" @update:checked="form.is_active = $event" :disabled="module?.slug === 'platform'" />
                </div>
            </div>

            <DialogFooter>
                <Button variant="ghost" @click="emit('update:open', false)" class="font-bold">Cancel</Button>
                <Button @click="submit" class="bg-accent hover:bg-accent/90 text-white font-bold px-6" :disabled="form.processing">
                    {{ module ? 'Save Changes' : 'Register Module' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
