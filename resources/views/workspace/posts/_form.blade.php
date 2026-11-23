{{-- Expects $post, $types, $categories --}}
@php
    $canPublish = auth()->user()->can('publish', App\Models\Post::class);
    $currentAction = old('publish_action', match ($post->exists ? $post->state() : 'draft') {
        'published' => 'publish',
        'scheduled' => 'schedule',
        default => 'draft',
    });
@endphp
<div class="grid gap-6 xl:grid-cols-3" x-data="{ action: @js($currentAction) }">
    <div class="space-y-6 xl:col-span-2">
        <x-ui.card>
            <div class="space-y-5">
                <x-ui.input name="title" label="Headline" :value="$post->title" required class="text-base font-semibold" />
                <x-ui.textarea name="summary" label="Summary" :value="$post->summary" rows="2" hint="One or two sentences shown on cards and in search results. Up to 500 characters." />
                <x-ui.textarea name="content" label="Article" :value="$post->content" rows="18" required class="font-mono text-[13px]"
                    hint="Markdown supported: ## heading, **bold**, *italic*, [link](https://…), - lists, > quotes. HTML is not allowed." />
            </div>
        </x-ui.card>
    </div>

    <div class="space-y-6">
        <x-ui.card title="Publishing">
            @if ($canPublish)
                <fieldset class="space-y-2">
                    <legend class="sr-only">Publishing option</legend>
                    @foreach (['draft' => ['Save as draft', 'Not visible on the public site.'], 'publish' => ['Publish now', 'Visible on the news page immediately.'], 'schedule' => ['Schedule', 'Goes live at a date and time you choose.']] as $value => [$label, $hint])
                        <label class="flex cursor-pointer gap-3 rounded-lg border border-slate-200 p-3 text-sm has-[:checked]:border-uew-royal has-[:checked]:bg-blue-50/60">
                            <input type="radio" name="publish_action" value="{{ $value }}" x-model="action" class="mt-0.5 size-4 border-slate-300 text-uew-navy focus:ring-uew-royal">
                            <span><span class="block font-semibold text-slate-900">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $hint }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                <div x-show="action === 'schedule'" x-cloak class="mt-4">
                    <x-ui.input name="published_at" type="datetime-local" label="Go live at" :value="$post->published_at?->isFuture() ? $post->published_at->format('Y-m-d\TH:i') : null" />
                </div>
            @else
                <input type="hidden" name="publish_action" value="draft">
                <x-ui.alert tone="info">Your post is saved as a draft. An officer will review and publish it.</x-ui.alert>
            @endif
        </x-ui.card>

        <x-ui.card title="Details">
            <div class="space-y-5">
                <x-ui.select name="type" label="Type" :options="$types" :value="$post->type" required />
                <x-ui.select name="category" label="Category" :options="$categories" :value="$post->category" required />
                <x-ui.checkbox name="is_featured" label="Feature on the homepage" :checked="$post->is_featured" />
            </div>
        </x-ui.card>

        <div class="flex flex-col gap-2">
            <x-ui.button type="submit" size="lg" icon="check">
                @if ($canPublish)
                    <span x-text="action === 'publish' ? 'Save and publish' : (action === 'schedule' ? 'Save and schedule' : 'Save draft')">Save</span>
                @else
                    Save draft
                @endif
            </x-ui.button>
            <x-ui.button :href="route('workspace.posts.index')" variant="ghost">Cancel</x-ui.button>
        </div>
    </div>
</div>
