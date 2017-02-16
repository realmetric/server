<?php

namespace App\Models;

abstract class AbstractModel
{
    const TABLE = null;

    private function table()
    {
        return [];
    }

    public function getById($primaryKey)
    {
    }

    public function insert($record)
    {

    }

    public function update($primaryId, $newData)
    {

    }
}