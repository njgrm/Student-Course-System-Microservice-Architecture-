<div>
    {{-- Success message --}}
    @if ($successMessage)
        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
            {{ $successMessage }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Courses</h1>
        @if (!$showForm)
            <button wire:click="openCreate"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                + Add Course
            </button>
        @endif
    </div>

    {{-- Create / Edit Form --}}
    @if ($showForm)
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">
                {{ $editingId ? 'Edit Course' : 'New Course' }}
            </h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Course Name</label>
                    <input type="text" id="name" wire:model="name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 border">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" wire:model="description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 border"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="credits" class="block text-sm font-medium text-gray-700">Credits</label>
                    <input type="number" id="credits" wire:model="credits"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 px-3 py-2 border">
                    @error('credits') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="cancel"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Courses Table --}}
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($courses as $course)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $course->id }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $course->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ $course->description }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $course->credits }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="openEdit({{ $course->id }})"
                                class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">
                                Edit
                            </button>
                            <button wire:click="delete({{ $course->id }})"
                                wire:confirm="Are you sure you want to delete this course?"
                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                            No courses found. Click "Add Course" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
