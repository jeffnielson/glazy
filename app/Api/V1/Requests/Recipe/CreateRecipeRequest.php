<?php

namespace App\Api\V1\Requests\Recipe;

//use App\Http\Requests\Request;

use App\Api\V1\Requests\ApiFormRequest;

class CreateRecipeRequest extends ApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
//            'recipe' => 'array|required',
//            'recipe.name' => 'required|string',
            'name' => 'required|string',
        ];
    }

    public function attributes()
    {
        return [
//            'recipe.name' => 'the recipe\'s name',
            'name' => 'the recipe\'s name',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
//            'recipe.name' => 'A name is required',
            'name' => 'A name is required',
        ];
    }
}
