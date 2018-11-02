<?php

namespace App\Api\V1\Transformers\Material;

use App\Api\V1\Transformers\MaterialAnalysis\MaterialAnalysisTransformer;
use App\Models\Material;

class CalculatorCalculatedMaterialTransformer extends ShallowMaterialTransformer
{

    public function includeAnalysis(Material $material)
    {
        return $this->item($material->analysis, new MaterialAnalysisTransformer());
    }

}