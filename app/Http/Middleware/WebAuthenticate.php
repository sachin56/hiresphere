<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CognitoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAuthenticate
{
    public function __construct(private readonly CognitoService $cognito) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = session('access_token');

        if (!$token) {
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        $claims = $this->cognito->verifyToken($token);

        if (!$claims) {
            session()->forget(['access_token', 'id_token', 'refresh_token', 'user']);
            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        $user = User::where('cognito_sub', $claims['sub'])
            ->orWhere('email', $claims['email'] ?? '')
            ->with(['candidateProfile', 'interviewerProfile'])
            ->first();

        if (!$user || !$user->is_active) {
            session()->forget(['access_token', 'id_token', 'refresh_token', 'user']);
            return redirect()->route('login')->with('error', 'Account not found or deactivated.');
        }

        auth()->setUser($user);
        view()->share('authUser', $user);

        return $next($request);
    }
}
