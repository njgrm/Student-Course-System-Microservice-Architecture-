<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Enrollment Service'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .toast-enter { animation: slideInRight 0.3s ease-out; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
    </style>
</head>
<body class="bg-slate-100 text-gray-900 min-h-screen">
    {{-- Dark header matching SAR2 style --}}
    <header class="bg-slate-800 text-white">
        <div class="max-w-5xl mx-auto px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                        Enrollment Service
                    </h1>
                    <p class="text-sm text-slate-400 mt-0.5">Manage student enrollments</p>
                </div>
                <span class="text-xs font-mono bg-slate-700 text-slate-300 px-2.5 py-1 rounded">:8003</span>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    {{-- Toast Stack --}}
    <div id="toast-stack" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-toast', (event) => {
                const data = Array.isArray(event) ? event[0] : event;
                const type = data.type || 'success';
                const message = data.message || '';
                const isSuccess = type === 'success';
                const color = isSuccess ? '#10b981' : '#ef4444';
                const autoClose = 3500;
                const container = document.getElementById('toast-stack');

                const toast = document.createElement('div');
                toast.className = 'flex items-start gap-3 bg-white border border-gray-200 shadow-lg rounded-lg px-4 py-3 min-w-[320px] max-w-sm relative overflow-hidden toast-enter';
                toast.style.cssText = `border-left: 4px solid ${color}`;

                const icon = isSuccess
                    ? '<svg class="w-5 h-5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
                    : '<svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>';

                toast.innerHTML = `
                    ${icon}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900">${isSuccess ? 'Success' : 'Error'}</p>
                        <p class="text-sm text-gray-600 mt-0.5">${message}</p>
                    </div>
                    <button class="toast-dismiss text-gray-400 hover:text-gray-600 shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="toast-progress absolute bottom-0 left-0 h-0.5" style="background:${color};width:100%;"></div>
                `;

                function dismiss() {
                    toast.style.transition = 'opacity 0.3s, transform 0.3s';
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => toast.remove(), 300);
                }

                toast.querySelector('.toast-dismiss').addEventListener('click', dismiss);
                const bar = toast.querySelector('.toast-progress');
                bar.style.transition = `width ${autoClose}ms linear`;
                requestAnimationFrame(() => { bar.style.width = '0%'; });

                container.appendChild(toast);
                setTimeout(dismiss, autoClose);
            });
        });
    </script>
</body>
</html>

