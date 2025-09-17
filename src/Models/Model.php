<?php

namespace Module\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    public function getTable(): string
    {
        return config('module.table_prefix').parent::getTable();
    }
}
