<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthBase
        title="Forgot password"
        description="Enter your email address and we will email you a password reset link."
    >
        <Head title="Forgot password" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-400 bg-green-500/10 py-2 rounded-lg border border-green-500/20"
        >
            {{ status }}
        </div>

        <Form
            v-bind="email()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label for="email" class="text-white/70">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="name@company.com"
                    class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                />
                <InputError :message="errors.email" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full bg-primary text-white hover:bg-primary/90 shadow-coral h-10 font-bold rounded-xl transition-all active:scale-95"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Email Reset Link
            </Button>
        </Form>
    </AuthBase>
</template>
