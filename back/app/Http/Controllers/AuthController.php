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

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            // Log incoming request for debugging
            Log::info('Registration attempt', ['request_data' => $request->all()]);

            // Validate request
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

            Log::info('Register request data', $request->all());
            // Create user
            $data = $request->json()->all();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            Log::info('User created successfully', ['user_id' => $user->id]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
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
            $data = $request->json()->all();

            // Fallback if JSON is empty
            if (empty($data)) {
                $data = $request->all();
            }

            Log::debug('Login attempt data', $data);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No login data provided',
                ], 400);
            }

            $validator = Validator::make($data, [
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

            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'github_id' => $request->user()->github_id,
                'created_at' => $request->user()->created_at,
            ]
        ]);
    }
    /**
     * Redirect to GitHub for authentication
     */
    public function redirectToGithub(): JsonResponse
    {
        return response()->json([
            'url' => Socialite::driver('github')->stateless()->redirect()->getTargetUrl()
        ]);
    }

    /**
     * Handle GitHub callback
     */
    public function handleGithubCallback(Request $request): JsonResponse
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            // Check if we have a valid GitHub user
            if (!$githubUser || !$githubUser->getEmail()) {
                throw new \Exception('Invalid GitHub user data');
            }

            // Find existing user by GitHub ID
            $user = User::where('github_id', $githubUser->getId())->first();

            if (!$user) {
                // Check if user exists with this email (to link accounts)
                $user = User::where('email', $githubUser->getEmail())->first();

                if ($user) {
                    // Update existing user with GitHub ID
                    $user->github_id = $githubUser->getId();
                    $user->save();
                } else {
                    // Create new user with GitHub data
                    $user = User::create([
                        'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                        'email' => $githubUser->getEmail(),
                        'github_id' => $githubUser->getId(),
                        'password' => null, // No password for GitHub users
                    ]);
                }
            }

            // Log in the user
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'GitHub authentication successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'github_id' => $user->github_id,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('GitHub auth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'GitHub authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 401);
        }
    }

    /**
     * Disconnect GitHub from user account
     */
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

            // If user has no password, require them to set one first
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

    /**
     * Set password for GitHub-authenticated users
     */
    public function setPassword(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Only allow password set if user doesn't have one
            if ($user->password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password already set'
                ], 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password set successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Set password error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to set password'
            ], 500);
        }
    }
}
