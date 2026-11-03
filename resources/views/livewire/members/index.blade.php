<div class="space-y-8 p-1 sm:p-2">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="font-serif tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Member
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Kelola pendaftaran anggota, monitoring keaktifan langganan, dan saldo sesi Personal Trainer.
            </flux:text>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="cursor-pointer shadow-sm">
                Daftar Member Baru
            </flux:button>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Cari berdasarkan nama, nomor HP, atau kode member..."
                clearable
            />
        </div>

        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status Membership</flux:select.option>
                <flux:select.option value="active">Membership Aktif</flux:select.option>
                <flux:select.option value="expiring_soon">Jatuh Tempo (H-7)</flux:select.option>
                <flux:select.option value="expired">Expired / Habis</flux:select.option>
                <flux:select.option value="inactive">Akun Non-Aktif</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Members Table Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="w-full overflow-x-auto">
            <flux:table class="min-w-full">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'member_code'" :direction="$sortDirection" wire:click="sort('member_code')" class="w-36 font-semibold cursor-pointer">Kode Member</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')" class="font-semibold cursor-pointer">Nama Lengkap</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'phone'" :direction="$sortDirection" wire:click="sort('phone')" class="font-semibold cursor-pointer">Nomor Kontak</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')" class="font-semibold cursor-pointer">Status Keanggotaan</flux:table.column>
                    <flux:table.column class="font-semibold">Saldo Kuota PT</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'join_date'" :direction="$sortDirection" wire:click="sort('join_date')" class="font-semibold cursor-pointer">Tanggal Bergabung</flux:table.column>
                    <flux:table.column align="end" class="font-semibold">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    {{-- Skeleton Loading Row --}}
                    <flux:table.row wire:loading.table wire:target="search, statusFilter, sort, previousPage, nextPage, gotoPage">
                        <flux:table.cell colspan="7" class="py-6">
                            <div class="space-y-4 py-2">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="flex items-center gap-6">
                                        <flux:skeleton animate="pulse" class="h-4 w-24 rounded" />
                                        <div class="flex items-center gap-3 flex-1">
                                            <flux:skeleton animate="pulse" class="h-9 w-9 rounded-full shrink-0" />
                                            <div class="space-y-1.5 flex-1">
                                                <flux:skeleton animate="pulse" class="h-4 w-36 rounded" />
                                                <flux:skeleton animate="pulse" class="h-3 w-20 rounded" />
                                            </div>
                                        </div>
                                        <flux:skeleton animate="pulse" class="h-4 w-24 rounded hidden sm:block" />
                                        <flux:skeleton animate="pulse" class="h-5 w-24 rounded-full" />
                                        <flux:skeleton animate="pulse" class="h-4 w-20 rounded hidden md:block" />
                                        <div class="flex gap-2 ml-auto">
                                            <flux:skeleton animate="pulse" class="h-7 w-7 rounded" />
                                            <flux:skeleton animate="pulse" class="h-7 w-7 rounded" />
                                            <flux:skeleton animate="pulse" class="h-7 w-7 rounded" />
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Data Rows --}}
                    @forelse ($this->members as $member)
                        <flux:table.row :key="$member->id" wire:loading.remove wire:target="search, statusFilter, sort, previousPage, nextPage, gotoPage">
                            <flux:table.cell class="font-mono text-xs font-semibold text-amber-600 dark:text-amber-400">
                                {{ $member->member_code }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($member->photo_url)
                                        <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700" />
                                    @else
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 font-bold text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ mb_substr($member->name, 0, 2) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $member->name }}
                                        </div>
                                        @if ($member->gender)
                                            <div class="text-xs text-zinc-500">
                                                {{ $member->gender->label() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ $member->phone }}
                                </div>
                                @if ($member->email)
                                    <div class="text-[11px] text-zinc-500 truncate max-w-[170px]" title="{{ $member->email }}">
                                        {{ $member->email }}
                                    </div>
                                @endif
                                @if ($member->emergency_contact)
                                    <div class="text-[11px] text-zinc-400">
                                        Darurat: {{ $member->emergency_contact }}
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $status = $member->membership_status;
                                @endphp
                                @if ($status === 'active')
                                    <flux:badge variant="success" size="sm" inset="top bottom">
                                        Aktif s/d {{ $member->activeSubscription?->end_date->format('d/m/Y') }}
                                    </flux:badge>
                                @elseif ($status === 'expiring_soon')
                                    <flux:badge variant="warning" size="sm" inset="top bottom">
                                        Habis {{ $member->activeSubscription?->end_date->format('d/m/Y') }}
                                    </flux:badge>
                                @elseif ($status === 'expired')
                                    <flux:badge variant="danger" size="sm" inset="top bottom">
                                        Expired
                                    </flux:badge>
                                @else
                                    <flux:badge variant="subtle" size="sm" inset="top bottom">
                                        Non-Aktif
                                    </flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @php
                                    $remPt = $member->active_remaining_pt_sessions ?? $member->total_remaining_pt_sessions;
                                    $totPt = $member->active_total_pt_sessions ?? $member->total_pt_sessions;
                                @endphp
                                @if ($remPt > 0)
                                    <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                        <span class="text-amber-500">{{ $remPt }}</span>
                                        <span class="text-zinc-400">/ {{ $totPt }} sesi</span>
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-400">Tidak ada</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $member->join_date->format('d M Y') }}
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1.5">
                                    <flux:tooltip content="Kartu / QR Code Member">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="qr-code"
                                            wire:click="openCardModal({{ $member->id }})"
                                            aria-label="Kartu Member"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="Lihat Detail Member">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="eye"
                                            wire:click="openDetailModal({{ $member->id }})"
                                            aria-label="Detail Member"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="Ubah Data Member">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="pencil-square"
                                            wire:click="openEditModal({{ $member->id }})"
                                            aria-label="Edit Member"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    @if ($member->email)
                                        <flux:tooltip content="Kirim Password ke Email">
                                            <flux:button
                                                size="xs"
                                                variant="subtle"
                                                icon="envelope"
                                                wire:click="sendTemporaryPassword({{ $member->id }})"
                                                wire:confirm="Kirim password sementara baru ke email {{ $member->email }}?"
                                                aria-label="Kirim Password"
                                                class="cursor-pointer text-amber-600 dark:text-amber-400"
                                            />
                                        </flux:tooltip>
                                    @endif

                                    <flux:tooltip content="{{ $member->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            :icon="$member->is_active ? 'no-symbol' : 'check'"
                                            wire:click="toggleActive({{ $member->id }})"
                                            :aria-label="$member->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="search, statusFilter, previousPage, nextPage, gotoPage">
                            <flux:table.cell colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="users" class="h-8 w-8 text-zinc-400" />
                                    <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                        Tidak ada data member yang sesuai
                                    </flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">
                                        Coba sesuaikan kata kunci pencarian atau ubah filter status.
                                    </flux:text>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- AJAX Pagination --}}
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
            {{ $this->members->links() }}
        </div>
    </div>

    {{-- Form Modal: Create & Edit Member --}}
    <flux:modal wire:model="showFormModal" class="max-w-xl">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-serif">
                    {{ $selectedMemberId ? 'Edit Data Member' : 'Pendaftaran Member Baru' }}
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Lengkapi identitas dasar dan kontak anggota gym.
                </flux:text>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:field>
                    <flux:label>Kode Member</flux:label>
                    <flux:input wire:model="member_code" placeholder="GYM-2611-0001" />
                    <flux:error name="member_code" />
                </flux:field>

                <flux:field>
                    <flux:label>Tanggal Bergabung</flux:label>
                    <flux:input type="date" wire:model="join_date" />
                    <flux:error name="join_date" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Nama Lengkap</flux:label>
                    <flux:input wire:model="name" placeholder="Nama sesuai identitas..." />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Nomor HP / WhatsApp</flux:label>
                    <flux:input wire:model="phone" placeholder="081234567890" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:field>
                    <flux:label>Alamat Email (Opsional)</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="member@email.com" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Jenis Kelamin</flux:label>
                    <flux:select wire:model="gender">
                        <flux:select.option value="">Pilih Jenis Kelamin</flux:select.option>
                        <flux:select.option value="male">Laki-laki</flux:select.option>
                        <flux:select.option value="female">Perempuan</flux:select.option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>

                <flux:field>
                    <flux:label>Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="birth_date" />
                    <flux:error name="birth_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Kontak Darurat (Opsional)</flux:label>
                    <flux:input wire:model="emergency_contact" placeholder="08xxxxxxxx" />
                    <flux:error name="emergency_contact" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Alamat Tinggal</flux:label>
                    <flux:textarea wire:model="address" rows="2" placeholder="Alamat lengkap member..." />
                    <flux:error name="address" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>Catatan Kebugaran / Medis</flux:label>
                    <flux:textarea wire:model="notes" rows="2" placeholder="Riwayat cedera, target latihan, dsb..." />
                    <flux:error name="notes" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="subtle" wire:click="$set('showFormModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $selectedMemberId ? 'Simpan Perubahan' : 'Daftarkan Member' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Detail Modal: Member Information Card --}}
    <flux:modal wire:model="showDetailModal" class="max-w-2xl">
        @if ($detailMember)
            <div class="space-y-6">
                {{-- Member Header --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-zinc-200 pb-4 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        @if ($detailMember->photo_url)
                            <img src="{{ $detailMember->photo_url }}" alt="{{ $detailMember->name }}" class="h-14 w-14 shrink-0 rounded-xl object-cover border border-amber-500/30 shadow-xs" />
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 font-bold text-amber-600 dark:text-amber-400">
                                {{ mb_substr($detailMember->name, 0, 2) }}
                            </div>
                        @endif
                        <div>
                            <flux:heading size="lg" class="font-serif">
                                {{ $detailMember->name }}
                            </flux:heading>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-mono text-zinc-500">
                                <span>{{ $detailMember->member_code }}</span>
                                <span>•</span>
                                <span>{{ $detailMember->phone }}</span>
                                @if ($detailMember->email)
                                    <span>•</span>
                                    <span class="text-zinc-700 dark:text-zinc-300 font-sans font-medium">{{ $detailMember->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        @php
                            $dStatus = $detailMember->membership_status;
                        @endphp
                        @if ($dStatus === 'active')
                            <flux:badge variant="success" size="md">Aktif</flux:badge>
                        @elseif ($dStatus === 'expiring_soon')
                            <flux:badge variant="warning" size="md">Segera Habis</flux:badge>
                        @elseif ($dStatus === 'expired')
                            <flux:badge variant="danger" size="md">Expired</flux:badge>
                        @else
                            <flux:badge variant="subtle" size="md">Non-Aktif</flux:badge>
                        @endif
                    </div>
                </div>

                {{-- Status Overview Grid --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Membership Plan Status --}}
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-semibold mb-2">
                            Paket Membership
                        </div>
                        @if ($sub = $detailMember->activeSubscription)
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $sub->plan?->name ?? 'Membership Langganan' }}
                            </div>
                            <div class="text-xs text-zinc-500 mt-1 font-mono">
                                {{ $sub->start_date->format('d M Y') }} s/d {{ $sub->end_date->format('d M Y') }}
                            </div>
                            <div class="mt-2 text-xs text-amber-600 dark:text-amber-400 font-medium">
                                Sisa {{ now()->diffInDays($sub->end_date, false) }} hari lagi
                            </div>
                        @else
                            <div class="text-sm text-zinc-500">
                                Tidak ada paket membership aktif saat ini.
                            </div>
                        @endif
                    </div>

                    {{-- PT Quota Status --}}
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="text-xs uppercase tracking-wider text-zinc-500 font-semibold mb-2">
                            Saldo Personal Trainer
                        </div>
                        @php
                            $activePtBatches = $detailMember->ptQuotas->where('is_active', true)->where('remaining_sessions', '>', 0)->sortBy('created_at');
                            $totalPtRemaining = $activePtBatches->sum('remaining_sessions');
                            $totalPtTotal = $activePtBatches->sum('total_sessions');
                        @endphp
                        @if ($totalPtRemaining > 0)
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                <span class="text-2xl font-mono text-amber-500">{{ $totalPtRemaining }}</span>
                                <span class="text-xs text-zinc-500">/ {{ $totalPtTotal }} sesi tersisa</span>
                            </div>
                            <div class="text-xs text-zinc-500 mt-1">
                                Akumulasi {{ $activePtBatches->count() }} paket aktif (Deduction: FIFO)
                            </div>
                            @if ($activePtBatches->count() > 1)
                                <div class="mt-2.5 space-y-1.5 border-t border-zinc-100 pt-2 dark:border-zinc-800">
                                    <div class="text-[11px] font-semibold text-zinc-500">Rincian Paket (Batch):</div>
                                    @foreach ($activePtBatches as $b)
                                        <div class="flex items-center justify-between text-[11px] text-zinc-600 dark:text-zinc-400">
                                            <span>
                                                {{ $b->invoice?->plan?->name ?? 'Paket PT' }}
                                                <span class="text-zinc-400">({{ $b->created_at->format('d/m/Y') }})</span>
                                            </span>
                                            <span class="font-mono font-medium text-amber-600 dark:text-amber-400">
                                                {{ $b->remaining_sessions }}/{{ $b->total_sessions }} sesi
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="text-sm text-zinc-500">
                                Tidak ada saldo kuota sesi PT aktif.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Detail Profile Fields --}}
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 space-y-3">
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-zinc-500">Jenis Kelamin:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">
                                {{ $detailMember->gender?->label() ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-500">Tanggal Lahir:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">
                                {{ $detailMember->birth_date?->format('d M Y') ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-500">Kontak Darurat:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">
                                {{ $detailMember->emergency_contact ?? '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-zinc-500">Tanggal Bergabung:</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100 ml-1">
                                {{ $detailMember->join_date->format('d M Y') }}
                            </span>
                        </div>
                    </div>

                    @if ($detailMember->address)
                        <div class="text-xs border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <span class="text-zinc-500">Alamat:</span>
                            <span class="text-zinc-800 dark:text-zinc-200 ml-1">{{ $detailMember->address }}</span>
                        </div>
                    @endif

                    @if ($detailMember->notes)
                        <div class="text-xs border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <span class="text-zinc-500">Catatan Medis/Target:</span>
                            <span class="text-zinc-800 dark:text-zinc-200 ml-1">{{ $detailMember->notes }}</span>
                        </div>
                    @endif
                </div>

                {{-- Recent Attendance History --}}
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-2">
                        Presensi Terakhir
                    </div>
                    @if ($detailMember->attendances->isNotEmpty())
                        <div class="space-y-1.5">
                            @foreach ($detailMember->attendances as $att)
                                <div class="flex items-center justify-between rounded-md bg-zinc-50 p-2.5 text-xs dark:bg-zinc-800/50">
                                    <span class="font-mono text-zinc-700 dark:text-zinc-300">
                                        {{ $att->check_in_time->format('d M Y, H:i') }} WIB
                                    </span>
                                    <span class="text-zinc-500">
                                        {{ $att->notes ?? 'Check-in reguler' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-xs text-zinc-500 italic">
                            Belum ada riwayat presensi tercatat.
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="flex flex-wrap justify-between items-center gap-2 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <div class="flex items-center gap-2">
                        <flux:button type="button" variant="primary" icon="qr-code" wire:click="openCardModal({{ $detailMember->id }})" class="cursor-pointer">
                            Lihat Kartu / QR Member
                        </flux:button>
                        @if ($detailMember->email)
                            <flux:button
                                type="button"
                                variant="subtle"
                                icon="envelope"
                                wire:click="sendTemporaryPassword({{ $detailMember->id }})"
                                wire:confirm="Kirim password sementara baru ke email {{ $detailMember->email }}?"
                                class="cursor-pointer text-amber-600 dark:text-amber-400"
                            >
                                Kirim Password ke Email
                            </flux:button>
                        @endif
                    </div>
                    <flux:button type="button" variant="subtle" wire:click="$set('showDetailModal', false)">
                        Tutup
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    {{-- Modal: Kartu Member Digital & QR Code --}}
    <flux:modal wire:model="showCardModal" class="max-w-md">
        @if ($cardMember)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg" class="font-serif">
                        Kartu Anggota Digital
                    </flux:heading>
                    <flux:text size="sm" class="text-zinc-500">
                        Scan QR ini di terminal presensi meja depan untuk check-in.
                    </flux:text>
                </div>

                {{-- Visual Membership Card Container --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-950 p-6 text-white shadow-xl border border-amber-500/30">
                    {{-- Card Glow & Background Details --}}
                    <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
                    <div class="absolute -left-8 -bottom-8 h-32 w-32 rounded-full bg-amber-500/5 blur-2xl pointer-events-none"></div>

                    {{-- Card Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-700/60">
                        <div class="flex items-center gap-2">
                            <flux:icon name="bolt" class="h-5 w-5 text-amber-400" />
                            <span class="font-bold tracking-widest text-xs uppercase text-amber-400">Gym Membership Card</span>
                        </div>
                        <flux:badge variant="primary" size="sm" class="font-mono text-[10px]">
                            {{ $cardMember->membership_status === 'active' ? 'AKTIF' : strtoupper($cardMember->membership_status) }}
                        </flux:badge>
                    </div>

                    {{-- Member Info --}}
                    <div class="mt-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            @if ($cardMember->photo_url)
                                <img src="{{ $cardMember->photo_url }}" alt="{{ $cardMember->name }}" class="h-14 w-14 rounded-full object-cover border-2 border-amber-400/80 shrink-0" />
                            @endif
                            <div>
                                <div class="text-xs uppercase tracking-wider text-zinc-400 font-medium">Nama Member</div>
                                <div class="text-lg font-bold text-zinc-100 font-serif leading-tight mt-0.5">
                                    {{ $cardMember->name }}
                                </div>
                                <div class="mt-1 font-mono text-xs font-semibold text-amber-400">
                                    {{ $cardMember->member_code }}
                                </div>
                            </div>
                        </div>

                        {{-- QR Code Image Box --}}
                        <div class="rounded-xl bg-white p-2.5 shadow-md shrink-0 flex items-center justify-center">
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data={{ urlencode($cardMember->member_code) }}"
                                alt="QR Code Member"
                                class="h-28 w-28 object-contain"
                                loading="lazy"
                            />
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="mt-5 flex items-center justify-between border-t border-zinc-700/60 pt-3 text-[11px] text-zinc-400">
                        <div>
                            <span>Paket: </span>
                            <span class="text-zinc-200 font-medium">{{ $cardMember->activeSubscription?->plan?->name ?? 'Membership' }}</span>
                        </div>
                        <div>
                            <span>Bergabung: </span>
                            <span class="text-zinc-200 font-mono">{{ $cardMember->join_date->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div class="flex items-center justify-between pt-2">
                    <flux:button variant="subtle" icon="printer" onclick="window.print()" class="cursor-pointer">
                        Cetak Kartu
                    </flux:button>
                    <flux:button variant="ghost" wire:click="$set('showCardModal', false)">
                        Tutup
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
