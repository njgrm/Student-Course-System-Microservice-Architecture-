<!DOCTYPE html>
<html lang="en" x-data x-bind:class="$store.darkMode.on ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Course System — Dashboard</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .toast-enter { animation: slideInRight 0.3s ease-out; }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
        .modal-panel { opacity: 0; transform: scale(0.95); transition: opacity 0.15s ease, transform 0.15s ease; }
        .modal-panel.modal-visible { opacity: 1; transform: scale(1); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen transition-colors duration-200">

    {{-- Page Header --}}
    <header class="bg-slate-800 dark:bg-gray-950 text-white shadow-lg transition-colors duration-200">
        <div class="max-w-8xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Student Course System</h1>
                        <p class="text-slate-400 text-sm">Microservices Dashboard</p>
                    </div>
                </div>
                {{-- Dark Mode Toggle --}}
                <button
                    @click="$store.darkMode.toggle()"
                    class="p-2 rounded-lg bg-slate-700 hover:bg-slate-600 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors"
                    title="Toggle dark mode">
                    {{-- Sun icon (shown in dark mode) --}}
                    <svg x-show="$store.darkMode.on" class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                    {{-- Moon icon (shown in light mode) --}}
                    <svg x-show="!$store.darkMode.on" class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-8xl mx-auto px-6 py-8">

        {{-- Two-column grid: Students + Courses side by side --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        {{-- ==================== STUDENTS SECTION ==================== --}}
        <section id="students-section" class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden transition-colors duration-200">
            {{-- Section header bar --}}
            <div class="bg-indigo-600 dark:bg-indigo-700 px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    <h2 class="text-white font-semibold text-lg">Students</h2>
                </div>
                <span id="student-status" class="bg-gray-100 text-gray-500 text-xs font-medium px-2.5 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-6">
                {{-- Add Student Form --}}
                <div id="student-form-container">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Add Student</h3>
                    <form id="student-form" class="mb-6">
                        <div class="grid grid-cols-1 gap-4 mb-4">
                            <div>
                                <label for="student-name" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Full Name</label>
                                <input type="text" id="student-name" placeholder="e.g. Juan Dela Cruz" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm px-3 py-2 border">
                            </div>
                            <div>
                                <label for="student-email" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Email</label>
                                <input type="email" id="student-email" placeholder="e.g. juan@email.com" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm px-3 py-2 border">
                            </div>
                            <div>
                                <label for="student-age" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Age</label>
                                <input type="number" id="student-age" placeholder="e.g. 20" min="1" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm px-3 py-2 border">
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Student
                        </button>
                    </form>
                </div>

                {{-- Table area --}}
                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">All Students</h3>
                        <span id="student-count" class="inline-flex items-center justify-center bg-indigo-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[1.25rem]">0</span>
                    </div>
                    <div id="student-table-area">
                        <table id="students-table" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Age</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="students-tbody">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- ==================== COURSES SECTION ==================== --}}
        <section id="courses-section" class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden transition-colors duration-200">
            <div class="bg-emerald-600 dark:bg-emerald-700 px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                    <h2 class="text-white font-semibold text-lg">Courses</h2>
                </div>
                <span id="course-status" class="bg-gray-100 text-gray-500 text-xs font-medium px-2.5 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-6">
                <div id="course-form-container">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Add Course</h3>
                    <form id="course-form" class="mb-6">
                        <div class="grid grid-cols-1 gap-4 mb-4">
                            <div>
                                <label for="course-name" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Course Name</label>
                                <input type="text" id="course-name" placeholder="e.g. Web Development" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border">
                            </div>
                            <div>
                                <label for="course-desc" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Description</label>
                                <input type="text" id="course-desc" placeholder="e.g. HTML, CSS, JS" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border">
                            </div>
                            <div>
                                <label for="course-credits" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Credits</label>
                                <input type="number" id="course-credits" placeholder="e.g. 3" min="1" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border">
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Add Course
                        </button>
                    </form>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">All Courses</h3>
                        <span id="course-count" class="inline-flex items-center justify-center bg-emerald-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[1.25rem]">0</span>
                    </div>
                    <div id="course-table-area">
                        <table id="courses-table" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Credits</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="courses-tbody">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        </div> {{-- end two-column grid --}}

        {{-- ==================== ENROLLMENTS SECTION (full width) ==================== --}}
        <section id="enrollments-section" class="bg-white dark:bg-gray-800 shadow rounded-xl mb-8 overflow-hidden transition-colors duration-200">
            <div class="bg-amber-600 dark:bg-amber-700 px-6 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    <h2 class="text-white font-semibold text-lg">Enrollments</h2>
                </div>
                <span id="enrollment-status" class="bg-gray-100 text-gray-500 text-xs font-medium px-2.5 py-0.5 rounded-full">Checking…</span>
            </div>

            <div class="p-6">
                <div id="enrollment-form-container">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Enroll Student in Course</h3>
                    <form id="enrollment-form" class="mb-6">
                        <div id="enrollment-dep-warning" class="hidden bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-300 p-3 rounded-lg text-sm mb-4"></div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="enroll-student" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Student</label>
                                <select id="enroll-student" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm px-3 py-2 border">
                                    <option value="">-- Choose a student --</option>
                                </select>
                            </div>
                            <div>
                                <label for="enroll-course" class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Course</label>
                                <select id="enroll-course" required
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm px-3 py-2 border">
                                    <option value="">-- Choose a course --</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" id="enroll-submit-btn"
                            class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Enroll Student
                        </button>
                    </form>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">All Enrollments</h3>
                        <span id="enrollment-count" class="inline-flex items-center justify-center bg-amber-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[1.25rem]">0</span>
                    </div>
                    <div id="enrollment-table-area">
                        <table id="enrollments-table" class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-700">
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Student</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Course</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Enrolled At</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="enrollments-tbody">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- Confirm Modal --}}
    <div id="confirm-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="modal-panel bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-sm w-full mx-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Delete</h3>
            </div>
            <p id="confirm-modal-message" class="text-sm text-gray-600 dark:text-gray-300 mb-6">Are you sure?</p>
            <div class="flex items-center justify-end gap-3">
                <button id="confirm-modal-cancel" class="px-4 py-2 rounded-md text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                <button id="confirm-modal-confirm" class="px-4 py-2 rounded-md text-sm font-medium bg-red-600 hover:bg-red-700 text-white transition-colors shadow-sm">Yes, Delete</button>
            </div>
        </div>
    </div>

    {{-- Toast container --}}
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

</body>
</html>
