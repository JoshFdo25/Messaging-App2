<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AuthenticatedLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';
import { ref } from 'vue';
import { Pencil } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    avatar: null as File | null,
    avatarUrl: user.avatar ? `/storage/${user.avatar}` : null,
    name: user.name,
    email: user.email,
});

const fileInput = ref<HTMLInputElement | null>(null);

const triggerFileInput = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const handleAvatarChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.avatar = file;
        form.avatarUrl = URL.createObjectURL(file);
    }
};

const getInitials = (name: string) => {
    return name
        ? name
              .split(' ')
              .map((n) => n[0])
              .join('')
              .toUpperCase()
        : '';
};

const submit = () => {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('email', form.email);
    
    if (form.avatar) {
        formData.append('avatar', form.avatar);
    }

    form.post(route('profile.update'), {
        preserveScroll: true,
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your name and email address" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="avatar">Profile Picture</Label>

                        <!-- Clickable Avatar -->
                        <div class="relative w-20 h-20 cursor-pointer group" @click="triggerFileInput">
                            <img 
                                v-if="form.avatarUrl" 
                                :src="form.avatarUrl" 
                                alt="Avatar Preview" 
                                class="w-full h-full rounded-full object-cover border border-black dark:border-gray-300 shadow-sm" 
                            />
                            <div v-else class="w-full h-full flex items-center justify-center rounded-full bg-gray-200 text-gray-600 text-lg font-semibold">
                                {{ getInitials(user.name) }}
                            </div>
                            
                            <!-- Pencil Icon (Changes color on hover) -->
                            <div class="absolute bottom-0 right-0 bg-black dark:bg-gray-300 shadow-sm rounded-full transition-colors duration-200 group-hover:bg-gray-500">
                                <Pencil class="w-6 h-6 p-1 text-white dark:text-black" />
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input
                            id="avatar"
                            type="file"
                            class="hidden"
                            ref="fileInput"
                            @change="handleAvatarChange"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:!decoration-current dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
