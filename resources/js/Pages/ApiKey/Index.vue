<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    apiKeys: Array,
});

const replizAccessKey = ref('');
const replizSecretKey = ref('');

const keyForm = useForm({
    key_name: '',
    provider: 'gemini',
    api_key: '',
});

const addKey = () => {
    if (keyForm.provider === 'repliz' && replizAccessKey.value && replizSecretKey.value) {
        keyForm.api_key = `${replizAccessKey.value.trim()}:${replizSecretKey.value.trim()}`;
    }

    keyForm.post(route('api-keys.store'), {
        onSuccess: () => {
            keyForm.reset();
            replizAccessKey.value = '';
            replizSecretKey.value = '';
        },
    });
};

const toggleKey = (keyId) => {
    useForm({}).patch(route('api-keys.toggle', keyId));
};

const deleteKey = (keyId) => {
    if (confirm('Apakah Anda yakin ingin menghapus API Key ini?')) {
        useForm({}).delete(route('api-keys.destroy', keyId));
    }
};
</script>

<template>
    <Head title="Smart API Key Pool" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    🔑 Smart API Key Pool & Rotator
                </h2>
                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                    Auto-Failover Active
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Info Banner -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    <strong>💡 Fitur Smart Key Rotation:</strong> Anda dapat menambahkan beberapa API Key (misal: Gemini, Repliz, Telegram).
                    Khusus <strong>Repliz API Key</strong>, sistem mendukung format <strong>Access Key + Secret Key</strong> (HTTP Basic Auth) untuk penerbitan ke media sosial.
                </div>

                <!-- Add New Key Form -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">
                        Tambah API Key / Token Baru
                    </h3>

                    <form @submit.prevent="addKey" class="mt-4 space-y-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <InputLabel for="key_name" value="Nama Label Key" />
                                <TextInput
                                    id="key_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="keyForm.key_name"
                                    required
                                    placeholder="Misal: Repliz Account Utama"
                                />
                                <InputError class="mt-2" :message="keyForm.errors.key_name" />
                            </div>

                            <div>
                                <InputLabel for="provider" value="Provider API" />
                                <select
                                    id="provider"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    v-model="keyForm.provider"
                                >
                                    <option value="gemini">Google Gemini AI</option>
                                    <option value="repliz">Repliz API (Access Key & Secret Key)</option>
                                    <option value="telegram">Telegram Bot Token</option>
                                    <option value="openai">OpenAI</option>
                                    <option value="claude">Anthropic Claude</option>
                                </select>
                                <InputError class="mt-2" :message="keyForm.errors.provider" />
                            </div>

                            <!-- Standard Single API Key Field -->
                            <div v-if="keyForm.provider !== 'repliz'">
                                <InputLabel for="api_key" value="API Key / Token Value" />
                                <TextInput
                                    id="api_key"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="keyForm.api_key"
                                    required
                                    placeholder="AIzaSy... atau 12345:ABC..."
                                />
                                <InputError class="mt-2" :message="keyForm.errors.api_key" />
                            </div>
                        </div>

                        <!-- Repliz Dual Field (Access Key & Secret Key) -->
                        <div v-if="keyForm.provider === 'repliz'" class="grid grid-cols-1 gap-4 md:grid-cols-2 rounded-xl bg-purple-50 p-4 border border-purple-200">
                            <div>
                                <InputLabel for="repliz_access" value="Repliz Access Key (Username)" />
                                <TextInput
                                    id="repliz_access"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="replizAccessKey"
                                    required
                                    placeholder="Misal: rep_acc_83e7..."
                                />
                            </div>
                            <div>
                                <InputLabel for="repliz_secret" value="Repliz Secret Key (Password)" />
                                <TextInput
                                    id="repliz_secret"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="replizSecretKey"
                                    required
                                    placeholder="Misal: rep_sec_91f4..."
                                />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton class="w-full md:w-auto px-6" :disabled="keyForm.processing">
                                + Simpan ke Pool
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Key Pool List Table -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Daftar API Key Pool Aktif ({{ apiKeys.length }})
                    </h3>

                    <div class="overflow-x-auto" v-if="apiKeys && apiKeys.length > 0">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Label Key</th>
                                    <th class="px-4 py-3">Provider</th>
                                    <th class="px-4 py-3">Status Pool</th>
                                    <th class="px-4 py-3">Total Penggunaan</th>
                                    <th class="px-4 py-3">Terakhir Dipakai</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="key in apiKeys" :key="key.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ key.key_name }}
                                    </td>
                                    <td class="px-4 py-3 uppercase text-xs font-bold text-indigo-600">
                                        {{ key.provider }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="key.quota_exceeded_until && new Date(key.quota_exceeded_until) > new Date()"
                                            class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800"
                                        >
                                            ⏳ Quota Cooldown (Hingga {{ new Date(key.quota_exceeded_until).toLocaleTimeString() }})
                                        </span>
                                        <span
                                            v-else-if="key.is_active"
                                            class="rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800"
                                        >
                                            ✅ Ready (Aktif)
                                        </span>
                                        <span
                                            v-else
                                            class="rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600"
                                        >
                                            🚫 Nonaktif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ key.usage_count }}x dipanggil
                                        <span v-if="key.error_count > 0" class="text-xs text-red-500">
                                            ({{ key.error_count }} limit hit)
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ key.last_used_at ? new Date(key.last_used_at).toLocaleString('id-ID') : 'Belum pernah' }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-2">
                                        <button
                                            @click="toggleKey(key.id)"
                                            class="text-xs font-semibold text-indigo-600 hover:underline"
                                        >
                                            {{ key.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                        <button
                                            @click="deleteKey(key.id)"
                                            class="text-xs font-semibold text-red-600 hover:underline"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-8 text-center text-sm text-gray-500">
                        Belum ada API Key yang tersimpan di Pool. Tambahkan API Key Gemini / Repliz / Telegram di atas!
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
