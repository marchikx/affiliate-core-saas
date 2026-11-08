<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Install — AffiliateCore SaaS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <style>
        @keyframes shimmer { to { background-position: -200% 0; } }
        .shimmer { background: linear-gradient(90deg, transparent 25%, rgba(52,211,153,.15) 50%, transparent 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
    </style>
</head>
<body class="dark bg-slate-900 min-h-screen flex items-center justify-center p-4">
<div id="app" class="w-full max-w-lg">
    <!-- Card -->
    <div class="rounded-2xl bg-slate-800 shadow-2xl shadow-black/60 overflow-hidden">
        <!-- Header -->
        <div class="px-8 pt-8 pb-6 border-b border-slate-700/60 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-emerald-500/20">
                <svg class="h-8 w-8 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">AffiliateCore SaaS</h1>
            <p class="mt-1 text-sm text-slate-400">Installation Wizard</p>
        </div>

        <!-- Steps Indicator -->
        <div class="px-8 py-4 flex items-center gap-2">
            <template v-for="(s, i) in steps" :key="i">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-all duration-500"
                        :class="currentStep > i ? 'bg-emerald-500 text-white' : currentStep === i ? 'bg-emerald-500/20 text-emerald-400 ring-2 ring-emerald-500/50' : 'bg-slate-700 text-slate-500'"
                    >
                        <svg v-if="currentStep > i" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <span v-else>{{ i + 1 }}</span>
                    </div>
                    <span class="text-xs font-medium hidden sm:inline" :class="currentStep >= i ? 'text-slate-300' : 'text-slate-600'">{{ s.label }}</span>
                </div>
                <div v-if="i < steps.length - 1" class="flex-1 h-px" :class="currentStep > i ? 'bg-emerald-500/60' : 'bg-slate-700'"></div>
            </template>
        </div>

        <!-- Body -->
        <div class="px-8 py-6">
            <!-- Error Alert -->
            <div v-if="error" class="mb-6 rounded-lg bg-red-500/10 border border-red-500/30 px-4 py-3 text-sm text-red-400">
                {{ error }}
            </div>

            <!-- Step 0: Database -->
            <div v-show="currentStep === 0">
                <h2 class="text-lg font-semibold text-white mb-1">Database Connection</h2>
                <p class="text-sm text-slate-400 mb-5">Enter your MySQL credentials from cPanel.</p>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-400 mb-1">Host</label>
                            <input v-model="db.host" type="text" placeholder="localhost" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Port</label>
                            <input v-model="db.port" type="number" placeholder="3306" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Database Name</label>
                        <input v-model="db.database" type="text" placeholder="affiliatecore_db" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Username</label>
                        <input v-model="db.username" type="text" placeholder="root" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Password</label>
                        <input v-model="db.password" type="password" placeholder="••••••••" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Step 1-2: Progress (Migration + Seeding) -->
            <div v-show="currentStep === 1 || currentStep === 2">
                <h2 class="text-lg font-semibold text-white mb-1">{{ statusLabel }}</h2>
                <p class="text-sm text-slate-400 mb-6">Please wait — do not close this window.</p>
                <div class="relative h-4 rounded-full bg-slate-900 overflow-hidden mb-3">
                    <div
                        class="absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-700 ease-out"
                        :style="{ width: progress + '%' }"
                    ></div>
                    <div class="absolute inset-0 shimmer rounded-full" v-if="loading"></div>
                </div>
                <p class="text-center text-sm font-semibold" :class="progress >= 100 ? 'text-emerald-400' : 'text-slate-400'">
                    {{ progress }}%
                </p>
            </div>

            <!-- Step 3: Admin Account -->
            <div v-show="currentStep === 3">
                <h2 class="text-lg font-semibold text-white mb-1">Administrator Account</h2>
                <p class="text-sm text-slate-400 mb-5">Create your super admin login.</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Full Name</label>
                        <input v-model="admin.name" type="text" placeholder="John Doe" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Email Address</label>
                        <input v-model="admin.email" type="email" placeholder="admin@example.com" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Password</label>
                        <input v-model="admin.password" type="password" placeholder="min. 8 characters" class="w-full rounded-lg bg-slate-900 border border-slate-700 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Step 4: Complete -->
            <div v-show="currentStep === 4" class="text-center py-4">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20">
                    <svg class="h-8 w-8 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-white mb-2">Installation Complete!</h2>
                <p class="text-sm text-slate-400 mb-6">AffiliateCore SaaS is ready. You can now log in to your dashboard.</p>
                <a href="/dashboard" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 transition-colors">
                    Open Dashboard
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                </a>
            </div>
        </div>

        <!-- Footer Button -->
        <div v-if="currentStep < 4 && (currentStep === 0 || currentStep === 3)" class="px-8 pb-8">
            <button
                @click="proceed"
                :disabled="loading"
                class="w-full flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition-all hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"/><path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" class="opacity-75"/></svg>
                {{ currentStep === 0 ? 'Connect & Install' : currentStep === 3 ? 'Create Account & Finish' : 'Please wait...' }}
            </button>
        </div>
    </div>

    <!-- Footer -->
    <p class="mt-6 text-center text-xs text-slate-600">&copy; {{ new Date().getFullYear() }} AffiliateCore SaaS. All rights reserved.</p>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
const { createApp, ref, computed } = Vue;

createApp({
    setup() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const currentStep = ref(0);
        const loading = ref(false);
        const error = ref('');
        const progress = ref(0);

        const db = ref({ host: 'localhost', port: 3306, database: '', username: '', password: '' });
        const admin = ref({ name: '', email: '', password: '' });

        const steps = [
            { label: 'Database' },
            { label: 'Schema' },
            { label: 'Data' },
            { label: 'Admin' },
        ];

        const statusLabels = {
            1: 'Creating Database Schema...',
            2: 'Seeding demo data...',
        };
        const statusLabel = computed(() => statusLabels[currentStep.value] || 'Processing...');

        async function post(url, body = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Request failed.');
            return data;
        }

        async function proceed() {
            error.value = '';
            loading.value = true;

            try {
                if (currentStep.value === 0) {
                    await post('/install/step1', {
                        db_host: db.value.host,
                        db_port: db.value.port,
                        db_database: db.value.database,
                        db_username: db.value.username,
                        db_password: db.value.password,
                    });

                    currentStep.value = 1;
                    progress.value = 5;

                    await post('/install/step2');
                    progress.value = 8;

                    currentStep.value = 2;

                    let chunk = 0;
                    while (true) {
                        const res = await post('/install/step3?chunk=' + chunk);
                        progress.value = res.progress || progress.value;
                        if (res.next_chunk === null || res.next_chunk === undefined) break;
                        chunk = res.next_chunk;
                    }

                    progress.value = 100;
                    await new Promise(r => setTimeout(r, 600));
                    currentStep.value = 3;

                } else if (currentStep.value === 3) {
                    if (!admin.value.name || !admin.value.email || admin.value.password.length < 8) {
                        throw new Error('All fields required. Password must be at least 8 characters.');
                    }
                    await post('/install/step4', admin.value);
                    currentStep.value = 4;
                }
            } catch (e) {
                error.value = e.message;
                if (currentStep.value === 1 || currentStep.value === 2) {
                    currentStep.value = 0;
                    progress.value = 0;
                }
            } finally {
                loading.value = false;
            }
        }

        return { currentStep, loading, error, progress, db, admin, steps, statusLabel, proceed };
    }
}).mount('#app');
</script>
</body>
</html>
