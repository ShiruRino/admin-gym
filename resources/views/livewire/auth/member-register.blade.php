<div class="flex flex-col gap-6">
    @if ($registeredMember)
        {{-- Success State --}}
        <div class="space-y-6 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                <flux:icon name="check-circle" class="h-8 w-8" />
            </div>

            <div class="space-y-1">
                <flux:heading size="xl" class="font-serif text-zinc-900 dark:text-zinc-100">
                    Pendaftaran Berhasil!
                </flux:heading>
                <flux:text class="text-zinc-500 text-sm">
                    Selamat bergabung, <strong class="text-zinc-800 dark:text-zinc-200">{{ $registeredMember->name }}</strong>. Akun keanggotaan Anda telah aktif.
                </flux:text>
            </div>

            {{-- Digital Card Preview --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-950 p-6 text-left text-white shadow-xl border border-amber-500/30">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-700/60">
                    <div class="flex items-center gap-2">
                        <flux:icon name="bolt" class="h-4 w-4 text-amber-400" />
                        <span class="font-bold tracking-widest text-[11px] uppercase text-amber-400">GymHub Digital Card</span>
                    </div>
                    <flux:badge variant="primary" size="sm" class="font-mono text-[10px]">AKTIF</flux:badge>
                </div>

                <div class="mt-4 flex items-center justify-between gap-4">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-zinc-400 font-medium">Kode Member</div>
                        <div class="font-mono text-base font-bold text-amber-400 mt-0.5">
                            {{ $registeredMember->member_code }}
                        </div>

                        <div class="mt-3 text-[11px] uppercase tracking-wider text-zinc-400 font-medium">Email Terdaftar</div>
                        <div class="text-xs text-zinc-200 truncate max-w-[180px]">
                            {{ $registeredMember->email }}
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-2 shadow-md shrink-0 flex items-center justify-center">
                        <img
                            src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&margin=0&data={{ urlencode($registeredMember->member_code) }}"
                            alt="QR Code Member"
                            class="h-24 w-24 object-contain"
                            loading="lazy"
                        />
                    </div>
                </div>

                <div class="mt-4 border-t border-zinc-700/60 pt-2.5 text-[11px] text-zinc-400 flex justify-between">
                    <span>Gunakan QR ini untuk presensi di meja gym</span>
                    <span class="font-mono">{{ now()->format('d/m/Y') }}</span>
                </div>
            </div>

            <div class="rounded-xl bg-amber-500/10 p-4 border border-amber-500/20 text-left text-xs text-amber-800 dark:text-amber-300 space-y-1">
                <div class="font-semibold flex items-center gap-1.5">
                    <flux:icon name="information-circle" class="h-4 w-4" />
                    Informasi Akun Telah Dikirim ke Email
                </div>
                <p>
                    Rincian kode member dan password telah dikirim ke <strong>{{ $registeredMember->email }}</strong>. Gunakan nomor HP atau email ini untuk login di Aplikasi Member Gym.
                </p>
            </div>

            <div class="flex flex-col gap-2 pt-2">
                <flux:button variant="primary" :href="route('login')" class="w-full">
                    Selesai & Ke Halaman Utama
                </flux:button>
            </div>
        </div>
    @else
        {{-- Registration Form --}}
        <div class="space-y-2 text-center">
            <flux:heading size="xl" class="font-serif text-zinc-900 dark:text-zinc-100">
                Pendaftaran Member Gym
            </flux:heading>
            <flux:text class="text-zinc-500 text-sm">
                Daftar sekarang untuk mulai berolahraga dan nikmati akses fasilitas gym.
            </flux:text>
        </div>

        <form wire:submit="register" class="flex flex-col gap-4">
            {{-- Name --}}
            <flux:field>
                <flux:label>Nama Lengkap</flux:label>
                <flux:input wire:model="name" placeholder="Nama sesuai KTP..." required autofocus />
                <flux:error name="name" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Email --}}
                <flux:field>
                    <flux:label>Alamat Email</flux:label>
                    <flux:input type="email" wire:model="email" placeholder="nama@email.com" required />
                    <flux:error name="email" />
                </flux:field>

                {{-- Phone --}}
                <flux:field>
                    <flux:label>Nomor WhatsApp / HP</flux:label>
                    <flux:input wire:model="phone" placeholder="081234567890" required />
                    <flux:error name="phone" />
                </flux:field>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Gender --}}
                <flux:field>
                    <flux:label>Jenis Kelamin</flux:label>
                    <flux:select wire:model="gender">
                        <flux:select.option value="">Pilih Jenis Kelamin</flux:select.option>
                        <flux:select.option value="male">Laki-laki</flux:select.option>
                        <flux:select.option value="female">Perempuan</flux:select.option>
                    </flux:select>
                    <flux:error name="gender" />
                </flux:field>

                {{-- Birth date --}}
                <flux:field>
                    <flux:label>Tanggal Lahir</flux:label>
                    <flux:input type="date" wire:model="birth_date" />
                    <flux:error name="birth_date" />
                </flux:field>
            </div>

            {{-- Address --}}
            <flux:field>
                <flux:label>Alamat Domisili (Opsional)</flux:label>
                <flux:textarea wire:model="address" rows="2" placeholder="Alamat tempat tinggal..." />
                <flux:error name="address" />
            </flux:field>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Password --}}
                <flux:field>
                    <flux:label>Password Akun</flux:label>
                    <flux:input type="password" wire:model="password" placeholder="Minimal 6 karakter" required viewable />
                    <flux:error name="password" />
                </flux:field>

                {{-- Confirm Password --}}
                <flux:field>
                    <flux:label>Konfirmasi Password</flux:label>
                    <flux:input type="password" wire:model="password_confirmation" placeholder="Ulangi password" required viewable />
                    <flux:error name="password_confirmation" />
                </flux:field>
            </div>

            <div class="pt-2">
                <flux:button type="submit" variant="primary" class="w-full">
                    Daftar Sebagai Member
                </flux:button>
            </div>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>Sudah pernah terdaftar?</span>
            <flux:link :href="route('login')" wire:navigate class="font-medium text-amber-600 dark:text-amber-400">
                Masuk ke Aplikasi
            </flux:link>
        </div>
    @endif
</div>

