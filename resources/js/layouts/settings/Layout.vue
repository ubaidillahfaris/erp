<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { Users, ShieldCheck } from 'lucide-vue-next';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => (page.props.auth as any).user);
const isSuperAdmin = computed(() => user.value?.roles?.includes('superadmin'));

const sidebarNavItems = computed((): NavItem[] => {
    const items: NavItem[] = [
        {
            title: 'Profile',
            href: editProfile(),
        },
        {
            title: 'Password',
            href: editPassword(),
        },
        {
            title: 'Two-Factor Auth',
            href: show(),
        },
        {
            title: 'Appearance',
            href: editAppearance(),
        },
    ];

    if (isSuperAdmin.value) {
        items.push(
            {
                title: 'User Management',
                href: '/settings/users',
                icon: Users,
            },
            {
                title: 'Role Management',
                href: '/settings/roles',
                icon: ShieldCheck,
            }
        );
    }

    return items;
});

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
