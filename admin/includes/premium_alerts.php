<!-- PalCus Premium Alerts System -->
<style>
@keyframes pcFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes pcScaleIn { from { opacity: 0; transform: scale(0.9) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
@keyframes pcScaleOut { from { opacity: 1; transform: scale(1); } to { opacity: 0; transform: scale(0.95); } }

.pc-alert-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);
    z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 1rem;
    animation: pcFadeIn 0.3s ease-out;
}
.pc-alert-box {
    background: white; width: 100%; max-width: 420px; border-radius: 2rem; overflow: hidden;
    box-shadow: 0 25px 70px -10px rgba(0,0,0,0.3);
    animation: pcScaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
.pc-alert-content { padding: 2.5rem 2rem; text-align: center; }
.pc-alert-icon {
    width: 80px; height: 80px; margin: 0 auto 1.5rem; border-radius: 2rem;
    display: flex; align-items: center; justify-content: center;
    animation: pcScaleIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.pc-alert-title { font-size: 1.5rem; font-weight: 800; color: #111827; margin-bottom: 0.75rem; letter-spacing: -0.025em; }
.pc-alert-text { font-size: 0.9375rem; color: #4b5563; line-height: 1.6; }
.pc-alert-actions { display: flex; gap: 1rem; padding: 1.5rem 2rem 2rem; }
.pc-btn {
    flex: 1; padding: 1rem; border-radius: 1.25rem; font-size: 0.9375rem; font-weight: 700;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; border: none;
}
.pc-btn-cancel { background: #f3f4f6; color: #4b5563; }
.pc-btn-cancel:hover { background: #e5e7eb; transform: translateY(-1px); }
.pc-btn-confirm { background: #111827; color: white; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
.pc-btn-confirm:hover { background: #000; transform: translateY(-2px); box-shadow: 0 15px 20px -5px rgba(0,0,0,0.2); }
.pc-btn-success { background: #111827; color: white; width: 100%; }

/* Toast Notifications */
.pc-toast-container {
    position: fixed; top: 2rem; right: 2rem; z-index: 10000;
    display: flex; flex-direction: column; gap: 1rem;
}
.pc-toast {
    background: #111827; color: white; padding: 1.25rem 1.5rem; border-radius: 1.25rem;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
    display: flex; align-items: center; gap: 1rem;
    animation: pcScaleIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    min-width: 340px; border: 1px solid rgba(255,255,255,0.1);
}
.pc-toast.success { border-left: 6px solid #10b981; }
.pc-toast.error { border-left: 6px solid #ef4444; }
</style>

<div id="pcAlertRoot"></div>
<div id="pcToastContainer" class="pc-toast-container"></div>

<script>
window.PalCus = {
    confirm: function(options) {
        return new Promise((resolve) => {
            const root = document.getElementById('pcAlertRoot');
            const overlay = document.createElement('div');
            overlay.className = 'pc-alert-overlay';
            
            overlay.innerHTML = `
                <div class="pc-alert-box">
                    <div class="pc-alert-content">
                        <div class="pc-alert-icon ${options.type === 'danger' ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-900'}">
                            ${options.type === 'danger' 
                                ? '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
                                : '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                            }
                        </div>
                        <h4 class="pc-alert-title">${options.title || '¿Estás seguro?'}</h4>
                        <p class="pc-alert-text">${options.text || ''}</p>
                    </div>
                    <div class="pc-alert-actions">
                        <button class="pc-btn pc-btn-cancel" id="pcBtnCancel">${options.cancelText || 'Cancelar'}</button>
                        <button class="pc-btn pc-btn-confirm" id="pcBtnConfirm">${options.confirmText || 'Confirmar'}</button>
                    </div>
                </div>
            `;
            
            root.appendChild(overlay);
            document.body.style.overflow = 'hidden';
            
            const close = (res) => {
                overlay.style.opacity = '0';
                overlay.querySelector('.pc-alert-box').style.animation = 'pcScaleOut 0.2s ease-in forwards';
                setTimeout(() => { 
                    overlay.remove(); 
                    document.body.style.overflow = '';
                    resolve(res); 
                }, 200);
            };
            
            overlay.querySelector('#pcBtnCancel').onclick = () => close(false);
            overlay.querySelector('#pcBtnConfirm').onclick = () => close(true);
            overlay.onclick = (e) => { if(e.target === overlay) close(false); };
        });
    },

    alert: function(options) {
        return new Promise((resolve) => {
            const root = document.getElementById('pcAlertRoot');
            const overlay = document.createElement('div');
            overlay.className = 'pc-alert-overlay';
            
            overlay.innerHTML = `
                <div class="pc-alert-box">
                    <div class="pc-alert-content">
                        <div class="pc-alert-icon ${options.type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-50 text-gray-900'}">
                            ${options.type === 'success' 
                                ? '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>'
                                : '<svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                            }
                        </div>
                        <h4 class="pc-alert-title">${options.title || '¡Hecho!'}</h4>
                        <p class="pc-alert-text">${options.text || ''}</p>
                    </div>
                    <div class="pc-alert-actions">
                        <button class="pc-btn pc-btn-confirm" id="pcBtnOk">${options.confirmText || 'Aceptar'}</button>
                    </div>
                </div>
            `;
            
            root.appendChild(overlay);
            document.body.style.overflow = 'hidden';
            
            const btn = overlay.querySelector('#pcBtnOk');
            btn.focus();
            
            const close = () => {
                overlay.style.opacity = '0';
                overlay.querySelector('.pc-alert-box').style.animation = 'pcScaleOut 0.2s ease-in forwards';
                setTimeout(() => { 
                    overlay.remove(); 
                    document.body.style.overflow = '';
                    resolve(); 
                }, 200);
            };
            
            btn.onclick = () => close();
            overlay.onclick = (e) => { if(e.target === overlay) close(); };
        });
    },
    
    toast: function(type, message) {
        // Fallback to alert for better visibility if success
        if (type === 'success') {
            return this.alert({ type: 'success', title: '¡Éxito!', text: message });
        }

        const container = document.getElementById('pcToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `pc-toast ${type}`;
        
        const icon = type === 'success' 
            ? '<svg class="w-6 h-6 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'
            : '<svg class="w-6 h-6 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>';

        toast.innerHTML = `
            ${icon}
            <span class="text-sm font-bold tracking-tight">${message}</span>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'pcScaleOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    },

    loading: function(show = true) {
        let overlay = document.getElementById('pcLoadingOverlay');
        if (!show) {
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 300);
            }
            return;
        }
        if (overlay) return;

        overlay = document.createElement('div');
        overlay.id = 'pcLoadingOverlay';
        overlay.className = 'pc-alert-overlay';
        overlay.style.background = 'rgba(255,255,255,0.9)';
        overlay.style.backdropFilter = 'blur(10px)';
        overlay.innerHTML = `
            <div class="flex flex-col items-center gap-5">
                <div class="relative w-16 h-16">
                    <div class="absolute inset-0 border-4 border-gray-100 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-gray-900 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <div class="text-center">
                    <p class="text-xs font-black text-gray-900 uppercase tracking-[0.3em] mb-1">PalCus Cloud</p>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest animate-pulse">Sincronizando cambios...</p>
                </div>
            </div>
        `;
        document.getElementById('pcAlertRoot').appendChild(overlay);
    }
};

// Global Loading trigger on form submit
document.addEventListener('submit', (e) => {
    const form = e.target.closest('form');
    if (!form) return;
    
    // Si el formulario ya tiene un loading manual o es preventDefault, no mostrar
    if (form.getAttribute('data-no-loading')) return;
    
    window.PalCus.loading(true);
});

// Global confirm override for PalCus
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[type="submit"]');
    if (!btn) return;
    
    const form = btn.closest('form');
    if (!form) return;
    
    const confirmMsg = form.getAttribute('data-confirm');
    if (confirmMsg && !form.dataset.confirmed) {
        e.preventDefault();
        e.stopPropagation();
        
        const ok = await window.PalCus.confirm({
            title: '¿Confirmar eliminación?',
            text: confirmMsg,
            type: 'danger',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar'
        });
        
        if (ok) {
            form.dataset.confirmed = "true";
            form.submit();
        }
    }
}, true);
</script>
