<?php

namespace App\Api\V1\Transformers\MaterialAnalysis;

use DerekPhilipAu\Ceramicscalc\Models\Analysis\Analysis;

use App\Models\MaterialAnalysis;

use League\Fractal;

class MaterialPercentageAnalysisTransformer extends Fractal\TransformerAbstract
{
    public function transform($analysis)
    {
        $analysis_data = array();

        if ($analysis)
        {
            $percent_analysis = [];
            foreach(Analysis::OXIDE_NAMES as $oxide_name)
            {
                $percent_oxide_name = $oxide_name.'_percent';

                if ($analysis[$percent_oxide_name] > 0)
                {
                    $percent_analysis[$oxide_name] = $analysis[$percent_oxide_name];
                }
            }
            $percent_analysis['loi'] = $analysis['loi'];
            $analysis_data['percentageAnalysis'] = $percent_analysis;
        }

        return $analysis_data;
    }

}