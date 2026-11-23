<x-layouts.app title="News & Content Management">
    <x-slot:header>
        CMS & News Publications
    </x-slot:header>

    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Articles & Dispatches</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage published news, research insights, and community announcements.</p>
        </div>
        <x-npontu.button href="{{ route('admin.posts.create') }}" variant="primary" size="sm">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
            <span>Write New Article</span>
        </x-npontu.button>
    </div>

    <x-npontu.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-500 font-mono uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Title & Classification</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Author</th>
                        <th class="py-3 px-4">Published Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3.5 px-4 max-w-md">
                                <a href="{{ route('posts.show', $post) }}" target="_blank" class="font-bold text-slate-900 hover:text-[#3157d5] block transition-colors leading-tight">
                                    {{ $post->title }}
                                </a>
                                <span class="text-[11px] text-slate-400 mt-0.5 block font-mono">Category: {{ $post->category }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold uppercase bg-blue-50 text-[#102a72] border border-blue-200">
                                    {{ $post->type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-600">
                                {{ $post->author?->name ?? 'Hub Media Desk' }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500">
                                {{ $post->published_at ? $post->published_at->format('Y-m-d H:i') : 'Draft' }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" onsubmit="return confirm('Delete this article?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-600" title="Delete Article">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">No CMS articles registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $posts->links() }}
            </div>
        @endif
    </x-npontu.card>
</x-layouts.app>
