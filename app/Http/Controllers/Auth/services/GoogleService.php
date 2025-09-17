<?php

namespace App\Http\Controllers\Auth\services;

use Google\Client;

class GoogleService
{
    /**
     * Verify Google ID token and return user information.
     *
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public static function verifyGoogleToken($token)
    {
        $client = new Client(['client_id' => env('GOOGLE_CLIENT_ID')]);

        try {
            $payload = $client->verifyIdToken($token);
            if (!$payload) {
                throw new \Exception('Token de Google inválido');
            }

            return [
                'email' => $payload['email'],
                'name'  => $payload['name'] ?? null,
                'sub'   => $payload['sub'], // Google user ID
                'given_name' => $payload['given_name'] ?? null,
                'family_name' => $payload['family_name'] ?? null,
                'picture' => $payload['picture'] ?? null,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error al verificar el token de Google: ' . $e->getMessage());
        }
    }
}
