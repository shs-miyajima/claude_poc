<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuthenticationService $authenticationService) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->authenticationService->attempt(
            $request->input('company_code'),
            (string) $request->input('email'),
            (string) $request->input('password'),
        );

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => '企業コード、メールアドレス、またはパスワードが正しくありません。',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->to($this->redirectPathFor($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectPathFor(User $user): string
    {
        return match (true) {
            $user->isSuperUser() => route('super.companies.index'),
            $user->isAdmin() => route('company.home'),
            default => route('user.home'),
        };
    }
}
