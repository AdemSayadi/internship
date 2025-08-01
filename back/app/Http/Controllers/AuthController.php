<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            Log::info('Registration attempt', ['request_data' => $request->all()]);

            $validator = Validator::make($request->json()->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                Log::warning('Registration validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => $user->only(['id', 'name', 'email', 'created_at']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Check if user has a password set (for GitHub-only users)
            if (!$user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account was created with GitHub. Please login with GitHub or set a password first.',
                    'requires_github_login' => true
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user->only(['id', 'name', 'email']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->only([
                'id', 'name', 'email', 'github_id', 'created_at'
            ])
        ]);
    }

    public function redirectToGithub(): JsonResponse
    {
        $redirectUrl = Socialite::driver('github')
            ->scopes(['read:user', 'user:email', 'repo'])
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'url' => $redirectUrl
        ]);
    }

    public function handleGithubCallback(Request $request)
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            if (!$githubUser || !$githubUser->getEmail()) {
                throw new \Exception('Could not retrieve GitHub user data');
            }

            $user = User::firstOrCreate(
                ['email' => $githubUser->getEmail()],
                [
                    'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                    'github_id' => $githubUser->getId(),
                    'password' => null,
                ]
            );

            // Update github_id if user exists but wasn't connected
            if (empty($user->github_id)) {
                $user->github_id = $githubUser->getId();
                $user->save();
            }

            $token = $user->createToken('github_token')->plainTextToken;

            // For popup-based auth, redirect to callback page with success data
            $callbackUrl = config('app.frontend_url', 'http://localhost:5173') . '/auth/github/callback';
            $callbackUrl .= '?' . http_build_query([
                    'success' => 'true',
                    'token' => $token,
                    'user' => json_encode($user->only(['id', 'name', 'email', 'github_id']))
                ]);

            return redirect($callbackUrl);

        } catch (\Exception $e) {
            Log::error('GitHub auth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $callbackUrl = config('app.frontend_url', 'http://localhost:5173') . '/auth/github/callback';
            $callbackUrl .= '?' . http_build_query([
                    'success' => 'false',
                    'error' => 'GitHub authentication failed'
                ]);

            return redirect($callbackUrl);
        }
    }

    public function disconnectGithub(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->github_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No GitHub account connected'
                ], 400);
            }

            if (!$user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please set a password before disconnecting GitHub',
                    'requires_password' => true
                ], 422);
            }

            $user->github_id = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'GitHub account disconnected'
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub disconnect error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect GitHub'
            ], 500);
        }
    }

    public function setPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password already set. Please use the regular login.'
                ], 400);
            }

            // Verify this is a GitHub user
            if (!$user->github_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature is only available for GitHub users'
                ], 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Create token for the user
            $token = $user->createToken('password_set_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Password set successfully',
                'user' => $user->only(['id', 'name', 'email', 'github_id']),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Set password error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to set password'
            ], 500);
        }
    }
}
