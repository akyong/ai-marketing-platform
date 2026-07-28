<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const copiedCommand = ref(false);

const copyCronCommand = () => {
    const text = '* * * * * cd /var/www/ai-marketing-platform && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1';
    navigator.clipboard.writeText(text);
    copiedCommand.value = true;
    setTimeout(() => {
        copiedCommand.value = false;
    }, 3000);
};
</script>

<template>
    <Head title="Panduan Autopilot AI Marketer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    📖 Panduan Autopilot AI Marketer (Dari Zero sampai Tinggalin)
                </h2>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                    Step-by-Step Setup Guide
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-8 sm:px-6 lg:px-8">
                <!-- Welcome Banner -->
                <div class="bg-gradient-to-r from-indigo-700 to-purple-800 p-8 text-white shadow-lg sm:rounded-xl">
                    <div class="max-w-3xl">
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur-sm">
                            Handbook Pemilik Bisnis
                        </span>
                        <h3 class="mt-3 text-3xl font-extrabold">
                            Cara Menjalankan AI Marketing Otonom Waperu.com
                        </h3>
                        <p class="mt-2 text-base text-indigo-100">
                            Ikuti 5 langkah sederhana berikut agar AI Marketer dapat meriset tren target seller COD & UMKM, menyusun postingan harian, dan mempublikasikannya otomatis ke social media tanpa perlu Anda sentuh lagi!
                        </p>
                    </div>
                </div>

                <!-- STEP 1 -->
                <div class="bg-white p-6 shadow-sm sm:rounded-xl border border-gray-100 flex gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white">
                        1
                    </div>
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-900">
                                Isi Knowledge Base Brand Waperu
                            </h4>
                            <Link
                                :href="route('workspace.settings')"
                                class="rounded bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100"
                            >
                                Ke Menu Workspace & Brand →
                            </Link>
                        </div>
                        <p class="text-sm text-gray-600">
                            AI membutuhkan pengetahuan dasar tentang produk Waperu. Masukkan profil brand di menu **Workspace & Brand**:
                        </p>
                        <ul class="list-disc list-inside text-xs text-gray-600 space-y-1 bg-gray-50 p-3 rounded">
                            <li><strong>Nama Brand:</strong> Waperu (Wadah Pejuang Rupiah)</li>
                            <li><strong>Website:</strong> https://waperu.com</li>
                            <li><strong>Industri:</strong> SaaS / Toko Online Mandiri 0% Komisi</li>
                            <li><strong>Target Audiens:</strong> Seller COD, UMKM Indonesia, Penjual Online di Social Media & Marketplace</li>
                            <li><strong>Tone of Voice:</strong> Solutif, Edukatif, Ramah, Semangat Pejuang Rupiah</li>
                        </ul>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div class="bg-white p-6 shadow-sm sm:rounded-xl border border-gray-100 flex gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white">
                        2
                    </div>
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-900">
                                Masukkan Google Gemini API Key (Otak AI)
                            </h4>
                            <Link
                                :href="route('api-keys.index')"
                                class="rounded bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100"
                            >
                                Ke Menu API Key Pool →
                            </Link>
                        </div>
                        <p class="text-sm text-gray-600">
                            Dapatkan Gemini API Key gratis dari Google AI Studio, lalu masukkan ke dalam sistem:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1 bg-gray-50 p-3 rounded">
                            <li>Buka website <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-indigo-600 underline font-bold">Google AI Studio (aistudio.google.com)</a>.</li>
                            <li>Klik tombol <strong>Create API Key</strong> dan salin kode API Key (misal: <code class="bg-gray-200 px-1 py-0.5 rounded">AIzaSy...</code>).</li>
                            <li>Buka menu <Link :href="route('api-keys.index')" class="text-indigo-600 underline">API Key Pool</Link>, pilih Provider <strong>Google Gemini</strong>, isi nama label (misal: <em>Gemini Akun 1</em>), dan simpan.</li>
                            <li>💡 <em>Tip Cerdas: Anda dapat memasukkan 2 hingga 5 API Key sekaligus! Jika Akun 1 koutanya habis, sistem otomatis beralih ke Akun 2 tanpa error.</em></li>
                        </ol>
                    </div>
                </div>

                <!-- STEP 3 -->
                <div class="bg-white p-6 shadow-sm sm:rounded-xl border border-gray-100 flex gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white">
                        3
                    </div>
                    <div class="space-y-2 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-lg font-bold text-gray-900">
                                Hubungkan Repliz API Key (Posting ke Social Media)
                            </h4>
                            <Link
                                :href="route('api-keys.index')"
                                class="rounded bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100"
                            >
                                Ke Menu API Key Pool →
                            </Link>
                        </div>
                        <p class="text-sm text-gray-600">
                            Repliz adalah saluran tempat AI mengirimkan postingan ke Instagram, Facebook, TikTok, & X secara otomatis:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1 bg-gray-50 p-3 rounded">
                            <li>Dapatkan API Key dari Dashboard Repliz Anda.</li>
                            <li>Buka menu <Link :href="route('api-keys.index')" class="text-indigo-600 underline">API Key Pool</Link>.</li>
                            <li>Pilih Provider <strong>Repliz API Key (Social Media Publishing)</strong>, beri nama <em>Repliz Production</em>, lalu salin tokennya di sana.</li>
                            <li>Mulai dari sekarang, semua postingan yang dibuat AI Marketer akan diterbitkan otomatis oleh Repliz!</li>
                        </ol>
                    </div>
                </div>

                <!-- STEP 4 -->
                <div class="bg-white p-6 shadow-sm sm:rounded-xl border border-gray-100 flex gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white">
                        4
                    </div>
                    <div class="space-y-2 flex-1">
                        <h4 class="text-lg font-bold text-gray-900">
                            (Opsional) Pasang Telegram Bot untuk Kontrol Lewat HP
                        </h4>
                        <p class="text-sm text-gray-600">
                            Anda bisa memberi perintah ke AI Marketer langsung lewat obrolan Telegram di HP:
                        </p>
                        <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1 bg-gray-50 p-3 rounded">
                            <li>Cari <strong>@BotFather</strong> di Telegram, ketik <code class="bg-gray-200 px-1 py-0.5 rounded">/newbot</code>, beri nama bot Anda, dan dapatkan <strong>Bot Token</strong>.</li>
                            <li>Simpan token tersebut di menu <Link :href="route('api-keys.index')" class="text-indigo-600 underline">API Key Pool</Link> dengan provider <strong>Telegram Bot Token</strong>.</li>
                            <li>Set Webhook URL ke: <code class="bg-gray-200 px-1 py-0.5 rounded font-mono">https://domain-anda.com/api/telegram/webhook</code></li>
                        </ol>
                    </div>
                </div>

                <!-- STEP 5 -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-700 p-6 text-white shadow-lg sm:rounded-xl flex gap-6">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-xl font-bold text-emerald-700">
                        5
                    </div>
                    <div class="space-y-3 flex-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xl font-extrabold">
                                Aktifkan Autopilot Cron Scheduler di VPS (Selesai!)
                            </h4>
                            <span class="rounded bg-white/20 px-2.5 py-1 text-xs font-bold backdrop-blur-sm">
                                Autopilot Mode
                            </span>
                        </div>
                        <p class="text-sm text-emerald-100">
                            Langkah terakhir agar bos bisa menutup laptop dan membiarkan AI bekerja otonom setiap hari:
                        </p>
                        <div class="space-y-2 rounded-lg bg-black/30 p-4 font-mono text-xs backdrop-blur-sm">
                            <div class="text-emerald-300"># 1. Buka terminal SSH VPS Anda dan jalankan perintah edit crontab:</div>
                            <div class="text-white font-bold">crontab -e</div>
                            <div class="pt-2 text-emerald-300"># 2. Tempelkan baris jadwal otomatis berikut di bagian paling bawah:</div>
                            <div class="flex items-center justify-between bg-black/40 p-2 rounded text-emerald-200">
                                <code>* * * * * cd /var/www/ai-marketing-platform && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1</code>
                                <button
                                    @click="copyCronCommand"
                                    class="ml-3 shrink-0 rounded bg-white px-2.5 py-1 text-xs font-bold text-emerald-900 hover:bg-emerald-100"
                                >
                                    {{ copiedCommand ? '✅ Tersalin!' : '📋 Copy Baris Cron' }}
                                </button>
                            </div>
                        </div>

                        <div class="rounded-lg bg-white/10 p-3 text-xs text-emerald-50">
                            🎉 <strong>SELAMAT!</strong> AI Marketer kini akan otomatis bangun setiap jam 07:00 pagi, meriset strategi pasar Waperu, membuat 3 postingan harian (09:00, 15:00, 20:00), dan menerbitkannya otomatis via Repliz tanpa henti!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
