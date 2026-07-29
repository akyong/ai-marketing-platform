<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    query: {
        type: String,
        default: 'ecommerce',
    },
    insights: {
        type: Array,
        default: () => [],
    },
    liveThreadsPosts: {
        type: Array,
        default: () => [],
    },
});

const searchForm = useForm({
    query: props.query,
});

const scanForm = useForm({
    keyword: props.query,
});

const handleSearch = () => {
    searchForm.get(route('threads-radar.index'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const triggerScan = () => {
    scanForm.keyword = searchForm.query;
    scanForm.post(route('threads-radar.scan'), {
        preserveScroll: true,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date) + ' WIB';
};
</script>

<template>
    <Head title="📡 Threads Social Radar & Prospecting" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-800">
                        📡 Threads Social Radar & Prospecting Engine
                    </h2>
                    <p class="text-xs text-gray-500">
                        Riset & Monitoring Otonom 24/7 dengan Local Ollama AI untuk Memikat Seller Online ke Waperu.com
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                        <span class="mr-1.5 h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Ollama AI 24/7 Active
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Search Bar & Controls -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <form @submit.prevent="handleSearch" class="flex-1">
                            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                                🔍 Cari Status / Topik Threads Berdasarkan Kata Kunci:
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="searchForm.query"
                                    type="text"
                                    placeholder="Contoh: ecommerce, komisi marketplace, potongan admin, jualanonline..."
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <button
                                    type="submit"
                                    :disabled="searchForm.processing"
                                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Cari Status
                                </button>
                            </div>
                        </form>

                        <button
                            @click="triggerScan"
                            :disabled="scanForm.processing"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <span>🚀</span>
                            <span>{{ scanForm.processing ? 'Menjalankan Ollama...' : 'Jalankan Riset Ollama AI Sekarang' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Live Threads Scanned Status List -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                            <span>🧵 Hasil Pencarian Threads Real-Time (Repliz API)</span>
                            <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800">
                                {{ liveThreadsPosts.length }} Status Ditemukan
                            </span>
                        </h3>
                    </div>

                    <div v-if="liveThreadsPosts && liveThreadsPosts.length > 0" class="space-y-4">
                        <div
                            v-for="(item, idx) in liveThreadsPosts"
                            :key="idx"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300 hover:shadow-sm"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                        Status Threads Publik
                                    </span>
                                    <span class="text-xs text-gray-400">Target Keyword: <strong>{{ query }}</strong></span>
                                </div>

                                <a
                                    :href="item.url || item.link || 'https://threads.net/search?q=' + encodeURIComponent(query)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline"
                                >
                                    <span>🔗 Buka Postingan Asli</span>
                                    <span>↗</span>
                                </a>
                            </div>

                            <p class="whitespace-pre-wrap text-sm text-gray-800 font-medium">
                                {{ item.text || item.description || item.content || item }}
                            </p>

                            <div class="mt-3 flex items-center justify-between gap-2 text-xs text-gray-500">
                                <span class="text-emerald-600 font-medium">
                                    ✓ Ollama AI Ready to Reply
                                </span>
                                <a
                                    :href="'https://threads.net/search?q=' + encodeURIComponent(query)"
                                    target="_blank"
                                    class="rounded bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                                >
                                    💬 Balas / Interaksi di Threads
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg bg-gray-50 p-8 text-center text-sm text-gray-500">
                        <p class="font-medium text-gray-700">⚠️ Tidak ada status baru yang ditemukan untuk kata kunci "{{ query }}"</p>
                        <p class="mt-1 text-xs text-gray-400">Klik tombol "Jalankan Riset Ollama AI Sekarang" untuk memicu pemindaian otonom.</p>
                    </div>
                </div>

                <!-- Ollama AI Prospecting Memory & Insight Log -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                            <span>🧠 Log Insight Keluhan Customer (Local Ollama AI Memory)</span>
                            <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">
                                {{ insights.length }} Memori Tersimpan
                            </span>
                        </h3>
                    </div>

                    <div v-if="insights && insights.length > 0" class="space-y-3">
                        <div
                            v-for="log in insights"
                            :key="log.id"
                            class="rounded-lg bg-amber-50/60 p-4 border border-amber-200/70"
                        >
                            <div class="flex items-center justify-between text-xs text-amber-900 font-semibold mb-1">
                                <span>💡 {{ log.metadata?.engine === 'ollama_local_ai' ? 'Local Ollama AI (Llama 3.2 1B)' : 'Social Radar Listening' }}</span>
                                <span>{{ formatDate(log.created_at) }}</span>
                            </div>

                            <p class="text-sm font-bold text-gray-900">
                                {{ log.prompt }}
                            </p>

                            <div v-if="log.response_text" class="mt-2 rounded bg-white p-3 text-xs text-gray-700 border border-gray-200 whitespace-pre-wrap">
                                <strong>Sampel Diskusi Pasar:</strong>
                                <br />
                                {{ log.response_text }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg bg-gray-50 p-6 text-center text-xs text-gray-500">
                        Belum ada log riset prospek tersimpan.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
