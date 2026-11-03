<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1" class="font-serif">
                Dashboard Operasional Gym
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Ringkasan performa keanggotaan, arus presensi hari ini, dan peringatan perpanjangan member.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="qr-code" :href="route('check-in.index')" wire:navigate class="cursor-pointer shadow-sm">
                Buka Terminal Check-in
            </flux:button>
        </div>
    </div>

    {{-- 4 Kartu Metrik Kunci --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Member Aktif --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between text-zinc-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Member Aktif</span>
                <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-600 dark:text-emerald-400">
                    <flux:icon name="users" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->totalActiveMembers }}
                </span>
                <span class="text-xs text-zinc-500">orang</span>
            </div>
            <div class="mt-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                Berlangganan aktif saat ini
            </div>
        </div>

        {{-- Card 2: Check-in Hari Ini --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between text-zinc-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Check-in Hari Ini</span>
                <div class="rounded-lg bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400">
                    <flux:icon name="qr-code" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->todayCheckIns }}
                </span>
                <span class="text-xs text-zinc-500">kunjungan</span>
            </div>
            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400 font-medium">
                {{ now()->format('d M Y') }}
            </div>
        </div>

        {{-- Card 3: Jatuh Tempo Minggu Ini (H-7) --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between text-zinc-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Jatuh Tempo (H-7)</span>
                <div class="rounded-lg bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="font-mono text-3xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ $this->expiringSoonCount }}
                </span>
                <span class="text-xs text-zinc-500">member</span>
            </div>
            <div class="mt-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                Perlu diingatkan perpanjang
            </div>
        </div>

        {{-- Card 4: Pendapatan Bulan Ini --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between text-zinc-500">
                <span class="text-xs font-semibold uppercase tracking-wider">Omzet {{ now()->translatedFormat('F Y') }}</span>
                <div class="rounded-lg bg-purple-500/10 p-2 text-purple-600 dark:text-purple-400">
                    <flux:icon name="banknotes" class="h-5 w-5" />
                </div>
            </div>
            <div class="mt-3 flex items-baseline gap-1">
                <span class="font-mono text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    Rp {{ number_format($this->monthlyRevenue, 0, ',', '.') }}
                </span>
            </div>
            <div class="mt-2 text-xs text-purple-600 dark:text-purple-400 font-medium">
                Total pembayaran lunas
            </div>
        </div>
    </div>

    {{-- Banner Shortcut Frontdesk --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-600/90 via-amber-500/90 to-amber-700/90 p-6 text-white shadow-md">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <flux:icon name="bolt" class="h-5 w-5 text-amber-200" />
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-100">Frontdesk & Meja Depan</span>
                </div>
                <h3 class="text-xl font-bold font-serif text-white">
                    Terminal Presensi Scanner Mandiri
                </h3>
                <p class="text-xs text-amber-100 max-w-xl">
                    Mendukung scanner barcode USB, scan kamera web/HP, dan pencarian manual dengan validasi instan warna Hijau (Aktif) / Merah (Expired).
                </p>
            </div>
            <div class="shrink-0 flex items-center gap-3">
                <flux:button variant="ghost" :href="route('members.index')" wire:navigate class="bg-white/10 hover:bg-white/20 text-white border-transparent cursor-pointer text-xs">
                    Kelola Member
                </flux:button>
                <flux:button variant="primary" icon="qr-code" :href="route('check-in.index')" wire:navigate class="bg-white hover:bg-amber-50 text-amber-900 font-semibold cursor-pointer shadow-md">
                    Buka Terminal Presensi
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Quick Action Table: Member Jatuh Tempo Minggu Ini (H-7) --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="p-5 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
            <div>
                <flux:heading size="base" class="font-semibold">
                    Perhatian Cepat: Member Jatuh Tempo (H-7)
                </flux:heading>
                <flux:text size="xs" class="text-zinc-500">
                    Daftar member yang masa berlaku langganannya akan habis dalam 7 hari ke depan. Kirim pengingat via WhatsApp dengan 1 klik.
                </flux:text>
            </div>

            <flux:badge variant="warning" size="sm">
                {{ $this->expiringMembers->count() }} Perlu Perhatian
            </flux:badge>
        </div>

        <div class="w-full overflow-x-auto">
            <flux:table class="min-w-full">
                <flux:table.columns>
                    <flux:table.column class="font-semibold">Member</flux:table.column>
                    <flux:table.column class="font-semibold">Kontak</flux:table.column>
                    <flux:table.column class="font-semibold">Paket Langganan</flux:table.column>
                    <flux:table.column class="font-semibold">Tanggal Berakhir</flux:table.column>
                    <flux:table.column class="font-semibold">Sisa Masa Aktif</flux:table.column>
                    <flux:table.column align="end" class="font-semibold">Aksi Cepat</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->expiringMembers as $member)
                        @php
                            $sub = $member->activeSubscription;
                            $daysLeft = (int) ceil(now()->diffInDays($sub?->end_date, false));
                        @endphp
                        <flux:table.row :key="'exp-'.$member->id">
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $member->name }}
                                </div>
                                <div class="font-mono text-xs text-zinc-500">
                                    {{ $member->member_code }}
                                </div>
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $member->phone }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="subtle" size="sm">
                                    {{ $sub?->plan?->name ?? 'Langganan' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $sub?->end_date->format('d M Y') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <flux:badge variant="warning" size="sm">
                                    {{ $daysLeft <= 0 ? 'Hari ini' : $daysLeft . ' hari lagi' }}
                                </flux:badge>
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        icon="chat-bubble-left-ellipsis"
                                        href="{{ $this->getWhatsAppReminderUrl($member) }}"
                                        target="_blank"
                                        class="cursor-pointer text-emerald-600 dark:text-emerald-400"
                                    >
                                        WhatsApp Reminder
                                    </flux:button>

                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        icon="banknotes"
                                        :href="route('invoices.index')"
                                        wire:navigate
                                        class="cursor-pointer"
                                    >
                                        Perpanjang
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6" class="py-10 text-center text-zinc-500 text-xs">
                                <flux:icon name="check-circle" class="h-6 w-6 mx-auto mb-1 text-emerald-500" />
                                Tidak ada member yang jatuh tempo dalam 7 hari ke depan. Semua langganan aman!
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</div>
