{{--
    Toast host (ITK Toaster). Mount once in the layout. Show a toast with
    window.itkToast('Changes saved') or $store.toasts.push('Changes saved', 'success').
    Polite, leaves after 5 s, pauses while hovered or focused, at most three at a time.
    Only for routine, undoable results; errors that block the task use x-itk.alert.
--}}
<div x-data aria-live="polite" class="itk-toaster">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div x-transition.opacity.duration.200ms
            x-on:mouseenter="$store.toasts.pause(toast)" x-on:mouseleave="$store.toasts.resume(toast)"
            x-on:focusin="$store.toasts.pause(toast)" x-on:focusout="$store.toasts.resume(toast)"
            :class="'itk-toast-' + toast.tone"
            class="itk-toast pointer-events-auto flex items-center gap-3 w-full max-w-105 py-3 pr-2 pl-4 rounded-md bg-surface-inverse text-ink-inverse shadow-3 text-callout">
            <span class="itk-toast-icon inline-flex">
                <template x-if="toast.tone === 'danger'"><span class="inline-flex"><x-itk.icon name="circle-alert" /></span></template>
                <template x-if="toast.tone === 'warning'"><span class="inline-flex"><x-itk.icon name="triangle-alert" /></span></template>
                <template x-if="toast.tone === 'info'"><span class="inline-flex"><x-itk.icon name="info" /></span></template>
                <template x-if="toast.tone === 'success'"><span class="inline-flex"><x-itk.icon name="circle-check" /></span></template>
            </span>
            <span class="flex-1" x-text="toast.message"></span>
            <button type="button" x-on:click="$store.toasts.dismiss(toast.id)" aria-label="Dismiss"
                class="itk-toast-x grid size-11 shrink-0 place-items-center rounded-full cursor-pointer">
                <x-itk.icon name="x" />
            </button>
        </div>
    </template>
</div>
