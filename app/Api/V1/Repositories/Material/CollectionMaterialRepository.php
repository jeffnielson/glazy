<?php

namespace App\Api\V1\Repositories\Material;

use App\Api\V1\Repositories\Repository;

use App\Models\Glazy\Material\CollectionMaterial;

use Illuminate\Database\Eloquent\Model;

class CollectionMaterialRepository extends Repository
{
    public function getModel()
    {
        return new CollectionMaterial();
    }

    public function create(array $data)
    {
        $collection_id = $data['collection_id'];
        $material_id = $data['material_id'];

        $collectionMaterial = CollectionMaterial::where('collection_id', $collection_id)
            ->where('material_id', $material_id)->first();

        if ($collectionMaterial) {
            // This collection/material is already in the db
            return;
        }

        $collectionMaterial = $this->getModel();
        $collectionMaterial->collection_id = $collection_id;
        $collectionMaterial->material_id = $material_id;
        $collectionMaterial->save();

        $collection = $collectionMaterial->collection;
        $collection->incrementCount();
        $collection->save();

        return $collectionMaterial;
    }

    public function destroy($collection_id, $material_id)
    {
        $collectionMaterial =
            CollectionMaterial::where('collection_id', $collection_id)
            ->where('material_id', $material_id)->first();

        if (!$collectionMaterial) {
            return false;
        }

        $collection = $collectionMaterial->collection;
        $collectionMaterial->delete();
        $collection->decrementCount();
        $collection->save();

        return true;
    }

}
