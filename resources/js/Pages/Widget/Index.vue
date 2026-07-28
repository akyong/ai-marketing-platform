<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    workspace: Object,
    brand: Object,
    appUrl: String,
});

const copiedSnippet = ref(false);

const snippetCode = computed(() => {
    const baseUrl = props.appUrl || 'http://localhost:8000';
    return `<script src="${baseUrl}/embed/chat.js" data-widget-key="${props.workspace?.widget_key}"><\/script>`;
});

const copySnippet = () => {
    navigator.clipboard.writeText(snippetCode.value);
    copiedSnippet.value = true;
    setTimeout(() => {
        copiedSnippet.value = false;
    }, 3000);
};
</script>

<template>
    <Head title="AI Customer Service Widget" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    💬 Embeddable AI Customer Service Live Chat Widget
                </h2>
                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-800">
                    Widget Key: {{ workspace?.widget_key }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Info Banner -->
                <div class="bg-gradient-to-r from-purple-700 to-indigo-700 p-6 text-white shadow-lg sm:rounded-lg">
                    <div class="max-w-3xl">
                        <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur-sm">
                            Fitur CS AI Live Chat
                        </span>
                        <h3 class="mt-2 text-2xl font-bold">
                            Pasang CS AI Pintar di Website {{ brand?.name || 'Waperu.com' }}
                        </h3>
                        <p class="mt-1 text-sm text-purple-100">
                            Cukup salin 1 baris kode script di bawah dan tempelkan ke website <strong>{{ brand?.website_url || 'waperu.com' }}</strong>. Pengunjung website dapat bertanya ke CS AI 24/7 dan dijawab otomatis berdasarkan Knowledge Base brand Anda!
                        </p>
                    </div>
                </div>

                <!-- Code Snippet Card -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium text-gray-900">
                            📋 Script Code Embed (Tempel di waperu.com)
                        </h3>
                        <PrimaryButton @click="copySnippet">
                            {{ copiedSnippet ? '✅ Script Tersalin!' : '📋 Copy Embed Code' }}
                        </PrimaryButton>
                    </div>

                    <p class="text-xs text-gray-500 mb-4">
                        Tempelkan baris kode HTML di bawah ini di bagian bawah sebelum tag <code class="bg-gray-100 px-1 py-0.5 rounded font-mono">&lt;/body&gt;</code> di website <strong>{{ brand?.name || 'Waperu.com' }}</strong>:
                    </p>

                    <div class="relative rounded-lg bg-gray-900 p-4 font-mono text-xs text-emerald-400 overflow-x-auto border border-gray-800">
                        <code>{{ snippetCode }}</code>
                    </div>
                </div>

                <!-- Live Test Simulator -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        🧪 Simulasi Uji Coba Chat CS AI
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">
                        Cobalah mengetik pertanyaan seolah-olah Anda adalah calon pembeli / pengunjung di Waperu.com!
                    </p>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 max-w-xl space-y-3">
                        <div class="text-xs font-semibold text-gray-400 uppercase">Interactive Widget Loaded:</div>
                        <div class="text-sm text-gray-700">
                            Tombol mengambang 💬 <strong>CS AI Assistant</strong> telah otomatis aktif di pojok kanan bawah layar ini! Silakan klik tombol 💬 di kanan bawah untuk mencoba obrolan live.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
