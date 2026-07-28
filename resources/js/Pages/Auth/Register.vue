<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun — Gotiau AI Platform" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Buat Akun Baru ✨
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Mulai kelola marketing & CS otonom brand Anda
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="font-semibold text-slate-700" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        👤
                    </span>
                    <TextInput
                        id="name"
                        type="text"
                        class="pl-10 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nama Lengkap Anda"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

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
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="font-semibold text-slate-700" />
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
                        autocomplete="new-password"
                        placeholder="Minimal 8 Karakter"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi Password" class="font-semibold text-slate-700" />
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        🔒
                    </span>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="pl-10 block w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi Password"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-500/25 transition hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                >
                    {{ form.processing ? 'Daftar...' : 'Daftar Akun Sekarang 🚀' }}
                </button>
            </div>

            <div class="pt-4 text-center text-xs text-slate-500">
                Sudah memiliki akun?
                <Link :href="route('login')" class="font-bold text-indigo-600 hover:underline ms-1">
                    Masuk di Sini
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
