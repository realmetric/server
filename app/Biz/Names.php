<?php
declare(strict_types = 1);

namespace App\Biz;

class Names
{
    use \App\Injectable;

    public function getId(string $name):int
    {

    }

    public function getName(int $id):string
    {
        return '';
    }
}