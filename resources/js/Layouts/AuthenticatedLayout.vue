<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-blue-50/50"> <nav class="bg-sky-500 border-b border-sky-600 shadow-lg">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo class="block h-12 w-auto drop-shadow-md" />
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')" 
                                    class="text-white font-bold hover:text-sky-100 border-transparent transition">
                                    Inicio
                                </NavLink>
                                <NavLink :href="route('estudiantes.index')" :active="route().current('estudiantes.index')"
                                    class="text-white font-bold hover:text-sky-100 border-transparent transition">
                                    Estudiantes
                                </NavLink>
                                <NavLink :href="route('materias.index')" :active="route().current('materias.index')"
                                    class="text-white font-bold hover:text-sky-100 border-transparent transition">
                                    Materias
                                </NavLink>
                                <NavLink :href="route('inscripciones.index')" :active="route().current('inscripciones.index')"
                                    class="text-white font-bold hover:text-sky-100 border-transparent transition">
                                    Inscripciones
                                </NavLink>
                                <NavLink :href="route('evaluaciones.index')" :active="route().current('evaluaciones.index')"
                                    class="text-white font-bold hover:text-sky-100 border-transparent transition">
                                    Evaluaciones
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-sky-700 bg-white hover:bg-sky-50 focus:outline-none transition ease-in-out duration-150 shadow-sm"
                                            >
                                                {{ $page.props.auth.user.name }}
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Perfil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Cerrar Sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-sky-600 focus:outline-none transition"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden bg-sky-600">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" class="text-white"> Inicio </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('estudiantes.index')" :active="route().current('estudiantes.index')" class="text-white"> Estudiantes </ResponsiveNavLink>
                        </div>
                </div>
            </nav>

            <header class="bg-white shadow-sm border-b border-sky-100" v-if="$slots.header">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div class="text-sky-900 font-extrabold">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>