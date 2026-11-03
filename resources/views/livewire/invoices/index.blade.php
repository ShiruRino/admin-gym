<div class="space-y-8 p-1 sm:p-2">
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
            <flux:heading size="xl" class="font-serif tracking-tight text-zinc-900 dark:text-zinc-100">
                Transaksi & Tagihan
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Pencatatan pembayaran kasir, aktivasi otomatis masa langganan gym, dan kwitansi resmi.
            </flux:text>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal" class="cursor-pointer shadow-sm">
                Catat Transaksi Baru
            </flux:button>
        </div>
    </div>

    {{-- Filter & Search Toolbar --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="Cari nomor invoice, nama member, atau kode..."
                clearable
            />
        </div>

        <div>
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">Semua Status Bayar</flux:select.option>
                <flux:select.option value="paid">Lunas</flux:select.option>
                <flux:select.option value="unpaid">Belum Lunas</flux:select.option>
                <flux:select.option value="cancelled">Dibatalkan</flux:select.option>
            </flux:select>
        </div>

        <div>
            <flux:select wire:model.live="methodFilter">
                <flux:select.option value="all">Semua Metode</flux:select.option>
                <flux:select.option value="cash">Tunai (Cash)</flux:select.option>
                <flux:select.option value="transfer">Transfer Bank</flux:select.option>
                <flux:select.option value="qris">QRIS</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Invoices Table Card --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
        <div class="w-full overflow-x-auto">
            <flux:table class="min-w-full">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'invoice_number'" :direction="$sortDirection" wire:click="sort('invoice_number')" class="font-semibold cursor-pointer">
                        No. Invoice
                    </flux:table.column>
                    <flux:table.column class="font-semibold">
                        Nama Member
                    </flux:table.column>
                    <flux:table.column class="font-semibold">
                        Paket Layanan
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')" class="font-semibold cursor-pointer">
                        Total Bayar
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'payment_method'" :direction="$sortDirection" wire:click="sort('payment_method')" class="font-semibold cursor-pointer">
                        Metode
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'payment_status'" :direction="$sortDirection" wire:click="sort('payment_status')" class="font-semibold cursor-pointer">
                        Status
                    </flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'paid_at'" :direction="$sortDirection" wire:click="sort('paid_at')" class="font-semibold cursor-pointer">
                        Waktu Bayar
                    </flux:table.column>
                    <flux:table.column align="end" class="font-semibold">
                        Aksi
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    {{-- Skeleton Loading Row --}}
                    <flux:table.row wire:loading.table wire:target="search, statusFilter, methodFilter, sort, previousPage, nextPage, gotoPage">
                        <flux:table.cell colspan="8" class="py-6">
                            <div class="space-y-4 py-2">
                                @for ($i = 0; $i < 4; $i++)
                                    <div class="flex items-center gap-6">
                                        <flux:skeleton animate="pulse" class="h-4 w-28 rounded font-mono" />
                                        <div class="space-y-1.5 flex-1">
                                            <flux:skeleton animate="pulse" class="h-4 w-36 rounded" />
                                            <flux:skeleton animate="pulse" class="h-3 w-20 rounded" />
                                        </div>
                                        <flux:skeleton animate="pulse" class="h-4 w-32 rounded hidden sm:block" />
                                        <flux:skeleton animate="pulse" class="h-4 w-24 rounded font-mono" />
                                        <flux:skeleton animate="pulse" class="h-5 w-20 rounded-full" />
                                        <flux:skeleton animate="pulse" class="h-5 w-16 rounded-full" />
                                        <flux:skeleton animate="pulse" class="h-4 w-24 rounded hidden md:block" />
                                        <div class="flex gap-2 ml-auto">
                                            <flux:skeleton animate="pulse" class="h-7 w-7 rounded" />
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </flux:table.cell>
                    </flux:table.row>

                    {{-- Data Rows --}}
                    @forelse ($this->invoices as $invoice)
                        <flux:table.row :key="$invoice->id" wire:loading.remove wire:target="search, statusFilter, methodFilter, sort, previousPage, nextPage, gotoPage">
                            <flux:table.cell class="font-mono text-xs font-semibold text-amber-600 dark:text-amber-400">
                                {{ $invoice->invoice_number }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $invoice->member->name }}
                                </div>
                                <div class="font-mono text-xs text-zinc-500">
                                    {{ $invoice->member->member_code }}
                                </div>
                            </flux:table.cell>

                            <flux:table.cell>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $invoice->plan?->name ?? 'Paket Khusus' }}
                                </div>
                                @if ($invoice->plan)
                                    <div class="text-xs text-zinc-500">
                                        {{ $invoice->plan->type->label() }}
                                    </div>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100 tabular-nums">
                                Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                <span class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $invoice->payment_method->label() }}
                                </span>
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($invoice->payment_status->value === 'paid')
                                    <flux:badge variant="success" size="sm">Lunas</flux:badge>
                                @elseif ($invoice->payment_status->value === 'unpaid')
                                    <flux:badge variant="warning" size="sm">Belum Lunas</flux:badge>
                                @else
                                    <flux:badge variant="danger" size="sm">Dibatalkan</flux:badge>
                                @endif
                            </flux:table.cell>

                            <flux:table.cell class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                {{ $invoice->paid_at ? $invoice->paid_at->format('d M Y H:i') : '-' }}
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <div class="flex items-center justify-end gap-1.5">
                                    <flux:tooltip content="Lihat & Cetak Kwitansi">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            icon="document-text"
                                            wire:click="openDetailModal({{ $invoice->id }})"
                                            aria-label="Detail Kwitansi"
                                            class="cursor-pointer"
                                        />
                                    </flux:tooltip>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="search, statusFilter, methodFilter, sort, previousPage, nextPage, gotoPage">
                            <flux:table.cell colspan="8" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="banknotes" class="h-8 w-8 text-zinc-400" />
                                    <flux:heading size="base" class="text-zinc-600 dark:text-zinc-300">
                                        Tidak ada transaksi tagihan
                                    </flux:heading>
                                    <flux:text size="sm" class="text-zinc-500">
                                        Belum ada data pembayaran atau sesuaikan kata kunci pencarian.
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
            {{ $this->invoices->links() }}
        </div>
    </div>

    {{-- Modal: Catat Transaksi Baru --}}
    <flux:modal wire:model="showCreateModal" class="max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg" class="font-serif">
                    Catat Transaksi Pembayaran
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Sistem otomatis mengaktifkan paket membership dan kuota PT ke akun member.
                </flux:text>
            </div>

            <div class="space-y-4">
                {{-- Pilih Member --}}
                <flux:field>
                    <flux:label>Pilih Member <span class="text-red-500">*</span></flux:label>
                    <x-select2 wire:model.live="member_id" placeholder="Cari nama, nomor HP, atau kode member...">
                        @foreach ($this->availableMembers as $m)
                            <option value="{{ $m->id }}">
                                {{ $m->name }} ({{ $m->member_code }} - {{ $m->phone }})
                            </option>
                        @endforeach
                    </x-select2>
                    <flux:error name="member_id" />
                </flux:field>

                {{-- Pilih Paket --}}
                <flux:field>
                    <flux:label>Pilih Paket Layanan <span class="text-red-500">*</span></flux:label>
                    <x-select2 wire:model.live="plan_id" placeholder="Pilih paket membership atau PT...">
                        @foreach ($this->availablePlans as $p)
                            <option value="{{ $p->id }}">
                                {{ $p->name }} — Rp {{ number_format((float) $p->price, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </x-select2>
                    <flux:error name="plan_id" />
                </flux:field>

                {{-- Informasi Otomatisasi Paket --}}
                @if ($this->selectedPlan)
                    <div class="rounded-lg border border-amber-500/20 bg-amber-500/5 p-3 text-xs space-y-1.5 text-zinc-700 dark:text-zinc-300">
                        <div class="font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <flux:icon name="sparkles" class="size-4 shrink-0" />
                            Manfaat Paket yang Diaktifkan:
                        </div>
                        @if ($this->selectedPlan->duration_days)
                            <div>• Masa aktif membership bertambah: <strong>{{ $this->selectedPlan->duration_days }} hari</strong> (akumulatif jika masih aktif).</div>
                        @endif
                        @if ($this->selectedPlan->pt_sessions_count)
                            <div>• Saldo kuota sesi Personal Trainer: <strong>+{{ $this->selectedPlan->pt_sessions_count }} sesi</strong> latihan.</div>
                        @endif
                    </div>
                @endif

                {{-- Nominal & Metode --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Nominal Bayar (Rp) <span class="text-red-500">*</span></flux:label>
                        <flux:input wire:model="amount" placeholder="Contoh: 350000" type="number" step="1000" />
                        <flux:error name="amount" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Metode Pembayaran <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="payment_method">
                            <flux:select.option value="cash">Tunai (Cash)</flux:select.option>
                            <flux:select.option value="transfer">Transfer Bank</flux:select.option>
                            <flux:select.option value="qris">QRIS</flux:select.option>
                        </flux:select>
                        <flux:error name="payment_method" />
                    </flux:field>
                </div>

                {{-- Catatan --}}
                <flux:field>
                    <flux:label>Catatan Pembayaran (Opsional)</flux:label>
                    <flux:input wire:model="notes" placeholder="Misal: Bukti transfer BCA, diskon promo, dsb." />
                    <flux:error name="notes" />
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">
                    Batal
                </flux:button>
                <flux:button type="submit" variant="primary" class="cursor-pointer">
                    Simpan & Aktifkan Paket
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal: Detail & Kwitansi Resmi Pembayaran --}}
    <flux:modal wire:model="showDetailModal" class="max-w-md">
        @if ($detailInvoice)
            <div class="space-y-6 printable-receipt">
                {{-- Header Kwitansi --}}
                <div class="text-center border-b border-zinc-200 dark:border-zinc-800 pb-4">
                    <div class="font-serif text-xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                        {{ config('app.name', 'Gym Management') }}
                    </div>
                    <div class="text-xs text-zinc-500 uppercase tracking-widest mt-0.5">
                        Kwitansi Resmi Pembayaran
                    </div>
                    <div class="font-mono text-sm font-semibold text-amber-600 dark:text-amber-400 mt-2">
                        {{ $detailInvoice->invoice_number }}
                    </div>
                </div>

                {{-- Informasi Member & Waktu --}}
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Nama Member:</span>
                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $detailInvoice->member->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Kode Member:</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ $detailInvoice->member->member_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Waktu Bayar:</span>
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">
                            {{ $detailInvoice->paid_at ? $detailInvoice->paid_at->format('d M Y, H:i') : '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Kasir / Penerima:</span>
                        <span class="text-zinc-800 dark:text-zinc-200">{{ $detailInvoice->receivedBy?->name ?? 'Admin' }}</span>
                    </div>
                </div>

                {{-- Detail Item & Rincian Paket --}}
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-3 space-y-2 text-xs border border-zinc-200/60 dark:border-zinc-700/60">
                    <div class="flex justify-between font-medium">
                        <span class="text-zinc-800 dark:text-zinc-200">{{ $detailInvoice->plan?->name ?? 'Paket Kebugaran' }}</span>
                        <span class="font-mono font-semibold text-zinc-900 dark:text-zinc-100 tabular-nums">
                            Rp {{ number_format((float) $detailInvoice->amount, 0, ',', '.') }}
                        </span>
                    </div>

                    @if ($detailInvoice->subscription)
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700/60 text-[11px] text-zinc-500 space-y-0.5">
                            <div class="text-emerald-600 dark:text-emerald-400 font-medium">✓ Akses Gym Diperpanjang:</div>
                            <div class="font-mono">{{ $detailInvoice->subscription->start_date->format('d/m/Y') }} s/d {{ $detailInvoice->subscription->end_date->format('d/m/Y') }}</div>
                        </div>
                    @endif

                    @if ($detailInvoice->ptQuota)
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700/60 text-[11px] text-zinc-500 space-y-0.5">
                            <div class="text-amber-600 dark:text-amber-400 font-medium">✓ Kuota Personal Trainer:</div>
                            <div class="font-mono">+{{ $detailInvoice->ptQuota->total_sessions }} Sesi Latihan</div>
                        </div>
                    @endif
                </div>

                {{-- Total & Metode Pembayaran --}}
                <div class="border-t border-dashed border-zinc-300 dark:border-zinc-700 pt-3 space-y-2">
                    <div class="flex justify-between text-sm font-semibold">
                        <span class="text-zinc-700 dark:text-zinc-300">Total Dibayar</span>
                        <span class="font-mono text-base text-zinc-900 dark:text-zinc-100 tabular-nums">
                            Rp {{ number_format((float) $detailInvoice->amount, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-500">
                        <span>Metode:</span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $detailInvoice->payment_method->label() }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-500">
                        <span>Status:</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold uppercase tracking-wider">● LUNAS</span>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" variant="ghost" wire:click="$set('showDetailModal', false)">
                        Tutup
                    </flux:button>
                    <flux:button type="button" variant="primary" icon="printer" onclick="window.print()" class="cursor-pointer">
                        Cetak Kwitansi
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>

