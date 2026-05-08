<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\InterviewerProfile;
use App\Models\User;
use App\Services\CognitoService;
use Aws\Exception\AwsException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly CognitoService $cognito) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $tokens = $this->cognito->login($data['email'], $data['password']);
        } catch (AwsException $e) {
            Log::warning('Cognito web login failed', [
                'email' => $data['email'],
                'aws_error_code' => $e->getAwsErrorCode(),
                'aws_error_message' => $e->getAwsErrorMessage(),
            ]);

            return back()->withInput()->with('error', $this->cognitoLoginErrorMessage($e));
        } catch (\Exception $e) {
            Log::warning('Web login failed before Cognito returned an AWS error', [
                'email' => $data['email'],
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', $this->loginErrorMessage($e));
        }

        $user = User::where('email', $data['email'])
            ->with(['candidateProfile', 'interviewerProfile'])
            ->first();

        session([
            'access_token'  => $tokens['access_token'],
            'id_token'      => $tokens['id_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user_id'       => $user?->id,
        ]);

        return redirect()->route('dashboard');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'role'                  => 'required|in:candidate,interviewer',
            'phone'                 => 'nullable|string|max:20',
        ]);

        try {
            $result = $this->cognito->register($data['email'], $data['password'], $data['name'], $data['role']);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Registration failed: ' . $e->getMessage());
        }

        $user = User::create([
            'id'          => Str::uuid(),
            'cognito_sub' => $result['user_sub'],
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'role'        => $data['role'],
        ]);

        if ($data['role'] === 'candidate') {
            CandidateProfile::create(['user_id' => $user->id]);
        } else {
            InterviewerProfile::create([
                'user_id'             => $user->id,
                'current_title'       => 'Software Engineer',
                'years_of_experience' => 0,
                'domains'             => [],
                'interview_types'     => [],
                'hourly_rate'         => 0,
            ]);
        }

        return redirect()->route('auth.confirm')
            ->with('email', $data['email'])
            ->with('success', 'Account created! Please check your email for the confirmation code.');
    }

    public function showConfirm(): View
    {
        return view('auth.confirm', ['email' => session('email')]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string',
        ]);

        $confirmed = $this->cognito->confirmAccount($data['email'], $data['code']);

        if (!$confirmed) {
            return back()->withInput()->with('error', 'Invalid confirmation code. Please try again.');
        }

        User::where('email', $data['email'])->update(['email_verified_at' => now(), 'is_verified' => true]);

        return redirect()->route('login')->with('success', 'Email confirmed! You can now log in.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email']);
        $this->cognito->forgotPassword($data['email']);

        return back()->with('success', 'If an account exists, a reset code has been sent to your email.');
    }

    public function showResetPassword(): View
    {
        return view('auth.reset-password', ['email' => request('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'             => 'required|email',
            'code'              => 'required|string',
            'password'          => 'required|string|min:8|confirmed',
        ]);

        $success = $this->cognito->confirmForgotPassword($data['email'], $data['code'], $data['password']);

        if (!$success) {
            return back()->withInput()->with('error', 'Password reset failed. Please check your code.');
        }

        return redirect()->route('login')->with('success', 'Password reset successfully. Please log in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $token = session('access_token');
        if ($token) {
            $this->cognito->logout($token);
        }
        session()->flush();
        return redirect()->route('login')->with('success', 'Logged out successfully.');
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
            default => 'Invalid email or password.',
        };
    }

    private function loginErrorMessage(\Exception $e): string
    {
        if (str_starts_with($e->getMessage(), 'Cognito login requires additional step:')) {
            return $e->getMessage();
        }

        return 'Invalid email or password.';
    }
}
