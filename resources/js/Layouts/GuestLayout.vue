<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-gray-900 via-indigo-950 to-slate-900 px-4 py-12">
        <!-- Ambient Glowing Background Circles -->
        <div class="pointer-events-none absolute top-1/4 left-1/4 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-1/4 right-1/4 h-96 w-96 rounded-full bg-purple-600/20 blur-3xl"></div>

        <div class="relative z-10 flex flex-col items-center">
            <Link href="/" class="transition hover:opacity-90">
                <ApplicationLogo />
            </Link>
        </div>

        <div class="relative z-10 mt-8 w-full overflow-hidden rounded-2xl border border-white/10 bg-white/95 p-8 shadow-2xl backdrop-blur-xl sm:max-w-md">
            <slot />
        </div>

        <div class="relative z-10 mt-6 text-center text-xs text-gray-400">
            &copy; Gotiau AI Platform. Autonomous Marketing & CS Operating System.
        </div>
    </div>
</template>
