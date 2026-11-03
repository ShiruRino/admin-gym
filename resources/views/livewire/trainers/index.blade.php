<div class="space-y-8 p-1 sm:p-2">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="font-serif tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Trainer
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Kelola data instruktur dan pelatih privat (Personal Trainer) gym.
            </flux:text>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="cursor-pointer shadow-sm">
                Tambah Trainer Baru
            </flux:button>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="sm:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Cari berdasarkan nama, spesialisasi, atau nomor HP..."
                clearable
            />
        </div>

        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status Trainer</flux:select.option>
                <flux:select.option value="active">Trainer Aktif</flux:select.option>
                <flux:select.option value="inactive">Trainer Non-Aktif</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Trainers Table Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="w-full overflow-x-auto">
            <flux:table class="min-w-full">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')" class="font-semibold cursor-pointer">Nama Trainer</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'specialty'" :direction="$sortDirection" wire:click="sort('specialty')" class="font-semibold cursor-pointer">Spesialisasi</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'phone'" :direction="$sortDirection" wire:click="sort('phone')" class="font-semibold cursor-pointer">Kontak</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'pt_session_logs_count'" :direction="$sortDirection" wire:click="sort('pt_session_logs_count')" class="font-semibold cursor-pointer">Sesi Terlaksana</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')" class="font-semibold cursor-pointer">Status</flux:table.column>
                    <flux:table.column align="end" class="font-semibold">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    {{-- Skeleton Loading Row --}}
                    <flux:table.row wire:loading.table wire:target="search, statusFilter, sort, previousPage, nextPage, gotoPage">
                        <flux:table.cell colspan="6" class="py-6">
                            <div class="space-y-4 py-2">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="flex items-center gap-6">
                                        <div class="flex items-center gap-3 flex-1">
                                            <flux:skeleton animate="pulse" class="h-9 w-9 rounded-full shrink-0" />
                                            <flux:skeleton animate="pulse" class="h-4 w-36 rounded" />
                                        </div>
                                        <flux:skeleton animate="pulse" class="h-5 w-32 rounded-full" />
                                        <flux:skeleton animate="pulse" class="h-4 w-28 rounded hidden sm:block" />
                                        <flux:skeleton animate="pulse" class="h-4 w-20 rounded" />
                                        <flux:skeleton animate="pulse" class="h-5 w-16 rounded-full" />
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
                    @forelse ($this->trainers as $trainer)
                        <flux:table.row :key="$trainer->id" wire:loading.remove wire:target="search, statusFilter, sort, previousPage, nextPage, gotoPage">
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    @if ($trainer->photo_url)
                                        <img src="{{ $trainer->photo_url }}" alt="{{ $trainer->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700" />
                                    @else
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-500/10 font-bold text-xs text-amber-600 dark:text-amber-400">
                                            {{ mb_substr($trainer->name, 0, 2) }}
                                        </div>
                                    @endif
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $trainer->name }}
                                    </div>
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="inline-flex items-center rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $trainer->specialty }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                {{ $trainer->phone }}
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-800 dark:text-zinc-200">
                                <span class="font-semibold text-amber-600 dark:text-amber-400">
                                    {{ $trainer->pt_session_logs_count }}
                                </span> sesi
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($trainer->is_active)
                                    <flux:badge variant="success" size="sm">Aktif</flux:badge>
                                @else
                                    <flux:badge variant="subtle" size="sm">Non-Aktif</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1.5">
                                    <flux:tooltip content="Lihat Detail Trainer">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="eye"
                                            wire:click="openDetailModal({{ $trainer->id }})"
                                            aria-label="Detail Trainer"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="Ubah Data Trainer">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="pencil-square"
                                            wire:click="openEditModal({{ $trainer->id }})"
                                            aria-label="Edit Trainer"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="{{ $trainer->is_active ? 'Nonaktifkan Trainer' : 'Aktifkan Trainer' }}">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            :icon="$trainer->is_active ? 'no-symbol' : 'check'"
                                            wire:click="toggleActive({{ $trainer->id }})"
                                            :aria-label="$trainer->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="search, statusFilter, previousPage, nextPage, gotoPage">
                            <flux:table.cell colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="user-group" class="h-8 w-8 text-zinc-400" />
                                    <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                        Tidak ada data trainer
                                    </flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">
                                        Silakan tambah trainer baru atau sesuaikan filter pencarian.
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
            {{ $this->trainers->links() }}
        </div>
    </div>

    {{-- Form Modal: Create & Edit Trainer --}}
    <flux:modal wire:model="showFormModal" class="max-w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg" class="font-serif">
                    {{ $selectedTrainerId ? 'Edit Data Trainer' : 'Tambah Trainer Baru' }}
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Masukkan rincian informasi instruktur gym.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Foto Profil Trainer</flux:label>
                <div class="flex items-center gap-4">
                    @if ($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="h-16 w-16 rounded-xl object-cover border border-amber-500/40 shadow-xs" />
                    @elseif ($existingPhotoUrl)
                        <div class="relative group">
                            <img src="{{ $existingPhotoUrl }}" alt="Foto Trainer" class="h-16 w-16 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700 shadow-xs" />
                            <button
                                type="button"
                                wire:click="removePhoto"
                                wire:confirm="Hapus foto profil trainer ini?"
                                class="absolute -top-1.5 -right-1.5 bg-red-500 text-white rounded-full p-0.5 shadow hover:bg-red-600 cursor-pointer"
                                title="Hapus foto"
                            >
                                <flux:icon name="x-mark" class="size-3" />
                            </button>
                        </div>
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500">
                            <flux:icon name="user" class="size-8" />
                        </div>
                    @endif

                    <div class="flex-1 space-y-1">
                        <input
                            type="file"
                            wire:model="photo"
                            accept="image/*"
                            class="block w-full text-xs text-zinc-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-500/10 dark:file:text-amber-400 cursor-pointer"
                        />
                        <flux:error name="photo" />
                        <div class="text-[11px] text-zinc-400">JPG, PNG, atau WebP (maks. 2MB)</div>
                    </div>
                </div>
            </flux:field>

            <flux:field>
                <flux:label>Nama Lengkap</flux:label>
                <flux:input wire:model="name" placeholder="Nama lengkap instruktur..." />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Nomor HP / WhatsApp</flux:label>
                <flux:input wire:model="phone" placeholder="081234567890" />
                <flux:error name="phone" />
            </flux:field>

            <flux:field>
                <flux:label>Spesialisasi / Keahlian</flux:label>
                <flux:input wire:model="specialty" placeholder="Contoh: Hypertrophy, Fat Loss, Calisthenics..." />
                <flux:error name="specialty" />
            </flux:field>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="subtle" wire:click="$set('showFormModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $selectedTrainerId ? 'Simpan Perubahan' : 'Tambah Trainer' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Detail Modal: Trainer Profile & Session Logs --}}
    <flux:modal wire:model="showDetailModal" class="max-w-xl">
        @if ($detailTrainer)
            <div class="space-y-6">
                <div class="flex items-center gap-3 border-b border-zinc-200 pb-4 dark:border-zinc-800">
                    @if ($detailTrainer->photo_url)
                        <img src="{{ $detailTrainer->photo_url }}" alt="{{ $detailTrainer->name }}" class="h-14 w-14 shrink-0 rounded-xl object-cover border border-amber-500/30 shadow-xs" />
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500/10 font-bold text-amber-600 dark:text-amber-400">
                            {{ mb_substr($detailTrainer->name, 0, 2) }}
                        </div>
                    @endif
                    <div>
                        <flux:heading size="lg" class="font-serif">
                            {{ $detailTrainer->name }}
                        </flux:heading>
                        <div class="flex items-center gap-2 text-xs font-mono text-zinc-500">
                            <span>{{ $detailTrainer->phone }}</span>
                            <span>•</span>
                            <span class="text-amber-600 dark:text-amber-400 font-sans font-medium">{{ $detailTrainer->specialty }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-2">
                        Histori Pendampingan Sesi Terakhir
                    </div>
                    @if ($detailTrainer->ptSessionLogs->isNotEmpty())
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($detailTrainer->ptSessionLogs as $log)
                                <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-800 dark:bg-zinc-800/40">
                                    <div class="flex items-center justify-between font-medium">
                                        <span class="text-zinc-900 dark:text-zinc-100">
                                            {{ $log->member?->name ?? 'Member Tidak Diketahui' }}
                                        </span>
                                        <span class="font-mono text-zinc-500">
                                            {{ $log->used_at->format('d M Y, H:i') }} WIB
                                        </span>
                                    </div>
                                    @if ($log->notes)
                                        <div class="mt-1 text-zinc-600 dark:text-zinc-400">
                                            Catatan: {{ $log->notes }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-xs text-zinc-500 italic p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/30">
                            Belum ada riwayat sesi yang diselesaikan bersama trainer ini.
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" variant="subtle" wire:click="$set('showDetailModal', false)">
                        Tutup
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
