<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Panduan Autopilot AI Marketer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900">
                        📖 Panduan Penggunaan Gotiau AI Platform
                    </h2>
                    <p class="text-xs text-slate-500">
                        Panduan sederhana dari awal sampai AI bekerja 100% otonom 24/7
                    </p>
                </div>
                <span class="rounded-full bg-emerald-100 px-3.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                    ⚡ Autopilot Ready
                </span>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <!-- Welcome Banner -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-purple-950 p-8 text-white shadow-xl border border-slate-800">
                    <div class="max-w-3xl space-y-3">
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur-md border border-white/15">
                            Panduan Pemilik Bisnis
                        </span>
                        <h3 class="text-3xl font-extrabold tracking-tight">
                            Cara Menjalankan AI Marketing Otonom
                        </h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Cukup 3 langkah utama berikut agar AI Marketer meriset tren pasar, menyusun kampanye harian, menerbitkan postingan ke social media, dan melayani pesan calon pelanggan 24/7 tanpa perlu Anda sentuh lagi!
                        </p>
                    </div>
                </div>

                <!-- STEP 1 -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80 flex gap-6 items-start">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-md">
                        1
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-base font-bold text-slate-900">
                                Isi Profil Knowledge Base Brand Anda
                            </h4>
                            <Link
                                :href="route('workspace.settings')"
                                class="rounded-xl bg-indigo-50 px-3.5 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition"
                            >
                                Ke Menu Workspace & Brand →
                            </Link>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            AI membutuhkan data dasar tentang produk Anda agar jawaban & postingan terasa relevan. Masukkan informasi berikut di menu <strong>Workspace & Brand</strong>:
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div>
                                <span class="font-bold text-slate-700">Nama Brand:</span>
                                <p class="text-slate-600">Waperu (Wadah Pejuang Rupiah)</p>
                            </div>
                            <div>
                                <span class="font-bold text-slate-700">Website:</span>
                                <p class="text-slate-600">https://waperu.com</p>
                            </div>
                            <div>
                                <span class="font-bold text-slate-700">Industri & Target:</span>
                                <p class="text-slate-600">SaaS Toko Online 0% Komisi / Seller COD & UMKM</p>
                            </div>
                            <div>
                                <span class="font-bold text-slate-700">Tone of Voice:</span>
                                <p class="text-slate-600">Solutif, Ramah, Semangat Pejuang Rupiah</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80 flex gap-6 items-start">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-md">
                        2
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-base font-bold text-slate-900">
                                Masukkan Google Gemini API Key (Otak AI)
                            </h4>
                            <Link
                                :href="route('api-keys.index')"
                                class="rounded-xl bg-indigo-50 px-3.5 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition"
                            >
                                Ke Menu API Key Pool →
                            </Link>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Dapatkan API Key gratis dari Google AI Studio untuk mengaktifkan pemikiran kecerdasan buatan:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-slate-600 space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <li>Buka <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-indigo-600 font-bold underline">Google AI Studio (aistudio.google.com)</a>.</li>
                            <li>Klik tombol <strong>Create API Key</strong> dan salin kodenya (misal: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono">AIzaSy...</code>).</li>
                            <li>Buka menu <Link :href="route('api-keys.index')" class="text-indigo-600 font-bold underline">API Key Pool</Link>, pilih Provider <strong>Google Gemini AI</strong>, lalu simpan.</li>
                            <li class="text-indigo-700 font-medium">💡 <em>Smart Feature: Anda bisa memasukkan 2-5 API Key sekaligus. Jika kuota 1 akun habis, sistem otomatis berpindah ke akun berikutnya secara seamless!</em></li>
                        </ol>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80 flex gap-6 items-start">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white shadow-md">
                        3
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-base font-bold text-slate-900">
                                Masukkan Repliz Key (Posting Otomatis ke Social Media)
                            </h4>
                            <Link
                                :href="route('api-keys.index')"
                                class="rounded-xl bg-indigo-50 px-3.5 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition"
                            >
                                Ke Menu API Key Pool →
                            </Link>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Repliz digunakan AI untuk menerbitkan postingan otomatis ke Instagram, Facebook, TikTok, & X:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-slate-600 space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <li>Buka Dashboard Repliz Anda dan ambil <strong>Access Key</strong> serta <strong>Secret Key</strong>.</li>
                            <li>Buka menu <Link :href="route('api-keys.index')" class="text-indigo-600 font-bold underline">API Key Pool</Link>, pilih Provider <strong>Repliz API</strong>.</li>
                            <li>Isi kolom Access Key & Secret Key lalu klik <strong>Simpan ke Pool</strong>. Postingan AI akan otomatis terbit ke sosmed Anda!</li>
                        </ol>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80 flex gap-6 items-start">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-600 text-lg font-bold text-white shadow-md">
                        4
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-base font-bold text-slate-900">
                                (Opsional) Hubungkan Bot Telegram untuk Kontrol Lewat HP
                            </h4>
                            <span class="rounded-full bg-purple-100 px-3 py-1 text-[10px] font-bold text-purple-800">
                                ⚡ Auto-Connected
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Anda bisa memantau statistik dan meminta AI membuat postingan langsung dari chat Telegram HP Anda:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-slate-600 space-y-2 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <li>Cari <strong>@BotFather</strong> di Telegram, ketik <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono">/newbot</code>, dan ambil <strong>Bot Token</strong>.</li>
                            <li>Masukkan token tersebut di menu <Link :href="route('api-keys.index')" class="text-indigo-600 font-bold underline">API Key Pool</Link> dengan provider <strong>Telegram Bot Token</strong>.</li>
                            <li class="text-emerald-700 font-bold">✨ Sistem Gotiau AI secara otomatis menghubungkan Webhook ke Telegram Anda (Tidak perlu setting manual!).</li>
                        </ol>
                    </div>
                </div>

                <!-- AUTOPILOT STATUS CARD -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-900 via-teal-900 to-slate-900 p-8 text-white shadow-xl border border-emerald-800/80">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/20 text-2xl text-emerald-400 border border-emerald-500/30">
                            🚀
                        </div>
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <h4 class="text-2xl font-extrabold tracking-tight">
                                    Autopilot Scheduler Mode (100% Aktif)
                                </h4>
                                <span class="rounded-full bg-emerald-400/20 px-3.5 py-1 text-xs font-bold text-emerald-300 border border-emerald-400/30">
                                    🟢 System Autonomous
                                </span>
                            </div>
                            <p class="text-sm text-emerald-100 leading-relaxed">
                                <strong>Bos sudah bisa dengan tenang menutup laptop!</strong> Jadwal Cron Scheduler otomatis di server VPS telah aktif berjalan 24/7 di latar belakang.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs pt-2">
                                <div class="rounded-xl bg-black/30 p-3.5 border border-white/10">
                                    <div class="font-bold text-emerald-300">🌅 07:00 Pagi</div>
                                    <div class="text-slate-300 mt-1">AI otomatis bangun & meriset angle pemasaran tren pasar.</div>
                                </div>
                                <div class="rounded-xl bg-black/30 p-3.5 border border-white/10">
                                    <div class="font-bold text-emerald-300">📅 09:00, 15:00, 20:00</div>
                                    <div class="text-slate-300 mt-1">AI merancang 3 postingan harian & menerbitkan via Repliz.</div>
                                </div>
                                <div class="rounded-xl bg-black/30 p-3.5 border border-white/10">
                                    <div class="font-bold text-emerald-300">💬 24/7 CS AI</div>
                                    <div class="text-slate-300 mt-1">Widget Live Chat di waperu.com menjawab calon pembeli.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
