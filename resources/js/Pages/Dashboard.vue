<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    generations: Array,
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
</script>

<template>
    <Head title="Dashboard AI Marketer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard AI Marketer — {{ currentWorkspace?.name || 'Workspace' }}
                </h2>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800" v-if="brand">
                    Brand: {{ brand.name }}
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Brand Context Card -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white shadow-lg sm:rounded-lg" v-if="brand">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs uppercase tracking-wider text-indigo-200">Active Brand Context</span>
                            <h3 class="text-2xl font-bold">{{ brand.name }}</h3>
                            <p class="mt-1 text-sm text-indigo-100 max-w-3xl">
                                {{ brand.description }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                <span class="rounded bg-white/20 px-2.5 py-1 backdrop-blur-sm">
                                    🎯 Target: {{ brand.target_audience || 'Belum diisi' }}
                                </span>
                                <span class="rounded bg-white/20 px-2.5 py-1 backdrop-blur-sm">
                                    🗣️ Tone: {{ brand.tone_of_voice || 'Belum diisi' }}
                                </span>
                            </div>
                        </div>

                        <!-- Quick Autonomous Trigger -->
                        <div>
                            <button
                                @click="runAutonomousWorkflow"
                                :disabled="autonomousForm.processing"
                                class="rounded-lg bg-white px-4 py-3 text-sm font-bold text-indigo-700 shadow transition hover:bg-indigo-50 disabled:opacity-50"
                            >
                                {{ autonomousForm.processing ? '⚡ AI Meriset & Menyusun Campaign...' : '⚡ Jalankan 3-Step Autonomous Campaign' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- AI Prompt Generator Box -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">
                        🤖 Tanya / Minta AI Marketer Bekerja
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Ketik instruksi riset audiens, ide konten harian, atau pembuatan caption untuk brand {{ brand?.name }}.
                    </p>

                    <form @submit.prevent="generateAiStrategy" class="mt-4 space-y-4">
                        <div>
                            <InputLabel for="prompt" value="Instruksi untuk AI Marketer" />
                            <textarea
                                id="prompt"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3"
                                v-model="aiForm.prompt"
                                required
                                placeholder="Contoh: Buatkan riset target audiens dan 3 ide konten TikTok untuk menarik seller jualan online COD di Waperu."
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-500">
                                💡 Tip: Anda juga dapat mengirimkan perintah langsung dari Telegram Bot setelah dipasang.
                            </div>
                            <PrimaryButton :disabled="aiForm.processing">
                                {{ aiForm.processing ? 'AI Sedang Berpikir...' : 'Jalankan AI Marketer 🚀' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- AI History List -->
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        📜 Riwayat Strategi & Konten AI
                    </h3>

                    <div v-if="generations && generations.length > 0" class="space-y-4">
                        <div
                            v-for="item in generations"
                            :key="item.id"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300"
                        >
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="font-medium text-indigo-600 uppercase">
                                    {{ item.type }}
                                </span>
                                <span>{{ new Date(item.created_at).toLocaleString('id-ID') }}</span>
                            </div>

                            <div class="mt-2 font-medium text-gray-900">
                                ❓ {{ item.prompt }}
                            </div>

                            <div class="mt-3 whitespace-pre-wrap rounded bg-gray-50 p-3 text-sm text-gray-700 font-mono">
                                {{ item.response_text }}
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-sm text-gray-500">
                        Belum ada riwayat strategi AI. Coba ketik instruksi di atas untuk memulai!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
