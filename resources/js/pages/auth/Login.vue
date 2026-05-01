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
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="text-white/70">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="name@company.com"
                        class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-white/70">Password</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs text-primary hover:text-primary/80 transition-colors"
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
                        class="bg-white/5 border-white/10 text-white placeholder:text-white/20 focus:border-primary/50 focus:ring-primary/20 rounded-xl h-10"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center space-x-3">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="h-4 w-4 rounded border-white/20 bg-white/5 text-primary focus:ring-offset-0 focus:ring-1 focus:ring-primary/50 cursor-pointer"
                    />
                    <Label for="remember" class="text-xs text-white/50 cursor-pointer font-normal">Keep me logged in</Label>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full bg-primary text-white hover:bg-primary/90 shadow-coral h-10 font-bold rounded-xl transition-all active:scale-95"
                    :tabindex="4"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Sign in
                </Button>

                <div class="relative mt-2">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-white/5" />
                    </div>
                    <div class="relative flex justify-center text-[10px] uppercase tracking-widest">
                        <span class="bg-[hsl(220_18%_12%)] px-2 text-white/30">Or continue with</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <Button
                        variant="outline"
                        type="button"
                        class="bg-white/5 border-white/10 text-white hover:bg-white/10 hover:border-white/20 transition-all rounded-xl h-10"
                        @click="loginWithSocial('google')"
                    >
                        <Chrome class="mr-2 h-4 w-4 text-white/60" /> Google
                    </Button>
                    <Button
                        variant="outline"
                        type="button"
                        class="bg-white/5 border-white/10 text-white hover:bg-white/10 hover:border-white/20 transition-all rounded-xl h-10"
                        @click="loginWithSocial('facebook')"
                    >
                        <Github class="mr-2 h-4 w-4 text-white/60" /> Github
                    </Button>
                </div>
            </div>

            <div
                class="text-center text-sm text-white/40"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink :href="register()" class="text-primary hover:underline ml-1" :tabindex="5">Create one for free</TextLink>
            </div>
        </Form>
    </AuthBase>
</template>
