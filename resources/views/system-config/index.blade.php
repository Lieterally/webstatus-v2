@extends('layouts.app')

@section('title', 'System config')

@section('content')
    <x-itk.page-header title="System config"
        description="Set the monitoring cycle, alert repeats and health check limits." />

    <x-itk.card class="max-w-lg">
        <form method="POST" action="{{ route('system-config.update') }}" class="grid gap-5">
            @csrf
            @method('PUT')

            {{-- Global monitoring switch, in a list row --}}
            <div>
                <ul class="bg-surface border border-line rounded-md">
                    <li class="px-4">
                        <x-itk.switch name="is_monitoring" label="Run monitoring cycles" unchecked="0"
                            :checked="(bool) old('is_monitoring', $isMonitoring)"
                            description="When off, no website is checked and no down alerts are sent. Turning this off closes all open outages." />
                    </li>
                </ul>
                @error('is_monitoring')
                    <p class="flex items-start gap-1 text-footnote font-medium text-error mt-1">
                        <x-itk.icon name="circle-alert" size="16" class="mt-px" />{{ $message }}
                    </p>
                @enderror
            </div>

            <x-itk.field name="cycle_interval_minutes" label="Cycle interval (minutes)"
                hint="How often every website is checked. 5 to 1440 minutes." required>
                <x-itk.input type="number" :value="old('cycle_interval_minutes', $cycleInterval)" min="5" max="1440"
                    step="1" inputmode="numeric" />
            </x-itk.field>

            <x-itk.field name="notification_cycle_threshold" label="Repeat down alerts every (cycles)"
                hint="Cycles between repeated down alerts for the same website. 1 to 100." required>
                <x-itk.input type="number" :value="old('notification_cycle_threshold', $notificationCycleThreshold)"
                    min="1" max="100" step="1" inputmode="numeric" />
            </x-itk.field>

            <fieldset class="grid gap-5 pt-5 border-t border-line">
                <legend class="text-headline text-ink pt-5 float-left w-full">Health checks</legend>

                <x-itk.field name="connection_timeout_seconds" label="Connection timeout (seconds)"
                    hint="Longest wait to open a connection. Past this, the website counts as unreachable. 1 to 60 seconds."
                    required>
                    <x-itk.input type="number" :value="old('connection_timeout_seconds', $connectionTimeout)" min="1"
                        max="60" step="1" inputmode="numeric" />
                </x-itk.field>

                <x-itk.field name="response_timeout_seconds" label="Response timeout (seconds)"
                    hint="Longest wait for a response after connecting. Slow websites may time out. 5 to 120 seconds."
                    required>
                    <x-itk.input type="number" :value="old('response_timeout_seconds', $responseTimeout)" min="5"
                        max="120" step="1" inputmode="numeric" />
                </x-itk.field>

                <x-itk.field name="concurrency_limit" label="Concurrency limit"
                    hint="Most requests sent at once per batch. Higher is faster but uses more resources. 5 to 100."
                    required>
                    <x-itk.input type="number" :value="old('concurrency_limit', $concurrencyLimit)" min="5" max="100"
                        step="1" inputmode="numeric" />
                </x-itk.field>
            </fieldset>

            <div class="flex items-center justify-end pt-5 border-t border-line">
                <x-itk.button type="submit">Save changes</x-itk.button>
            </div>
        </form>
    </x-itk.card>
@endsection
