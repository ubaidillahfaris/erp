<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthBase
        title="Join the ecosystem"
        description="Start scaling your business with our all-in-one platform"
    >
        <Head :title="`Register - ${$page.props.name}`" />

        <Form
            v-bind="store()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label for="name" class="text-foreground/70 font-medium ml-1 text-xs">Full Name</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="John Doe"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="email" class="text-foreground/70 font-medium ml-1 text-xs">Email Address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="name@company.com"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="password" class="text-foreground/70 font-medium ml-1 text-xs">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="••••••••"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="password_confirmation" class="text-foreground/70 font-medium ml-1 text-xs">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="••••••••"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full bg-gradient-coral text-white hover:opacity-90 shadow-coral h-11 font-semibold text-base rounded-2xl transition-all active:scale-95"
                    tabindex="5"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-xs text-muted-foreground/60">
                Already have an account?
                <Link
                    :href="login()"
                    class="text-primary hover:underline ml-1 font-semibold"
                    :tabindex="6"
                >Sign in instead</Link>
            </div>
        </Form>
    </AuthBase>
</template>
