<div>
    {{-- Success message --}}
    @if ($successMessage)
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
            {{ $successMessage }}
        </div>
    @endif

    {{-- Error message --}}
    @if ($errorMessage)
        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-6">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Enrollments</h1>
        @if (!$showForm)
            <button wire:click="openCreate"
                class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                + Add Enrollment
            </button>
        @endif
    </div>

    {{-- Create Form --}}
    @if ($showForm)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">New Enrollment</h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="student_id" class="block text-sm font-medium text-gray-700">Student</label>
                    <select id="student_id" wire:model="student_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 px-3 py-2 border">
                        <option value="">Select a student...</option>
                        @foreach ($students as $student)
                            <option value="{{ $student['id'] }}">{{ $student['full_name'] }} ({{ $student['email'] }})</option>
                        @endforeach
                    </select>
                    @error('student_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700">Course</label>
                    <select id="course_id" wire:model="course_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 px-3 py-2 border">
                        <option value="">Select a course...</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course['id'] }}">{{ $course['name'] }} ({{ $course['credits'] }} credits)</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        Enroll
                    </button>
                    <button type="button" wire:click="cancel"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Enrollments Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enrolled At</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($enrollments as $enrollment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment['id'] }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $enrollment['student_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $enrollment['course_name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($enrollment['enrolled_at'])->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="delete({{ $enrollment['id'] }})"
                                wire:confirm="Are you sure you want to remove this enrollment?"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Remove
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                            No enrollments found. Click "Add Enrollment" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
