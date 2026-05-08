<?php

namespace App\Models;

use CodeIgniter\Model;

class SpaceModel extends Model
{
    protected $table            = 'spaces';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'user_id',
        'environment_type',
        'custom_name',
        'min_temperature',
        'max_temperature',
        'min_humidity',
        'max_humidity',
        'max_co2',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
