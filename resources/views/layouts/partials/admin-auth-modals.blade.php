{{-- Admin Auth Modal (overlay) --}}
<div id="admin-auth-modal" class="fixed inset-0 z-[110] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAdminAuthModal()"></div>

    <div class="relative flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800">
                <h2 id="admin-auth-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">Admin Login</h2>
                <button onclick="closeAdminAuthModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6">
action="{{ route('admin.login.post') }}"
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                            <input type="email" name="email" required class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                            <input type="password" name="password" required class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>

                        <div id="admin-login-error" class="hidden text-sm text-red-500 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg p-3"></div>

                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg text-sm font-medium transition shadow-sm shadow-purple-600/25">
                            Sign in as Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAdminAuthModal() {
        const modal = document.getElementById('admin-auth-modal');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeAdminAuthModal() {
        const modal = document.getElementById('admin-auth-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        const errorEl = document.getElementById('admin-login-error');
        if (errorEl) errorEl.classList.add('hidden');
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAdminAuthModal();
        }
    });

    // If we got an error flash from the server, show it in the overlay.
    @if(session('admin_login_error'))
        (function(){
            const modal = document.getElementById('admin-auth-modal');
            if (modal) modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            const errorEl = document.getElementById('admin-login-error');
            if (errorEl) {
                errorEl.textContent = @json(session('admin_login_error'));
                errorEl.classList.remove('hidden');
            }
        })();
    @endif
</script>
@endpush

