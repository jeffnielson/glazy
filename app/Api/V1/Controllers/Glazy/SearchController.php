<?php

namespace App\Api\V1\Controllers\Glazy;

use App\Api\V1\Requests\Search\SimilarMaterialsMaterialRequest;
use App\Api\V1\Requests\Search\SimilarUnityFormulaRequest;

use App\Api\V1\Transformers\ChartMaterialTransformer;
use App\Api\V1\Transformers\Material\ChartPointMaterialTransformer;
use App\Api\V1\Transformers\Material\MaterialTransformer;
use App\Api\V1\Transformers\MaterialImage\MaterialImageTransformer;
use App\Api\V1\Transformers\NoComponentsMaterialTransformer;
use App\Api\V1\Transformers\Material\ShallowMaterialFromMaterialImageTransformer;
use App\Api\V1\Transformers\Material\ShallowMaterialTransformer;

use App\Api\V1\Repositories\MaterialRepository;

use App\Api\V1\Transformers\User\UserTransformer;
use App\Models\Material;

use App\Models\MaterialAnalysis;
use App\Models\MaterialImage;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use DerekPhilipAu\Ceramicscalc\Models\Analysis\Analysis;

use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\Manager as FractalManager;

use App\Api\V1\Serializers\GlazySerializer;


/**
 * Class FrontendController
 * @package App\Http\Controllers
 */
class SearchController extends ApiBaseController
{
    const MAX_ITEMS_PER_PAGE = 50;
    const DEFAULT_ITEMS_PER_PAGE = 48;
//    const MAX_ITEMS_PER_PAGE = 1000;
//    const DEFAULT_ITEMS_PER_PAGE = 1000;

    /**
     * @var MaterialRepository
     */
	protected $materialRepository;

    public function __construct(
        MaterialRepository $materialRepository,
        FractalManager $manager,
        GlazySerializer $serializer)
    {
        parent::__construct($manager, $serializer);
        $this->materialRepository = $materialRepository;
    }

    public function index(Request $request)
    {
        // DB::enableQueryLog();

        $isColorQuery = false;

        $page = (int)$request->input('p');
        if (!$page || $page <= 0) {
            // if page not specified, start w/ page 1
            $page = 1;
        }

        $search_user_id = $request->input('u');
        $collection_id = (int)$request->input('collection');
        $is_primitive = (int)$request->input('primitive'); // 0 == false
        $is_analysis = (int)$request->input('analysis'); // 0 == false
        $keywords = $request->input('keywords');
        $has_thumbnail = $request->input('photo');
        $base_type_id = (int)$request->input('base_type');
        $type_id = (int)$request->input('type');
        $orton_cone_id = $request->input('cone');
        $atmosphere_id = (int)$request->input('atmosphere');
        $surface_type_id = (int)$request->input('surface');
        $transparency_type_id = (int)$request->input('transparency');
        $material_state_id = (int)$request->input('state');
        $country_id = (int)$request->input('country');
        $username = $request->input('username');
        $hex_color = $request->input('hex_color');

        $is_deep = (int)$request->input('deep'); // 0 == false

        $r = null;
        $g = null;
        $b = null;
        $is_image_search = $request->input('images');
        $ids = $request->input('id');

        $order_id = $request->input('order');
        $view_option = $request->input('view');
        $view_option_paginate = $request->input('pag');

        $count = $request->input('count');

        $jsonUser = null;
        if ($search_user_id) {
            // We're searching within a user's recipes
            $searchUser = null;

            if (ctype_digit($search_user_id)) {
                $search_user_id = (int)$search_user_id;
                // this is a primary id for a user
                $searchUser = User::with('profile')
                    ->with(['collections' => function ($q) {
                        $q->orderBy('name', 'asc');
                    }])
                    ->whereNull('deleted_at')
                    ->find($search_user_id);
            } else {
                // this is an alphanumeric username
                $searchUser = User::with('profile')
                    ->with('collections.created_by_user')
                    ->with('collections.created_by_user.profile')
                    ->with(['collections' => function ($q) {
                        $q->orderBy('name', 'asc');
                    }])->whereHas('profile', function($q) use ($search_user_id) {
                        $q->where('username', $search_user_id);
                    })
                    ->whereNull('deleted_at')
                    ->first();

                if ($searchUser) {
                    $search_user_id = (int)$searchUser->id;
                }
            }

            if ($searchUser) {
                $this->manager->parseIncludes(['collections', 'profile']);
                $userResource = new FractalItem($searchUser, new UserTransformer());
                $jsonUser = $this->manager->createData($userResource)->toArray();
            }
        }

        $query = Material::query();

        $query->select(
            'materials.id', 'materials.name',
            'materials.is_primitive', 'materials.material_type_id',
            'materials.is_analysis', 'materials.is_theoretical', 'materials.material_state_id',
            'materials.from_orton_cone_id', 'materials.to_orton_cone_id',
            'materials.surface_type_id', 'materials.transparency_type_id', 'materials.country_id',
            'materials.rating_total', 'materials.rating_number', 'materials.rating_average',
            'materials.rgb_r', 'materials.rgb_g', 'materials.rgb_b', 'materials.thumbnail_id',
            'materials.is_private', 'materials.is_archived', 'materials.created_by_user_id',
            'materials.updated_by_user_id', 'materials.created_at', 'materials.updated_at'
        );

//        $user = null;
        $current_user_id = null;
        if (Auth::check())
        {
            $user = Auth::guard('api')->user();
            $current_user_id = $user->id;
        }

        // TODO: Why is query not handling this automatically?
        //todo fixed in model        $query->whereNull('deleted_at');

        if ($collection_id) {
            // Collections can contain materials not belonging to the currently viewed user
            // So don't limit the search by search user's own materials
            $query->ofUserViewable($current_user_id, null);
        }
        elseif ($search_user_id && !$is_image_search) {
            $query->ofUserViewable($current_user_id, $search_user_id);
        }
        else {
            $query->ofUserViewable($current_user_id, null);
        }

        if ($collection_id) {
            $query->ofCollection($collection_id);
        }

        $query->ofKeywords($keywords);

        if ($has_thumbnail) {
            $query->ofHasThumbnail($has_thumbnail);
        }

        $query->ofMaterialType($base_type_id, $type_id);

        $query->ofOrtonCone($orton_cone_id);

        $query->ofAtmosphere($atmosphere_id);

        $query->ofSurfaceType($surface_type_id);

        $query->ofTransparencyType($transparency_type_id);

        $query->ofMaterialState($material_state_id);

        $query->ofCountry($country_id);

        $query->ofUser($username);

        $query->ofIds($ids);

//        $query->where('materials.is_analysis', false);

        $query->with('analysis');
        $query->with('atmospheres');
        $query->with('material_type');
        if ($is_deep) {
            $query->with('components');
        }
        else {
            $query->with('shallowComponents');
        }
        $query->with('thumbnail');
        $query->with('created_by_user');
        $query->with('created_by_user.profile');

        // For image searches, we don't care if the materials are primitive or not.
        // 20181119: For Collections, also don't limit by primitive/analysis
        // All other searches should filter by primitive:
        if (!$is_image_search && !$collection_id) {
            if ($is_primitive) {
                $query->where('materials.is_primitive', true);
            }
            else {
                $query->where('materials.is_primitive', false);
            }
            if ($is_analysis) {
                $query->where('materials.is_analysis', true);
            }
            else {
                $query->where('materials.is_analysis', false);
            }
        }

        if (!empty($hex_color)) {
            list($r, $g, $b) = sscanf($hex_color, "%02x%02x%02x");
        }

        if (is_int($r) && is_int($g) && is_int($b)) {

            /*
            $imageQuery = $this->getImageQuery($query, $r, $g, $b);

            if ($count && $count < self::MAX_ITEMS_PER_PAGE) {
                $recipes = $imageQuery->paginate($count, ['*'], 'page', $page);
            }
            else {
                $recipes = $imageQuery->paginate(self::DEFAULT_ITEMS_PER_PAGE, ['*'], 'page', $page);
            }

            $this->manager->parseIncludes(['atmospheres', 'materialComponents', 'thumbnail', 'createdByUser']);

            $resource = new FractalCollection($recipes, new MaterialImageTransformer());
            $resource->setPaginator(new IlluminatePaginatorAdapter($recipes));

            if ($jsonUser) {
                $resource->setMetaValue('user', $jsonUser);
            }

            return $this->manager->createData($resource)->toArray();
            */

            $query->join('material_images', 'material_images.material_id', '=', 'materials.id');

            $selectColor = '((CAST(material_images.dominant_rgb_r AS SIGNED) - '.$r.')*(CAST(material_images.dominant_rgb_r AS SIGNED) - '.$r.'))';
            $selectColor .= ' + ((CAST(material_images.dominant_rgb_g AS SIGNED) - '.$g.')*(CAST(material_images.dominant_rgb_g AS SIGNED) - '.$g.'))';
            $selectColor .= ' + ((CAST(material_images.dominant_rgb_b AS SIGNED) - '.$b.')*(CAST(material_images.dominant_rgb_b AS SIGNED) - '.$b.'))';
            $selectColor .= ' AS colordiff, material_images.filename AS selected_image_filename, ';
            $selectColor .= ' material_images.dominant_rgb_r AS selected_image_dominant_rgb_r, ';
            $selectColor .= ' material_images.secondary_rgb_r AS selected_image_secondary_rgb_r';

            $query->selectRaw($selectColor);

            $query->addSelect(
                'material_images.filename AS selected_image_filename',
                'material_images.dominant_rgb_r AS selected_image_dominant_rgb_r',
                'material_images.dominant_rgb_g AS selected_image_dominant_rgb_g',
                'material_images.dominant_rgb_b AS selected_image_dominant_rgb_b',
                'material_images.secondary_rgb_r AS selected_image_secondary_rgb_r',
                'material_images.secondary_rgb_g AS selected_image_secondary_rgb_g',
                'material_images.secondary_rgb_b AS selected_image_secondary_rgb_b');

            $query->whereNotNull('material_images.dominant_rgb_r');

            if ($is_image_search && $search_user_id) {
                $query->where('material_images.created_by_user_id', '=', $search_user_id);
            }

            $query->orderByRaw('colordiff ASC');
        }
        elseif ($is_image_search && $search_user_id) {
            $query->join('material_images', 'material_images.material_id', '=', 'materials.id');

            $query->addSelect(
                'material_images.filename AS selected_image_filename',
                'material_images.dominant_rgb_r AS selected_image_dominant_rgb_r',
                'material_images.dominant_rgb_g AS selected_image_dominant_rgb_g',
                'material_images.dominant_rgb_b AS selected_image_dominant_rgb_b',
                'material_images.secondary_rgb_r AS selected_image_secondary_rgb_r',
                'material_images.secondary_rgb_g AS selected_image_secondary_rgb_g',
                'material_images.secondary_rgb_b AS selected_image_secondary_rgb_b');

            $query->where('material_images.created_by_user_id', '=', $search_user_id);

            $query->orderBy('material_images.updated_at', 'DESC');
        }

        if (!empty($order_id))
        {
            if ($order_id == 'az')
            {
                $query->orderBy('materials.name', 'ASC');
            }
            elseif ($order_id == 'za')
            {
                $query->orderBy('materials.name', 'DESC');
            }
            elseif ($order_id == 'oldest')
            {
                $query->orderBy('materials.updated_at', 'ASC');
            }
            elseif ($order_id == 'best')
            {
                $query->orderBy('materials.rating_average', 'DESC');
                $query->orderBy('materials.rating_number', 'DESC');
                $query->orderBy('materials.updated_at', 'DESC');
            }
            elseif ($order_id == 'worst')
            {
                $query->orderBy('materials.rating_average', 'ASC');
                $query->orderBy('materials.rating_number', 'DESC');
                $query->orderBy('materials.updated_at', 'DESC');
            }
            else
            {
                $query->orderBy('materials.updated_at', 'DESC');
            }
        }
        else
        {
            $query->orderBy('materials.updated_at', 'DESC');
        }

        if ($count && $count < self::MAX_ITEMS_PER_PAGE) {
            $recipes = $query->paginate($count, ['*'], 'page', $page);
        }
        else {
            $recipes = $query->paginate(self::DEFAULT_ITEMS_PER_PAGE, ['*'], 'page', $page);
        }

        /*
        if (!$recipes || $recipes->total() == 0)
        {
            return $this->respondNotFound('No recipes found.');
        }
        */

        //$this->manager->parseIncludes(['atmospheres', 'materialComponents', 'thumbnail', 'createdByUser']);
        $this->manager->parseIncludes(['atmospheres', 'materialComponents']);

        $resource = new FractalCollection($recipes, new ShallowMaterialTransformer());
        $resource->setPaginator(new IlluminatePaginatorAdapter($recipes));

        if ($jsonUser) {
            $resource->setMetaValue('user', $jsonUser);
        }

        // DB::disableQueryLog();

        return $this->manager->createData($resource)->toArray();
    }

    /*
     * TODO: check query needs optimizing
     */
    /*
    protected function getImageQuery($materialQuery, $r, $g, $b)
    {
        $imageQuery = MaterialImage::query();

        $imageQuery->join('materials', 'material_images.material_id', '=', 'materials.id');

        $selectColor = 'material_images.*, ';
        $selectColor .= '((CAST(material_images.dominant_rgb_r AS SIGNED) - '.$r.')*(CAST(material_images.dominant_rgb_r AS SIGNED) - '.$r.'))';
        $selectColor .= ' + ((CAST(material_images.dominant_rgb_g AS SIGNED) - '.$g.')*(CAST(material_images.dominant_rgb_g AS SIGNED) - '.$g.'))';
        $selectColor .= ' + ((CAST(material_images.dominant_rgb_b AS SIGNED) - '.$b.')*(CAST(material_images.dominant_rgb_b AS SIGNED) - '.$b.'))';
        $selectColor .= ' AS colordiff ';
        $selectColor .= ', materials.*';
        $imageQuery->selectRaw($selectColor);

        $q = $materialQuery->getQuery();
        $wheres = $q->wheres;
        $bindings = $q->getBindings();
        $imageQuery->whereHas('material', function ($q) use ($wheres, $bindings) {
            $q->mergeWheres($wheres, $bindings);
        });

        $imageQuery->whereNotNull('material_images.dominant_rgb_r');

        $imageQuery->with('material.analysis');
        $imageQuery->with('material.atmospheres');
        $imageQuery->with('material.material_type');
        $imageQuery->with('material.shallowComponents');
        $imageQuery->with('material.thumbnail');
        $imageQuery->with('material.created_by_user');

        $imageQuery->orderByRaw('colordiff ASC');

        return $imageQuery;

    }
    */

    /**
     * @param $id The Recipe ID
     * @return \Illuminate\Http\JsonResponse  Returns a list of nearest neighbors

        select id, `SiO2_umf`,
        `Al2O3_umf`,
        `SiO2_Al2O3`,
        SiO2_Al2O3_ratio_umf
        from material_analyses
        where SiO2_Al2O3_ratio_umf IS NOT NULL
        order by
        st_distance(SiO2_Al2O3, ST_GeomFromText('POINT(2.90345711144 0.371202367614)')) ASC;
    */
    public function nearestXY(Request $request)
    {

        $material_id = (int)$request->input('material_id');
        $material_type_id = (int)$request->input('material_type_id');
        $orton_cone_id = $request->input('cone');
        $isMine = $request->input('isMine');
        $xOxide = $request->input('x');
        $yOxide = $request->input('y');

        $yOxide = $this->getDBFieldFromJSONField($yOxide, Analysis::SiO2.'_umf');
        $xOxide = $this->getDBFieldFromJSONField($xOxide, Analysis::Al2O3.'_umf');

        $material = MaterialAnalysis::where('material_id', '=', $material_id)->first();

        if (!$material)
        {
            return $this->respondNotFound('Search material analysis not found.');
        }

        $yOxide_umf = $material[$yOxide];
        $xOxide_umf = $material[$xOxide];

        if ($yOxide_umf === null) {
            $yOxide_umf = 0;
        }
        if ($xOxide_umf === null) {
            $xOxide_umf = 0;
        }

        $query = Material::query();

        $distanceField =
            '('.$yOxide_umf.' - analyses.'.$yOxide.') * ('.$yOxide_umf.' - analyses.'.$yOxide.') + '
            . '('.$xOxide_umf.' - analyses.'.$xOxide.') * ('.$xOxide_umf.' - analyses.'.$xOxide.')';

        /*
         * 3-axis query:
        if ($oxide3) {
            $distanceField =
                '('.$yOxide_umf.' - analyses.'.$yOxide.') * ('.$yOxide_umf.' - analyses.'.$yOxide.') + '
                . '('.$xOxide_umf.' - analyses.'.$xOxide.') * ('.$xOxide_umf.' - analyses.'.$xOxide.') + '
                . '('.$oxide3_umf.' - analyses.'.$oxide3.') * ('.$oxide3_umf.' - analyses.'.$oxide3.')';
        }
        */

        $selectFields = 'materials.id, materials.name, materials.is_primitive, materials.material_type_id, '
            .'materials.is_analysis, materials.is_theoretical, materials.material_state_id, materials.from_orton_cone_id, '
            .'materials.to_orton_cone_id, materials.surface_type_id, materials.transparency_type_id, '
            .'materials.thumbnail_id, materials.is_private, materials.is_archived, materials.created_by_user_id, '
            .'materials.created_at, materials.updated_at, '
            . $distanceField
            .' as distance';

        $query->join('material_analyses as analyses', 'analyses.material_id', '=', 'materials.id');

        $query->select(DB::raw($selectFields));

        $query->with('material_type');
        $query->with('atmospheres');
        $query->with('analysis');
        $query->with('thumbnail');
        $query->with('created_by_user');
        $query->with('created_by_user.profile');

        // Only search for recipes
        // TODO:  Why not also search for primitives?
        // $query->where('materials.is_primitive', false);

        // Needs to have both oxides in order to be charted
        // Hmm, Does it need both oxides?
        // $query->where('analyses.'.$yOxide.'_umf', '>', 0);
        // $query->where('analyses.'.$xOxide.'_umf', '>', 0);
        // Unneeded $query->where('analyses.SiO2_Al2O3_ratio_umf', '>', 0);

        //$query->where('analyses.'.$yOxide, '<>', null);
        //$query->where('analyses.'.$xOxide, '<>', null);

        if ($material_type_id > 0) {
            // Search only within a type
            $query->ofMaterialType(null, $material_type_id);
        }

        $query->ofOrtonCone($orton_cone_id);

        $current_user_id = null;
        if (Auth::check())
        {
            $user = Auth::guard('api')->user();
            $current_user_id = $user->id;
        }
        if ($isMine && $current_user_id) {
            $query->ofUser($current_user_id);
        }
        else {
            $query->ofUserViewable($current_user_id, null);
        }

        // Exclude the original material id
        $query->where('materials.id', '<>', $material_id);

        // $query->orderByRaw('st_distance(analyses.SiO2_Al2O3, ST_GeomFromText(\'POINT('.$yOxide_umf.' '.$xOxide_umf.')\')) ASC');

        $query->orderBy('distance', 'asc');
        $query->orderBy('analyses.'.$yOxide, 'asc');
        $query->orderBy('analyses.'.$xOxide, 'asc');

        $query->limit(100);

        $materials = $query->get();

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $resource = new FractalCollection($materials, new ChartPointMaterialTransformer());
        return $this->manager->createData($resource)->toArray();
        /*
         * TODO

        $chartMaterialTransformer = new ChartMaterialTransformer();

        return $this->respond([
            'data' => $chartMaterialTransformer->transformCollection($materials->all())
        ]);
        */
    }

    protected function getDBFieldFromJSONField($oxide, $defaultValue = null) {
        if ($oxide) {
            if (in_array($oxide, Analysis::OXIDE_NAMES)) {
                return $oxide.'_umf';
            }
            else if ($oxide === 'R2OTotal') {
                return 'R2O_umf';
            }
            else if ($oxide === 'ROTotal') {
                return 'RO_umf';
            }
            else if ($oxide === 'SiO2Al2O3Ratio') {
                return 'SiO2_Al2O3_ratio_umf';
            }
        }
        if ($defaultValue) {
            return $defaultValue;
        }
        return null;
    }

    /**
     * @param $id The Recipe ID
     * @return \Illuminate\Http\JsonResponse  Returns a list of nearest neighbors

    select id, `SiO2_umf`,
    `Al2O3_umf`,
    `SiO2_Al2O3`,
    SiO2_Al2O3_ratio_umf
    from material_analyses
    where SiO2_Al2O3_ratio_umf IS NOT NULL
    order by
    st_distance(SiO2_Al2O3, ST_GeomFromText('POINT(2.90345711144 0.371202367614)')) ASC;
     */
    /*
    public function nearestSiAlOriginal(Request $request)
    {
        $material_id = (int)$request->input('material_id');
        $material_type_id = (int)$request->input('material_type_id');
        $orton_cone_id = $request->input('cone');

        $oxide1 = $request->input('oxide1');
        $oxide2 = $request->input('oxide2');

        $material = MaterialAnalysis::where('material_id', '=', $material_id)->first();

        if (!$material)
        {
            return $this->respondNotFound('Search material analysis not found.');
        }

        $oxide1_umf = $material->SiO2_umf;
        $oxide2_umf = $material->Al2O3_umf;

        if (!($oxide1_umf > 0) || !($oxide2_umf > 0))
        {
            return $this->respondNotFound('Search material analysis does not have SiO2 or Al2O3.');
        }

        $query = Material::query();

        $query->join('material_analyses as analyses', 'analyses.material_id', '=', 'materials.id');

        $query->select(
            'materials.id', 'materials.name',
            'materials.is_primitive', 'materials.material_type_id',
            'materials.is_analysis', 'materials.is_theoretical',
            'materials.from_orton_cone_id', 'materials.to_orton_cone_id',
            'materials.surface_type_id', 'materials.transparency_type_id',
            'materials.thumbnail_id',
            'materials.is_private', 'materials.created_by_user_id',
            'materials.created_at', 'materials.updated_at'
        );

        $query->with('material_type');
        $query->with('analysis');
        $query->with('thumbnail');
        $query->with('created_by_user');

        $query->where('materials.is_primitive', false);       // Only search for recipes
        $query->where('analyses.SiO2_umf', '>', 0); // Needs to have both SiO2 and Al2O3 to be charted
        $query->where('analyses.Al2O3_umf', '>', 0);
        $query->where('analyses.SiO2_Al2O3_ratio_umf', '>', 0);

        if ($material_type_id > 0) {
            // Search only within a type
            //$query->where('materials.material_type_id', $material_type_id);
            $query->ofMaterialType(null, $material_type_id);
        }

        $query->ofOrtonCone($orton_cone_id);

        $current_user_id = null;
        if (Auth::check())
        {
            $user = Auth::guard('api')->user();
            $current_user_id = $user->id;
        }
        $query->ofUserViewable($current_user_id, null);

        // Exclude the original material id
        $query->where('materials.id', '<>', $material_id);

        $query->orderByRaw('st_distance(analyses.SiO2_Al2O3, ST_GeomFromText(\'POINT('.$oxide1_umf.' '.$oxide2_umf.')\')) ASC');

        $query->limit(100);

        $materials = $query->get();

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $resource = new FractalCollection($materials, new ShallowMaterialTransformer());
        return $this->manager->createData($resource)->toArray();
    }
    */

    public function similarMaterials(SimilarMaterialsMaterialRequest $request)
    {
        $data = $request->all();

        $materials = $this->materialRepository->similarMaterials($data);

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $this->manager->parseIncludes(['atmospheres', 'materialComponents']);
        $resource = new FractalCollection($materials, new ShallowMaterialTransformer());
        $resource->setPaginator(new IlluminatePaginatorAdapter($materials));

        return $this->manager->createData($resource)->toArray();
    }

    public function containsMaterials(Request $request)
    {
        $data = $request->all();

        $materials = $this->materialRepository->containsMaterials($data);

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $this->manager->parseIncludes(['materialComponents', 'atmospheres', 'thumbnail']);

        $resource = new FractalCollection($materials, new ShallowMaterialTransformer());
        $resource->setPaginator(new IlluminatePaginatorAdapter($materials));

        return $this->manager->createData($resource)->toArray();
    }

    /*
    public function similarUnityFormula($material_id)
    {
        $materials = $this->materialRepository->similarUnityFormula($material_id);

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $resource = new FractalCollection($materials, new ChartPointMaterialTransformer());
        return $this->manager->createData($resource)->toArray();
    }
    */

    public function similarAnalysis($material_id)
    {
        $materials = $this->materialRepository->similarAnalysis($material_id);

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $resource = new FractalCollection($materials, new ChartPointMaterialTransformer());
        return $this->manager->createData($resource)->toArray();
    }

    public function similarBaseComponents($material_id)
    {
        $materials = $this->materialRepository->similarBaseComponents($material_id);

        if (!$materials)
        {
            return $this->respondNotFound('No materials found.');
        }

        $this->manager->parseIncludes(['materialComponents', 'atmospheres', 'thumbnail']);

        $resource = new FractalCollection($materials, new ShallowMaterialTransformer());
        return $this->manager->createData($resource)->toArray();
        /*
        $transformer = new NoComponentsMaterialTransformer();
        return $this->respond([
            'data' => $transformer->transformCollection($materials->all())
        ]);
        */
    }


}
