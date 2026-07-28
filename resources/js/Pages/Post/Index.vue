<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    posts: Array,
});

const activeTab = ref('all');
const isPublishingInstant = ref(false);

const testInstantPost = () => {
    if (confirm('AI akan langsung membuat 1 materi postingan baru dan mempublikasikannya INSTAN ke Repliz sekarang. Lanjutkan?')) {
        isPublishingInstant.value = true;
        useForm({}).post(route('posts.instant'), {
            onFinish: () => {
                isPublishingInstant.value = false;
            }
        });
    }
};

const postForm = useForm({
    title: '',
    content: '',
    platforms: ['instagram', 'facebook', 'threads', 'tiktok'],
    scheduled_at: '',
});

const filteredPosts = computed(() => {
    if (activeTab.value === 'all') return props.posts;
    return props.posts.filter((p) => p.status === activeTab.value);
});

const createPost = () => {
    postForm.post(route('posts.store'), {
        onSuccess: () => postForm.reset(),
    });
};

const publishNow = (postId) => {
    if (confirm('Apakah Anda yakin ingin langsung mempublikasikan postingan ini ke Social Media via Repliz?')) {
        useForm({}).post(route('posts.publish', postId));
    }
};

const deletePost = (postId) => {
    if (confirm('Hapus postingan ini?')) {
        useForm({}).delete(route('posts.destroy', postId));
    }
};
</script>

<template>
    <Head title="Content Planner & Publishing" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    📢 Social Media Publishing Engine (Repliz Integration)
                </h2>
                <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800">
                    Phase 4 Active
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Repliz Status Banner -->
                <div class="flex items-center justify-between rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900 flex-wrap gap-3">
                    <div class="flex-1">
                        <strong>🔌 Status Integrasi Repliz:</strong> 
                        Setiap postingan yang dibuat atau di-generate otomatis oleh AI Marketer akan dikirimkan ke Repliz Engine untuk diterbitkan ke Instagram, Facebook, TikTok, dan X.
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="testInstantPost"
                            :disabled="isPublishingInstant"
                            class="shrink-0 rounded bg-emerald-600 px-3.5 py-1.5 text-xs font-bold text-white shadow hover:bg-emerald-700 disabled:opacity-50 transition"
                        >
                            🚀 {{ isPublishingInstant ? 'Memproses Repliz...' : 'Test Instant Post Now' }}
                        </button>
                        <a
                            :href="route('api-keys.index')"
                            class="shrink-0 rounded bg-indigo-600 px-3.5 py-1.5 text-xs font-bold text-white shadow hover:bg-indigo-700 transition"
                        >
                            ⚙️ Atur Key Repliz
                        </a>
                    </div>
                </div>

                <!-- Create Post / Draft Form -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        ✍️ Buat Draft & Jadwal Postingan Baru
                    </h3>
                    <p class="text-xs text-gray-500 mb-4">
                        Tulis postingan manual atau salin hasil generate AI Marketer untuk dipublikasikan ke Instagram, Facebook, TikTok, & X.
                    </p>

                    <form @submit.prevent="createPost" class="space-y-4">
                        <div>
                            <InputLabel for="title" value="Judul / Topik Postingan" />
                            <TextInput
                                id="title"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="postForm.title"
                                required
                                placeholder="Misal: Tips Jualan Online COD 0% Komisi"
                            />
                            <InputError class="mt-2" :message="postForm.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="content" value="Caption / Isi Konten" />
                            <textarea
                                id="content"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-sans"
                                rows="4"
                                v-model="postForm.content"
                                required
                                placeholder="Tulis caption lengkap dengan hashtag (#Waperu #PejuangRupiah)..."
                            ></textarea>
                            <InputError class="mt-2" :message="postForm.errors.content" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Platform Media Sosial Target" />
                                <div class="mt-2 flex flex-wrap gap-4 text-sm font-medium text-gray-700">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="instagram" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>📸 Instagram</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="facebook" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>📘 Facebook</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="threads" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>🧵 Threads</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="tiktok" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>🎵 TikTok</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="linkedin" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>💼 LinkedIn</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="checkbox" value="youtube" v-model="postForm.platforms" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                        <span>🔴 YouTube</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="scheduled_at" value="Jadwalkan Posting (Opsional)" />
                                <TextInput
                                    id="scheduled_at"
                                    type="datetime-local"
                                    class="mt-1 block w-full"
                                    v-model="postForm.scheduled_at"
                                />
                                <span class="text-xs text-gray-400">Kosongkan jika ingin disimpan sebagai Draft biasa.</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <PrimaryButton :disabled="postForm.processing">
                                💾 Simpan Postingan
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Content Board List -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            📋 Daftar Postingan Media Sosial
                        </h3>

                        <!-- Filter Tabs -->
                        <div class="flex space-x-2 text-xs font-semibold">
                            <button
                                @click="activeTab = 'all'"
                                class="rounded-lg px-3 py-1.5 transition"
                                :class="activeTab === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                Semua ({{ posts.length }})
                            </button>
                            <button
                                @click="activeTab = 'draft'"
                                class="rounded-lg px-3 py-1.5 transition"
                                :class="activeTab === 'draft' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                Draft
                            </button>
                            <button
                                @click="activeTab = 'scheduled'"
                                class="rounded-lg px-3 py-1.5 transition"
                                :class="activeTab === 'scheduled' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                Scheduled
                            </button>
                            <button
                                @click="activeTab = 'published'"
                                class="rounded-lg px-3 py-1.5 transition"
                                :class="activeTab === 'published' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >
                                Published
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredPosts && filteredPosts.length > 0" class="space-y-4">
                        <div
                            v-for="post in filteredPosts"
                            :key="post.id"
                            class="rounded-lg border border-gray-200 p-4 transition hover:border-indigo-300"
                        >
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold text-gray-900 text-base">
                                    {{ post.title }}
                                </h4>
                                <span
                                    class="rounded px-2.5 py-0.5 text-xs font-bold uppercase"
                                    :class="{
                                        'bg-gray-100 text-gray-700': post.status === 'draft',
                                        'bg-amber-100 text-amber-800': post.status === 'scheduled',
                                        'bg-emerald-100 text-emerald-800': post.status === 'published',
                                        'bg-red-100 text-red-800': post.status === 'failed',
                                    }"
                                >
                                    {{ post.status }}
                                </span>
                            </div>

                            <div class="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-3 text-sm text-gray-700">
                                {{ post.content }}
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
                                <div class="flex items-center gap-2">
                                    <span>Target:</span>
                                    <span v-for="plat in post.platforms" :key="plat" class="rounded bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700 capitalize">
                                        {{ plat }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button
                                        v-if="post.status !== 'published'"
                                        @click="publishNow(post.id)"
                                        class="rounded bg-emerald-600 px-3 py-1 text-xs font-bold text-white transition hover:bg-emerald-700"
                                    >
                                        🚀 Publish Now (Repliz)
                                    </button>
                                    <button
                                        @click="deletePost(post.id)"
                                        class="text-red-600 hover:underline font-medium"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-8 text-center text-sm text-gray-500">
                        Belum ada postingan di kategori ini.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
