<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, MessageCircle } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { ref } from 'vue';
import axios from 'axios';
import UserInfo from './UserInfo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: MessageCircle,
    },
];

// const footerNavItems: NavItem[] = [
//     {
//         title: 'Github Repo',
//         href: 'https://github.com/laravel/vue-starter-kit',
//         icon: Folder,
//     },
//     {
//         title: 'Documentation',
//         href: 'https://laravel.com/docs/starter-kits',
//         icon: BookOpen,
//     },
// ];

interface User {
    id: number;
    name: string;
    email: string;
    avatar: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

const users = ref<User[]>([]);

const fetchUsers = async () => {
    try {
        const response = await axios.get('/chat/users');
        users.value = response.data.map((user: any) => ({
            id: user.id,
            name: user.name,
            email_verified_at: user.email_verified_at,
            created_at: user.created_at,
            updated_at: user.updated_at,
            email: user.email,
            avatar: user.avatar,
        }));
    } catch (error) {
        console.error('Failed to fetch users:', error);
    }
};

fetchUsers();
</script>

<template>
    <Sidebar collapsible="icon" variant="floating">

        <!-- header -->
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" as-child>
                    <Link :href="route('dashboard')">
                        <AppLogo />
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <div v-for="user in users" :key="user.id" class="">
                <SidebarMenuButton size="lg" as-child>
                    <Link :href="route('chat', { user: user.id })">
                        <UserInfo :user="user" />
                    </Link>
                </SidebarMenuButton>
            </div>
        </SidebarContent>

        <!-- footer -->
        <!-- <NavFooter :items="footerNavItems" /> -->
        <NavUser />

    </Sidebar>
    <slot />
</template>
