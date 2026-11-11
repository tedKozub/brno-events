<div>
    <!-- Trigger Button -->
    <button
        wire:click="openModal"
        type="button"
        class="btn btn-sm btn-ghost border border-base-300 font-bold rounded-xl px-2.5 sm:px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center text-base-content/70 hover:text-error hover:border-error/40 transition-colors"
        title="Nahlásit chybu nebo nesrovnalost u této akce"
    >
        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
        </svg>
        <span class="hidden sm:inline">Nahlásit chybu</span>
        <span class="sm:hidden">Nahlásit</span>
    </button>

    <!-- Modal Dialog -->
    @if($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div
                wire:click="closeModal"
                class="fixed inset-0 bg-base-900/60 backdrop-blur-xs transition-opacity"
            ></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-base-100 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-base-300">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-base-200">
                        <div class="flex items-center gap-2 text-base font-bold text-base-content">
                            <svg class="w-5 h-5 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Nahlásit nesrovnalost nebo chybu</span>
                        </div>
                        <button
                            wire:click="closeModal"
                            type="button"
                            class="text-base-content/40 hover:text-base-content p-1 rounded-lg hover:bg-base-200"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        @if($submitted)
                            <div class="text-center py-6">
                                <div class="w-12 h-12 rounded-full bg-success/10 text-success mx-auto flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-base-content">Děkujeme za nahlášení!</h4>
                                <p class="text-sm text-base-content/70 mt-1 max-w-sm mx-auto">
                                    Informaci jsme předali našemu moderátorovi a případnou nesrovnalost co nejdříve prověříme.
                                </p>
                                <button
                                    wire:click="closeModal"
                                    type="button"
                                    class="btn btn-sm btn-primary mt-5"
                                >
                                    Zavřít
                                </button>
                            </div>
                        @else
                            <form wire:submit.prevent="submit" class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1.5">
                                        Co je u akce špatně? <span class="text-error">*</span>
                                    </label>
                                    <select
                                        wire:model="reason"
                                        class="select select-bordered w-full text-sm font-medium"
                                    >
                                        @foreach($reasons as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('reason') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1.5">
                                        Podrobnosti (volitelné)
                                    </label>
                                    <textarea
                                        wire:model="details"
                                        rows="3"
                                        placeholder="např. Akce byla přesunuta na jinou hodinu, nebo byl změněn klub..."
                                        class="textarea textarea-bordered w-full text-sm"
                                    ></textarea>
                                    @error('details') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-1.5">
                                        Tvůj e-mail (volitelné pro případ dotazu)
                                    </label>
                                    <input
                                        type="email"
                                        wire:model="contactEmail"
                                        placeholder="student@vut.cz nebo jmeno@muni.cz"
                                        class="input input-bordered w-full text-sm"
                                    />
                                    @error('contactEmail') <span class="text-xs text-error mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-2 flex items-center justify-end gap-2 border-t border-base-200">
                                    <button
                                        type="button"
                                        wire:click="closeModal"
                                        class="btn btn-ghost btn-sm"
                                    >
                                        Zrušit
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-sm"
                                    >
                                        <span wire:loading.remove wire:target="submit">Odeslat hlášení</span>
                                        <span wire:loading wire:target="submit" class="loading loading-spinner loading-xs"></span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
