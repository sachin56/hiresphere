<?php

namespace App\Services;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CognitoService
{
    private CognitoIdentityProviderClient $client;
    private string $userPoolId;
    private string $clientId;
    private string $clientSecret;
    private string $region;

    public function __construct()
    {
        $this->userPoolId   = config('services.cognito.user_pool_id', '');
        $this->clientId     = config('services.cognito.client_id', '');
        $this->clientSecret = config('services.cognito.client_secret', '');
        $this->region       = config('services.cognito.region', 'us-east-1');

        $this->client = new CognitoIdentityProviderClient([
            'version' => 'latest',
            'region'  => $this->region,
        ]);
    }

    public function register(string $email, string $password, string $name, string $role): array
    {
        $result = $this->client->signUp([
            'ClientId'       => $this->clientId,
            'SecretHash'     => $this->computeSecretHash($email),
            'Username'       => $email,
            'Password'       => $password,
            'UserAttributes' => [
                ['Name' => 'email', 'Value' => $email],
                ['Name' => 'name',  'Value' => $name],
            ],
        ]);

        return [
            'user_sub'            => $result['UserSub'],
            'confirmation_needed' => !$result['UserConfirmed'],
        ];
    }

    public function confirmAccount(string $email, string $code): bool
    {
        try {
            $this->client->confirmSignUp([
                'ClientId'         => $this->clientId,
                'SecretHash'       => $this->computeSecretHash($email),
                'Username'         => $email,
                'ConfirmationCode' => $code,
            ]);
            return true;
        } catch (AwsException) {
            return false;
        }
    }

    public function login(string $email, string $password): array
    {
        $result = $this->client->initiateAuth([
            'AuthFlow'       => 'USER_PASSWORD_AUTH',
            'ClientId'       => $this->clientId,
            'AuthParameters' => [
                'USERNAME'    => $email,
                'PASSWORD'    => $password,
                'SECRET_HASH' => $this->computeSecretHash($email),
            ],
        ]);

        $auth = $result['AuthenticationResult'] ?? null;

        if (!$auth) {
            $challenge = $result['ChallengeName'] ?? 'unknown';
            throw new RuntimeException("Cognito login requires additional step: {$challenge}");
        }

        return [
            'access_token'  => $auth['AccessToken'],
            'id_token'      => $auth['IdToken'],
            'refresh_token' => $auth['RefreshToken'],
            'expires_in'    => $auth['ExpiresIn'],
            'token_type'    => $auth['TokenType'],
        ];
    }

    public function refreshToken(string $email, string $refreshToken): array
    {
        $result = $this->client->initiateAuth([
            'AuthFlow'       => 'REFRESH_TOKEN_AUTH',
            'ClientId'       => $this->clientId,
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
                'SECRET_HASH'   => $this->computeSecretHash($email),
            ],
        ]);

        $auth = $result['AuthenticationResult'];

        return [
            'access_token' => $auth['AccessToken'],
            'id_token'     => $auth['IdToken'],
            'expires_in'   => $auth['ExpiresIn'],
        ];
    }

    public function verifyToken(string $token): ?array
    {
        $jwks = $this->getJwks();

        try {
            $keys    = JWK::parseKeySet($jwks);
            $decoded = JWT::decode($token, $keys);
            return (array) $decoded;
        } catch (\Exception) {
            return null;
        }
    }

    public function logout(string $accessToken): bool
    {
        try {
            $this->client->globalSignOut(['AccessToken' => $accessToken]);
            return true;
        } catch (AwsException) {
            return false;
        }
    }

    public function forgotPassword(string $email): bool
    {
        try {
            $this->client->forgotPassword([
                'ClientId'   => $this->clientId,
                'SecretHash' => $this->computeSecretHash($email),
                'Username'   => $email,
            ]);
            return true;
        } catch (AwsException) {
            return false;
        }
    }

    public function confirmForgotPassword(string $email, string $code, string $newPassword): bool
    {
        try {
            $this->client->confirmForgotPassword([
                'ClientId'         => $this->clientId,
                'SecretHash'       => $this->computeSecretHash($email),
                'Username'         => $email,
                'ConfirmationCode' => $code,
                'Password'         => $newPassword,
            ]);
            return true;
        } catch (AwsException) {
            return false;
        }
    }

    private function getJwks(): array
    {
        return Cache::remember('cognito_jwks', 3600, function () {
            $url = config('services.cognito.jwk_url');
            return Http::get($url)->json();
        });
    }

    private function computeSecretHash(string $username): string
    {
        return base64_encode(hash_hmac(
            'sha256',
            $username . $this->clientId,
            $this->clientSecret,
            true
        ));
    }
}
