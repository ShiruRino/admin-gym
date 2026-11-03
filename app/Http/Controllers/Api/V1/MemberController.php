<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AttendanceListRequest;
use App\Http\Requests\Api\V1\ChangePasswordRequest;
use App\Http\Requests\Api\V1\InvoiceListRequest;
use App\Http\Requests\Api\V1\PtSessionListRequest;
use App\Http\Requests\Api\V1\UpdatePhotoRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\MemberAttendanceResource;
use App\Http\Resources\MemberCardResource;
use App\Http\Resources\MemberInvoiceResource;
use App\Http\Resources\MemberProfileResource;
use App\Http\Resources\MemberPtSessionResource;
use App\Http\Resources\MemberSubscriptionResource;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * @tags Data & Layanan Member
 */
class MemberController extends Controller
{
    /**
     * Profil Member
     *
     * Mengambil informasi lengkap biodata member yang sedang login, termasuk status keanggotaan saat ini.
     *
     * @response 200 {"success": true, "message": "Data profil member berhasil diambil.", "data": {"id": 1, "member_code": "GYM-2611-0001", "name": "Budi Sanjaya", "phone": "081234567890", "gender": "male", "gender_label": "Laki-laki", "birth_date": "1995-05-15", "address": "Jl. Melati No. 12", "emergency_contact": "Ibu Siti - 081298765432", "notes": "Fokus cardio", "join_date": "2026-11-03", "is_active": true, "membership_status": "active"}}
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Data profil member berhasil diambil.',
            'data' => new MemberProfileResource($member),
        ]);
    }

    /**
     * Perbarui Profil Mandiri
     *
     * Mengubah data kontak, alamat domisili, atau catatan kebugaran pribadi member.
     *
     * @response 200 {"success": true, "message": "Profil berhasil diperbarui.", "data": {"id": 1, "address": "Jl. Mawar No. 4", "emergency_contact": "Ayah - 081233334444"}}
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        $member->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => new MemberProfileResource($member->fresh()),
        ]);
    }

    /**
     * Unggah / Ganti Foto Profil Member
     *
     * Mengunggah foto profil baru member (maksimal 2MB). Foto lama akan dihapus otomatis dari storage.
     *
     * @response 200 {"success": true, "message": "Foto profil berhasil diperbarui.", "data": {"photo_url": "http://.../storage/members/photos/photo.jpg", "member": {"id": 1, "name": "Budi"}}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"photo": ["File foto wajib diunggah."]}}
     */
    public function updatePhoto(UpdatePhotoRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $path = $request->file('photo')->store('members/photos', 'public');
        $member->update(['photo_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'photo_url' => $member->photo_url,
                'member' => new MemberProfileResource($member->fresh()),
            ],
        ]);
    }

    /**
     * Hapus Foto Profil Member
     *
     * Menghapus foto profil yang saat ini digunakan member dan mengembalikannya ke avatar default.
     *
     * @response 200 {"success": true, "message": "Foto profil berhasil dihapus.", "data": {"photo_url": null, "member": {"id": 1, "name": "Budi"}}}
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $member->update(['photo_path' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus.',
            'data' => [
                'photo_url' => null,
                'member' => new MemberProfileResource($member->fresh()),
            ],
        ]);
    }

    /**
     * Ganti Password / PIN Member
     *
     * Mengubah password atau PIN akses akun member native dengan memvalidasi password lama terlebih dahulu.
     *
     * @response 200 {"success": true, "message": "Password berhasil diubah. Silakan gunakan password baru untuk login berikutnya."}
     * @response 422 {"message": "Password lama yang Anda masukkan tidak sesuai.", "errors": {"current_password": ["Password saat ini salah."]}}
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        if (! Hash::check($request->validated('current_password'), $member->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini yang Anda masukkan tidak sesuai.',
                'errors' => [
                    'current_password' => ['Password saat ini salah.'],
                ],
            ], 422);
        }

        $member->update([
            'password' => Hash::make($request->validated('new_password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan gunakan password baru untuk login berikutnya.',
        ]);
    }

    /**
     * Kartu Member & QR Code Digital
     *
     * Mengambil payload kode QR dan informasi ringkas kartu anggota untuk ditampilkan di layar smartphone saat melakukan check-in di terminal resepsionis gym.
     *
     * @response 200 {"success": true, "message": "Data kartu member digital berhasil diambil.", "data": {"member_code": "GYM-2611-0001", "name": "Budi Sanjaya", "qr_payload": "GYM-2611-0001", "membership_status": "active", "plan_name": "Paket Gold 1 Bulan", "start_date": "2026-11-03", "end_date": "2026-12-03", "days_remaining": 30}}
     */
    public function card(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user()->loadMissing(['activeSubscription.plan']);

        return response()->json([
            'success' => true,
            'message' => 'Data kartu member digital berhasil diambil.',
            'data' => new MemberCardResource($member),
        ]);
    }

    /**
     * Informasi Paket Membership Langganan
     *
     * Mengambil rincian paket langganan aktif, tanggal berlaku, dan sisa hari sebelum kedaluwarsa.
     *
     * @response 200 {"success": true, "message": "Informasi paket langganan member berhasil diambil.", "data": {"has_active_subscription": true, "membership_status": "active", "active_subscription": {"id": 1, "plan_id": 2, "plan_name": "Paket Gold 1 Bulan", "start_date": "2026-11-03", "end_date": "2026-12-03", "days_remaining": 30, "status": "active"}}}
     */
    public function subscription(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user()->loadMissing(['activeSubscription.plan', 'latestSubscription.plan']);

        return response()->json([
            'success' => true,
            'message' => 'Informasi paket langganan member berhasil diambil.',
            'data' => new MemberSubscriptionResource($member),
        ]);
    }

    /**
     * Saldo Kuota Sesi Personal Trainer (PT)
     *
     * Mengambil total kuota sesi PT akumulatif yang dimiliki member, kuota terpakai, sisa sesi, dan riwayat 10 sesi latihan terakhir bersama pelatih (trainer).
     *
     * @response 200 {"success": true, "message": "Informasi saldo sesi Personal Trainer berhasil diambil.", "data": {"total_quota": 10, "used_quota": 3, "remaining_quota": 7, "active_batches_count": 1, "recent_sessions": [{"id": 1, "trainer_name": "Coach Hendra", "trainer_specialty": "Hypertrophy & Strength", "used_at": "2026-11-03 10:15:00", "notes": "Chest day bench press"}]}}
     */
    public function ptSessions(PtSessionListRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user()->loadMissing(['activePtQuotas', 'ptSessionLogs.trainer']);

        return response()->json([
            'success' => true,
            'message' => 'Informasi saldo sesi Personal Trainer berhasil diambil.',
            'data' => new MemberPtSessionResource($member),
        ]);
    }

    /**
     * Riwayat Presensi / Kunjungan Check-in
     *
     * Menampilkan riwayat tanggal dan jam check-in kehadiran member di gym secara paginated.
     *
     * @response 200 {"success": true, "message": "Riwayat presensi kehadiran berhasil diambil.", "data": [{"id": 1, "check_in_time": "2026-11-03 14:02:00", "notes": "Latihan sore"}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     */
    public function attendances(AttendanceListRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        $perPage = (int) ($request->validated('per_page') ?? 15);
        $attendances = $member->attendances()
            ->latest('check_in_time')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat presensi kehadiran berhasil diambil.',
            'data' => MemberAttendanceResource::collection($attendances->items()),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    /**
     * Riwayat Pembayaran & Tagihan (Invoices)
     *
     * Menampilkan histori transaksi pembelian paket membership dan sesi PT beserta status lunas dan metode pembayaran.
     *
     * @response 200 {"success": true, "message": "Riwayat transaksi & tagihan berhasil diambil.", "data": [{"id": 1, "invoice_number": "INV-20261103-0001", "plan_name": "Paket Gold 1 Bulan", "amount": 350000, "payment_method": "qris", "payment_method_label": "QRIS", "payment_status": "paid", "payment_status_label": "Lunas", "paid_at": "2026-11-03 11:00:00", "notes": null, "created_at": "2026-11-03 11:00:00"}], "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}}
     */
    public function invoices(InvoiceListRequest $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user();

        $perPage = (int) ($request->validated('per_page') ?? 15);
        $invoices = $member->invoices()
            ->with('plan')
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi & tagihan berhasil diambil.',
            'data' => MemberInvoiceResource::collection($invoices->items()),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }
}
