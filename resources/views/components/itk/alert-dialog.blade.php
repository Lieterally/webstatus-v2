{{--
    Destructive confirmation (ITK AlertDialog) on a native <dialog>: focus is trapped, focus starts on
    Cancel, Esc cancels, clicking outside does nothing, focus returns to the trigger on close.
    Open it from Alpine with $refs[ref].showModal(). The confirm button submits a form whose action is
    the Alpine expression in `action` (for example "target.url").
    Slots: heading (the question, may use x-text) and the default slot (what is lost).
--}}
@props(['ref' => 'confirmDialog', 'action', 'confirmLabel', 'method' => 'DELETE', 'id' => 'confirm-dialog'])

<dialog x-ref="{{ $ref }}" aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-desc"
    {{ $attributes->class('itk-dialog w-full max-w-sm m-auto p-6 rounded-lg bg-surface-raised text-ink shadow-3') }}>
    <div class="grid justify-items-center gap-3 text-center">
        <div class="grid place-items-center size-12 rounded-full bg-error-soft text-error">
            <x-itk.icon name="triangle-alert" size="24" />
        </div>
        <h2 id="{{ $id }}-title" class="text-title-3 text-ink">{{ $heading }}</h2>
        <p id="{{ $id }}-desc" class="text-body text-ink-muted">{{ $slot }}</p>
    </div>
    <div class="grid grid-cols-2 gap-2 mt-6">
        <x-itk.button variant="secondary" autofocus x-on:click="$refs.{{ $ref }}.close()">Cancel</x-itk.button>
        <form method="POST" x-bind:action="{{ $action }}" class="grid">
            @csrf
            @method($method)
            <x-itk.button type="submit" variant="danger">{{ $confirmLabel }}</x-itk.button>
        </form>
    </div>
</dialog>
