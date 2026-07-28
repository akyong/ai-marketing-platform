<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    generations: Array,
    stats: Object,
});

const page = usePage();
const currentWorkspace = computed(() => page.props.auth.current_workspace);
const brand = computed(() => currentWorkspace.value?.default_brand);

const aiForm = useForm({
    prompt: '',
});

const autonomousForm = useForm({
    goal: 'Buat strategi marketing harian untuk menarik seller online ke Waperu',
});

const generateAiStrategy = () => {
    aiForm.post(route('ai.generate'), {
        onSuccess: () => aiForm.reset('prompt'),
    });
};

const runAutonomousWorkflow = () => {
    autonomousForm.post(route('ai.autonomous'));
};

const fillPresetPrompt = (preset) => {
    aiForm.prompt = preset;
};
</script>

<template>
    <Head title="Dashboard AI Operations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-extrabold leading-tight text-slate-900">
                        ⚡ AI Marketing Control Center
                    </h2>
                    <p class="text-xs text-slate-500">
                        Workspace Active: <strong class="text-indigo-600">{{ currentWorkspace?.name || 'Waperu Workspace' }}</strong>
                    </p>
                </div>
                <span class="rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 px-3.5 py-1 text-xs font-bold text-white shadow-sm" v-if="brand">
                    Brand: {{ brand.name }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Brand Context Card -->
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-6 md:p-8 text-white shadow-xl" v-if="brand">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                        <div class="max-w-3xl">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-indigo-500/20 px-2.5 py-1 text-[10px] font-bold tracking-widest text-indigo-300 uppercase">
                                    Knowledge Base Brand Aktif
                                </span>
                            </div>
                            <h3 class="mt-2 text-2xl md:text-3xl font-extrabold text-white">{{ brand.name }}</h3>
                            <p class="mt-1 text-sm text-slate-300 leading-relaxed">
                                {{ brand.description }}
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-lg bg-white/10 px-3 py-1.5 backdrop-blur-md border border-white/10 text-indigo-200">
                                    🎯 Target: {{ brand.target_audience || 'Online Sellers, Pejuang COD, UMKM' }}
                                </span>
                                <span class="rounded-lg bg-white/10 px-3 py-1.5 backdrop-blur-md border border-white/10 text-purple-200">
                                    🗣️ Tone: {{ brand.tone_of_voice || 'Empathetic, Professional, Empowering' }}
                                </span>
                            </div>
                        </div>

                        <!-- Quick Autonomous Trigger -->
                        <div class="shrink-0 w-full md:w-auto">
                            <button
                                @click="runAutonomousWorkflow"
                                :disabled="autonomousForm.processing"
                                class="w-full md:w-auto rounded-xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 px-6 py-4 text-sm font-extrabold text-white shadow-lg shadow-indigo-500/30 transition hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                {{ autonomousForm.processing ? '⚡ AI Meriset & Menyusun Campaign...' : '⚡ Jalankan 3-Step Autonomous Campaign' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview Grid -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4" v-if="stats">
                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200/80 transition hover:shadow-md">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Eksekusi AI</div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ stats.total_generations }}</div>
                        <div class="mt-1 text-xs font-medium text-indigo-600">Riset & Strategy Runs</div>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200/80 transition hover:shadow-md">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Postingan</div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ stats.total_posts }}</div>
                        <div class="mt-1 text-xs font-medium text-amber-600">{{ stats.scheduled_posts }} Terjadwal</div>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200/80 transition hover:shadow-md">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Dipublikasikan (Repliz)</div>
                        <div class="mt-2 text-3xl font-black text-emerald-600">{{ stats.published_posts }}</div>
                        <div class="mt-1 text-xs font-medium text-emerald-600">Live di Social Media</div>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200/80 transition hover:shadow-md">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">API Key Pool</div>
                        <div class="mt-2 text-3xl font-black text-indigo-600">{{ stats.active_keys }}</div>
                        <div class="mt-1 text-xs font-medium text-indigo-600">Keys Active (Failover)</div>
                    </div>
                </div>

                <!-- AI Prompt Generator Box -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-bold text-slate-900">
                            🤖 AI Marketer & Research Workbench
                        </h3>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            Gemini 2.0 Engine
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">
                        Ketik perintah khusus atau klik instruksi instan di bawah ini untuk meminta AI meriset & membuatkan strategi marketing.
                    </p>

                    <!-- Preset Prompt Chips -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <button
                            type="button"
                            @click="fillPresetPrompt('Buatkan riset target pasar seller COD dan 3 ide konten TikTok untuk Waperu')"
                            class="rounded-full bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 px-3 py-1 text-xs font-medium text-slate-600 transition"
                        >
                            💡 Riset TikTok Seller COD
                        </button>
                        <button
                            type="button"
                            @click="fillPresetPrompt('Buatkan 3 caption Instagram edukasi 0% komisi untuk seller UMKM')"
                            class="rounded-full bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 px-3 py-1 text-xs font-medium text-slate-600 transition"
                        >
                            📸 Caption IG Edukasi Komisi
                        </button>
                        <button
                            type="button"
                            @click="fillPresetPrompt('Buatkan thread X storytelling motivasi Pejuang Rupiah dan solusi Toko Online Waperu')"
                            class="rounded-full bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 px-3 py-1 text-xs font-medium text-slate-600 transition"
                        >
                            🐦 Thread X Storytelling
                        </button>
                    </div>

                    <form @submit.prevent="generateAiStrategy" class="space-y-4">
                        <div>
                            <InputLabel for="prompt" value="Instruksi untuk AI Marketer" />
                            <textarea
                                id="prompt"
                                class="mt-1 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                rows="3"
                                v-model="aiForm.prompt"
                                required
                                placeholder="Contoh: Buatkan riset target audiens dan 3 ide konten TikTok untuk menarik seller jualan online COD di Waperu."
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs text-slate-400">
                                💬 Anda juga dapat berinteraksi dengan AI via <strong>Telegram Bot</strong> atau <strong>CS Widget</strong>.
                            </div>
                            <PrimaryButton :disabled="aiForm.processing">
                                {{ aiForm.processing ? 'AI Sedang Berpikir...' : 'Jalankan AI Marketer 🚀' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- AI History List -->
                <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200/80">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">
                        📜 Riwayat Eksekusi & Strategi AI
                    </h3>

                    <div v-if="generations && generations.length > 0" class="space-y-4">
                        <div
                            v-for="item in generations"
                            :key="item.id"
                            class="rounded-xl border border-slate-200/80 p-5 transition hover:border-indigo-300 hover:shadow-sm"
                        >
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span class="font-bold text-indigo-600 uppercase tracking-wider">
                                    {{ item.type }}
                                </span>
                                <span>{{ new Date(item.created_at).toLocaleString('id-ID') }}</span>
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                ❓ {{ item.prompt }}
                            </div>

                            <div class="mt-3 whitespace-pre-wrap rounded-xl bg-slate-900 p-4 text-xs text-slate-200 font-mono leading-relaxed overflow-x-auto border border-slate-800">
                                {{ item.response_text }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center text-sm text-slate-400">
                        Belum ada riwayat strategi AI. Coba ketik instruksi di atas untuk memulai!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
