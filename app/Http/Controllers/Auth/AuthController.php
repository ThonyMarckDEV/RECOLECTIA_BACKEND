<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Controllers\Auth\Services\GoogleService;
use App\Http\Controllers\Auth\Services\TokenService;
use App\Http\Controllers\Auth\Utilities\AuthValidations;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle username/password login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate request
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        try {
            // Find user by username with rol relationship
            $user = User::with('rol')->where('username', $request->username)->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Usuario o contraseña incorrectos',
                ], 401);
            }

            // Check user status
            if ($user->estado !== 1) {
                return response()->json([
                    'message' => 'Error: estado del usuario inactivo',
                ], 403);
            }

            // Delete existing refresh tokens for new session
            DB::table('refresh_tokens')
                ->where('idUsuario', $user->idUsuario)
                ->delete();
            Log::info('Sesiones antiguas eliminadas para idUsuario: ' . $user->idUsuario);

            // Generate tokens
            $tokens = TokenService::generateTokens($user, $request->remember_me ?? false, $request->ip(), $request->userAgent());

            return response()->json([
                'message' => 'Login exitoso',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'idRefreshToken' => $tokens['idRefreshToken'],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error en login: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al iniciar sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Google login.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function googleLogin(Request $request)
    {
        // Validate request
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            // Verify Google token and get user info
            $googleUser = GoogleService::verifyGoogleToken($request->token);

            // Find or create user using username (stores the Google email)
            $user = User::with('rol')->firstOrCreate(
                ['username' => $googleUser['email']],
                [
                    'username' => $googleUser['email'],
                    'name' => $googleUser['name'] ?? $googleUser['given_name'] ?? 'Usuario Google',
                    'idRol' => 2, // Default to 'usuario' role
                    'estado' => 1,
                ]
            );

            // Check user status
            if ($user->estado !== 1) {
                return response()->json([
                    'message' => 'Error: estado del usuario inactivo',
                ], 403);
            }

            // Delete existing refresh tokens for new session
            DB::table('refresh_tokens')
                ->where('idUsuario', $user->idUsuario)
                ->delete();
            Log::info('Sesiones antiguas eliminadas para idUsuario: ' . $user->idUsuario);

            // Generate tokens
            $tokens = TokenService::generateTokens($user, true, $request->ip(), $request->userAgent());

            return response()->json([
                'message' => 'Login con Google exitoso',
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'idRefreshToken' => $tokens['idRefreshToken'],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error en googleLogin: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al autenticar con Google',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Refresh access token using refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        // Validate request
        $validator = AuthValidations::validateRefreshToken($request);
        if ($validator->fails()) {
            Log::warning('Validación de refresh token fallida: ' . json_encode($validator->errors()));
            return response()->json([
                'message' => 'Refresh token inválido',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Decode refresh token
            $secret = config('jwt.secret');
            if (!$secret) {
                Log::error('JWT_SECRET no está definido en config');
                throw new \Exception('Clave secreta JWT no configurada');
            }
            Log::info('Intentando decodificar refresh token con secret: ' . substr($secret, 0, 10) . '...');
            $payload = JWT::decode($request->refresh_token, new Key($secret, 'HS256'));
            Log::info('Payload decodificado: ' . json_encode($payload));

            // Verify token type
            if (!isset($payload->type) || $payload->type !== 'refresh') {
                Log::warning('Token no es de tipo refresh: ' . json_encode($payload));
                return response()->json([
                    'message' => 'El token proporcionado no es un token de refresco',
                ], 401);
            }

            // Find user
            $user = User::with('rol')->find($payload->sub);
            if (!$user) {
                Log::error('Usuario no encontrado para sub: ' . $payload->sub);
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            // Generate new access token only
            $accessToken = TokenService::generateAccessToken($user, $request->ip(), $request->userAgent());
            Log::info('Nuevo access token generado para usuario: ' . $user->idUsuario);

            return response()->json([
                'message' => 'Token actualizado',
                'access_token' => $accessToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ], 200);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::error('Refresh token expirado: ' . $e->getMessage());
            return response()->json([
                'message' => 'Refresh token expirado',
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::error('Firma de refresh token inválida: ' . $e->getMessage());
            return response()->json([
                'message' => 'Refresh token inválido',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Error al procesar el token: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al procesar el token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate refresh token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateRefreshToken(Request $request)
    {
        // Validate request
        $validator = AuthValidations::validateRefreshTokenValidation($request);
        if ($validator->fails()) {
            Log::warning('Validación de refresh token ID fallida: ' . json_encode($validator->errors()));
            return response()->json([
                'valid' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Check refresh token in database
            $refreshToken = DB::table('refresh_tokens')
                ->where('idToken', $request->refresh_token_id)
                ->where('idUsuario', $request->userID)
                ->first();

            if (!$refreshToken) {
                Log::error('Refresh token no encontrado para idToken: ' . $request->refresh_token_id . ', idUsuario: ' . $request->userID);
                return response()->json([
                    'valid' => false,
                    'message' => 'Token no válido o no autorizado',
                ], 401);
            }

            // Check if token has expired
            if ($refreshToken->expires_at && now()->greaterThan($refreshToken->expires_at)) {
                Log::warning('Refresh token expirado para idToken: ' . $request->refresh_token_id);
                DB::table('refresh_tokens')
                    ->where('idToken', $request->refresh_token_id)
                    ->where('idUsuario', $request->userID)
                    ->delete();
                return response()->json([
                    'valid' => false,
                    'message' => 'Token expirado',
                ], 401);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Token válido',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error validando refresh token: ' . $e->getMessage());
            return response()->json([
                'valid' => false,
                'message' => 'Error al validar el token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle user logout.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Validate request
        $validator = AuthValidations::validateLogout($request);
        if ($validator->fails()) {
            Log::warning('Validación de logout fallida: ' . json_encode($validator->errors()));
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Delete refresh token
        $deleted = DB::table('refresh_tokens')
            ->where('idToken', $request->idToken)
            ->delete();

        if ($deleted) {
            Log::info('Logout exitoso para idToken: ' . $request->idToken);
            return response()->json([
                'message' => 'OK',
            ], 200);
        }

        Log::error('No se encontró refresh token para idToken: ' . $request->idToken);
        return response()->json([
            'message' => 'Error: No se encontró el token de refresco',
        ], 404);
    }
}