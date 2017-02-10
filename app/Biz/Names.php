<?php
declare(strict_types = 1);

namespace App\Biz;

use App\Injectable;

class Names extends Injectable
{
    public function getId(string $name):int
    {

    }

    public function getName(int $id):string
    {
        return '';
    }
}