<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const fillDemoAdmin = () => {
    form.email = 'admin@waperu.com';
    form.password = 'password';
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk — Gotiau AI Platform" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Selamat Datang Kembali 👋
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Masuk ke Gotiau AI Marketing & CS Operating System
            </p>
        </div>

        <div v-if="status" class="mb-4 rounded-xl bg-emerald-50 p-3 text-xs font-semibold text-emerald-700 border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email Address" class="font-semibold text-slate-700" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        📧
                    </span>
                    <TextInput
                        id="email"
                        type="email"
                        class="pl-10 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Password" class="font-semibold text-slate-700" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs text-indigo-600 font-medium hover:underline"
                    >
                        Lupa password?
                    </Link>
                </div>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        🔒
                    </span>
                    <TextInput
                        id="password"
                        type="password"
                        class="pl-10 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs font-medium text-slate-600">Ingat Saya</span>
                </label>
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                >
                    {{ form.processing ? 'Memproses Login...' : 'Masuk ke Platform 🚀' }}
                </button>
            </div>

            <!-- Quick Demo Credentials Filler Button -->
            <div class="pt-2">
                <button
                    type="button"
                    @click="fillDemoAdmin"
                    class="w-full rounded-xl border border-dashed border-indigo-300 bg-indigo-50/50 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition"
                >
                    ⚡ Isi Otomatis Akun Admin Waperu
                </button>
            </div>

            <div class="pt-4 text-center text-xs text-slate-500">
                Belum memiliki akun?
                <Link :href="route('register')" class="font-bold text-indigo-600 hover:underline ms-1">
                    Daftar Akun Baru
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
