{{-- Auth Modals --}}
<div id="auth-modal" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeAuthModal()"></div>
    <div class="relative flex items-center justify-center min-h-screen px-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-800">
                <h2 id="auth-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">Log in</h2>
                <button onclick="closeAuthModal()" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6">
                {{-- Login Form --}}
                <form id="login-form" method="POST" action="{{ route('login') }}">
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
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="remember" class="rounded border-gray-300 dark:border-gray-700 text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                            </label>
                            <a href="#" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300">Forgot password?</a>
                        </div>
                        <div id="login-error" class="hidden text-sm text-red-500 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg p-3"></div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg text-sm font-medium transition shadow-sm shadow-purple-600/25">
                            Log in
                        </button>
                    </div>
                </form>

                {{-- Register Form --}}
                <form id="register-form" method="POST" action="{{ route('register') }}" class="hidden">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                            <input type="text" name="name" required class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                            <input type="email" name="email" required class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Password</label>
                            <input type="password" name="password" required minlength="8" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Confirm Password</label>
                            <input type="password" name="password_confirmation" required minlength="8" class="w-full bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-gray-700 rounded-lg px-3.5 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500 transition">
                        </div>
                        <div id="register-error" class="hidden text-sm text-red-500 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-lg p-3"></div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg text-sm font-medium transition shadow-sm shadow-purple-600/25">
                            Create account
                        </button>
                    </div>
                </form>
            </div>

            <div class="px-6 pb-6">
                <p id="auth-switch-text" class="text-center text-sm text-gray-500 dark:text-gray-400">
                    Don't have an account?
                    <button onclick="switchToRegister()" class="text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300">Sign up</button>
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const recaptchaSiteKey = '{{ config('services.recaptcha.site_key') }}';
const recaptchaEnabled = {{ config('services.recaptcha.enabled') ? 'true' : 'false' }};
let recaptchaReady = false;

function loadRecaptchaV3() {
    if (!recaptchaEnabled || recaptchaReady || typeof grecaptcha !== 'undefined') return;
    const script = document.createElement('script');
    script.src = 'https://www.google.com/recaptcha/api.js?render=' + recaptchaSiteKey;
    script.onload = () => { recaptchaReady = true; };
    document.head.appendChild(script);
}

function getRecaptchaToken(action) {
    return new Promise((resolve) => {
        if (!recaptchaEnabled) { resolve(''); return; }
        if (typeof grecaptcha === 'undefined') {
            resolve('');
            return;
        }
        grecaptcha.ready(function() {
            grecaptcha.execute(recaptchaSiteKey, {action: action}).then(function(token) {
                resolve(token || '');
            }).catch(function() {
                resolve('');
            });
        });
        setTimeout(() => resolve(''), 5000);
    });
}

function openAuthModal(mode) {
    loadRecaptchaV3();
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (mode === 'register') {
        switchToRegister();
    } else {
        switchToLogin();
    }
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('login-error').classList.add('hidden');
    document.getElementById('register-error').classList.add('hidden');
}

function switchToLogin() {
    document.getElementById('login-form').classList.remove('hidden');
    document.getElementById('register-form').classList.add('hidden');
    document.getElementById('auth-modal-title').textContent = 'Log in';
    document.getElementById('auth-switch-text').innerHTML = 'Don\'t have an account? <button onclick="switchToRegister()" class="text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300">Sign up</button>';
}

function switchToRegister() {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.remove('hidden');
    document.getElementById('auth-modal-title').textContent = 'Create account';
    document.getElementById('auth-switch-text').innerHTML = 'Already have an account? <button onclick="switchToLogin()" class="text-purple-600 dark:text-purple-400 font-medium hover:text-purple-700 dark:hover:text-purple-300">Log in</button>';
}

// Handle login form submissions via fetch
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const errorEl = document.getElementById('login-error');
    errorEl.classList.add('hidden');

    getRecaptchaToken('login').then(function(token) {
        const formData = Object.fromEntries(new FormData(form));
        formData['g-recaptcha-response'] = token;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            },
            body: JSON.stringify(formData),
        })
        .then(async res => {
            if (res.ok) {
                window.location.reload();
                return;
            }
            const data = await res.json().catch(() => ({}));
            const msg = data.message || data.error || 'Invalid credentials.';
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        })
        .catch(() => {
            errorEl.textContent = 'Something went wrong. Please try again.';
            errorEl.classList.remove('hidden');
        });
    });
});

// Handle register form submissions via fetch
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const errorEl = document.getElementById('register-error');
    errorEl.classList.add('hidden');

    getRecaptchaToken('register').then(function(token) {
        const formData = Object.fromEntries(new FormData(form));
        formData['g-recaptcha-response'] = token;

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            },
            body: JSON.stringify(formData),
        })
        .then(async res => {
            if (res.ok) {
                window.location.reload();
                return;
            }
            const data = await res.json().catch(() => ({}));
            const msg = data.message || data.error || 'Registration failed.';
            if (data.errors) {
                errorEl.textContent = Object.values(data.errors).flat().join('. ');
            } else {
                errorEl.textContent = msg;
            }
            errorEl.classList.remove('hidden');
        })
        .catch(() => {
            errorEl.textContent = 'Something went wrong. Please try again.';
            errorEl.classList.remove('hidden');
        });
    });
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAuthModal();
});

// Load recaptcha if modal forms exist
if (document.getElementById('login-form')) {
    loadRecaptchaV3();
}
</script>
@endpush
