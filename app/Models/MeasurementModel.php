<?php

namespace App\Models;

use CodeIgniter\Model;

class MeasurementModel extends Model
{
    protected $table            = 'measurements';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'device_id',
        'user_id',
        'space_id',
        'source',
        'temperature',
        'humidity',
        'co2_ppm',
        'air_quality_index',
        'air_quality_label',
        'notes',
        'captured_at',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
