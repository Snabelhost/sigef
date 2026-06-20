<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Autenticar utilizador e gerar token de acesso.
     *
     * POST /api/v1/login
     * Body: { "email": "...", "password": "...", "device_name": "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorrectas.'],
            ]);
        }

        $deviceName = $request->input('device_name', 'api-token');

        $token = $user->createToken($deviceName);

        return response()->json([
            'message' => 'Autenticação realizada com sucesso.',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Revogar o token actual do utilizador.
     *
     * POST /api/v1/logout
     * Header: Authorization: Bearer TOKEN
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso. Token revogado.',
        ]);
    }

    /**
     * Revogar todos os tokens do utilizador.
     *
     * POST /api/v1/logout-all
     * Header: Authorization: Bearer TOKEN
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Todas as sessões foram encerradas. Todos os tokens revogados.',
        ]);
    }
}
