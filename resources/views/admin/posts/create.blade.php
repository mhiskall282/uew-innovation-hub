<x-layouts.app title="Create News / Blog Article">
    <x-slot:header>
        Compose Article
    </x-slot:header>

    <div class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('admin.posts.store') }}" class="space-y-6">
            @csrf

            <x-npontu.card title="Article Content & Metadata" subtitle="Publish updates to the public portal and research feed">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Article Headline <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Directorate Launches 2026 Innovation Fellowship" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden font-bold">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Article Type <span class="text-rose-500">*</span></label>
                            <select name="type" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                                <option value="news">News Article</option>
                                <option value="blog">Research Blog</option>
                                <option value="announcement">Public Announcement</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Pillar Category <span class="text-rose-500">*</span></label>
                            <select name="category" required class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Short Excerpt / Abstract</label>
                        <textarea name="summary" rows="2" placeholder="Brief 1-2 sentence lead paragraph..." class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden">{{ old('summary') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">Full Article Body <span class="text-rose-500">*</span></label>
                        <textarea name="content" rows="10" required placeholder="Write the complete article content here..." class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-[#3157d5] outline-hidden leading-relaxed">{{ old('content') }}</textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700">
                            <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-[#102a72] focus:ring-[#3157d5]">
                            <span>Mark as Featured Spotlight</span>
                        </label>
                    </div>
                </div>
            </x-npontu.card>

            <div class="flex items-center justify-end gap-3 pt-2">
                <x-npontu.button href="{{ route('admin.posts.index') }}" variant="secondary">
                    Cancel
                </x-npontu.button>
                <x-npontu.button type="submit" variant="primary">
                    <span>Publish Article</span>
                </x-npontu.button>
            </div>
        </form>
    </div>
</x-layouts.app>
