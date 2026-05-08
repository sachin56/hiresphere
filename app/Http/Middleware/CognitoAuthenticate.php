<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\CognitoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CognitoAuthenticate
{
    public function __construct(private readonly CognitoService $cognito) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $claims = $this->cognito->verifyToken($token);

        if (!$claims) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = User::where('cognito_sub', $claims['sub'])
            ->orWhere('email', $claims['email'] ?? '')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated.'], 403);
        }

        $request->setUserResolver(fn() => $user);
        auth()->setUser($user);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return $request->query('token');
    }
}
