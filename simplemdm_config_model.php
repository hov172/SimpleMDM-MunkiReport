<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_config_model extends Eloquent
{
    protected $table = 'simplemdm_config';

    protected $fillable = [
        'name',
        'value',
    ];

    public $timestamps = true;
}
