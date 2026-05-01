<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
</script>

<template>
    <AuthBase
        title="Confirm password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <Form
            v-bind="store()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label for="password" class="text-white/70">Password</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-primary text-white hover:bg-primary/90 shadow-coral h-10 font-bold rounded-xl transition-all active:scale-95"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Confirm
            </Button>
        </Form>
    </AuthBase>
</template>
