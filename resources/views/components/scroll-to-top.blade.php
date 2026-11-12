{{-- Plovoucí tlačítko pro plynulé scrollování zpět nahoru --}}
<button
    x-data="{ visible: false }"
    @scroll.window.passive="visible = (window.pageYOffset || document.documentElement.scrollTop) > 280"
    x-show="visible"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-3 scale-90"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-3 scale-90"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    type="button"
    class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-40 btn btn-circle btn-primary btn-sm sm:btn-md shadow-2xl border-2 border-base-100 hover:scale-110 active:scale-95 transition-all flex items-center justify-center cursor-pointer"
    aria-label="Zpět nahoru"
    title="Zpět nahoru"
>
    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary-content" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>
