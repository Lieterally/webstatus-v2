{{--
    Table frame (ITK Table): caption (required, names the scroll region), horizontal scroll inside the
    frame (focusable for keyboard scrolling), hairline rows, primary-soft row hover, tabular figures.
    hideCaption keeps the caption for screen readers only. Put <thead>/<tbody> in the slot;
    give numeric cells class="itk-num".
--}}
@props(['caption', 'hideCaption' => false, 'framed' => true, 'captionId' => null])

@php $captionId = $captionId ?? 'tbl-' . \Illuminate\Support\Str::slug($caption); @endphp

<div role="region" aria-labelledby="{{ $captionId }}" tabindex="0"
    {{ $attributes->class(['itk-table-wrap overflow-x-auto bg-surface', 'border border-line rounded-md' => $framed]) }}>
    <table class="itk-table">
        <caption id="{{ $captionId }}" @class(['sr-only' => $hideCaption])>{{ $caption }}</caption>
        {{ $slot }}
    </table>
</div>
