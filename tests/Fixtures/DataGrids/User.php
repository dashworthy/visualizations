<?php

namespace Dashworthy\Visualizations\Tests\Fixtures\DataGrids;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email'];
}
