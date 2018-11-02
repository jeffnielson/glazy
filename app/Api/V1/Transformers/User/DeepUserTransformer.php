<?php

namespace App\Api\V1\Transformers\User;

use App\Api\V1\Transformers\Notification\NotificationTransformer;
use App\Api\V1\Transformers\UserMaterial\UserMaterialTransformer;
use App\User;

use App\Api\V1\Transformers\UserProfile\UserProfileTransformer;
use App\Api\V1\Transformers\Collection\CollectionTransformer;
use App\Api\V1\Transformers\JsonDateTransformer;

use League\Fractal;

class DeepUserTransformer extends Fractal\TransformerAbstract
{
    use JsonDateTransformer;

    const DB_ID = 'id';
    const DB_EMAIL = 'email';
    const DB_NAME = 'name';
    const DB_CREATED_AT = 'created_at';
    const DB_UPDATED_AT = 'updated_at';

    const JSON_NAMES = [
        self::DB_ID         => 'id',
        self::DB_EMAIL      => 'email',
        self::DB_NAME       => 'name',
        self::DB_CREATED_AT => 'createdAt',
        self::DB_UPDATED_AT => 'updatedAt'
    ];

    protected $defaultIncludes = [
        'profile',
        'collections',
        'unreadNotifications',
        'userMaterials'
    ];

    public function transform(User $user)
    {
        $user_data = [];

        $user_data[self::JSON_NAMES[self::DB_ID]] = $user[self::DB_ID];
        //$user_data[self::JSON_NAMES[self::DB_EMAIL]] = $user[self::DB_EMAIL];
        $user_data[self::JSON_NAMES[self::DB_NAME]] = $user[self::DB_NAME];
        $user_data[self::JSON_NAMES[self::DB_CREATED_AT]] = $this->jsonDate($user[self::DB_CREATED_AT]);
        $user_data[self::JSON_NAMES[self::DB_UPDATED_AT]] = $this->jsonDate($user[self::DB_UPDATED_AT]);

        return $user_data;
    }

    public function includeProfile(User $user)
    {
        if ($user->profile) {
            return $this->item($user->profile, new UserProfileTransformer());
        }
    }

    public function includeCollections(User $user)
    {
        if ($user->collections && count($user->collections) > 0) {
            return $this->collection($user->collections, new CollectionTransformer());
        }
    }

    public function includeUnreadNotifications(User $user)
    {
        if ($user->unreadNotifications && count($user->unreadNotifications) > 0) {
            return $this->collection($user->unreadNotifications, new NotificationTransformer());
        }
    }

    public function includeUserMaterials(User $user)
    {
        if ($user->userMaterials && count($user->userMaterials) > 0) {
            return $this->collection($user->userMaterials, new UserMaterialTransformer());
        }
    }


}