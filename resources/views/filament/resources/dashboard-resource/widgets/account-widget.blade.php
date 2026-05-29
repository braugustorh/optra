@php
    $user = filament()->auth()->user();
@endphp

<x-filament-widgets::widget class="fi-account-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3">
            @if($user->profile_photo)
                <x-filament::avatar src="{{ Storage::disk('sedyco_disk')->url($user->profile_photo) }}" size="h-16" />
            @else
                <x-filament-panels::avatar.user size="h-16" :user="$user" />
            @endif


            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    @if($cumple->isNotEmpty())
                        Feliz Cumpleaños 🎂🎉
                    @else
                        @if($user->sex === 'Masculino')
                            Bienvenido
                        @else
                            Bienvenida
                        @endif
                    @endif
                </h2>

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{-- filament()->getUserName($user) --}}
                    <strong>{{ $user->name . ' ' . $user->first_name . ' ' . $user->last_name }}</strong>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{$user->position_id ? $user->position->name : null}} <br>
                    {{$user->department_id ? $user->department->name : null}}<br>
                    {{$user->sede->name ?? null}}
                    {{-- filament()->getSedeName($user)--}}
                </p>
            </div>

            <form action="{{ filament()->getLogoutUrl() }}" method="post" class="my-auto">
                @csrf

                <x-filament::button color="gray" icon="heroicon-m-arrow-left-on-rectangle"
                    icon-alias="panels::widgets.account.logout-button" labeled-from="sm" tag="button" type="submit">
                    {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
                </x-filament::button>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>