<?php

namespace App\Api\V1\Controllers\Glazy;

use App\Api\V1\Controllers\ApiController;
use App\Api\V1\Repositories\MaterialRepository;
use App\Api\V1\Transformers\Material\MaterialTransformer;

use App\Api\V1\Repositories\MaterialMaterialRepository;

use App\Api\V1\Requests\MaterialMaterial\UpdateMaterialMaterialRequest;

use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Manager as FractalManager;

use App\Api\V1\Serializers\GlazySerializer;

use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

use Auth;

/**
 * Class FrontendController
 * @package App\Http\Controllers
 */
class MaterialMaterialController extends ApiBaseController
{

    /**
     * @var MaterialMaterialRepository
     */
	protected $materialMaterialRepository;

    public function __construct(
        MaterialMaterialRepository $materialMaterialRepository,
        FractalManager $manager,
        GlazySerializer $serializer)
    {
        parent::__construct($manager, $serializer);
        $this->materialMaterialRepository = $materialMaterialRepository;
    }

    public function store(UpdateMaterialMaterialRequest $request)
    {
        $data = $request->all();

        $newName = $data['newName'];
        if (!$newName) {
            $newName = 'Please give this recipe a name!';
        }
        // Create a new Material
        $materialRepository = new MaterialRepository();

        $material = null;
        if (isset($data["originalId"]) && !empty($data["originalId"] && $data["originalId"] > 0)) {
            // This material needs to be copied, first
            $oldMaterial = $materialRepository->getWithDetails($data['originalId']);
            if (!$oldMaterial) {
                return $this->respondNotFound('Original recipe does not exist');
            }
            if ($oldMaterial->is_private) {
                if (!Auth::guard()->user()) {
                    return $this->respondUnauthorized('Original recipe is private. Please login.');
                } else if (!Auth::guard()->user()->can('view', $oldMaterial)) {
                    return $this->respondUnauthorized('Original recipe is private.');
                }
            }
            $material = $materialRepository->copy($oldMaterial);
            if ($newName) {
                $material->name = $newName;
                $material->save();
            }
        }
        else {
            $material_data = [
                'name' => $newName,
                'is_analysis' => false,
                'is_primitive' => false,
                'material_type_id' => 460
            ];
            $material = $materialRepository->create($material_data);
        }

        if (!$material) {
            return $this->respondNotFound('Recipe does not exist');
        }
        if ($material->is_private) {
            if (!Auth::guard()->user()) {
                return $this->respondUnauthorized('Recipe is private. Please login.');
            } else if (!Auth::guard()->user()->can('view', $material)) {
                return $this->respondUnauthorized('Recipe is private.');
            }
        }

        // Add material ingredients to new recipe
        $material = $this->materialMaterialRepository->updateAll($material->id, $data);

        $resource = new FractalItem($material, new MaterialTransformer());
        return $this->manager->createData($resource)->toArray();
    }

    public function update($recipeId, UpdateMaterialMaterialRequest $request)
    {
        $materialRepository = new MaterialRepository();
        $material = $materialRepository->getWithDetails($recipeId);
        if (!Auth::guard()->user()->can('update', $material)) {
            return $this->respondUnauthorized('This recipe does not belong to you.');
        }

        $data = $request->all();

        $material = $this->materialMaterialRepository->updateAll($recipeId, $data);

        $resource = new FractalItem($material, new MaterialTransformer());
        return $this->manager->createData($resource)->toArray();
    }

}
