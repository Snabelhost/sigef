<div style="display: flex; align-items: center; gap: 12px; width: 100%;">
    <img src="/images/logo-sigef.png" alt="SIGEF Logo" style="width: 50px; height: 50px; object-fit: contain;">
    <span style="font-size: 1.75rem; font-weight: 700; color: #041842; letter-spacing: 0.05em;">SIGEF</span>
    
    {{-- Botão para colapsar/expandir sidebar (Desktop) --}}
    <button 
        type="button"
        class="brand-logo-btn hidden lg:flex"
        x-data="{}"
        x-on:click.prevent.stop="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        style="margin-left: 100px; padding: 2px; border-radius: 3px; border: none; background: transparent; cursor: pointer; align-items: center; justify-content: center; transition: all 0.2s; visibility: visible !important; opacity: 1 !important; width: 10px !important; height: 10px !important; position: static !important;"
        onmouseover="this.style.opacity='0.7'"
        onmouseout="this.style.opacity='1'"
        title="Esconder/Mostrar Menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#041842" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</div>

{{-- Botão flutuante para mobile (aparece no canto superior esquerdo) --}}
<div 
    class="lg:hidden fixed top-4 left-4 z-50"
    x-data="{ open: false }"
    style="display: none;"
    id="mobile-menu-btn"
>
    <button 
        type="button"
        x-on:click.prevent.stop="$dispatch('open-sidebar')"
        style="padding: 10px; border-radius: 8px; border: none; background: rgba(4, 24, 66, 0.9); cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.2);"
        title="Abrir Menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</div>

<script>
    // Mostrar botão mobile apenas em telas pequenas
    (function() {
        function checkMobile() {
            var btn = document.getElementById('mobile-menu-btn');
            if (btn) {
                if (window.innerWidth < 1024) {
                    btn.style.display = 'block';
                } else {
                    btn.style.display = 'none';
                }
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkMobile);
        } else {
            checkMobile();
        }
        
        window.addEventListener('resize', checkMobile);
    })();
</script>
