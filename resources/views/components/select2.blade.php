@props([
    'placeholder' => 'Pilih salah satu...',
    'id' => null,
])

@php
    $id = $id ?? 'select2-' . uniqid();
    $wireModel = $attributes->wire('model')->value();
    $isLive = $attributes->wire('model')->hasModifier('live');
@endphp

<div
    wire:ignore
    x-data="{
        select2Instance: null,
        init() {
            let setup = () => {
                if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) {
                    setTimeout(setup, 40);
                    return;
                }

                let $select = $(this.$refs.select);
                let parentDialog = $select.closest('dialog');

                let options = {
                    placeholder: @js($placeholder),
                    allowClear: true,
                    width: '100%',
                };

                if (parentDialog.length) {
                    options.dropdownParent = parentDialog;
                }

                this.select2Instance = $select.select2(options);

                @if ($wireModel)
                    let currentVal = $wire.get(@js($wireModel));
                    if (currentVal !== null && currentVal !== undefined && currentVal !== '') {
                        $select.val(currentVal).trigger('change.select2');
                    }

                    $select.on('change', () => {
                        let val = $select.val();
                        let cleanVal = (val === '' || val === null) ? null : val;
                        @if ($isLive)
                            $wire.set(@js($wireModel), cleanVal);
                        @else
                            $wire.set(@js($wireModel), cleanVal, false);
                        @endif
                    });

                    $wire.watch(@js($wireModel), (newVal) => {
                        let current = $select.val();
                        let target = (newVal === null || newVal === undefined) ? '' : newVal;
                        if (current != target) {
                            $select.val(target).trigger('change.select2');
                        }
                    });
                @endif
            };

            setup();
        }
    }"
    class="w-full relative select2-wrapper"
>
    <select
        x-ref="select"
        id="{{ $id }}"
        {{ $attributes->whereDoesntStartWith('wire:model') }}
        class="w-full"
    >
        <option value="">{{ $placeholder }}</option>
        {{ $slot }}
    </select>
</div>

