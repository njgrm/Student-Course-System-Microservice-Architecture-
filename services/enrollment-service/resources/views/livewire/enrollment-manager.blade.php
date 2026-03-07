<div>
    {{-- Toast notifications --}}
    @if ($successMessage)
        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span class="text-sm font-medium">{{ $successMessage }}</span>
        </div>
    @endif

    @if ($errorMessage)
        <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
            <span class="text-sm font-medium">{{ $errorMessage }}</span>
        </div>
    @endif

    {{-- Section card --}}
    <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {{-- Section heading with accent border --}}
        <div class="px-6 py-4 border-b-2 border-amber-500 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Enrollments</h2>
            @if (!$showForm)
                <button wire:click="openCreate"
                    class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Enrollment
                </button>
            @endif
        </div>

        {{-- Inline form (SAR2 style: side-by-side selects) --}}
        @if ($showForm)
            <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">New Enrollment</h3>
                <form wire:submit="save">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="student_id" class="block text-xs font-semibold text-gray-500 mb-1">Student</label>
                            <select id="student_id" wire:model="student_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm px-3 py-2 border transition-colors">
                                <option value="">Select a student...</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student['id'] }}">{{ $student['full_name'] }} ({{ $student['email'] }})</option>
                                @endforeach
                            </select>
                            @error('student_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="course_id" class="block text-xs font-semibold text-gray-500 mb-1">Course</label>
                            <select id="course_id" wire:model="course_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm px-3 py-2 border transition-colors">
                                <option value="">Select a course...</option>
                                @foreach ($courses as $course)
                                    <option value="{{ $course['id'] }}">{{ $course['name'] }} ({{ $course['credits'] }} credits)</option>
                                @endforeach
                            </select>
                            @error('course_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                            Enroll Student
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Table header with count badge --}}
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-500">All Enrollments</h3>
            <span class="inline-flex items-center justify-center bg-amber-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[1.25rem]">
                {{ count($enrollments) }}
            </span>
        </div>

        {{-- Data table --}}
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Course</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enrolled At</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($enrollments as $enrollment)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3.5 text-sm font-medium text-gray-900">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 text-indigo-600 text-xs font-bold">
                                    {{ strtoupper(substr($enrollment['student_name'], 0, 1)) }}
                                </span>
                                {{ $enrollment['student_name'] }}
                            </div>
                        </td>
                        <td class="px-6 py-3.5 text-sm text-gray-600">
                            <span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded">
                                {{ $enrollment['course_name'] }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($enrollment['enrolled_at'])->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <button wire:click="delete({{ $enrollment['id'] }})"
                                wire:confirm="Are you sure you want to remove this enrollment?"
                                class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-sm font-medium transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                                <p class="text-sm italic">No enrollments yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
