<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Valida credenciales y emite un token de texto plano.
     */
    public function login(Request $request)
    {
        // 1. Validación estricta de los datos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Búsqueda del usuario en la base de datos
        $user = User::where('email', $request->email)->first();

        // 3. Verificación de la contraseña encriptada
        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Retorna un error 401 (No Autorizado) genérico por seguridad
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        // 4. Generación del Token Sanctum
        // El nombre del token puede ser el dispositivo desde donde se conecta
        $token = $user->createToken('pwa-dispositivo')->plainTextToken;

        // 5. Respuesta JSON con el token
        return response()->json([
            'message' => 'Autenticación exitosa',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    /**
     * Revoca el token actual del usuario (Cerrar sesión).
     */
    public function logout(Request $request)
    {
        // Borra el token que se utilizó para autenticar la petición actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ], 200);
    }
}