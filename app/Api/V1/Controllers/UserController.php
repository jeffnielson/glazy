<?php

namespace App\Api\V1\Controllers;

use App\Api\V1\Serializers\GlazySerializer;
use App\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\ChangePasswordRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Manager as FractalManager;
use App\Api\V1\Transformers\User\DeepUserTransformer;
use Auth;

use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', []);
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user()
    {
        $user = Auth::guard()->user();
        //$user->load('collections');

        /*
        $user->load(['collections' => function ($q) {
            $q->orderBy('name', 'asc');
        }])
        ->load('userMaterials')
        ->load('profile')
        ->load('unreadNotifications');
        //$user->load('profile');
        */

        // Reload the user with required relationships
        // Todo: Move to User class as method
        $user = User::with(['collections' =>
            function ($q) {
                $q->orderBy('name', 'asc');
            }])
            ->with('userMaterials')
            ->with('userMaterials.material')
            ->with('userMaterials.material.analysis')
            ->with('userMaterials.material.thumbnail')
            ->with('userMaterials.material.created_by_user')
            ->with('userMaterials.material.created_by_user.profile')
            ->with('profile')
            ->with(['unreadNotifications' =>
                function ($q) {
                    $q->limit(10);
                }])
            ->find($user->id);

        $resource = new FractalItem($user, new DeepUserTransformer());
        $manager = new FractalManager();
        $manager->setSerializer(new GlazySerializer());
        return $manager->createData($resource)->toArray();
        //return response()->json(['data' => $user]);
    }

    public function changePassword(ChangePasswordRequest $request, JWTAuth $JWTAuth)
    {
        $current_password = $request->input('old_password');

        // Verify the user knows the original password
        $credentials['email'] = $request->input('email');
        $credentials['password'] = $current_password;
        try {
            $token = Auth::guard()->attempt($credentials);
            if(!$token) {
                throw new AccessDeniedHttpException();
            }
        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        $user = Auth::guard()->user();

        if (!Hash::check($current_password, $user->password)) {
            throw new AccessDeniedHttpException();
        }

        //Change Password
        $user->password = $request->input('password');
        $user->save();

        return response()->json([
            'status' => 'ok',
            'token' => $JWTAuth->fromUser($user)
        ]);
    }

}
