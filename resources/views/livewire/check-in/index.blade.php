<div class="space-y-6" x-data="{
    playChime() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) {
            console.log('Audio not supported or blocked:', e);
        }
    }
}" @play-checkin-chime.window="playChime()">
    {{-- Page Header --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" level="1" class="font-serif">
                Terminal Presensi & Check-in
            </flux:heading>
            <flux:text size="sm" class="text-zinc-500">
                Pencatatan kehadiran member harian, verifikasi keaktifan kartu langganan, dan pemantauan realtime meja depan.
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:badge variant="subtle" size="lg" icon="clock" class="font-mono text-xs">
                {{ now()->format('d M Y') }}
            </flux:badge>
            <flux:button variant="subtle" icon="camera" wire:click="$set('showCameraModal', true)" class="cursor-pointer shadow-xs">
                Scan Kamera Web
            </flux:button>
        </div>
    </div>

    {{-- Split Layout Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
        {{-- SISI KIRI: INPUT SCAN & STATUS VERIFIKASI (7 Kolom Desktop) --}}
        <div class="space-y-5 lg:col-span-7">
            {{-- Omni Search / Scan Box --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900 relative">
                <label for="scanner-input" class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-2">
                    Scan Barcode / QR atau Cari Member
                </label>

                <div class="relative">
                    <flux:input
                        id="scanner-input"
                        wire:model.live.debounce.250ms="searchInput"
                        wire:keydown.enter="handleScanOrEnter"
                        placeholder="Scan QR code atau ketik nama/nomor HP/kode..."
                        icon="qr-code"
                        autofocus
                        class="text-base py-3"
                    />

                    @if ($searchInput !== '')
                        <button
                            type="button"
                            wire:click="clearSelection"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        >
                            Reset
                        </button>
                    @endif
                </div>

                <div class="mt-2 flex items-center justify-between text-[11px] text-zinc-400">
                    <span>💡 Alat scanner barcode USB otomatis mengetik & menekan Enter.</span>
                    <span>Tekan <kbd class="rounded border border-zinc-300 px-1 py-0.5 font-mono text-[10px] dark:border-zinc-700">Enter</kbd> untuk verifikasi</span>
                </div>

                {{-- Autocomplete Dropdown Suggestions --}}
                @if ($this->searchSuggestions->isNotEmpty())
                    <div class="absolute left-0 right-0 top-full z-30 mt-2 max-h-72 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-1.5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="px-3 py-1.5 text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                            Hasil Pencarian Member
                        </div>
                        @foreach ($this->searchSuggestions as $item)
                            <button
                                type="button"
                                wire:click="selectMember({{ $item->id }})"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm transition hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer"
                            >
                                <div class="flex items-center gap-3">
                                    @if ($item->photo_url)
                                        <img src="{{ $item->photo_url }}" alt="{{ $item->name }}" class="h-8 w-8 shrink-0 rounded-full object-cover border border-zinc-200 dark:border-zinc-700" />
                                    @else
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500/10 font-bold text-xs text-amber-600 dark:text-amber-400">
                                            {{ mb_substr($item->name, 0, 2) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item->name }}
                                        </div>
                                        <div class="font-mono text-xs text-zinc-500">
                                            {{ $item->member_code }} • {{ $item->phone }}
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    @php $st = $item->membership_status; @endphp
                                    @if ($st === 'active')
                                        <flux:badge variant="success" size="sm">Aktif</flux:badge>
                                    @elseif ($st === 'expiring_soon')
                                        <flux:badge variant="warning" size="sm">Exp. Soon</flux:badge>
                                    @elseif ($st === 'expired')
                                        <flux:badge variant="danger" size="sm">Expired</flux:badge>
                                    @else
                                        <flux:badge variant="subtle" size="sm">Non-Aktif</flux:badge>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- KARTU STATUS VERIFIKASI MEMBER --}}
            @if ($member = $this->selectedMember)
                @php
                    $status = $member->membership_status;
                    $sub = $member->activeSubscription;
                    $alreadyCheckedIn = app(\App\Services\AttendanceService::class)->hasCheckedInToday($member);
                @endphp

                <div class="rounded-xl border p-6 shadow-sm transition-all {{ $status === 'active' || $status === 'expiring_soon' ? 'border-emerald-500/40 bg-emerald-500/5 dark:bg-emerald-950/20' : 'border-red-500/40 bg-red-500/5 dark:bg-red-950/20' }}">
                    {{-- Status Banner Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-zinc-200 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            @if ($member->photo_url)
                                <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" class="h-16 w-16 shrink-0 rounded-2xl object-cover border-2 border-emerald-500/40 shadow-sm" />
                            @else
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-zinc-100 font-bold text-lg text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 shadow-inner">
                                    {{ mb_substr($member->name, 0, 2) }}
                                </div>
                            @endif
                            <div>
                                <flux:heading size="lg" class="font-serif text-zinc-900 dark:text-zinc-100">
                                    {{ $member->name }}
                                </flux:heading>
                                <div class="flex items-center gap-2 font-mono text-xs text-zinc-500">
                                    <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $member->member_code }}</span>
                                    <span>•</span>
                                    <span>{{ $member->phone }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            @if ($status === 'active')
                                <flux:badge variant="success" size="lg" icon="check-circle" class="px-3 py-1 font-semibold">
                                    MEMBERSHIP AKTIF (VALID)
                                </flux:badge>
                            @elseif ($status === 'expiring_soon')
                                <flux:badge variant="warning" size="lg" icon="exclamation-triangle" class="px-3 py-1 font-semibold">
                                    HABIS DALAM {{ now()->diffInDays($sub?->end_date, false) }} HARI
                                </flux:badge>
                            @elseif ($status === 'expired')
                                <flux:badge variant="danger" size="lg" icon="x-circle" class="px-3 py-1 font-semibold">
                                    MEMBERSHIP KEDALUWARSA
                                </flux:badge>
                            @else
                                <flux:badge variant="subtle" size="lg" icon="minus-circle" class="px-3 py-1 font-semibold">
                                    MEMBER NON-AKTIF
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    {{-- Warning jika sudah check in hari ini --}}
                    @if ($alreadyCheckedIn)
                        <div class="mt-4 flex items-center gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-300">
                            <flux:icon name="information-circle" class="h-4 w-4 shrink-0" />
                            <span><strong>Pemberitahuan:</strong> Member ini sudah melakukan check-in hari ini. Check-in ulang akan dicatat sebagai kunjungan sesi tambahan.</span>
                        </div>
                    @endif

                    {{-- Details Info Grid --}}
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Paket Langganan Gym --}}
                        <div class="rounded-lg border border-zinc-200 bg-white/70 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/60">
                            <div class="text-[11px] uppercase tracking-wider text-zinc-500 font-semibold">
                                Paket Langganan Gym
                            </div>
                            @if ($sub)
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $sub->plan?->name ?? 'Langganan Membership' }}
                                </div>
                                <div class="mt-0.5 font-mono text-xs text-zinc-500">
                                    Masa aktif: {{ $sub->start_date->format('d/m/Y') }} s/d {{ $sub->end_date->format('d/m/Y') }}
                                </div>
                                <div class="mt-1 text-xs {{ $status === 'expired' ? 'text-red-500 font-semibold' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    @if ($status === 'expired')
                                        Kedaluwarsa sejak {{ $sub->end_date->diffForHumans() }}
                                    @else
                                        Sisa {{ now()->diffInDays($sub->end_date, false) }} hari lagi
                                    @endif
                                </div>
                            @else
                                <div class="mt-1 text-sm text-red-500 font-medium">
                                    Belum memiliki paket langganan aktif.
                                </div>
                            @endif
                        </div>

                        {{-- Saldo Sesi PT --}}
                        <div class="rounded-lg border border-zinc-200 bg-white/70 p-3.5 dark:border-zinc-800 dark:bg-zinc-900/60">
                            <div class="text-[11px] uppercase tracking-wider text-zinc-500 font-semibold">
                                Saldo Personal Trainer
                            </div>
                            @php
                                $remPt = $member->total_remaining_pt_sessions;
                                $totPt = $member->total_pt_sessions;
                            @endphp
                            @if ($remPt > 0)
                                <div class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">
                                    <span class="font-mono text-xl text-amber-500">{{ $remPt }}</span>
                                    <span class="text-xs text-zinc-500 font-normal">/ {{ $totPt }} sesi tersedia</span>
                                </div>
                                <div class="mt-0.5 text-xs text-zinc-500">
                                    Dapat digunakan bersama trainer hari ini
                                </div>
                            @else
                                <div class="mt-1 text-sm text-zinc-400">
                                    Tidak ada kuota sesi PT aktif.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Form Catatan & Tombol Aksi --}}
                    <div class="mt-5 space-y-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                        <flux:field>
                            <flux:label class="text-xs">Catatan Kunjungan (Opsional)</flux:label>
                            <flux:input wire:model="checkInNotes" placeholder="Misal: Latihan pagi, bawa teman, loker 12..." size="sm" />
                        </flux:field>

                        @if ($status === 'expired')
                            <div class="rounded-lg border border-red-500/20 bg-red-500/10 p-3 space-y-2">
                                <div class="text-xs text-red-700 dark:text-red-300 font-medium">
                                    ⚠️ Keanggotaan sudah habis. Arahkan member untuk memperpanjang paket gym.
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <flux:button variant="primary" icon="banknotes" :href="route('invoices.index')" wire:navigate class="cursor-pointer text-xs">
                                        Perpanjang / Buat Tagihan
                                    </flux:button>

                                    <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300 cursor-pointer">
                                        <input type="checkbox" wire:model.live="allowExpiredOverride" class="rounded border-zinc-300 text-amber-600 focus:ring-amber-500 dark:border-zinc-700" />
                                        <span>Bypass / Izinkan Check-in Darurat</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-2">
                            <flux:button variant="ghost" wire:click="clearSelection" class="cursor-pointer">
                                Batalkan (Pilih Member Lain)
                            </flux:button>

                            <flux:button
                                variant="{{ $status === 'active' || $status === 'expiring_soon' || $allowExpiredOverride ? 'primary' : 'subtle' }}"
                                icon="check"
                                wire:click="executeCheckIn"
                                @class([
                                    'cursor-pointer px-6 py-3 font-semibold text-sm shadow-md',
                                    'opacity-50 pointer-events-none' => ($status === 'expired' && ! $allowExpiredOverride) || $status === 'inactive',
                                ])
                            >
                                Catat Check-in Masuk (Enter)
                            </flux:button>
                        </div>
                    </div>
                </div>
            @else
                {{-- State Default Saat Belum Ada Member Dipilih --}}
                @if ($lastCheckedInMember)
                    <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/5 p-4 text-emerald-800 dark:text-emerald-200 text-xs flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-2">
                            <flux:icon name="check-circle" class="h-5 w-5 text-emerald-500 shrink-0" />
                            <div>
                                Check-in Terakhir Berhasil: <strong>{{ $lastCheckedInMember['name'] }}</strong> ({{ $lastCheckedInMember['member_code'] }}) pada pukul {{ $lastCheckedInMember['time'] }} WIB.
                            </div>
                        </div>
                        <flux:badge variant="success" size="sm">Berhasil Masuk</flux:badge>
                    </div>
                @endif

                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/50 p-10 text-center dark:border-zinc-800 dark:bg-zinc-900/30">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                        <flux:icon name="qr-code" class="h-8 w-8" />
                    </div>
                    <flux:heading size="lg" class="mt-4 font-serif">
                        Menunggu Scan Member
                    </flux:heading>
                    <flux:text size="sm" class="mt-1 text-zinc-500 max-w-sm mx-auto">
                        Silakan scan kartu/QR code member dengan alat scanner USB, atau cari nama/nomor HP pada kotak pencarian di atas.
                    </flux:text>
                </div>
            @endif
        </div>

        {{-- SISI KANAN: LOG KEHADIRAN HARI INI (5 Kolom Desktop) --}}
        <div class="space-y-4 lg:col-span-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <flux:heading size="base" class="font-semibold">
                            Presensi Hari Ini
                        </flux:heading>
                        <flux:text size="xs" class="text-zinc-500 font-mono">
                            {{ now()->format('l, d M Y') }}
                        </flux:text>
                    </div>

                    <flux:badge variant="primary" size="lg" class="font-mono font-bold">
                        {{ $this->todayCount }} Hadir
                    </flux:badge>
                </div>

                {{-- Filter Log Hari Ini --}}
                <div class="mt-3">
                    <flux:input
                        wire:model.live.debounce.250ms="todaySearch"
                        placeholder="Cari member yang sudah hadir hari ini..."
                        icon="magnifying-glass"
                        size="sm"
                    />
                </div>

                {{-- Feed List Kehadiran Hari Ini --}}
                <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800 max-h-[520px] overflow-y-auto pr-1">
                    @forelse ($this->todayAttendances as $att)
                        <div class="py-3 flex items-start justify-between gap-3 first:pt-0 last:pb-0" wire:key="att-{{ $att->id }}">
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 font-bold text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ mb_substr($att->member->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="font-medium text-xs text-zinc-900 dark:text-zinc-100">
                                        {{ $att->member->name }}
                                    </div>
                                    <div class="font-mono text-[11px] text-zinc-400">
                                        {{ $att->member->member_code }}
                                    </div>
                                    @if ($att->notes)
                                        <div class="text-[10px] text-zinc-500 italic mt-0.5">
                                            "{{ $att->notes }}"
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $att->check_in_time->format('H:i') }} WIB
                                </div>
                                <div class="text-[10px] text-zinc-400">
                                    {{ $att->verifiedBy?->name ?? 'Staf' }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-zinc-400 text-xs">
                            <flux:icon name="clock" class="h-6 w-6 mx-auto mb-1 opacity-50" />
                            Belum ada member yang check-in hari ini.
                        </div>
                    @endforelse
                </div>

                @if ($this->todayAttendances->hasPages())
                    <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $this->todayAttendances->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL KAMERA SCANNER --}}
    <flux:modal wire:model="showCameraModal" class="max-w-md" x-data="{
        scanning: false,
        videoStream: null,
        errorMsg: '',
        startCamera() {
            this.errorMsg = '';
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.errorMsg = 'Browser Anda tidak mendukung akses kamera.';
                return;
            }

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(stream => {
                    this.videoStream = stream;
                    this.$refs.video.srcObject = stream;
                    this.$refs.video.setAttribute('playsinline', true);
                    this.$refs.video.play();
                    this.scanning = true;
                    this.detectBarcode();
                })
                .catch(err => {
                    console.error('Camera error:', err);
                    this.errorMsg = 'Gagal mengakses kamera. Pastikan izin kamera telah diberikan.';
                });
        },
        stopCamera() {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(track => track.stop());
                this.videoStream = null;
            }
            this.scanning = false;
        },
        async detectBarcode() {
            if (!this.scanning) return;

            if ('BarcodeDetector' in window) {
                try {
                    const detector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39'] });
                    const barcodes = await detector.detect(this.$refs.video);
                    if (barcodes.length > 0) {
                        const code = barcodes[0].rawValue;
                        this.stopCamera();
                        $wire.handleCameraScan(code);
                        return;
                    }
                } catch (e) {
                    console.error('Detection error:', e);
                }
            }

            if (this.scanning) {
                requestAnimationFrame(() => this.detectBarcode());
            }
        }
    }" x-init="$watch('$wire.showCameraModal', value => { if (value) { startCamera(); } else { stopCamera(); } })">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg" class="font-serif">
                    Scan Kartu Member via Kamera
                </flux:heading>
                <flux:text size="sm" class="text-zinc-500">
                    Arahkan kamera ke QR Code di layar HP atau kartu member.
                </flux:text>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-black aspect-video flex items-center justify-center">
                <video x-ref="video" class="w-full h-full object-cover"></video>

                {{-- Target Box Overlay --}}
                <div class="absolute inset-0 border-2 border-amber-500/60 rounded-xl m-10 pointer-events-none flex items-center justify-center animate-pulse">
                    <span class="text-[10px] text-amber-400 font-mono bg-black/60 px-2 py-0.5 rounded">Arahkan QR ke sini</span>
                </div>

                <div x-show="errorMsg" class="absolute inset-0 bg-zinc-900/90 p-4 text-center text-xs text-red-400 flex flex-col items-center justify-center gap-2">
                    <flux:icon name="exclamation-circle" class="h-6 w-6" />
                    <span x-text="errorMsg"></span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <flux:button variant="ghost" wire:click="$set('showCameraModal', false)">
                    Tutup
                </flux:button>
                <flux:button variant="primary" icon="arrow-path" x-on:click="startCamera" class="cursor-pointer">
                    Muat Ulang Kamera
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

