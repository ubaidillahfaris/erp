<?php

namespace App\Scribe;

use App\Models\User;
use Illuminate\Http\Request;
use Knuckles\Scribe\Extracting\Strategies\Responses\ResponseCallsAuthenticator;

class SanctumAuthenticator extends ResponseCallsAuthenticator
{
    public function setup(
        Request $request,
        $route,
        array $annotations,
        array $authRules,
        array $responseCallRules
    ): void {
        // Find a user with superadmin role for the best coverage
        $user = User::role('superadmin')->first();

        if ($user) {
            // Log in the user for this request
            app('auth')->guard('sanctum')->setUser($user);

            // Add the Bearer token to the request headers
            $token = $user->createToken('scribe-test-token')->plainTextToken;
            $request->headers->set('Authorization', 'Bearer '.$token);
        }
    }
}
