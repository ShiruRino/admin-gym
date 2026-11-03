<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\MemberProfileResource;
use App\Models\Member;
use App\Notifications\MemberTemporaryPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @tags Autentikasi Member
 */
class AuthController extends Controller
{
    /**
     * Registrasi Mandiri Member Baru
     *
     * Mendaftarkan akun member baru secara mandiri dari aplikasi mobile. Sistem akan membuat kode member unik otomatis (format GYM-YYMM-XXXX), mengirimkan rincian akun ke email, dan mengembalikan Bearer token Sanctum.
     *
     * @response 201 {"success": true, "message": "Pendaftaran member berhasil.", "data": {"token": "1|token...", "token_type": "Bearer", "member": {"id": 1, "member_code": "GYM-2611-0001", "name": "Budi Santoso", "phone": "081234567890", "email": "budi@gmail.com"}}}
     * @response 422 {"message": "The given data was invalid.", "errors": {"email": ["Alamat email ini sudah terdaftar sebagai member."]}}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $memberCode = Member::generateMemberCode();

        $member = Member::create([
            'member_code' => $memberCode,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'gender' => $request->validated('gender'),
            'birth_date' => $request->validated('birth_date'),
            'address' => $request->validated('address'),
            'emergency_contact' => $request->validated('emergency_contact'),
            'join_date' => Carbon::today(),
            'is_active' => true,
        ]);

        if ($member->email) {
            $member->notify(new MemberTemporaryPasswordNotification($request->validated('password'), isNewRegistration: true));
        }

        $token = $member->createToken('member-native-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Pendaftaran member berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'member' => new MemberProfileResource($member),
            ],
        ], 201);
    }

    /**
     * Login Member Native
     *
     * Autentikasi akun member menggunakan nomor handphone atau email dan PIN/password. Menghasilkan Bearer token Laravel Sanctum untuk mengakses seluruh endpoint privat member.
     *
     * @response 200 {"success": true, "message": "Login berhasil.", "data": {"token": "1|token...", "token_type": "Bearer", "member": {"id": 1, "member_code": "GYM-2611-0001", "name": "Budi Sanjaya", "phone": "081234567890"}}}
     * @response 401 {"success": false, "message": "Nomor handphone atau PIN/password yang Anda masukkan salah."}
     * @response 403 {"success": false, "message": "Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = trim((string) ($request->input('login') ?? $request->input('phone') ?? $request->input('email')));
        $member = Member::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $member || ! Hash::check($request->validated('password'), $member->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor handphone atau PIN/password yang Anda masukkan salah.',
            ], 401);
        }

        if (! $member->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir.',
            ], 403);
        }

        $token = $member->createToken('member-native-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'member' => new MemberProfileResource($member),
            ],
        ]);
    }

    /**
     * Lupa Password Member
     *
     * Mengirimkan password sementara baru ke email akun member yang terdaftar. Member kemudian dapat login menggunakan password sementara tersebut dan mengubahnya di menu profil.
     *
     * @response 200 {"success": true, "message": "Password sementara baru telah berhasil dikirim ke alamat email Anda. Silakan cek kotak masuk atau folder spam email Anda."}
     * @response 404 {"success": false, "message": "Alamat email tidak terdaftar sebagai member."}
     * @response 403 {"success": false, "message": "Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir."}
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = trim($request->validated('email'));
        $member = Member::where('email', $email)->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email tidak terdaftar sebagai member.',
            ], 404);
        }

        if (! $member->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun member Anda berstatus non-aktif. Silakan hubungi meja kasir.',
            ], 403);
        }

        $temporaryPassword = Str::password(8, letters: true, numbers: true, symbols: false);
        $member->password = $temporaryPassword;
        $member->save();

        $member->notify(new MemberTemporaryPasswordNotification($temporaryPassword, isNewRegistration: false));

        return response()->json([
            'success' => true,
            'message' => 'Password sementara baru telah berhasil dikirim ke alamat email Anda. Silakan cek kotak masuk atau folder spam email Anda.',
        ]);
    }

    /**
     * Logout Member
     *
     * Mencabut dan menghapus Bearer token sesi yang sedang digunakan oleh member.
     *
     * @response 200 {"success": true, "message": "Logout berhasil. Sesi token telah dicabut."}
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Sesi token telah dicabut.',
        ]);
    }
}
