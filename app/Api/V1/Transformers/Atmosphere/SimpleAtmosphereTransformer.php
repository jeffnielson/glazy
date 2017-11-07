<?php

namespace App\Api\V1\Transformers\Atmosphere;

use App\Models\Glazy\Material\Atmosphere;

use League\Fractal;

class SimpleAtmosphereTransformer extends Fractal\TransformerAbstract
{
    public function transform(Atmosphere $atmosphere)
    {
        $atmosphereLookup = new Atmosphere();

        $atmosphere_data = [];

        if ($atmosphere)
        {
            $atmosphere_data = [
                'id' => $atmosphere->id
            ];
        }

        return $atmosphere_data;
    }
}