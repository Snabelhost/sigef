<div
    x-data="{ open: false }"
    x-on:open-change-password.window="open = true"
    x-init="$watch('open', value => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    })">
    {{-- Overlay --}}
    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 999999; background: rgba(0,0,0,0.5);"
        x-on:click.self="open = false">
        {{-- Centering wrapper --}}
        <div style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;">
            {{-- Modal Box --}}
            <div
                style="background: white; border-radius: 12px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); width: 100%; max-width: 420px; padding: 24px; margin: 16px;"
                x-on:click.stop>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h2 style="font-size: 18px; font-weight: bold; color: #111;">Alterar Palavra-passe</h2>
                    <button x-on:click="open = false" style="color: #999; cursor: pointer; background: none; border: none; font-size: 20px;">✕</button>
                </div>

                <form wire:submit="save" x-on:password-changed.window="open = false">
                    {{-- Palavra-passe actual --}}
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">
                            Palavra-passe Actual <span style="color: red;">*</span>
                        </label>
                        <input
                            type="password"
                            wire:model="current_password"
                            placeholder="Palavra-passe Actual"
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
                        @error('current_password')
                        <p style="color: red; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nova palavra-passe --}}
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">
                            Nova Palavra-passe <span style="color: red;">*</span>
                        </label>
                        <input
                            type="password"
                            wire:model="new_password"
                            placeholder="Nova Palavra-passe"
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
                        @error('new_password')
                        <p style="color: red; font-size: 12px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Confirmar palavra-passe --}}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">
                            Confirmar Palavra-passe <span style="color: red;">*</span>
                        </label>
                        <input
                            type="password"
                            wire:model="new_password_confirmation"
                            placeholder="Confirmar Palavra-passe"
                            style="width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; outline: none; box-sizing: border-box;">
                    </div>

                    {{-- Botões --}}
                    <div style="display: flex; justify-content: flex-end; gap: 12px;">
                        <button
                            type="submit"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; font-size: 14px; font-weight: 500; color: white; background-color: #041c4f; border: none; border-radius: 8px; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Guardar
                        </button>
                        <button
                            type="button"
                            x-on:click="open = false"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 20px; font-size: 14px; font-weight: 500; color: white; background-color: #dc2626; border: none; border-radius: 8px; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>