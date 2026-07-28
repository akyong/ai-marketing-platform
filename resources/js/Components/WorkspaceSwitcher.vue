<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentWorkspace = computed(() => page.props.auth.current_workspace);
const workspaces = computed(() => page.props.auth.workspaces || []);

const switchForm = useForm({});

const switchWorkspace = (workspaceId) => {
    switchForm.post(route('workspaces.switch', workspaceId));
};
</script>

<template>
    <div class="relative ms-3" v-if="currentWorkspace">
        <Dropdown align="left" width="60">
            <template #trigger>
                <span class="inline-flex rounded-md">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none"
                    >
                        <span class="mr-2 inline-block h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                        <span class="font-semibold">{{ currentWorkspace.name }}</span>

                        <svg
                            class="-me-0.5 ms-2 h-4 w-4 text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </span>
            </template>

            <template #content>
                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    Workspaces
                </div>

                <div v-for="workspace in workspaces" :key="workspace.id">
                    <button
                        @click="switchWorkspace(workspace.id)"
                        class="block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out hover:bg-gray-100 focus:outline-none"
                        :class="{
                            'font-bold text-indigo-600 bg-indigo-50': workspace.id === currentWorkspace.id,
                            'text-gray-700': workspace.id !== currentWorkspace.id
                        }"
                    >
                        {{ workspace.name }}
                    </button>
                </div>

                <div class="border-t border-gray-100"></div>

                <DropdownLink :href="route('workspace.settings')">
                    ⚙️ Workspace & Brand Settings
                </DropdownLink>
            </template>
        </Dropdown>
    </div>
</template>
