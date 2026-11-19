<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }

        // Preserve technician's explicit offline preference across logins; default new/null accounts to online
        $user->update([
            'is_online'    => $user->is_online === null ? true : (bool) $user->is_online,
            'last_seen_at' => now(),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'  => $token,
            'user'   => $user,
            'config' => $this->buildClientConfig(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['is_online' => false]);
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }
        return response()->json(['message' => 'Signed out successfully.']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data'   => $request->user(),
            'config' => $this->buildClientConfig(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'phone'     => 'sometimes|string|max:50',
            'is_online' => 'sometimes|boolean',
        ]);

        $updates = $request->only('name', 'phone', 'is_online');
        if (!empty($updates['is_online'])) {
            $updates['last_seen_at'] = now();
        }

        $request->user()->update($updates);
        return response()->json(['data' => $request->user()]);
    }

    private function buildClientConfig(): array
    {
        return [
            'gps_refresh_interval'       => (int) SettingService::get('gps_refresh_interval', '15'),
            'geofence_check_meters'      => (int) SettingService::get('geofence_check_meters', '0'),
            'require_proof_photo'        => SettingService::get('require_proof_photo', '1') === '1',
            'require_customer_signature' => SettingService::get('require_customer_signature', '0') === '1',
            'currency'                   => (string) SettingService::get('currency', 'USD'),
            'default_country_code'       => (string) SettingService::get('default_country_code', '62'),
        ];
    }
}
