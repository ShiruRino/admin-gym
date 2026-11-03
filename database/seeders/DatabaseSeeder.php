<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlanType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\MemberPtQuota;
use App\Models\MemberSubscription;
use App\Models\Plan;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Internal Staff Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@gym.com'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $receptionist = User::firstOrCreate(
            ['email' => 'receptionist@gym.com'],
            [
                'name' => 'Resepsionis Meja Depan',
                'password' => Hash::make('password'),
                'role' => UserRole::Receptionist,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Master Plans
        $plansData = [
            [
                'name' => 'Membership Harian / Visit',
                'type' => PlanType::Membership,
                'duration_days' => 1,
                'pt_sessions_count' => null,
                'price' => 50000.00,
                'description' => 'Akses 1 hari bebas fasilitas gym tanpa ikatan langganan.',
                'is_active' => true,
            ],
            [
                'name' => 'Membership Regular 1 Bulan',
                'type' => PlanType::Membership,
                'duration_days' => 30,
                'pt_sessions_count' => null,
                'price' => 350000.00,
                'description' => 'Akses gym bebas setiap hari selama 30 hari penuh.',
                'is_active' => true,
            ],
            [
                'name' => 'Membership Regular 3 Bulan',
                'type' => PlanType::Membership,
                'duration_days' => 90,
                'pt_sessions_count' => null,
                'price' => 950000.00,
                'description' => 'Akses gym 3 bulan hemat dengan diskon langganan kuartal.',
                'is_active' => true,
            ],
            [
                'name' => 'Membership Regular 6 Bulan',
                'type' => PlanType::Membership,
                'duration_days' => 180,
                'pt_sessions_count' => null,
                'price' => 1750000.00,
                'description' => 'Akses gym 6 bulan dengan fasilitas loker gratis.',
                'is_active' => true,
            ],
            [
                'name' => 'Membership Regular 12 Bulan (1 Tahun)',
                'type' => PlanType::Membership,
                'duration_days' => 365,
                'pt_sessions_count' => null,
                'price' => 3000000.00,
                'description' => 'Paket tahunan paling hemat, bonus merchandise dan loker permanen.',
                'is_active' => true,
            ],
            [
                'name' => 'Paket Personal Trainer 5 Sesi',
                'type' => PlanType::PtSession,
                'duration_days' => null,
                'pt_sessions_count' => 5,
                'price' => 750000.00,
                'description' => '5 sesi pendampingan privat bersama trainer bersertifikasi.',
                'is_active' => true,
            ],
            [
                'name' => 'Paket Personal Trainer 10 Sesi',
                'type' => PlanType::PtSession,
                'duration_days' => null,
                'pt_sessions_count' => 10,
                'price' => 1400000.00,
                'description' => '10 sesi privat personal training + konsultasi nutrisi berkala.',
                'is_active' => true,
            ],
            [
                'name' => 'Paket Personal Trainer 20 Sesi',
                'type' => PlanType::PtSession,
                'duration_days' => null,
                'pt_sessions_count' => 20,
                'price' => 2500000.00,
                'description' => '20 sesi transformasi intensif dengan meal plan komprehensif.',
                'is_active' => true,
            ],
            [
                'name' => 'Bundling 3 Bulan + 5 Sesi PT',
                'type' => PlanType::Bundle,
                'duration_days' => 90,
                'pt_sessions_count' => 5,
                'price' => 1550000.00,
                'description' => 'Kombinasi 3 bulan akses gym ditambah 5 sesi personal training privat.',
                'is_active' => true,
            ],
        ];

        $createdPlans = [];
        foreach ($plansData as $plan) {
            $createdPlans[$plan['name']] = Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }

        // 3. Master Trainers
        $trainersData = [
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567801',
                'specialty' => 'Hypertrophy & Bodybuilding',
                'is_active' => true,
            ],
            [
                'name' => 'Siti Rahma',
                'phone' => '081234567802',
                'specialty' => 'Fat Loss & Conditioning',
                'is_active' => true,
            ],
            [
                'name' => 'Rio Pratama',
                'phone' => '081234567803',
                'specialty' => 'Functional Training & Calisthenics',
                'is_active' => true,
            ],
            [
                'name' => 'Denny Wijaya',
                'phone' => '081234567804',
                'specialty' => 'Powerlifting & Strength',
                'is_active' => true,
            ],
        ];

        $createdTrainers = [];
        foreach ($trainersData as $trainer) {
            $createdTrainers[] = Trainer::updateOrCreate(['phone' => $trainer['phone']], $trainer);
        }

        // 4. Sample Members with Master Data
        $membersData = [
            [
                'member_code' => 'GYM-2611-0001',
                'name' => 'Ahmad Fauzi',
                'phone' => '081298765401',
                'gender' => Gender::Male,
                'birth_date' => '1995-04-12',
                'address' => 'Jl. Sudirman No. 45, Jakarta Pusat',
                'emergency_contact' => '081298765409',
                'notes' => 'Fokus program penambahan massa otot (hypertrophy).',
                'join_date' => '2026-01-10',
                'is_active' => true,
            ],
            [
                'member_code' => 'GYM-2611-0002',
                'name' => 'Jessica Tanuwijaya',
                'phone' => '081298765402',
                'gender' => Gender::Female,
                'birth_date' => '1998-08-23',
                'address' => 'Apartemen Mediterania Tower B 12-05',
                'emergency_contact' => '081298765408',
                'notes' => 'Ada cedera lutut ringan, hindari beban jumping.',
                'join_date' => '2026-03-15',
                'is_active' => true,
            ],
            [
                'member_code' => 'GYM-2611-0003',
                'name' => 'Kevin Sanjaya',
                'phone' => '081298765403',
                'gender' => Gender::Male,
                'birth_date' => '1992-11-05',
                'address' => 'Komplek Permata Hijau Blok C3 No. 12',
                'emergency_contact' => '081298765407',
                'notes' => 'Pernah ikut kompetisi powerlifting.',
                'join_date' => '2026-06-01',
                'is_active' => true,
            ],
            [
                'member_code' => 'GYM-2611-0004',
                'name' => 'Maya Anggraini',
                'phone' => '081298765404',
                'gender' => Gender::Female,
                'birth_date' => '2001-02-17',
                'address' => 'Jl. Tebet Barat Dalam VII No. 8',
                'emergency_contact' => '081298765406',
                'notes' => 'Member paket visit / harian.',
                'join_date' => '2026-10-20',
                'is_active' => true,
            ],
            [
                'member_code' => 'GYM-2611-0005',
                'name' => 'Bambang Hartono',
                'phone' => '081298765405',
                'gender' => Gender::Male,
                'birth_date' => '1980-07-30',
                'address' => 'Jl. Kebon Jeruk Baru No. 19',
                'emergency_contact' => '081298765400',
                'notes' => 'Membership telah kedaluwarsa.',
                'join_date' => '2025-05-01',
                'is_active' => true,
            ],
        ];

        foreach ($membersData as $index => $mData) {
            $member = Member::updateOrCreate(
                ['phone' => $mData['phone']],
                array_merge($mData, ['password' => Hash::make('123456')])
            );

            // Seed sample active subscription for member 1 & 2
            if ($index === 0) {
                // Active 1 Month (ends in 25 days)
                $plan = $createdPlans['Membership Regular 1 Bulan'];
                $invoice = Invoice::updateOrCreate(
                    ['invoice_number' => 'INV-20261101-0001'],
                    [
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'amount' => $plan->price,
                        'payment_method' => PaymentMethod::Transfer,
                        'payment_status' => PaymentStatus::Paid,
                        'paid_at' => Carbon::today()->subDays(5),
                        'received_by_user_id' => $admin->id,
                        'notes' => 'Pendaftaran membership awal bulan.',
                    ]
                );

                MemberSubscription::updateOrCreate(
                    ['member_id' => $member->id, 'invoice_id' => $invoice->id],
                    [
                        'plan_id' => $plan->id,
                        'start_date' => Carbon::today()->subDays(5),
                        'end_date' => Carbon::today()->addDays(25),
                        'status' => SubscriptionStatus::Active,
                    ]
                );

                // Add PT Quota
                $ptPlan = $createdPlans['Paket Personal Trainer 10 Sesi'];
                MemberPtQuota::updateOrCreate(
                    ['member_id' => $member->id],
                    [
                        'total_sessions' => 10,
                        'used_sessions' => 3,
                        'remaining_sessions' => 7,
                        'is_active' => true,
                    ]
                );
            } elseif ($index === 1) {
                // Expiring soon (ends in 3 days)
                $plan = $createdPlans['Membership Regular 1 Bulan'];
                $invoice = Invoice::updateOrCreate(
                    ['invoice_number' => 'INV-20261005-0002'],
                    [
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'amount' => $plan->price,
                        'payment_method' => PaymentMethod::Qris,
                        'payment_status' => PaymentStatus::Paid,
                        'paid_at' => Carbon::today()->subDays(27),
                        'received_by_user_id' => $receptionist->id,
                        'notes' => 'Pembayaran via QRIS resepsionis.',
                    ]
                );

                MemberSubscription::updateOrCreate(
                    ['member_id' => $member->id, 'invoice_id' => $invoice->id],
                    [
                        'plan_id' => $plan->id,
                        'start_date' => Carbon::today()->subDays(27),
                        'end_date' => Carbon::today()->addDays(3),
                        'status' => SubscriptionStatus::Active,
                    ]
                );
            } elseif ($index === 4) {
                // Expired member
                $plan = $createdPlans['Membership Regular 1 Bulan'];
                $invoice = Invoice::updateOrCreate(
                    ['invoice_number' => 'INV-20250901-0005'],
                    [
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'amount' => $plan->price,
                        'payment_method' => PaymentMethod::Cash,
                        'payment_status' => PaymentStatus::Paid,
                        'paid_at' => Carbon::today()->subDays(60),
                        'received_by_user_id' => $receptionist->id,
                    ]
                );

                MemberSubscription::updateOrCreate(
                    ['member_id' => $member->id, 'invoice_id' => $invoice->id],
                    [
                        'plan_id' => $plan->id,
                        'start_date' => Carbon::today()->subDays(60),
                        'end_date' => Carbon::today()->subDays(30),
                        'status' => SubscriptionStatus::Expired,
                    ]
                );
            }
        }
    }
}
