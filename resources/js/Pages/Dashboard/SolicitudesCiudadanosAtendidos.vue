
<template>
    <div
        class="bg-gradient-to-br from-color1-800 via-color1-800 to-color1-900
        hover:from-color1-700/90 hover:to-color1-800
        text-white p-4 rounded-xl flex flex-col justify-between
        h-full min-h-[160px] relative overflow-hidden cursor-pointer
        group border border-white/10 hover:border-color1-400/70
        shadow-sm hover:shadow-md transition-all duration-300 ease-out"
    >
        <!-- Decoración de fondo -->
        <div
            class="absolute -right-4 -bottom-4 w-24 h-24
            bg-white/5 rounded-full blur-xl pointer-events-none"
        ></div>

        <div
            class="absolute -top-8 -right-8 w-28 h-28
            rounded-full border border-white/5 pointer-events-none"
        ></div>

        <!-- Cabecera -->
        <div class="flex items-center justify-between relative z-10">
            <span
                class="text-[11px] font-bold uppercase tracking-wider
                text-color1-200/80 group-hover:text-white
                transition-colors duration-200"
            >
                Atención Ciudadana
            </span>

            <!-- Icono de personas -->
            <div
                class="p-2 rounded-lg bg-white/10 text-white
                backdrop-blur-sm shadow-sm ring-1 ring-white/20
                group-hover:bg-white/20 group-hover:ring-white/40
                transition-all duration-300"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="10" cy="7" r="4"/>
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>

        <!-- KPI principal -->
        <div
            v-if="panelData"
            class="my-auto py-1 relative z-10
            transition-transform duration-300
            group-hover:-translate-y-0.5"
        >
            <div class="flex items-baseline gap-2">
                <span
                    class="text-3xl sm:text-4xl font-black text-white
                    tracking-tight tabular-nums drop-shadow-sm"
                >
                    {{ panelData.ciudadanos_atendidos ?? 0 }}
                </span>
            </div>

            <div class="text-xs font-bold text-color1-100 mt-1">
                Solicitantes únicos
            </div>

            <div class="text-[0.65rem] text-color1-200/70 mt-0.5">
                Personas que han solicitado apoyo
            </div>
        </div>

        <!-- Estado vacío -->
        <div
            v-else
            class="text-center text-color1-200/60 py-4
            text-xs italic my-auto relative z-10"
        >
            Sin registros en este periodo.
        </div>

        <!-- Pie de tarjeta -->
        <div
            class="mt-3 pt-2.5 border-t border-white/10
            group-hover:border-white/20 flex items-center
            justify-between gap-2 text-xs relative z-10
            transition-colors duration-300"
        >
            <span
                class="text-[11px] text-color1-200/70 italic
                group-hover:text-white/90 transition-colors"
            >
                Indicadores de solicitantes
            </span>

            <div
                class="flex items-center gap-1 shrink-0
                text-[11px] font-semibold text-color1-100
                opacity-90 group-hover:opacity-100
                transition-opacity"
            >
                <span
                    class="hidden sm:inline uppercase
                    tracking-wider text-[10px]"
                >
                    Ver
                </span>

                <svg
                    class="w-3.5 h-3.5 transform
                    transition-transform duration-300
                    group-hover:translate-x-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m9 18 6-6-6-6"
                    />
                </svg>
            </div>
        </div>
    </div>
</template>


<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    panelData: Object,
    currentPeriod: String
});

</script>


