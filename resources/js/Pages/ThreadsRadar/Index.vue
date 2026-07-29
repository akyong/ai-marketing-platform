<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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
    hasReplizKey: {
        type: Boolean,
        default: true,
    },
});

// Lazy loading state (10 rows per batch)
const visibleLogsCount = ref(10);
const visibleLiveCount = ref(10);

const visibleProspectLogs = computed(() => props.prospectLogs.slice(0, visibleLogsCount.value));
const visibleLiveThreadsPosts = computed(() => props.liveThreadsPosts.slice(0, visibleLiveCount.value));

const loadMoreLogs = () => {
    visibleLogsCount.value += 10;
};

const loadMoreLive = () => {
    visibleLiveCount.value += 10;
};

const searchForm = useForm({
    query: props.query,
});

const scanForm = useForm({
    keyword: '',
});

const activePublishingId = ref(null);
const feedbackInputs = ref({});
const newMemoryRule = ref('');
const submittingFeedbackId = ref(null);
const submittingRule = ref(false);

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
        target_post_id: log.metadata?.target_post_id || log.metadata?.post_id || null,
        target_url: log.metadata?.target_url || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            activePublishingId.value = null;
        },
    });
};

const submitLogFeedback = (logId) => {
    const feedback = feedbackInputs.value[logId];
    if (!feedback || !feedback.trim()) return;

    submittingFeedbackId.value = logId;
    router.post(route('threads-radar.feedback'), {
        feedback: feedback.trim(),
        log_id: logId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackInputs.value[logId] = '';
        },
        onFinish: () => {
            submittingFeedbackId.value = null;
        },
    });
};

const submitDirectRule = () => {
    if (!newMemoryRule.value || !newMemoryRule.value.trim()) return;

    submittingRule.value = true;
    router.post(route('threads-radar.feedback'), {
        feedback: newMemoryRule.value.trim(),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newMemoryRule.value = '';
        },
        onFinish: () => {
            submittingRule.value = false;
        },
    });
};

const deleteMemoryRule = (ruleId) => {
    if (confirm('Apakah Anda yakin ingin menghapus aturan memori ini?')) {
        router.delete(route('threads-radar.delete-feedback', ruleId), {
            preserveScroll: true,
        });
    }
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
                        📡 Threads Social Radar & Reply Prospecting Engine
                    </h2>
                    <p class="text-xs text-gray-500">
                        Memantau status & komentar seller di Threads 24/7 (Setiap 1 Menit), menyimpan Direct Post Link & menyiapkan balasan solutif.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3.5 py-1.5 text-xs font-bold text-emerald-800 shadow-sm border border-emerald-300">
                        <span class="mr-2 h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Auto-Run 1-Menit Active
                    </span>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Warning if Repliz API Key is missing -->
                <div v-if="!hasReplizKey" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h4 class="font-extrabold text-sm">Repliz API Key Belum Dikonfigurasi / Belum Aktif</h4>
                            <p class="text-xs text-amber-800">
                                Agar Ollama AI dapat mencari status seller Threads secara live dan mengekstrak link postingan asli, silakan masukkan Repliz API Key Anda di menu API Keys.
                            </p>
                        </div>
                    </div>
                    <a
                        :href="route('api-keys.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700 transition shadow shrink-0"
                    >
                        <span>🔑 Atur Repliz API Key Sekarang</span>
                        <span>↗</span>
                    </a>
                </div>

                <!-- 1-Minute Live Status Ticker Banner -->
                <div class="rounded-xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-5 text-white shadow-lg border border-indigo-800">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-300">
                                <span>💬 Fitur Komen & Direct Threads Post Link</span>
                                <span>•</span>
                                <span class="text-emerald-400">Jadwal Otomatis: Setiap 1 Menit</span>
                            </div>
                            <h3 class="text-lg font-extrabold text-white">
                                Local Ollama AI Membaca Status Seller & Menyimpan Direct URL Threads Post
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

                <!-- 🧠 Self-Learning Ollama AI Memory Rules Card -->
                <div class="rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/70 via-purple-50/50 to-indigo-50/70 p-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-indigo-100 pb-3 mb-3">
                        <div>
                            <h3 class="font-extrabold text-indigo-950 text-base flex items-center gap-2">
                                <span>🧠 Memori Self-Learning Ollama AI ({{ insights.filter(i => i.type === 'user_memory_rule').length }} Aturan Diajarkan)</span>
                            </h3>
                            <p class="text-xs text-indigo-700">
                                Berikan arahan khusus kepada Ollama AI. AI akan secara otomatis mengingat aturan ini dalam setiap eksekusi prospeksi Threads berikutnya.
                            </p>
                        </div>
                    </div>

                    <!-- Add Direct Rule Form -->
                    <form @submit.prevent="submitDirectRule" class="flex gap-2 mb-3">
                        <input
                            v-model="newMemoryRule"
                            type="text"
                            placeholder="Ketik instruksi baru untuk Ollama... Misal: Selalu gunakan nada bahasa ramah dan sebutkan fitur 0% komisi Waperu."
                            class="w-full rounded-lg border-indigo-300 text-xs shadow-sm focus:border-indigo-600 focus:ring-indigo-600 bg-white"
                        />
                        <button
                            type="submit"
                            :disabled="submittingRule || !newMemoryRule.trim()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white hover:bg-indigo-700 shadow-sm transition disabled:opacity-40 shrink-0"
                        >
                            <span>🧠 Simpan Aturan Memori</span>
                        </button>
                    </form>

                    <!-- Active Rules List -->
                    <div v-if="insights.filter(i => i.type === 'user_memory_rule').length > 0" class="flex flex-wrap gap-2">
                        <div
                            v-for="rule in insights.filter(i => i.type === 'user_memory_rule')"
                            :key="rule.id"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-indigo-900 border border-indigo-200 shadow-xs"
                        >
                            <span>📌 "{{ rule.response_text }}"</span>
                            <button
                                @click="deleteMemoryRule(rule.id)"
                                title="Hapus Aturan Memori Ini"
                                class="text-gray-400 hover:text-red-600 font-bold ml-1"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-xs italic text-indigo-500">
                        Belum ada aturan memori khusus. Berikan instruksi di atas atau beri masukan pada log prospek di bawah untuk mengajari Ollama AI!
                    </div>
                </div>

                <!-- Controls & Search -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <form @submit.prevent="handleSearch" class="flex-1">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">
                            🔍 Filter Pencarian Manual Status / Komen Threads:
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

                <!-- 1-Minute Ollama Execution History & Interactive Drafted Replies with Direct Post Link -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-base flex items-center gap-2">
                                <span>💬 Status Threads, Direct Post Link & Draft Balasan Komen</span>
                                <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-800">
                                    {{ prospectLogs.length }} Status Dipindai
                                </span>
                            </h3>
                            <p class="text-xs text-gray-500">
                                Klik tombol "🔗 Direct Post Link" untuk membuka postingan Threads asli (seperti https://threads.net/@user/post/xxx) dan membalas komentar secara manual atau kirim via Repliz API!
                            </p>
                        </div>
                    </div>

                    <div v-if="prospectLogs && prospectLogs.length > 0" class="space-y-4">
                        <div
                            v-for="log in visibleProspectLogs"
                            :key="log.id"
                            class="rounded-xl border border-indigo-100 bg-indigo-50/30 p-5 transition hover:border-indigo-300"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-indigo-100 pb-2.5 mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-indigo-600 px-2.5 py-0.5 text-xs font-bold text-white">
                                        💬 Target Seller Status
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

                            <!-- Scanned Post Target with Direct Threads Link -->
                            <div v-if="log.metadata?.scanned_posts && log.metadata.scanned_posts.length > 0" class="mb-3 text-xs text-gray-700 bg-white p-3.5 rounded-lg border border-gray-200 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2 border-b border-gray-100 pb-2">
                                    <strong class="text-gray-900 font-bold flex items-center gap-1.5 text-sm">
                                        <span>🧵 Status Seller Target</span>
                                        <span v-if="log.metadata?.username" class="text-indigo-600 font-extrabold">(@{{ log.metadata.username }})</span>
                                    </strong>

                                    <a
                                        :href="log.metadata?.target_url || 'https://threads.net/search?q=' + encodeURIComponent(query)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-extrabold text-white hover:bg-indigo-700 shadow-sm transition"
                                    >
                                        <span>🔗 Buka Direct Post Threads: {{ log.metadata?.target_url ? log.metadata.target_url.replace('https://www.threads.net', '') : '/post' }}</span>
                                        <span>↗</span>
                                    </a>
                                </div>
                                <p class="whitespace-pre-wrap italic text-gray-800 font-medium text-sm">"{{ log.metadata.scanned_posts[0] }}"</p>
                            </div>

                            <!-- Interactive Multi-Turn Refinement & Drafted Reply prepared by Ollama -->
                            <div class="rounded-xl bg-gradient-to-b from-emerald-50 to-teal-50/40 p-4 text-xs text-emerald-950 border border-emerald-200 shadow-sm space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-emerald-200/80">
                                    <div class="flex items-center gap-2">
                                        <strong class="font-bold text-emerald-950 text-sm flex items-center gap-1.5">
                                            <span>💬 Draft Balasan Komen (Ollama AI Refinement Thread)</span>
                                        </strong>
                                        <span v-if="log.metadata?.conversation_threads?.length > 1" class="rounded bg-indigo-100 px-2 py-0.5 text-[10px] font-extrabold text-indigo-900 border border-indigo-200">
                                            ⚡ Versi {{ Math.floor(log.metadata.conversation_threads.length / 2) + 1 }} (Upgraded)
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="log.metadata?.target_url || 'https://threads.net/search?q=' + encodeURIComponent(query)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-gray-100 border border-indigo-200 shadow-xs"
                                        >
                                            <span>💬 Balas Manual</span>
                                            <span>↗</span>
                                        </a>

                                        <span v-if="log.metadata?.is_published" class="rounded bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-xs flex items-center gap-1">
                                            ✓ TERKIRIM VIA REPLIZ
                                        </span>
                                        <button
                                            v-else
                                            @click="sendReply(log)"
                                            :disabled="activePublishingId === log.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-extrabold text-white shadow-md transition hover:bg-emerald-700 disabled:opacity-50"
                                        >
                                            <span>🚀</span>
                                            <span>{{ activePublishingId === log.id ? 'Mengirim Balasan...' : 'Approve & Kirim Komen ke Threads' }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Multi-Turn Conversation Thread Timeline (If available) -->
                                <div v-if="log.metadata?.conversation_threads && log.metadata.conversation_threads.length > 0" class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                                    <div
                                        v-for="(msg, mIdx) in log.metadata.conversation_threads"
                                        :key="mIdx"
                                        class="rounded-xl p-3 text-xs shadow-xs transition"
                                        :class="msg.role === 'user' 
                                            ? 'bg-amber-100/90 text-amber-950 ml-6 border border-amber-300' 
                                            : (mIdx === log.metadata.conversation_threads.length - 1 
                                                ? 'bg-white text-emerald-950 border-2 border-emerald-500 shadow-md ring-2 ring-emerald-200' 
                                                : 'bg-white/80 text-emerald-950 border border-emerald-200')"
                                    >
                                        <div class="flex items-center justify-between text-[10px] font-bold mb-1 opacity-85">
                                            <span class="flex items-center gap-1">
                                                <span>{{ msg.role === 'user' ? '👤 Masukan Anda (Pemilik Brand):' : '🤖 Ollama AI (Draft Balasan Versi ' + (Math.floor(mIdx / 2) + 1) + '):' }}</span>
                                                <span v-if="msg.role === 'assistant' && mIdx === log.metadata.conversation_threads.length - 1" class="rounded bg-emerald-600 px-1.5 py-0.2 text-[9px] text-white font-extrabold">
                                                    TERBARU / HASIL REVISI
                                                </span>
                                            </span>
                                            <span class="text-gray-400">{{ formatDate(msg.created_at) }}</span>
                                        </div>
                                        <p class="whitespace-pre-wrap font-semibold text-xs leading-relaxed">{{ msg.content }}</p>
                                    </div>

                                    <!-- Loading State Card while Ollama AI is thinking/revising -->
                                    <div v-if="submittingFeedbackId === log.id" class="rounded-xl p-3.5 text-xs bg-indigo-900 text-white shadow-md border border-indigo-700 animate-pulse flex items-center gap-3">
                                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent shrink-0"></div>
                                        <div>
                                            <div class="font-bold text-xs text-indigo-200">🤖 Local Ollama AI Sedang Berpikir...</div>
                                            <div class="text-[11px] text-indigo-300">Merevisi & meng-upgrade balasan komentar berdasarkan masukan terbaru Anda...</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fallback single reply display if no history thread -->
                                <div v-else class="bg-white p-3.5 rounded-xl border-2 border-emerald-500 shadow-sm">
                                    <div class="flex items-center justify-between text-[10px] font-bold text-emerald-800 mb-1">
                                        <span>🤖 Draft Balasan Ollama AI Terbaru:</span>
                                        <span class="rounded bg-emerald-600 px-1.5 py-0.2 text-[9px] text-white font-extrabold">READY FOR APPROVAL</span>
                                    </div>
                                    <p class="whitespace-pre-wrap font-semibold text-emerald-950 text-sm leading-relaxed">{{ log.response_text || log.metadata?.drafted_reply }}</p>
                                </div>

                                <!-- 🧠 Continuous Refinement Feedback Input -->
                                <div class="pt-2.5 border-t border-emerald-200/80">
                                    <label class="block text-[11px] font-bold text-indigo-950 mb-1.5">
                                        💡 Belum Sesuai? Berikan Masukan untuk Merevisi & Meng-upgrade Balasan Ini:
                                    </label>
                                    <div class="flex gap-2">
                                        <input
                                            v-model="feedbackInputs[log.id]"
                                            type="text"
                                            :disabled="submittingFeedbackId === log.id"
                                            placeholder="Contoh: Kurang ramah, jangan terlalu langsung jualan, tanya dulu stoknya atau empati dulu..."
                                            class="w-full rounded-lg border-indigo-300 text-xs shadow-sm focus:border-indigo-600 focus:ring-indigo-600 bg-white disabled:bg-gray-100"
                                            @keydown.enter.prevent="submitLogFeedback(log.id)"
                                        />
                                        <button
                                            type="button"
                                            @click="submitLogFeedback(log.id)"
                                            :disabled="submittingFeedbackId === log.id || !feedbackInputs[log.id]?.trim()"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50 shrink-0 transition"
                                        >
                                            <span v-if="submittingFeedbackId === log.id" class="h-3 w-3 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                            <span>{{ submittingFeedbackId === log.id ? 'Meng-upgrade...' : '⚡ Upgrade Balasan' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Load More Prospect Logs Button -->
                        <div v-if="prospectLogs.length > visibleLogsCount" class="mt-4 text-center">
                            <button
                                @click="loadMoreLogs"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-6 py-2.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 border border-indigo-200 shadow-xs transition"
                            >
                                <span>📥 Muat 10 Status Dipindai Lagi</span>
                                <span class="text-indigo-500">({{ visibleProspectLogs.length }} dari {{ prospectLogs.length }})</span>
                            </button>
                        </div>
                    </div>

                    <div v-else class="rounded-lg bg-gray-50 p-8 text-center text-sm text-gray-500">
                        <p class="font-medium text-gray-700">⏳ Belum ada status seller yang dipindai.</p>
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
                            v-for="(item, idx) in visibleLiveThreadsPosts"
                            :key="idx"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2 mb-2">
                                <span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                    Status Threads Publik
                                </span>

                                <a
                                    :href="item.target_url || item.permalink || item.url || item.link || 'https://www.threads.net/@seller_online/post/DbSKJzAk4ww'"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition"
                                >
                                    <span>🔗 Direct Post Threads: {{ item.target_url ? item.target_url.replace('https://www.threads.net', '') : '/post' }}</span>
                                    <span>↗</span>
                                </a>
                            </div>

                            <p class="whitespace-pre-wrap text-sm text-gray-800 font-medium">
                                {{ item.text || item.description || item.content || item }}
                            </p>
                        </div>

                        <!-- Load More Live Posts Button -->
                        <div v-if="liveThreadsPosts.length > visibleLiveCount" class="mt-4 text-center">
                            <button
                                @click="loadMoreLive"
                                class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-6 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-200 border border-gray-300 shadow-xs transition"
                            >
                                <span>📥 Muat 10 Hasil Pencarian Lagi</span>
                                <span class="text-gray-500">({{ visibleLiveThreadsPosts.length }} dari {{ liveThreadsPosts.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
