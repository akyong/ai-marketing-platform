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
    prospectLogs: {
        type: Array,
        default: () => [],
    },
    lastExecutedAt: {
        type: String,
        default: null,
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
    keyword: '',
});

const activePublishingId = ref(null);

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

const sendReply = (log) => {
    const replyText = log.response_text || log.metadata?.drafted_reply;
    if (!replyText) return;

    activePublishingId.value = log.id;
    router.post(route('threads-radar.reply-now'), {
        content: replyText,
        log_id: log.id,
    }, {
        preserveScroll: true,
        onFinish: () => {
            activePublishingId.value = null;
        },
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
        second: '2-digit',
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
                        📡 Threads Social Radar & Prospecting Engine (Ollama AI)
                    </h2>
                    <p class="text-xs text-gray-500">
                        Riset & Monitoring Otonom 24/7 (Setiap 1 Menit) dengan Kata Kunci Fleksibel AI Membaca Memori Brand Waperu.com
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-sm border border-emerald-300">
                        <span class="mr-2 h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Auto-Run Setiap 1 Menit Active
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- 1-Minute Live Status Ticker Banner -->
                <div class="rounded-xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-5 text-white shadow-lg border border-indigo-800">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-300">
                                <span>⚡ Status AI Runner Otonom</span>
                                <span>•</span>
                                <span class="text-emerald-400">Jadwal Otomatis: Setiap 1 Menit</span>
                            </div>
                            <h3 class="text-lg font-extrabold text-white">
                                Local Ollama AI (Llama 3.2 1B) Bekerja Non-Stop Menganalisis Customer Waperu
                            </h3>
                            <p class="text-xs text-indigo-200">
                                Terakhir Dieksekusi: <strong class="text-emerald-300 underline">{{ lastExecutedAt ? formatDate(lastExecutedAt) : 'Menunggu Run 1-Menit Pertama' }}</strong>
                            </p>
                        </div>
                        <div>
                            <button
                                @click="triggerScan"
                                :disabled="scanForm.processing"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md transition hover:bg-emerald-400 disabled:opacity-50"
                            >
                                <span>⚡</span>
                                <span>{{ scanForm.processing ? 'Sedang Eksekusi Ollama...' : 'Picu Eksekusi Manual Now' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Controls & Search -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="handleSearch" class="flex-1">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            🔍 Filter Pencarian Manual Threads (Opsional):
                        </label>
                        <div class="flex gap-2">
                            <input
                                v-model="searchForm.query"
                                type="text"
                                placeholder="Cari kata kunci spesifik..."
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
                </div>

                <!-- 1-Minute Ollama Execution History & Interactive Drafted Replies -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                                <span>🤖 Log Eksekusi Ollama AI (Setiap 1 Menit) & Interaksi Balas 1-Klik</span>
                                <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800">
                                    {{ prospectLogs.length }} Eksekusi Dicatat
                                </span>
                            </h3>
                            <p class="text-xs text-gray-500">
                                Klik tombol "🚀 Kirim Balasan Ini Sekarang via Repliz" untuk langsung menerbitkan draft balasan Ollama AI ke Threads!
                            </p>
                        </div>
                    </div>

                    <div v-if="prospectLogs && prospectLogs.length > 0" class="space-y-4">
                        <div
                            v-for="log in prospectLogs"
                            :key="log.id"
                            class="rounded-xl border border-indigo-100 bg-indigo-50/30 p-5 transition hover:border-indigo-300"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-100 pb-2.5 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-indigo-600 px-2.5 py-0.5 text-xs font-bold text-white">
                                        ⚡ Eksekusi 1-Menit
                                    </span>
                                    <span v-if="log.metadata?.is_duplicate" class="rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900 border border-amber-300">
                                        🛡️ Anti-Repeat (Sudah Pernah Diproses)
                                    </span>
                                    <span v-else class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-900 border border-emerald-300">
                                        🔥 Postingan Unik Baru
                                    </span>
                                    <span class="text-xs font-semibold text-indigo-900">
                                        {{ log.prompt }}
                                    </span>
                                </div>

                                <span class="text-xs font-bold text-gray-500">
                                    🕒 {{ formatDate(log.created_at) }}
                                </span>
                            </div>

                            <!-- Insight Extracted -->
                            <div v-if="log.metadata?.insight_extracted" class="mb-3 rounded-lg bg-amber-50 p-3 text-xs text-amber-900 border border-amber-200">
                                <strong>💡 Analisis Keluhan Seller (Ollama AI):</strong> {{ log.metadata.insight_extracted }}
                            </div>

                            <!-- Scanned Post Sample -->
                            <div v-if="log.metadata?.scanned_posts && log.metadata.scanned_posts.length > 0" class="mb-3 text-xs text-gray-700 bg-white p-3 rounded border border-gray-200">
                                <strong class="text-gray-900">🧵 Postingan Target Seller:</strong>
                                <p class="mt-1 whitespace-pre-wrap italic">"{{ log.metadata.scanned_posts[0] }}"</p>
                            </div>

                            <!-- Interactive Drafted Reply prepared by Ollama -->
                            <div class="rounded-lg bg-emerald-50 p-4 text-xs text-emerald-950 border border-emerald-200 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5 pb-2 border-b border-emerald-200/80">
                                    <strong class="font-bold text-emerald-900 text-sm flex items-center gap-1.5">
                                        <span>💬 Draft Balasan Solutif Waperu (Local Ollama AI)</span>
                                    </strong>

                                    <span v-if="log.metadata?.is_published" class="rounded bg-emerald-600 px-3 py-1 text-xs font-bold text-white shadow-sm flex items-center gap-1">
                                        ✓ TERKIRIM KE THREADS VIA REPLIZ
                                    </span>
                                    <button
                                        v-else
                                        @click="sendReply(log)"
                                        :disabled="activePublishingId === log.id"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50"
                                    >
                                        <span>🚀</span>
                                        <span>{{ activePublishingId === log.id ? 'Mengirim ke Threads...' : 'Kirim Balasan Ini Sekarang via Repliz' }}</span>
                                    </button>
                                </div>
                                <p class="whitespace-pre-wrap font-semibold leading-relaxed text-emerald-950 text-sm bg-white p-3 rounded border border-emerald-100">{{ log.response_text || log.metadata?.drafted_reply }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg bg-gray-50 p-8 text-center text-sm text-gray-500">
                        <p class="font-medium text-gray-700">⏳ Belum ada log eksekusi 1-menit tersimpan.</p>
                        <p class="mt-1 text-xs text-gray-400">Schedule otomatis `ollama:research` berjalan setiap 1 menit di VPS Anda.</p>
                    </div>
                </div>

                <!-- Live Threads Scanned Status List -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                            <span>🧵 Hasil Pencarian Status Threads Real-Time</span>
                        </h3>
                    </div>

                    <div v-if="liveThreadsPosts && liveThreadsPosts.length > 0" class="space-y-4">
                        <div
                            v-for="(item, idx) in liveThreadsPosts"
                            :key="idx"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2 mb-2">
                                <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                    Status Threads Publik
                                </span>

                                <a
                                    :href="item.url || item.link || 'https://threads.net/search?q=' + encodeURIComponent(query)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline"
                                >
                                    <span>🔗 Buka Postingan Asli & Balas</span>
                                    <span>↗</span>
                                </a>
                            </div>

                            <p class="whitespace-pre-wrap text-sm text-gray-800 font-medium">
                                {{ item.text || item.description || item.content || item }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
