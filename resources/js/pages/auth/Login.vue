<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Github, Chrome } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const loginWithSocial = (provider: string) => {
    window.location.href = `/auth/${provider}/redirect`;
};
</script>

<template>
    <AuthBase
        title="Welcome back"
        description="Enter your credentials to access your account"
    >
        <Head :title="`Login - ${$page.props.name}`" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-400 bg-green-500/10 py-2 rounded-lg border border-green-500/20"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-4">
                <div class="grid gap-1.5">
                    <Label for="email" class="text-foreground/70 font-medium ml-1 text-xs">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <div class="flex items-center justify-between ml-1">
                        <Label for="password" class="text-foreground/70 font-medium text-xs">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-[10px] text-primary hover:underline transition-colors"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="bg-white/60 border-foreground/5 text-foreground placeholder:text-foreground/20 focus:border-primary/30 focus:ring-primary/10 rounded-2xl h-11 text-sm"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center space-x-2 ml-1">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="h-3.5 w-3.5 rounded border-foreground/10 bg-white/60 text-primary focus:ring-offset-0 focus:ring-1 focus:ring-primary/30 cursor-pointer"
                    />
                    <Label for="remember" class="text-[10px] text-muted-foreground cursor-pointer font-medium">Keep me logged in</Label>
                </div>

                <Button
                    type="submit"
                    class="mt-1 w-full bg-gradient-coral text-white hover:opacity-90 shadow-coral h-11 font-semibold text-base rounded-2xl transition-all active:scale-95"
                    :tabindex="4"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Sign in
                </Button>

                <div class="relative mt-1">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-foreground/5" />
                    </div>
                    <div class="relative flex justify-center text-[9px] uppercase tracking-widest">
                        <span class="bg-white/80 backdrop-blur px-3 text-muted-foreground/40">Or continue with</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <Button
                        variant="outline"
                        type="button"
                        class="bg-white/60 border-foreground/5 text-foreground hover:bg-white/80 transition-all rounded-2xl h-10 text-xs"
                        @click="loginWithSocial('google')"
                    >
                        <Chrome class="mr-2 h-3.5 w-3.5 opacity-70" /> Google
                    </Button>
                    <Button
                        variant="outline"
                        type="button"
                        class="bg-white/60 border-foreground/5 text-foreground hover:bg-white/80 transition-all rounded-2xl h-10 text-xs"
                        @click="loginWithSocial('github')"
                    >
                        <Github class="mr-2 h-3.5 w-3.5 opacity-70" /> Github
                    </Button>
                </div>
            </div>

            <div
                class="text-center text-xs text-muted-foreground/60"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink :href="register()" class="text-primary font-semibold hover:underline ml-1" :tabindex="5">Create one for free</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
