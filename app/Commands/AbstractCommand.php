<?php

namespace App\Commands;

abstract class AbstractCommand extends \Symfony\Component\Console\Command\Command
{
    use \App\Injectable;
}