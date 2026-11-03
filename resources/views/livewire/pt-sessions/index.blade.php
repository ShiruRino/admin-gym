<div class="space-y-8 p-1 sm:p-2">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="font-serif tracking-tight text-zinc-900 dark:text-zinc-100">
                Sesi Personal Trainer
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Monitoring kuota latihan privat member dan pencatatan log pemakaian sesi bersama trainer.
            </flux:text>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <flux:button variant="primary" icon="minus-circle" wire:click="openDeductModal" class="cursor-pointer shadow-sm">
                Pakai Sesi Latihan
            </flux:button>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-6">
        <button
            type="button"
            wire:click="setTab('quotas')"
            class="pb-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'quotas' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            <span class="flex items-center gap-2">
                <flux:icon name="ticket" class="size-4" />
                Saldo Kuota Member
            </span>
        </button>

        <button
            type="button"
            wire:click="setTab('logs')"
            class="pb-3 text-sm font-medium border-b-2 transition-colors cursor-pointer {{ $activeTab === 'logs' ? 'border-amber-500 text-amber-600 dark:text-amber-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
        >
            <span class="flex items-center gap-2">
                <flux:icon name="clock" class="size-4" />
                Riwayat Sesi Terlaksana
            </span>
        </button>
    </div>

    {{-- TAB 1: SALDO KUOTA MEMBER --}}
    @if ($activeTab === 'quotas')
        <div class="space-y-6">
            {{-- Filter & Search Toolbar --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="quotaSearch"
                        icon="magnifying-glass"
                        placeholder="Cari nama member, nomor HP, atau kode..."
                        clearable
                    />
                </div>

                <div>
                    <flux:select wire:model.live="quotaStatusFilter">
                        <flux:select.option value="all">Semua Status Kuota</flux:select.option>
                        <flux:select.option value="available">Kuota Masih Tersedia</flux:select.option>
                        <flux:select.option value="depleted">Kuota Habis (Selesai)</flux:select.option>
                    </flux:select>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="w-full overflow-x-auto">
                    <flux:table class="min-w-full">
                        <flux:table.columns>
                            <flux:table.column class="font-semibold">Nama Member</flux:table.column>
                            <flux:table.column sortable :sorted="$quotaSortBy === 'total_sessions'" :direction="$quotaSortDirection" wire:click="sortQuotas('total_sessions')" class="font-semibold cursor-pointer">
                                Total Kuota
                            </flux:table.column>
                            <flux:table.column class="font-semibold">Terpakai</flux:table.column>
                            <flux:table.column sortable :sorted="$quotaSortBy === 'remaining_sessions'" :direction="$quotaSortDirection" wire:click="sortQuotas('remaining_sessions')" class="font-semibold cursor-pointer">
                                Sisa Sesi
                            </flux:table.column>
                            <flux:table.column class="font-semibold">Status Kuota</flux:table.column>
                            <flux:table.column class="font-semibold">Asal Transaksi</flux:table.column>
                            <flux:table.column sortable :sorted="$quotaSortBy === 'created_at'" :direction="$quotaSortDirection" wire:click="sortQuotas('created_at')" class="font-semibold cursor-pointer">
                                Tanggal Beli
                            </flux:table.column>
                            <flux:table.column align="end" class="font-semibold">Aksi</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            {{-- Skeleton Loading Row --}}
                            <flux:table.row wire:loading.table wire:target="quotaSearch, quotaStatusFilter, sortQuotas, previousPage, nextPage, gotoPage">
                                <flux:table.cell colspan="8" class="py-6">
                                    <div class="space-y-4 py-2">
                                        @for ($i = 0; $i < 4; $i++)
                                            <div class="flex items-center gap-6">
                                                <div class="space-y-1.5 flex-1">
                                                    <flux:skeleton animate="pulse" class="h-4 w-36 rounded" />
                                                    <flux:skeleton animate="pulse" class="h-3 w-20 rounded" />
                                                </div>
                                                <flux:skeleton animate="pulse" class="h-4 w-16 rounded font-mono" />
                                                <flux:skeleton animate="pulse" class="h-4 w-16 rounded font-mono" />
                                                <flux:skeleton animate="pulse" class="h-5 w-20 rounded-full" />
                                                <flux:skeleton animate="pulse" class="h-5 w-16 rounded-full" />
                                                <flux:skeleton animate="pulse" class="h-4 w-28 rounded" />
                                                <flux:skeleton animate="pulse" class="h-4 w-20 rounded" />
                                                <div class="flex gap-2 ml-auto">
                                                    <flux:skeleton animate="pulse" class="h-7 w-20 rounded" />
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>

                            {{-- Data Rows --}}
                            @forelse ($this->quotas as $quota)
                                <flux:table.row :key="$quota->id" wire:loading.remove wire:target="quotaSearch, quotaStatusFilter, sortQuotas, previousPage, nextPage, gotoPage">
                                    <flux:table.cell>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $quota->member->name }}
                                        </div>
                                        <div class="font-mono text-xs text-zinc-500">
                                            {{ $quota->member->member_code }}
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                        {{ $quota->total_sessions }} sesi
                                    </flux:table.cell>

                                    <flux:table.cell class="font-mono text-xs text-zinc-500">
                                        {{ $quota->used_sessions }} sesi
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="inline-flex items-center gap-1 font-mono text-xs font-bold {{ $quota->remaining_sessions > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                                            <span>{{ $quota->remaining_sessions }}</span>
                                            <span class="text-zinc-400 font-normal">/ {{ $quota->total_sessions }}</span>
                                        </span>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if ($quota->remaining_sessions > 0 && $quota->is_active)
                                            <flux:badge variant="success" size="sm">Tersedia</flux:badge>
                                        @else
                                            <flux:badge variant="subtle" size="sm">Habis</flux:badge>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $quota->invoice?->plan?->name ?? 'Paket PT' }}
                                        </div>
                                        <div class="font-mono text-[11px] text-zinc-400">
                                            {{ $quota->invoice?->invoice_number ?? '-' }}
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $quota->created_at->format('d M Y') }}
                                    </flux:table.cell>

                                    <flux:table.cell align="end">
                                        @if ($quota->remaining_sessions > 0 && $quota->is_active)
                                            <flux:button
                                                size="xs"
                                                variant="primary"
                                                icon="check"
                                                wire:click="openDeductModal({{ $quota->member_id }})"
                                                class="cursor-pointer shadow-xs"
                                            >
                                                Pakai Sesi
                                            </flux:button>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">Selesai</span>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row wire:loading.remove wire:target="quotaSearch, quotaStatusFilter, sortQuotas, previousPage, nextPage, gotoPage">
                                    <flux:table.cell colspan="8" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <flux:icon name="ticket" class="h-8 w-8 text-zinc-400" />
                                            <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                                Tidak ada data kuota Personal Trainer
                                            </flux:heading>
                                            <flux:text size="sm" class="text-zinc-500">
                                                Belum ada member yang membeli paket PT atau sesuaikan filter pencarian.
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
                    {{ $this->quotas->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- TAB 2: RIWAYAT SESI TERLAKSANA --}}
    @if ($activeTab === 'logs')
        <div class="space-y-6">
            {{-- Filter & Search Toolbar --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model.live.debounce.300ms="logSearch"
                        icon="magnifying-glass"
                        placeholder="Cari nama member, trainer, atau catatan latihan..."
                        clearable
                    />
                </div>

                <div>
                    <flux:select wire:model.live="logTrainerFilter">
                        <flux:select.option :value="null">Semua Trainer</flux:select.option>
                        @foreach ($this->activeTrainers as $trainer)
                            <flux:select.option :value="$trainer->id">
                                {{ $trainer->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
                <div class="w-full overflow-x-auto">
                    <flux:table class="min-w-full">
                        <flux:table.columns>
                            <flux:table.column sortable :sorted="$logSortBy === 'used_at'" :direction="$logSortDirection" wire:click="sortLogs('used_at')" class="font-semibold cursor-pointer">
                                Waktu Sesi
                            </flux:table.column>
                            <flux:table.column class="font-semibold">Member</flux:table.column>
                            <flux:table.column class="font-semibold">Trainer Pendamping</flux:table.column>
                            <flux:table.column class="font-semibold">Catatan Latihan</flux:table.column>
                            <flux:table.column class="font-semibold">Diverifikasi Oleh</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            {{-- Skeleton Loading Row --}}
                            <flux:table.row wire:loading.table wire:target="logSearch, logTrainerFilter, sortLogs, previousPage, nextPage, gotoPage">
                                <flux:table.cell colspan="5" class="py-6">
                                    <div class="space-y-4 py-2">
                                        @for ($i = 0; $i < 4; $i++)
                                            <div class="flex items-center gap-6">
                                                <flux:skeleton animate="pulse" class="h-4 w-32 rounded font-mono" />
                                                <div class="space-y-1.5 flex-1">
                                                    <flux:skeleton animate="pulse" class="h-4 w-36 rounded" />
                                                    <flux:skeleton animate="pulse" class="h-3 w-20 rounded" />
                                                </div>
                                                <flux:skeleton animate="pulse" class="h-4 w-28 rounded" />
                                                <flux:skeleton animate="pulse" class="h-4 w-44 rounded" />
                                                <flux:skeleton animate="pulse" class="h-4 w-20 rounded" />
                                            </div>
                                        @endfor
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>

                            {{-- Data Rows --}}
                            @forelse ($this->sessionLogs as $log)
                                <flux:table.row :key="$log->id" wire:loading.remove wire:target="logSearch, logTrainerFilter, sortLogs, previousPage, nextPage, gotoPage">
                                    <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $log->used_at->format('d M Y') }}
                                        </div>
                                        <div class="text-zinc-500 text-[11px]">
                                            {{ $log->used_at->format('H:i') }} WIB
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $log->member->name }}
                                        </div>
                                        <div class="font-mono text-xs text-zinc-500">
                                            {{ $log->member->member_code }}
                                        </div>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="user" class="size-4 text-amber-500" />
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">
                                                {{ $log->trainer->name }}
                                            </span>
                                        </div>
                                        @if ($log->trainer->specialty)
                                            <div class="text-[11px] text-zinc-500 pl-6">
                                                {{ $log->trainer->specialty }}
                                            </div>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if ($log->notes)
                                            <div class="text-xs text-zinc-700 dark:text-zinc-300 max-w-sm">
                                                {{ $log->notes }}
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-400 italic">-</span>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell class="text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $log->recordedBy?->name ?? 'Staf' }}
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row wire:loading.remove wire:target="logSearch, logTrainerFilter, sortLogs, previousPage, nextPage, gotoPage">
                                    <flux:table.cell colspan="5" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <flux:icon name="clock" class="h-8 w-8 text-zinc-400" />
                                            <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                                Belum ada riwayat sesi latihan
                                            </flux:heading>
                                            <flux:text size="sm" class="text-zinc-500">
                                                Riwayat latihan akan tercatat saat kuota sesi PT dipotong oleh staf kasir/trainer.
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
                    {{ $this->sessionLogs->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: Catat Pemakaian Sesi Latihan --}}
    <flux:modal wire:model="showDeductModal" class="max-w-md">
        <form wire:submit="deductSession" class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-serif">
                    Catat Sesi Personal Trainer
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Memotong 1 sesi dari saldo kuota member dan mencatat pendampingan trainer.
                </flux:text>
            </div>

            <div class="space-y-4">
                {{-- Pilih Member --}}
                <flux:field>
                    <flux:label>Pilih Member <span class="text-red-500">*</span></flux:label>
                    <x-select2 wire:model.live="selectedMemberId" placeholder="Pilih member yang punya kuota aktif...">
                        @foreach ($this->availableMembers as $m)
                            <option value="{{ $m->id }}">
                                {{ $m->name }} ({{ $m->member_code }}) — Sisa: {{ $m->active_remaining_pt_sessions }} sesi ({{ $m->active_batches_count }} paket)
                            </option>
                        @endforeach
                    </x-select2>
                    <flux:error name="selectedMemberId" />
                </flux:field>

                {{-- Feedback Sisa Kuota & FIFO Info --}}
                @if ($this->selectedMember)
                    @php
                        $activeBatches = $this->selectedMember->activePtQuotas;
                        $oldestBatch = $activeBatches->first();
                        $totalRemaining = $activeBatches->sum('remaining_sessions');
                    @endphp
                    <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-xs space-y-1.5 text-zinc-700 dark:text-zinc-300">
                        <div class="font-semibold text-amber-600 dark:text-amber-400">
                            {{ $this->selectedMember->name }} ({{ $this->selectedMember->member_code }})
                        </div>
                        <div>
                            Total saldo kuota: <strong class="font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $totalRemaining }} sesi</strong> (dari {{ $activeBatches->count() }} paket aktif).
                        </div>
                        @if ($oldestBatch)
                            <div class="text-[11px] text-zinc-500">
                                Sesi akan otomatis memotong batch tertua (FIFO): <strong>{{ $oldestBatch->invoice?->plan?->name ?? 'Paket PT' }}</strong> tgl {{ $oldestBatch->created_at->format('d/m/Y') }} (sisa batch ini: {{ $oldestBatch->remaining_sessions }} sesi).
                            </div>
                        @endif
                        <div class="text-zinc-500 text-[11px] pt-1 border-t border-amber-500/10">
                            Setelah sesi ini disimpan, total sisa kuota member akan menjadi <strong>{{ $totalRemaining - 1 }} sesi</strong>.
                        </div>
                    </div>
                @endif

                {{-- Pilih Trainer --}}
                <flux:field>
                    <flux:label>Trainer Pendamping <span class="text-red-500">*</span></flux:label>
                    <x-select2 wire:model="selectedTrainerId" placeholder="Pilih pelatih...">
                        @foreach ($this->activeTrainers as $t)
                            <option value="{{ $t->id }}">
                                {{ $t->name }} ({{ $t->specialty }})
                            </option>
                        @endforeach
                    </x-select2>
                    <flux:error name="selectedTrainerId" />
                </flux:field>

                {{-- Catatan Latihan --}}
                <flux:field>
                    <flux:label>Catatan Latihan / Workout (Opsional)</flux:label>
                    <flux:input wire:model="sessionNotes" placeholder="Misal: Chest day, Bench press 60kg, Form evaluasi." />
                    <flux:error name="sessionNotes" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showDeductModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Simpan Sesi Selesai
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

