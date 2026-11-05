<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * Create a new customer account and generate a Sanctum
     * personal access token.
     *
     * Required fields and validation rules are defined
     * in RegisterRequest.
     *
     * Returns the newly created user and authentication token.
     *
     * This endpoint is publicly accessible.
     *
     * @param RegisterRequest $request Validated registration data.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * Authenticate a user.
     *
     * Verify the user's email and password.
     *
     * If the credentials are valid, generate a new
     * Sanctum personal access token.
     *
     * Returns HTTP 401 if the credentials are invalid.
     *
     * Required fields and validation rules are defined
     * in LoginRequest.
     *
     * This endpoint is publicly accessible.
     *
     * @param LoginRequest $request Validated login credentials.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * Retrieve the profile information of the currently
     * authenticated user.
     *
     * Requires a valid Sanctum Bearer Token.
     *
     * @authenticated
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user(),
        ]);
    }

    /**
     * Logout the authenticated user.
     *
     * Revoke the current Sanctum access token.
     *
     * The user must provide a valid Bearer Token.
     * Other tokens belonging to the same user remain active.
     *
     * Requires authentication.
     *
     * @authenticated
     */
    public function logout(): JsonResponse
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
