<x-layouts.workspace title="New post">
    <x-ui.page-header title="New post" :back="route('workspace.posts.index')" back-label="News & posts" />

    <form method="POST" action="{{ route('workspace.posts.store') }}">
        @csrf
        @include('workspace.posts._form')
    </form>
</x-layouts.workspace>
