<?php

namespace App\Console\Commands;

use App\Api\V1\Repositories\MaterialMaterialRepository;
use App\Models\Material;
use App\Models\MaterialAnalysis;
use Illuminate\Console\Command;

class UpdateRecipeAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updaterecipeanalysis {id : The ID of the recipe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update recipe analysis for a single recipe';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        echo "Updating recipe analysis...\n";

        $id = $this->argument('id');

        if (!$id) {
            echo "No ID given";
            return;
        }

        $materialMaterialRepository = new MaterialMaterialRepository();

        $material = Material::with('components')
            ->with('analysis')
            ->where('is_analysis', false)
            ->where('is_primitive', false)
            ->find($id);

        if (!$material)
        {
            echo "No material found with ID = ".$id."\n";
            return;
        }

        echo "Updating: ".$material->id." ".$material->name."\n";

        if ($material->analysis) {
            $analysis = $material->analysis;
            echo "Old Analysis UMF: SiO2 ".$analysis->SiO2_umf." Al2O3 ".$analysis->Al2O3_umf."\n";
            $materialMaterialRepository->updateAnalysis($material);
            echo "New Analysis UMF: SiO2 ".$analysis->SiO2_umf." Al2O3 ".$analysis->Al2O3_umf."\n";
        }

        echo "Completed material update.\n";
    }
}
