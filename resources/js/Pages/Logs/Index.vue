<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    logs: Array,
    fileSizeFormatted: String,
    logExists: Boolean,
});

const searchQuery = ref('');
const selectedLevel = ref('ALL');
const isClearing = ref(false);

const filteredLogs = computed(() => {
    let result = props.logs || [];

    if (selectedLevel.value !== 'ALL') {
        result = result.filter(log => log.level === selectedLevel.value);
    }

    if (searchQuery.value.trim() !== '') {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(log =>
            log.message.toLowerCase().includes(query) ||
            log.timestamp.toLowerCase().includes(query) ||
            log.level.toLowerCase().includes(query)
        );
    }

    return result;
});

const refreshLogs = () => {
    router.reload({ preserveScroll: true });
};

const clearLogs = () => {
    if (confirm('Apakah Anda yakin ingin mengosongkan file laravel.log?')) {
        isClearing.value = true;
        router.post(route('logs.clear'), {}, {
            onFinish: () => {
                isClearing.value = false;
            }
        });
    }
};

const getLevelBadgeClass = (level) => {
    switch (level) {
        case 'ERROR':
        case 'CRITICAL':
        case 'EMERGENCY':
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        case 'WARNING':
            return 'bg-amber-500/20 text-amber-400 border-amber-500/30';
        case 'INFO':
        case 'NOTICE':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
        default:
            return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
    }
};
</script>

<template>
    <Head title="System Logs Viewer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-slate-900">
                        📋 System Log Viewer (`laravel.log`)
                    </h2>
                    <p class="text-xs text-slate-500">
                        Pantau error runtime, log API, dan aktivitas sistem secara real-time
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                        Ukuran File: {{ fileSizeFormatted }}
                    </span>
                    <button
                        @click="refreshLogs"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        🔄 Refresh Log
                    </button>
                    <button
                        @click="clearLogs"
                        :disabled="isClearing"
                        class="rounded-xl bg-red-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-red-700 disabled:opacity-50"
                    >
                        🗑️ Bersihkan Log
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Filters & Search Toolbar -->
                <div class="rounded-2xl bg-white p-4 shadow-sm border border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
                    <!-- Level Filter Buttons -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button
                            v-for="lvl in ['ALL', 'ERROR', 'WARNING', 'INFO', 'DEBUG']"
                            :key="lvl"
                            @click="selectedLevel = lvl"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                selectedLevel === lvl
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                            ]"
                        >
                            {{ lvl }}
                        </button>
                    </div>

                    <!-- Search Box -->
                    <div class="relative min-w-[280px]">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari teks error / timestamp..."
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- High-Tech Dark Terminal Log Window -->
                <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/80 px-4 py-3 text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-red-500"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
                            <span class="ms-2 font-mono font-bold text-slate-300">storage/logs/laravel.log</span>
                        </div>
                        <div>
                            Menampilkan {{ filteredLogs.length }} entri log
                        </div>
                    </div>

                    <div class="max-h-[650px] overflow-y-auto p-4 font-mono text-xs space-y-2">
                        <div v-if="filteredLogs.length > 0">
                            <div
                                v-for="log in filteredLogs"
                                :key="log.id"
                                class="rounded-xl border border-slate-800/80 bg-slate-900/50 p-3.5 transition hover:border-slate-700"
                            >
                                <div class="flex items-center justify-between flex-wrap gap-2 mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span :class="['rounded border px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase', getLevelBadgeClass(log.level)]">
                                            {{ log.level }}
                                        </span>
                                        <span class="text-slate-500">[{{ log.timestamp }}]</span>
                                    </div>
                                    <span class="text-[10px] text-slate-600">{{ log.environment }}</span>
                                </div>
                                <div class="text-slate-200 whitespace-pre-wrap leading-relaxed break-words">
                                    {{ log.message }}
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-16 text-center text-slate-500 font-sans">
                            <p class="text-base font-semibold">Tidak Ada Entri Log Ditemukan 🟢</p>
                            <p class="mt-1 text-xs">File log bersih atau tidak ada entri yang sesuai dengan filter.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
