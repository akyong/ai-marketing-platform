<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    workspace: Object,
    brand: Object,
});

const brandForm = useForm({
    name: props.brand?.name || '',
    website_url: props.brand?.website_url || '',
    logo_url: props.brand?.logo_url || '',
    brand_color: props.brand?.brand_color || '#4F46E5',
    industry: props.brand?.industry || '',
    description: props.brand?.description || '',
    target_audience: props.brand?.target_audience || '',
    tone_of_voice: props.brand?.tone_of_voice || '',
    knowledge_base: props.brand?.knowledge_base || '',
    custom_rules: props.brand?.custom_rules || '',
});

const newWorkspaceForm = useForm({
    name: '',
    brand_name: '',
    industry: '',
});

const updateBrand = () => {
    if (props.brand?.id) {
        brandForm.put(route('brands.update', props.brand.id));
    }
};

const createWorkspace = () => {
    newWorkspaceForm.post(route('workspaces.store'), {
        onSuccess: () => newWorkspaceForm.reset(),
    });
};
</script>

<template>
    <Head title="Workspace & Brand Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Workspace & Brand Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Current Workspace Overview -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900">
                        Active Workspace: {{ workspace.name }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Slug: <code class="rounded bg-gray-100 px-2 py-1 text-xs">{{ workspace.slug }}</code>
                    </p>
                </div>

                <!-- Brand Profile Form -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <header>
                        <h3 class="text-lg font-medium text-gray-900">
                            Profil Brand (Knowledge Base untuk AI)
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Informasi ini digunakan oleh AI Marketer untuk memahami identitas, audiens, dan gaya komunikasi brand Anda (contoh: Waperu).
                        </p>
                    </header>

                    <form @submit.prevent="updateBrand" class="mt-6 space-y-6">
                        <div>
                            <InputLabel for="brand_name" value="Nama Brand" />
                            <TextInput
                                id="brand_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="brandForm.name"
                                required
                            />
                            <InputError class="mt-2" :message="brandForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="website_url" value="Website URL" />
                            <TextInput
                                id="website_url"
                                type="url"
                                class="mt-1 block w-full"
                                v-model="brandForm.website_url"
                                placeholder="https://waperu.com"
                            />
                            <InputError class="mt-2" :message="brandForm.errors.website_url" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="logo_url" value="URL Logo Brand (PNG/SVG Transparent)" />
                                <TextInput
                                    id="logo_url"
                                    type="url"
                                    class="mt-1 block w-full"
                                    v-model="brandForm.logo_url"
                                    placeholder="https://waperu.com/logo.png"
                                />
                                <InputError class="mt-2" :message="brandForm.errors.logo_url" />
                            </div>

                            <div>
                                <InputLabel for="brand_color" value="Warna Utama Brand (Hex Color)" />
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        type="color"
                                        v-model="brandForm.brand_color"
                                        class="h-10 w-12 rounded cursor-pointer border border-gray-300"
                                    />
                                    <TextInput
                                        id="brand_color"
                                        type="text"
                                        class="block w-full"
                                        v-model="brandForm.brand_color"
                                        placeholder="#4F46E5"
                                    />
                                </div>
                                <InputError class="mt-2" :message="brandForm.errors.brand_color" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="industry" value="Industri / Kategori" />
                            <TextInput
                                id="industry"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="brandForm.industry"
                                placeholder="E-Commerce Platform / SaaS UMKM"
                            />
                            <InputError class="mt-2" :message="brandForm.errors.industry" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Deskripsi Singkat Brand" />
                            <textarea
                                id="description"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3"
                                v-model="brandForm.description"
                                placeholder="Platform toko online mandiri 0% komisi..."
                            ></textarea>
                            <InputError class="mt-2" :message="brandForm.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="target_audience" value="Target Audiens Utama (Pelanggan)" />
                            <textarea
                                id="target_audience"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3"
                                v-model="brandForm.target_audience"
                                placeholder="Seller Online, Pejuang COD, UMKM Indonesia..."
                            ></textarea>
                            <InputError class="mt-2" :message="brandForm.errors.target_audience" />
                        </div>

                        <div>
                            <InputLabel for="tone_of_voice" value="Gaya Bahasa / Tone of Voice AI" />
                            <textarea
                                id="tone_of_voice"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="2"
                                v-model="brandForm.tone_of_voice"
                                placeholder="Profesional, Empatis, Semangat Pejuang, Langsung ke Solusi..."
                            ></textarea>
                            <InputError class="mt-2" :message="brandForm.errors.tone_of_voice" />
                        </div>

                        <div>
                            <InputLabel for="knowledge_base" value="🧠 Knowledge Base Brand (Fakta Kunci & Panduan Resmi)" />
                            <textarea
                                id="knowledge_base"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                rows="4"
                                v-model="brandForm.knowledge_base"
                                placeholder="1. Waperu.com adalah platform toko online mandiri 0% komisi...&#10;2. Landing Page & Scrape Produk...&#10;3. Referensi resmi: https://waperu.com/guides"
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">Poin-poin fakta, keunggulan produk, & link panduan resmi yang otomatis dibaca & diingat oleh AI Engine.</p>
                            <InputError class="mt-2" :message="brandForm.errors.knowledge_base" />
                        </div>

                        <div>
                            <InputLabel for="custom_rules" value="📜 Aturan Khusus Penulisan AI (Custom Prompt Rules)" />
                            <textarea
                                id="custom_rules"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                rows="4"
                                v-model="brandForm.custom_rules"
                                placeholder="1. Dilarang mengaku sebagai AI atau Bot.&#10;2. Dilarang menggunakan tanda tebal asterisks **...&#10;3. Gunakan sapaan Bosku..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">Aturan ketat penulisan gaya bahasa yang wajib ditaati AI saat mempublikasikan konten ke sosial media.</p>
                            <InputError class="mt-2" :message="brandForm.errors.custom_rules" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="brandForm.processing">
                                Simpan Perubahan Brand
                            </PrimaryButton>
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="brandForm.recentlySuccessful" class="text-sm text-gray-600">
                                    Tersimpan.
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <!-- Create New Workspace Form -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <header>
                        <h3 class="text-lg font-medium text-gray-900">
                            Buat Workspace Baru
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Tambahkan workspace baru untuk mengelola bisnis atau brand lain.
                        </p>
                    </header>

                    <form @submit.prevent="createWorkspace" class="mt-6 space-y-6 max-w-xl">
                        <div>
                            <InputLabel for="new_ws_name" value="Nama Workspace" />
                            <TextInput
                                id="new_ws_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="newWorkspaceForm.name"
                                required
                                placeholder="Misal: Bisnis Kopiku"
                            />
                            <InputError class="mt-2" :message="newWorkspaceForm.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="new_brand_name" value="Nama Brand Utama" />
                            <TextInput
                                id="new_brand_name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="newWorkspaceForm.brand_name"
                                placeholder="Misal: Kopiku Original"
                            />
                            <InputError class="mt-2" :message="newWorkspaceForm.errors.brand_name" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="newWorkspaceForm.processing">
                                Buat Workspace
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
