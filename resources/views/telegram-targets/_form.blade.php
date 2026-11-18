{{-- Fields shared by telegram-targets/create and edit. $telegramTarget is null on create. --}}
@php $telegramTarget = $telegramTarget ?? null; @endphp

<x-itk.field name="chat_id" label="Chat ID" hint="Digits only, up to 32 characters. For example 123456789." required>
    <x-itk.input :value="old('chat_id', $telegramTarget?->chat_id)" maxlength="32" inputmode="numeric"
        autocomplete="off" spellcheck="false" autofocus />
</x-itk.field>

<x-itk.field name="username" label="Telegram username (optional)" hint="Without the @. For example johndoe.">
    <x-itk.input :value="old('username', $telegramTarget?->username)" maxlength="100" autocomplete="off"
        autocapitalize="none" spellcheck="false" />
</x-itk.field>

<div>
    <x-itk.checkbox name="is_active" label="Active" unchecked="0"
        :checked="old('is_active', $telegramTarget ? ($telegramTarget->is_active ? '1' : '0') : '1') == '1'"
        description="Down alerts are sent to this chat only while it is active." />
    @error('is_active')
        <p class="flex items-start gap-1 text-footnote font-medium text-error mt-1">
            <x-itk.icon name="circle-alert" size="16" class="mt-px" />{{ $message }}
        </p>
    @enderror
</div>
