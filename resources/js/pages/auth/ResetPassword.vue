<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();
</script>

<template>
    <AuthBase
        title="Reset password"
        description="Please enter your new password below to reset your account access."
    >
        <Head title="Reset password" />

        <Form
            v-bind="update()"
            :defaults="{ token, email }"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-white/70">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="password" class="text-white/70">New Password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autofocus
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-white/70">Confirm</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                            class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                        />
                    </div>
                </div>
                <div class="col-span-2">
                    <InputError :message="errors.password" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full bg-primary text-white hover:bg-primary/90 shadow-coral h-10 font-bold rounded-xl transition-all active:scale-95"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Reset Password
                </Button>
            </div>
        </Form>
    </AuthBase>
</template>
