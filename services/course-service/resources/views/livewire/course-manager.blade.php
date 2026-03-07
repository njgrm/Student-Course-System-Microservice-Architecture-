<div>
    {{-- Toast notifications --}}
    @if ($successMessage)
        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-6 animate-pulse">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <span class="text-sm font-medium">{{ $successMessage }}</span>
        </div>
    @endif

    {{-- Section card --}}
    <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {{-- Section heading with accent border --}}
        <div class="px-6 py-4 border-b-2 border-emerald-500 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Courses</h2>
            @if (!$showForm)
                <button wire:click="openCreate"
                    class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Add Course
                </button>
            @endif
        </div>

        {{-- Inline form (SAR2 style: side-by-side inputs) --}}
        @if ($showForm)
            <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                    {{ $editingId ? 'Edit Course' : 'Add Course' }}
                </h3>
                <form wire:submit="save">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-gray-500 mb-1">Course Name</label>
                            <input type="text" id="name" wire:model="name" placeholder="e.g. Web Development"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border transition-colors">
                            @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="description" class="block text-xs font-semibold text-gray-500 mb-1">Description</label>
                            <input type="text" id="description" wire:model="description" placeholder="e.g. HTML, CSS, JS"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border transition-colors">
                            @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="credits" class="block text-xs font-semibold text-gray-500 mb-1">Credits</label>
                            <input type="number" id="credits" wire:model="credits" placeholder="e.g. 3" min="1"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm px-3 py-2 border transition-colors">
                            @error('credits') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors shadow-sm">
                            {{ $editingId ? 'Update Course' : 'Add Course' }}
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
            <h3 class="text-sm font-medium text-gray-500">All Courses</h3>
            <span class="inline-flex items-center justify-center bg-emerald-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[1.25rem]">
                {{ $courses->count() }}
            </span>
        </div>

        {{-- Data table --}}
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Credits</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($courses as $course)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $course->name }}</td>
                        <td class="px-6 py-3.5 text-sm text-gray-600 max-w-xs truncate">{{ $course->description }}</td>
                        <td class="px-6 py-3.5 text-sm text-gray-600">
                            <span class="inline-flex items-center bg-emerald-50 text-emerald-700 text-xs font-semibold px-2 py-0.5 rounded">
                                {{ $course->credits }} cr
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <div class="inline-flex items-center gap-1">
                                <button wire:click="openEdit({{ $course->id }})"
                                    class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 px-2 py-1 rounded text-sm font-medium transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                    Edit
                                </button>
                                <button wire:click="delete({{ $course->id }})"
                                    wire:confirm="Are you sure you want to delete this course?"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded text-sm font-medium transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                                <p class="text-sm italic">No courses yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

