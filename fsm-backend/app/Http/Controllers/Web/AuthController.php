<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::withoutGlobalScopes()->where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            if (!$user->is_active) {
                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ])->onlyInput('email');
            }

            if (!empty($user->tenant_id)) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant && !$tenant->isActive()) {
                    return back()->withErrors([
                        'email' => 'Your organization subscription is suspended or expired. Please contact your administrator.',
                    ])->onlyInput('email');
                }
                TenantContext::setTenant($tenant);
                $request->session()->put('active_tenant_id', $user->tenant_id);
            }

            if (!$user->isPortalStaff()) {
                return back()->withErrors([
                    'email' => 'Only authorized NOC/Admin portal staff can sign in here. Field technicians should use the Mobile App.',
                ])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid email address or password.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('admin.login'));
    }
}
