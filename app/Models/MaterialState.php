<?php namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\LookupModel;


class MaterialState extends LookupModel {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'material_states';

	use SoftDeletes;

	protected $fillable = [
		'name',
		'description'
	];

	function __construct()
	{
        $this->static_values = [
            1 => 'Testing',
            2 => 'Production',
            3 => 'Discontinued'
        ];
	}

}

