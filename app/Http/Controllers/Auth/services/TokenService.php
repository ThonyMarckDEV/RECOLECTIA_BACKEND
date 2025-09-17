<?php

namespace App\Http\Controllers\Auth\services;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate access and refresh tokens for a user.
     *
     * @param User $user
     * @param bool $rememberMe
     * @param string $ipAddress
     * @param string $userAgent
     * @return array
     */
    public static function generateTokens(User $user, bool $rememberMe, string $ipAddress, string $userAgent): array
    {
        $now = time();
        $accessTtl = config('jwt.ttl') * 60;
        $refreshTtl = $rememberMe ? 7 * 24 * 60 * 60 : 1 * 24 * 60 * 60;
        $secret = config('jwt.secret');

        // Access token payload
        $accessPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $accessTtl,
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            'rol' => $user->rol->nombre,
            'username' => $user->username,
            'nombre' => $user->name ?? 'N/A',
            'email' => $user->username ?? 'N/A',
        ];

        // Refresh token payload
        $refreshPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $refreshTtl,
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            'type' => 'refresh',
            'rol' => $user->rol->nombre,
        ];

        // Generate tokens
        $accessToken = JWT::encode($accessPayload, $secret, 'HS256');
        $refreshToken = JWT::encode($refreshPayload, $secret, 'HS256');

        // Manage active sessions (max 1)
        $activeSessions = DB::table('refresh_tokens')
            ->where('idUsuario', $user->idUsuario)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->get();

        if ($activeSessions->count() >= 1) {
            DB::table('refresh_tokens')
                ->where('idToken', $activeSessions->first()->idToken)
                ->delete();
        }

        // Insert new refresh token
        $refreshTokenId = DB::table('refresh_tokens')->insertGetId([
            'idUsuario' => $user->idUsuario,
            'refresh_token' => $refreshToken,
            'ip_address' => $ipAddress,
            'device' => $userAgent,
            'expires_at' => date('Y-m-d H:i:s', $now + $refreshTtl),
            'created_at' => date('Y-m-d H:i:s', $now),
            'updated_at' => date('Y-m-d H:i:s', $now),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'idRefreshToken' => $refreshTokenId,
            'expires_in' => $accessTtl,
        ];
    }
}