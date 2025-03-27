<script setup lang="ts">
import { computed, ref, onMounted, nextTick, watch } from "vue";

declare global {
    interface Window {
        Echo: any;
        Pusher: any;
    }
}
import { usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AuthenticatedLayout.vue";
import { useInitials } from "@/composables/useInitials";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import type { User } from "@/types";
import Pusher from "pusher-js";
import axios from "axios";
import Echo from "laravel-echo";
import { SidebarTrigger } from '@/components/ui/sidebar';

// Get authenticated user data
const page = usePage();
const authUser = computed(() => (page.props.auth as { user: User }).user);
const user = computed(() => (page.props.user as User));

// Props
interface Props {
    user: User;
}
const props = withDefaults(defineProps<Props>(), {});

// Utilities
const { getInitials } = useInitials();
const avatarUrl = computed(() =>
    props.user.avatar ? `/storage/${props.user.avatar}` : null
);

// Messages
const messages = ref<Array<{ id: number; message: string; who: string }>>(page.props.messages as Array<{ id: number; message: string; who: string }> || []);
const message = ref("");
const chatContainer = ref<HTMLElement | null>(null);
const isTyping = ref(false);

// ✅ Auto-scroll to latest message with smooth behavior
const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTo({
                top: chatContainer.value.scrollHeight,
                behavior: "smooth",
            });
        }
    });
};

// ✅ Initialize Laravel Echo
onMounted(() => {
    // if (!window.Echo) {
    //     window.Pusher = Pusher;
    //     window.Echo = new Echo({
    //         broadcaster: "reverb",
    //         key: import.meta.env.VITE_REVERB_APP_KEY,
    //         wsHost: import.meta.env.VITE_REVERB_HOST,
    //         wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    //         wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    //         forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    //         enabledTransports: ["ws", "wss"],
    //     });
    // }

    // ✅ Listen for messages
    interface MessageReceivedEvent {
        message: string;
        who: string;
    }

    window.Echo.private("messages." + authUser.value.id).listen("MessageReceived", (e: MessageReceivedEvent) => {
        messages.value.push({ 
            id: Date.now(), 
            message: e.message, 
            who: e.who 
        });
        scrollToBottom();
    });

    // ✅ Listen for "typing" status using Laravel Echo's "whisper"
    window.Echo.private("messages." + authUser.value.id).listenForWhisper("typing", (e: { user_id: number }) => {
        if (e.user_id !== authUser.value.id) {
            isTyping.value = true;
            setTimeout(() => (isTyping.value = false), 2000);
        }
    });

    // ✅ Scroll to latest message on page load
    scrollToBottom();
});

// ✅ Detect typing
watch(message, (newVal) => {
    if (newVal.length > 0) {
        window.Echo.private("messages." + props.user.id).whisper("typing", {
            user_id: authUser.value.id,
            user_name: authUser.value.name,
        });
    }
});

// ✅ Send message (handles Enter keypress)
const handleSubmit = async (event?: KeyboardEvent) => {
    if (event && event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
};

// ✅ Send message function
const sendMessage = () => {
    if (!message.value.trim()) return;

    const msg = {
        id: Date.now(),
        message: message.value.trim(),
        who: "me",
    };

    messages.value.push(msg);
    scrollToBottom();

    axios
        .post("/messages", {
            message: msg.message,
            id: props.user.id,
        })
        .catch((error) => {
            console.error("Failed to send message:", error.response.data);
        });

    message.value = "";
    isTyping.value = false;
};
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="flex items-center px-4 py-3 border-b bg-white dark:bg-neutral-950 rounded-t-xl">
            <!-- <SidebarTrigger class="-ml-1" /> -->
            <Avatar class="size-10 overflow-hidden rounded-md">
                <AvatarImage v-if="avatarUrl" :src="avatarUrl" :alt="user.name" />
                <AvatarFallback class="rounded-lg text-black dark:text-white">
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="ml-3 text-left">
                <p class="font-semibold text-gray-900 dark:text-white">
                    {{ user.name }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Online</p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div
            ref="chatContainer"
            class="flex-1 overflow-y-auto p-4 bg-gray-100 dark:bg-neutral-950 max-h-[73vh]"
        >
            <ul class="space-y-3">
                <li
                    v-for="msg in messages"
                    :key="msg.id"
                    class="flex items-start transition-opacity duration-300"
                    :class="{ 'justify-end': msg.who === 'me' }"
                >
                    <div
                        class="px-4 py-2 rounded-lg max-w-xs text-sm transition-all duration-200 hover:scale-105"
                        :class="{
                            'bg-blue-500 text-white': msg.who === 'me',
                            'bg-gray-200 text-gray-900': msg.who !== 'me'
                        }"
                    >
                        <strong v-if="msg.who !== 'me'" class="text-xs text-gray-500">{{
                            msg.who
                        }}</strong>
                        <p>{{ msg.message }}</p>
                    </div>
                </li>
            </ul>

            <!-- Typing Indicator -->
            <div v-if="isTyping" class="text-gray-500 text-sm mt-2 animate-pulse">
                Typing...
            </div>
        </div>

        <!-- Chat Input -->
        <div class="p-4 border-t bg-white dark:bg-neutral-950 rounded-b-lg">
            <form @submit.prevent="sendMessage">
                <div class="relative">
                    <textarea
                        v-model="message"
                        @keydown="handleSubmit"
                        placeholder="Type a message..."
                        rows="2"
                        class="w-full p-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-neutral-900 dark:text-white resize-none transition-all duration-200"
                    ></textarea>
                    <button
                        type="submit"
                        class="absolute right-2 bottom-2 px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-all duration-200"
                    >
                        Send
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>