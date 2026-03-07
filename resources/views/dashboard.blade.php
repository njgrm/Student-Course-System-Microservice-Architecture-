<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course System — Dashboard</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&family=jetbrains-mono:400" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        code, .font-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
        .toast-enter { animation: slideInRight 0.3s ease-out; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
        .modal-panel { opacity: 0; transform: scale(0.95); transition: opacity 0.15s ease, transform 0.15s ease; }
        .modal-panel.modal-visible { opacity: 1; transform: scale(1); }
        .glow-violet { box-shadow: 0 0 40px -8px rgba(139, 92, 246, 0.12); }
        .glow-teal { box-shadow: 0 0 40px -8px rgba(20, 184, 166, 0.12); }
        .glow-amber { box-shadow: 0 0 40px -8px rgba(245, 158, 11, 0.12); }
    </style>
</head>
<body class="bg-[#0a0a0f] min-h-screen text-zinc-100">

    {{-- Page Header --}}
    <header class="bg-zinc-900/80 border-b border-zinc-800/50 backdrop-blur-sm sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-teal-400">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    </div>
                    <div>
                        <h1 class="text-sm font-semibold tracking-tight text-zinc-100">Student Course System</h1>
                        <p class="text-[11px] text-zinc-500">Microservices Dashboard</p>
                    </div>
                </div>
                <span class="text-[10px] font-mono text-zinc-600 bg-zinc-800/50 px-2 py-0.5 rounded">v1.0</span>
            </div>
        </div>
        <div class="h-px bg-gradient-to-r from-transparent via-violet-500/30 to-transparent"></div>
    </header>

    <main class="max-w-4xl mx-auto px-6 py-6 space-y-5">

        {{-- ==================== STUDENTS SECTION ==================== --}}
        <section id="students-section" class="bg-zinc-900/60 border border-zinc-800/50 rounded-xl overflow-hidden glow-violet">
            <div class="px-5 py-3 flex items-center justify-between border-b border-zinc-800/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-1 h-4 rounded-full bg-violet-500"></div>
                    <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <h2 class="text-sm font-semibold text-zinc-200 tracking-wide">Students</h2>
                </div>
                <span id="student-status" class="text-[10px] font-medium text-zinc-500 bg-zinc-800/80 px-2 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-5">
                <div id="student-form-container">
                    <h3 class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-2.5">Add Student</h3>
                    <form id="student-form" class="mb-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label for="student-name" class="block text-[11px] font-medium text-zinc-500 mb-1">Full Name</label>
                                <input type="text" id="student-name" placeholder="e.g. Juan Dela Cruz" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/20 focus:outline-none transition">
                            </div>
                            <div>
                                <label for="student-email" class="block text-[11px] font-medium text-zinc-500 mb-1">Email</label>
                                <input type="email" id="student-email" placeholder="e.g. juan@email.com" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/20 focus:outline-none transition">
                            </div>
                            <div>
                                <label for="student-age" class="block text-[11px] font-medium text-zinc-500 mb-1">Age</label>
                                <input type="number" id="student-age" placeholder="e.g. 20" min="1" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-violet-500/50 focus:ring-1 focus:ring-violet-500/20 focus:outline-none transition">
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Student
                        </button>
                    </form>
                </div>

                <div class="border-t border-zinc-800/50 pt-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">All Students</h3>
                        <span id="student-count" class="text-[11px] font-mono font-bold text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded">0</span>
                    </div>
                    <div id="student-table-area">
                        <table id="students-table" class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800/50">
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Email</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Age</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="students-tbody">
                                <tr><td colspan="4" class="px-3 py-6 text-center text-zinc-600 italic text-sm">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== COURSES SECTION ==================== --}}
        <section id="courses-section" class="bg-zinc-900/60 border border-zinc-800/50 rounded-xl overflow-hidden glow-teal">
            <div class="px-5 py-3 flex items-center justify-between border-b border-zinc-800/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-1 h-4 rounded-full bg-teal-500"></div>
                    <svg class="w-4 h-4 text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                    <h2 class="text-sm font-semibold text-zinc-200 tracking-wide">Courses</h2>
                </div>
                <span id="course-status" class="text-[10px] font-medium text-zinc-500 bg-zinc-800/80 px-2 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-5">
                <div id="course-form-container">
                    <h3 class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-2.5">Add Course</h3>
                    <form id="course-form" class="mb-5">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label for="course-name" class="block text-[11px] font-medium text-zinc-500 mb-1">Course Name</label>
                                <input type="text" id="course-name" placeholder="e.g. Web Development" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-teal-500/50 focus:ring-1 focus:ring-teal-500/20 focus:outline-none transition">
                            </div>
                            <div>
                                <label for="course-desc" class="block text-[11px] font-medium text-zinc-500 mb-1">Description</label>
                                <input type="text" id="course-desc" placeholder="e.g. HTML, CSS, JS" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-teal-500/50 focus:ring-1 focus:ring-teal-500/20 focus:outline-none transition">
                            </div>
                            <div>
                                <label for="course-credits" class="block text-[11px] font-medium text-zinc-500 mb-1">Credits</label>
                                <input type="number" id="course-credits" placeholder="e.g. 3" min="1" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 placeholder:text-zinc-600 px-3 py-2 focus:border-teal-500/50 focus:ring-1 focus:ring-teal-500/20 focus:outline-none transition">
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-teal-600 hover:bg-teal-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Course
                        </button>
                    </form>
                </div>

                <div class="border-t border-zinc-800/50 pt-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">All Courses</h3>
                        <span id="course-count" class="text-[11px] font-mono font-bold text-teal-400 bg-teal-500/10 px-2 py-0.5 rounded">0</span>
                    </div>
                    <div id="course-table-area">
                        <table id="courses-table" class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800/50">
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Name</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Description</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Credits</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="courses-tbody">
                                <tr><td colspan="4" class="px-3 py-6 text-center text-zinc-600 italic text-sm">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== ENROLLMENTS SECTION ==================== --}}
        <section id="enrollments-section" class="bg-zinc-900/60 border border-zinc-800/50 rounded-xl overflow-hidden glow-amber">
            <div class="px-5 py-3 flex items-center justify-between border-b border-zinc-800/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-1 h-4 rounded-full bg-amber-500"></div>
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    <h2 class="text-sm font-semibold text-zinc-200 tracking-wide">Enrollments</h2>
                </div>
                <span id="enrollment-status" class="text-[10px] font-medium text-zinc-500 bg-zinc-800/80 px-2 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-5">
                <div id="enrollment-form-container">
                    <h3 class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-2.5">Enroll Student in Course</h3>
                    <form id="enrollment-form" class="mb-5">
                        <div id="enrollment-dep-warning" class="hidden bg-amber-500/10 border border-amber-500/20 text-amber-300 p-3 rounded-lg text-sm mb-3"></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label for="enroll-student" class="block text-[11px] font-medium text-zinc-500 mb-1">Student</label>
                                <select id="enroll-student" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 px-3 py-2 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/20 focus:outline-none transition">
                                    <option value="">-- Choose a student --</option>
                                </select>
                            </div>
                            <div>
                                <label for="enroll-course" class="block text-[11px] font-medium text-zinc-500 mb-1">Course</label>
                                <select id="enroll-course" required
                                    class="w-full rounded-lg bg-zinc-800/50 border border-zinc-700/50 text-sm text-zinc-100 px-3 py-2 focus:border-amber-500/50 focus:ring-1 focus:ring-amber-500/20 focus:outline-none transition">
                                    <option value="">-- Choose a course --</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" id="enroll-submit-btn"
                            class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Enroll Student
                        </button>
                    </form>
                </div>

                <div class="border-t border-zinc-800/50 pt-4">
                    <div class="flex items-center justify-between mb-2.5">
                        <h3 class="text-[11px] font-medium text-zinc-500 uppercase tracking-wider">All Enrollments</h3>
                        <span id="enrollment-count" class="text-[11px] font-mono font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded">0</span>
                    </div>
                    <div id="enrollment-table-area">
                        <table id="enrollments-table" class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-800/50">
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Student</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Course</th>
                                    <th class="px-3 py-2 text-left text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Enrolled At</th>
                                    <th class="px-3 py-2 text-right text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="enrollments-tbody">
                                <tr><td colspan="4" class="px-3 py-6 text-center text-zinc-600 italic text-sm">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- Confirm Modal --}}
    <div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="modal-panel bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xl p-6 max-w-sm w-full mx-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-500/10 shrink-0">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-zinc-100">Confirm Delete</h3>
            </div>
            <p id="confirm-modal-message" class="text-sm text-zinc-400 mb-6">Are you sure?</p>
            <div class="flex items-center justify-end gap-3">
                <button id="confirm-modal-cancel" class="px-4 py-2 rounded-lg text-sm font-medium text-zinc-400 hover:bg-zinc-800 transition-colors">Cancel</button>
                <button id="confirm-modal-confirm" class="px-4 py-2 rounded-lg text-sm font-medium bg-red-600 hover:bg-red-500 text-white transition-colors">Yes, Delete</button>
            </div>
        </div>
    </div>

    {{-- Toast container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

</body>
</html>
