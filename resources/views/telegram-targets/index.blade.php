@extends('layouts.app')

@section('title', 'Telegram manager')

@section('content')
    <x-itk.page-header title="Telegram manager" description="Choose which Telegram chats receive down alerts.">
        <x-slot:actions>
            <x-itk.button :href="route('telegram-targets.create')" icon="plus">Add target</x-itk.button>
        </x-slot:actions>
    </x-itk.page-header>

    <div x-data="{ target: { name: '', url: '' }, confirmDelete(name, url) { this.target = { name, url }; $refs.confirmDialog.showModal(); } }">
        @if ($targets->isEmpty())
            <x-itk.card>
                <x-itk.empty title="No Telegram targets yet" icon="send">
                    <x-slot:action>
                        <x-itk.button :href="route('telegram-targets.create')" icon="plus">Add target</x-itk.button>
                    </x-slot:action>
                    Add a Telegram chat to receive alerts when a website goes down.
                </x-itk.empty>
            </x-itk.card>
        @else
            <x-itk.table caption="Telegram targets" hide-caption caption-id="telegram-targets-caption" class="shadow-1">
                <thead>
                    <tr>
                        <th scope="col">Chat ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="itk-num"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($targets as $target)
                        <tr>
                            <th scope="row"><code class="font-mono font-normal">{{ $target->chat_id }}</code></th>
                            <td>
                                @if ($target->username)
                                    {{ '@' . $target->username }}
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($target->is_active)
                                    <x-itk.badge tone="success" icon="circle-check">Active</x-itk.badge>
                                @else
                                    <x-itk.badge tone="neutral" icon="circle-pause">Inactive</x-itk.badge>
                                @endif
                            </td>
                            <td class="itk-num">
                                <div class="inline-flex gap-2">
                                    <x-itk.button variant="ghost" size="sm" icon="pencil" :href="route('telegram-targets.edit', $target)"
                                        :aria-label="'Edit chat ' . $target->chat_id">Edit</x-itk.button>
                                    <x-itk.button variant="ghost" size="sm" icon="trash-2" class="text-error hover:bg-error-soft"
                                        :aria-label="'Delete chat ' . $target->chat_id"
                                        x-on:click="confirmDelete({{ Js::from('chat ' . $target->chat_id) }}, {{ Js::from(route('telegram-targets.destroy', $target)) }})">
                                        Delete
                                    </x-itk.button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-itk.table>
        @endif

        <x-itk.alert-dialog action="target.url" confirm-label="Delete target" id="delete-telegram-target">
            <x-slot:heading>Delete <span x-text="target.name"></span>?</x-slot:heading>
            This chat stops receiving down alerts. This cannot be undone.
        </x-itk.alert-dialog>
    </div>
@endsection
