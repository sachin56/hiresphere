<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\InterviewerProfile;
use App\Models\User;
use App\Services\CognitoService;
use Aws\Exception\AwsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(name="Authentication", description="User registration, login and token management")
 */
class AuthController extends Controller
{
    public function __construct(private readonly CognitoService $cognito) {}

    /**
     * @OA\Post(
     *   path="/api/auth/register",
     *   tags={"Authentication"},
     *   summary="Register a new user",
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"name","email","password","role"},
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="email", type="string", format="email"),
     *     @OA\Property(property="password", type="string", minLength=8),
     *     @OA\Property(property="role", type="string", enum={"candidate","interviewer"}),
     *     @OA\Property(property="phone", type="string"),
     *   )),
     *   @OA\Response(response=201, description="User registered successfully"),
     *   @OA\Response(response=422, description="Validation error"),
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:candidate,interviewer',
            'phone'    => 'nullable|string|max:20',
        ]);

        try {
            $cognitoResult = $this->cognito->register(
                $data['email'],
                $data['password'],
                $data['name'],
                $data['role']
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 422);
        }

        $user = DB::transaction(function () use ($data, $cognitoResult) {
            $user = User::create([
                'id'          => Str::uuid(),
                'cognito_sub' => $cognitoResult['user_sub'],
                'name'        => $data['name'],
                'email'       => $data['email'],
                'phone'       => $data['phone'] ?? null,
                'role'        => $data['role'],
            ]);

            if ($data['role'] === 'candidate') {
                CandidateProfile::create(['user_id' => $user->id]);
            } else {
                // Interviewer profile needs separate onboarding
                InterviewerProfile::create([
                    'user_id'         => $user->id,
                    'current_title'   => 'Software Engineer',
                    'years_of_experience' => 0,
                    'domains'         => [],
                    'interview_types' => [],
                    'hourly_rate'     => 0,
                ]);
            }

            return $user;
        });

        return response()->json([
            'message'              => 'Registration successful. Please verify your email.',
            'user_id'              => $user->id,
            'confirmation_needed'  => $cognitoResult['confirmation_needed'],
        ], 201);
    }

    /**
     * @OA\Post(path="/api/auth/confirm", tags={"Authentication"}, summary="Confirm email with OTP")
     */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $confirmed = $this->cognito->confirmAccount($data['email'], $data['code']);

        if (!$confirmed) {
            return response()->json(['message' => 'Invalid confirmation code.'], 422);
        }

        User::where('email', $data['email'])->update(['email_verified_at' => now(), 'is_verified' => true]);

        return response()->json(['message' => 'Email confirmed. You can now log in.']);
    }

    /**
     * @OA\Post(path="/api/auth/login", tags={"Authentication"}, summary="Login with email and password")
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $tokens = $this->cognito->login($data['email'], $data['password']);
        } catch (AwsException $e) {
            Log::warning('Cognito API login failed', [
                'email' => $data['email'],
                'aws_error_code' => $e->getAwsErrorCode(),
                'aws_error_message' => $e->getAwsErrorMessage(),
            ]);

            return response()->json(['message' => $this->cognitoLoginErrorMessage($e)], 401);
        } catch (\Exception $e) {
            Log::warning('API login failed before Cognito returned an AWS error', [
                'email' => $data['email'],
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => $this->loginErrorMessage($e)], 401);
        }

        $user = User::where('email', $data['email'])
            ->with(['candidateProfile', 'interviewerProfile'])
            ->first();

        return response()->json([
            'tokens' => $tokens,
            'user'   => $user,
        ]);
    }

    /**
     * @OA\Post(path="/api/auth/refresh", tags={"Authentication"}, summary="Refresh access token")
     */
    public function refresh(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'         => 'required|email',
            'refresh_token' => 'required|string',
        ]);

        try {
            $tokens = $this->cognito->refreshToken($data['email'], $data['refresh_token']);
            return response()->json($tokens);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token refresh failed.'], 401);
        }
    }

    /**
     * @OA\Post(path="/api/auth/logout", tags={"Authentication"}, summary="Logout", security={{"bearerAuth":{}}})
     */
    public function logout(Request $request): JsonResponse
    {
        $token = substr($request->header('Authorization', ''), 7);
        $this->cognito->logout($token);

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * @OA\Post(path="/api/auth/forgot-password", tags={"Authentication"}, summary="Initiate password reset")
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        $this->cognito->forgotPassword($data['email']);

        return response()->json(['message' => 'If an account exists, a reset code has been sent.']);
    }

    /**
     * @OA\Post(path="/api/auth/reset-password", tags={"Authentication"}, summary="Reset password with code")
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'        => 'required|email',
            'code'         => 'required|string',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $success = $this->cognito->confirmForgotPassword($data['email'], $data['code'], $data['password']);

        if (!$success) {
            return response()->json(['message' => 'Password reset failed. Invalid code.'], 422);
        }

        return response()->json(['message' => 'Password reset successful.']);
    }

    /**
     * @OA\Get(path="/api/auth/me", tags={"Authentication"}, summary="Get current user", security={{"bearerAuth":{}}})
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['candidateProfile', 'interviewerProfile']);
        return response()->json($user);
    }

    private function cognitoLoginErrorMessage(AwsException $e): string
    {
        $message = $e->getAwsErrorMessage();

        if ($e->getAwsErrorCode() === 'NotAuthorizedException' && str_contains($message, 'secret hash')) {
            return 'Cognito client secret is missing or incorrect. Check AWS_COGNITO_CLIENT_SECRET in .env.';
        }

        return match ($e->getAwsErrorCode()) {
            'UserNotConfirmedException' => 'Please confirm your email before logging in.',
            'PasswordResetRequiredException' => 'You need to reset your password before logging in.',
            'InvalidParameterException' => 'Cognito login is not fully configured. Enable USER_PASSWORD_AUTH for this app client.',
            default => 'Invalid credentials.',
        };
    }

    private function loginErrorMessage(\Exception $e): string
    {
        if (str_starts_with($e->getMessage(), 'Cognito login requires additional step:')) {
            return $e->getMessage();
        }

        return 'Invalid credentials.';
    }
}
