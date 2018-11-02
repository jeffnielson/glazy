<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Repositories\CollectionRepository;
use App\Api\V1\Repositories\UserMaterialRepository;
use App\Mail\UserRegistered;
use App\Models\Collection;
use Config;
use App\User;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Auth;

class SignUpController extends Controller
{
    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            // A user is already registered with this email address
            throw new AccessDeniedHttpException('This email has already been used to register.');
        }

        $user = new User($request->all());
        if(!$user->save()) {
            throw new HttpException(500);
        }

        Mail::send(new UserRegistered($user));

        // For new users, create an initial list of user materials (inventory) they can use in the calculator.
        $userMaterialRepository = new UserMaterialRepository();
        $userMaterialRepository->initializeUserMaterials($user->id);

        // For new users, create an initial bookmarks folder
        $collectionRepository = new CollectionRepository();
        $defaultCollection = [
            'name' => 'Bookmarks',
            'description' => 'Your personal bookmarks'
        ];
        $collectionRepository->create($defaultCollection, $user->id);

        if(!Config::get('boilerplate.sign_up.release_token')) {
            return response()->json([
                'status' => 'ok'
            ], 201);
        }
        $token = $JWTAuth->fromUser($user);

        // Reload the user with required relationships
        // Todo: Move to User class as method
        $user = User::with(['collections' =>
            function ($q) {
                $q->orderBy('name', 'asc');
            }])
            ->with('userMaterials')
            ->with('profile')
            ->with(['unreadNotifications' =>
                function ($q) {
                    $q->limit(10);
                }])
            ->find($user->id);

        return response([
            'status' => 'success',
            'token' => $token,
            'expires_in' => Auth::guard()->factory()->getTTL() * 60,
            'data' => $user
        ])->header('Access-Control-Expose-Headers', 'Authorization')
            ->header('Authorization', 'Bearer '.$token);
    }
}
