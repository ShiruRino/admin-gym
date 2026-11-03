<div class="space-y-8 p-1 sm:p-2">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="font-serif tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Paket Layanan
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Konfigurasi opsi langganan membership, paket kuota sesi trainer, dan bundling.
            </flux:text>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="cursor-pointer shadow-sm">
                Tambah Paket Baru
            </flux:button>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Cari berdasarkan nama paket..."
                clearable
            />
        </div>

        <div>
            <flux:select wire:model.live="typeFilter">
                <flux:select.option value="all">Semua Kategori</flux:select.option>
                <flux:select.option value="membership">Membership Gym</flux:select.option>
                <flux:select.option value="pt_session">Personal Trainer (PT)</flux:select.option>
                <flux:select.option value="bundle">Bundling (Member + PT)</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status</flux:select.option>
                <flux:select.option value="active">Paket Aktif</flux:select.option>
                <flux:select.option value="inactive">Paket Non-Aktif</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Plans Table Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="w-full overflow-x-auto">
            <flux:table class="min-w-full">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')" class="font-semibold cursor-pointer">Nama Paket</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'type'" :direction="$sortDirection" wire:click="sort('type')" class="font-semibold cursor-pointer">Kategori</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'duration_days'" :direction="$sortDirection" wire:click="sort('duration_days')" class="font-semibold cursor-pointer">Durasi / Kuota</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'price'" :direction="$sortDirection" wire:click="sort('price')" class="font-semibold cursor-pointer">Harga</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'subscriptions_count'" :direction="$sortDirection" wire:click="sort('subscriptions_count')" class="font-semibold cursor-pointer">Total Langganan</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'is_active'" :direction="$sortDirection" wire:click="sort('is_active')" class="font-semibold cursor-pointer">Status</flux:table.column>
                    <flux:table.column align="end" class="font-semibold">Aksi</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    {{-- Skeleton Loading Row --}}
                    <flux:table.row wire:loading.table wire:target="search, typeFilter, statusFilter, sort, previousPage, nextPage, gotoPage">
                        <flux:table.cell colspan="7" class="py-6">
                            <div class="space-y-4 py-2">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="flex items-center gap-6">
                                        <div class="space-y-1.5 flex-1">
                                            <flux:skeleton animate="pulse" class="h-4 w-44 rounded" />
                                            <flux:skeleton animate="pulse" class="h-3 w-60 rounded" />
                                        </div>
                                        <flux:skeleton animate="pulse" class="h-5 w-24 rounded-full" />
                                        <flux:skeleton animate="pulse" class="h-4 w-20 rounded hidden sm:block" />
                                        <flux:skeleton animate="pulse" class="h-4 w-24 rounded font-mono" />
                                        <flux:skeleton animate="pulse" class="h-4 w-16 rounded hidden md:block" />
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
                    @forelse ($this->plans as $plan)
                        <flux:table.row :key="$plan->id" wire:loading.remove wire:target="search, typeFilter, statusFilter, sort, previousPage, nextPage, gotoPage">
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $plan->name }}
                                </div>
                                @if ($plan->description)
                                    <div class="text-xs text-zinc-500 truncate max-w-xs mt-0.5">
                                        {{ $plan->description }}
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($plan->type->value === 'membership')
                                    <flux:badge variant="subtle" size="sm">Membership</flux:badge>
                                @elseif ($plan->type->value === 'pt_session')
                                    <flux:badge variant="warning" size="sm">Sesi PT</flux:badge>
                                @else
                                    <flux:badge variant="success" size="sm">Bundling</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-700 dark:text-zinc-300">
                                @if ($plan->type->value === 'membership')
                                    <span>{{ $plan->duration_days }} hari</span>
                                @elseif ($plan->type->value === 'pt_session')
                                    <span>{{ $plan->pt_sessions_count }} sesi</span>
                                @else
                                    <span>{{ $plan->duration_days }} hari + {{ $plan->pt_sessions_count }} PT</span>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100 tabular-nums">
                                Rp {{ number_format((float) $plan->price, 0, ',', '.') }}
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $plan->subscriptions_count }} kali
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($plan->is_active)
                                    <flux:badge variant="success" size="sm">Aktif</flux:badge>
                                @else
                                    <flux:badge variant="subtle" size="sm">Non-Aktif</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1.5">
                                    <flux:tooltip content="Lihat Detail Paket">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="eye"
                                            wire:click="openDetailModal({{ $plan->id }})"
                                            aria-label="Detail Paket"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="Ubah Data Paket">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="pencil-square"
                                            wire:click="openEditModal({{ $plan->id }})"
                                            aria-label="Edit Paket"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>

                                    <flux:tooltip content="{{ $plan->is_active ? 'Nonaktifkan Penjualan' : 'Aktifkan Penjualan' }}">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            :icon="$plan->is_active ? 'no-symbol' : 'check'"
                                            wire:click="toggleActive({{ $plan->id }})"
                                            :aria-label="$plan->is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="search, typeFilter, statusFilter, previousPage, nextPage, gotoPage">
                            <flux:table.cell colspan="7" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="ticket" class="h-8 w-8 text-zinc-400" />
                                    <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                        Tidak ada data paket layanan
                                    </flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">
                                        Silakan buat paket baru atau sesuaikan filter.
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
            {{ $this->plans->links() }}
        </div>
    </div>

    {{-- Form Modal: Create & Edit Plan --}}
    <flux:modal wire:model="showFormModal" class="max-w-md">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg" class="font-serif">
                    {{ $selectedPlanId ? 'Edit Data Paket' : 'Tambah Paket Baru' }}
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Tentukan rincian paket langganan atau kuota sesi PT.
                </flux:text>
            </div>

            <flux:field>
                <flux:label>Nama Paket</flux:label>
                <flux:input wire:model="name" placeholder="Contoh: Gym Regular 1 Bulan..." />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Kategori Paket</flux:label>
                <flux:select wire:model.live="type">
                    <flux:select.option value="membership">Membership Gym (Durasi Hari)</flux:select.option>
                    <flux:select.option value="pt_session">Personal Trainer (Kuota Sesi)</flux:select.option>
                    <flux:select.option value="bundle">Bundling (Membership + Sesi PT)</flux:select.option>
                </flux:select>
                <flux:error name="type" />
            </flux:field>

            @if ($type === 'membership' || $type === 'bundle')
                <flux:field>
                    <flux:label>Durasi Membership (Jumlah Hari)</flux:label>
                    <flux:input type="number" wire:model="duration_days" placeholder="Contoh: 30" min="1" />
                    <flux:error name="duration_days" />
                </flux:field>
            @endif

            @if ($type === 'pt_session' || $type === 'bundle')
                <flux:field>
                    <flux:label>Kuota Sesi Personal Trainer</flux:label>
                    <flux:input type="number" wire:model="pt_sessions_count" placeholder="Contoh: 10" min="1" />
                    <flux:error name="pt_sessions_count" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Harga Paket (Rp)</flux:label>
                <flux:input type="number" wire:model="price" placeholder="350000" min="0" step="1000" />
                <flux:error name="price" />
            </flux:field>

            <flux:field>
                <flux:label>Deskripsi / Benefit Paket (Opsional)</flux:label>
                <flux:textarea wire:model="description" rows="2" placeholder="Fasilitas yang didapat..." />
                <flux:error name="description" />
            </flux:field>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="subtle" wire:click="$set('showFormModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $selectedPlanId ? 'Simpan Perubahan' : 'Buat Paket' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Detail Modal: Plan Information --}}
    <flux:modal wire:model="showDetailModal" class="max-w-md">
        @if ($detailPlan)
            <div class="space-y-6">
                <div class="border-b border-zinc-200 pb-3 dark:border-zinc-800">
                    <div class="text-xs uppercase tracking-wider font-semibold text-zinc-500 mb-1">
                        {{ $detailPlan->type->label() }}
                    </div>
                    <flux:heading size="lg" class="font-serif">
                        {{ $detailPlan->name }}
                    </flux:heading>
                    <div class="text-xl font-mono font-bold text-amber-500 mt-2">
                        Rp {{ number_format((float) $detailPlan->price, 0, ',', '.') }}
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-800 space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Masa Aktif Membership:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $detailPlan->duration_days ? $detailPlan->duration_days.' hari' : 'Tidak Termasuk' }}
                        </span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Kuota Sesi PT:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $detailPlan->pt_sessions_count ? $detailPlan->pt_sessions_count.' sesi' : 'Tidak Termasuk' }}
                        </span>
                    </div>

                    <div class="flex justify-between py-1 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Total Pernah Terjual:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100 font-mono">
                            {{ $detailPlan->invoices_count }} transaksi
                        </span>
                    </div>

                    <div class="flex justify-between py-1">
                        <span class="text-zinc-500">Status Penjualan:</span>
                        <span>
                            @if ($detailPlan->is_active)
                                <flux:badge variant="success" size="sm">Aktif Dijual</flux:badge>
                            @else
                                <flux:badge variant="subtle" size="sm">Non-Aktif</flux:badge>
                            @endif
                        </span>
                    </div>
                </div>

                @if ($detailPlan->description)
                    <div class="text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 p-3 rounded-lg dark:bg-zinc-800/40">
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300 block mb-1">Keterangan:</span>
                        {{ $detailPlan->description }}
                    </div>
                @endif

                <div class="flex justify-end pt-3 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" variant="subtle" wire:click="$set('showDetailModal', false)">
                        Tutup
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
