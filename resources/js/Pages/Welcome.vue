<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Gotiau AI — Autonomous Marketing & CS Operating System" />

    <div class="min-h-screen bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white font-sans antialiased overflow-x-hidden">
        <!-- Ambient Glow Backgrounds -->
        <div class="pointer-events-none fixed top-0 left-1/2 -translate-x-1/2 h-[600px] w-[1000px] rounded-full bg-gradient-to-tr from-indigo-600/30 via-purple-600/20 to-pink-500/10 blur-[120px]"></div>

        <!-- Header Navigation -->
        <header class="relative z-20 mx-auto flex max-w-7xl items-center justify-between px-6 py-6 border-b border-slate-800/60 backdrop-blur-md">
            <Link href="/" class="transition hover:opacity-90">
                <ApplicationLogo />
            </Link>

            <nav v-if="canLogin" class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 transition hover:bg-indigo-500"
                >
                    Masuk ke Dashboard →
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-sm font-medium text-slate-300 transition hover:text-white"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/30 transition hover:bg-indigo-500"
                    >
                        Daftar Akun Baru
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="relative z-10 mx-auto max-w-7xl px-6 pt-20 pb-16 text-center">
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-4 py-1.5 text-xs font-semibold text-indigo-300 backdrop-blur-md mb-6">
                <span class="flex h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span>
                Gotiau AI Operating System v2.0 Live
            </div>

            <h1 class="mx-auto max-w-4xl text-4xl font-extrabold tracking-tight text-white sm:text-6xl lg:text-7xl leading-tight">
                Biarkan AI Menjadi <br />
                <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                    Tim Marketing & CS Otonom Anda
                </span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-300 leading-relaxed">
                Gotiau AI meriset pasar, merancang 3 strategi konten harian, memposting otomatis ke Instagram, Facebook, TikTok, & X via Repliz, serta melayani pengunjung website Anda 24/7!
            </p>

            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <Link
                    :href="route('register')"
                    class="rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-8 py-4 text-base font-bold text-white shadow-xl shadow-indigo-500/25 transition hover:scale-105"
                >
                    Mulai Sekarang Gratis 🚀
                </Link>
                <Link
                    :href="route('login')"
                    class="rounded-xl border border-slate-700 bg-slate-900/80 px-8 py-4 text-base font-semibold text-slate-200 backdrop-blur-md transition hover:bg-slate-800"
                >
                    Masuk ke Sistem
                </Link>
            </div>
        </section>

        <!-- Feature Grid Section -->
        <section class="relative z-10 mx-auto max-w-7xl px-6 py-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white">Fitur Unggulan Gotiau AI</h2>
                <p class="mt-2 text-slate-400">Dirancang khusus untuk mengotomatisasi pertumbuhan bisnis Anda secara total.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Feature 1 -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-xl transition hover:border-indigo-500/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/10 text-2xl text-indigo-400 mb-4">
                        🧠
                    </div>
                    <h3 class="text-lg font-bold text-white">Autonomous AI Brain</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Menyelami masalah audiens (seperti komisi marketplace & COD) dan meriset angle marketing terbaik secara mandiri.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-xl transition hover:border-purple-500/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500/10 text-2xl text-purple-400 mb-4">
                        🚀
                    </div>
                    <h3 class="text-lg font-bold text-white">Repliz Social Publishing</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Otomatis menerbitkan video TikTok, poster Instagram, dan thread X pada jam-jam puncak keterlibatan audiens.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-xl transition hover:border-pink-500/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pink-500/10 text-2xl text-pink-400 mb-4">
                        🔑
                    </div>
                    <h3 class="text-lg font-bold text-white">Smart Key Pool & Failover</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Menyimpan banyak API Key Gemini/Telegram di database dengan rotasi otomatis & penanganan batas kuota (429).
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 backdrop-blur-xl transition hover:border-emerald-500/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/10 text-2xl text-emerald-400 mb-4">
                        💬
                    </div>
                    <h3 class="text-lg font-bold text-white">Embeddable CS AI Widget</h3>
                    <p class="mt-2 text-sm text-slate-400">
                        Cukup salin 1 baris kode ke website Anda (seperti waperu.com), dan AI akan melayani pesan pengunjung 24 jam nonstop!
                    </p>
                </div>
            </div>
        </section>

        <!-- Case Study Banner -->
        <section class="relative z-10 mx-auto max-w-7xl px-6 py-12">
            <div class="rounded-3xl border border-indigo-500/20 bg-gradient-to-r from-indigo-950 via-slate-900 to-purple-950 p-8 md:p-12 shadow-2xl">
                <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                    <div>
                        <span class="rounded-full bg-indigo-500/20 px-3 py-1 text-xs font-bold text-indigo-300 uppercase">
                            Studi Kasus Klien Pertama
                        </span>
                        <h3 class="mt-3 text-3xl font-extrabold text-white">
                            Pemasaran Otonom untuk Waperu.com
                        </h3>
                        <p class="mt-2 text-slate-300 max-w-2xl">
                            Mengedukasi seller online Indonesia tentang keuntungan Toko Online Mandiri 0% Komisi, QRIS langsung ke rekening, dan integrasi kurir COD secara otomatis 24/7.
                        </p>
                    </div>
                    <a
                        href="https://waperu.com"
                        target="_blank"
                        class="shrink-0 rounded-xl bg-white px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg hover:bg-slate-100 transition"
                    >
                        Kunjungi Waperu.com ↗
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-slate-900 py-12 text-center text-xs text-slate-500">
            &copy; 2026 Gotiau AI Platform. All rights reserved. Built with Laravel 13, Vue 3 Inertia, Gemini 2.0 & Repliz Engine.
        </footer>
    </div>
</template>
