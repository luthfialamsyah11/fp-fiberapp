<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskStatusHistory;
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
            'stats'  => $this->buildTechnicianStats($user),
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
        $user = $request->user();

        return response()->json([
            'data'   => $user,
            'stats'  => $this->buildTechnicianStats($user),
            'config' => $this->buildClientConfig(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'phone'     => 'sometimes|nullable|string|max:50',
            'is_online' => 'sometimes|boolean',
        ]);

        $updates = $request->only('name', 'phone', 'is_online');
        if (array_key_exists('name', $updates)) {
            $updates['name'] = trim((string) $updates['name']);
        }
        if (array_key_exists('phone', $updates)) {
            $updates['phone'] = $updates['phone'] !== null ? trim((string) $updates['phone']) : null;
        }
        if (!empty($updates['is_online'])) {
            $updates['last_seen_at'] = now();
        }

        $user = $request->user();
        $user->update($updates);

        return response()->json([
            'data'   => $user->fresh(),
            'stats'  => $this->buildTechnicianStats($user),
            'config' => $this->buildClientConfig(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors'  => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke other device tokens while keeping the current active session alive
        $currentToken = $user->currentAccessToken();
        if ($currentToken && isset($currentToken->id)) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        }

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    private function buildTechnicianStats(User $user): array
    {
        $counts = Task::where('technician_id', $user->id)
            ->selectRaw("
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status IN ('assigned', 'accepted', 'on-going') THEN 1 END) as active_count,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_count,
                COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
            ")
            ->first();

        $completed = (int) ($counts->completed_count ?? 0);
        $active = (int) ($counts->active_count ?? 0);
        $accepted = (int) ($counts->accepted_count ?? 0);
        $ongoing = (int) ($counts->ongoing_count ?? 0);

        $declinedHistory = TaskStatusHistory::where('actor_id', $user->id)
            ->where('from_status', 'assigned')
            ->whereIn('to_status', ['pending', 'rejected'])
            ->count();
        $declined = max((int) ($counts->rejected_count ?? 0), $declinedHistory);

        $positiveDecisions = $accepted + $ongoing + $completed;
        $totalDecisions = $positiveDecisions + $declined;
        $acceptanceRate = $totalDecisions > 0
            ? round(($positiveDecisions / $totalDecisions) * 100, 1)
            : 100.0;

        $completedWithProof = $completed > 0
            ? Task::where('technician_id', $user->id)->where('status', 'completed')->has('proofs')->count()
            : 0;
        $proofComplianceRate = $completed > 0
            ? round(($completedWithProof / $completed) * 100, 1)
            : 100.0;

        return [
            'completed_count'       => $completed,
            'active_count'          => $active,
            'declined_count'        => $declined,
            'acceptance_rate'       => $acceptanceRate,
            'proof_compliance_rate' => $proofComplianceRate,
        ];
    }

    private function buildClientConfig(): array
    {
        return [
            'company_name'               => (string) SettingService::get('company_name', 'FiberOps Global Carrier'),
            'support_email'              => (string) SettingService::get('support_email', 'noc@bentengsiber.com'),
            'support_phone'              => (string) SettingService::get('support_phone', '+1-800-555-0199'),
            'map_style'                  => (string) SettingService::get('map_style', 'openstreetmap'),
            'gps_refresh_interval'       => (int) SettingService::get('gps_refresh_interval', '15'),
            'geofence_check_meters'      => (int) SettingService::get('geofence_check_meters', '0'),
            'require_proof_photo'        => SettingService::get('require_proof_photo', '1') === '1',
            'require_customer_signature' => SettingService::get('require_customer_signature', '0') === '1',
            'currency'                   => (string) SettingService::get('currency', 'USD'),
            'default_country_code'       => (string) SettingService::get('default_country_code', '62'),
        ];
    }
}
