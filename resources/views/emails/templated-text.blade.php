{!! strip_tags($rendered['text']) !!}
@if ($actionUrl)

{{ $actionLabel }}: {{ $actionUrl }}
@endif
@if ($footerNote)

{!! strip_tags($footerNote) !!}
@endif

--
{{ config('hub.name') }}
