<x-layouts.workspace title="New page">
    <x-ui.page-header title="New page" :back="route('workspace.pages.index')" back-label="Pages" />
    <form method="POST" action="{{ route('workspace.pages.store') }}">
        @csrf
        @include('workspace.pages._form')
    </form>
</x-layouts.workspace>
