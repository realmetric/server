<?php

namespace App\Command;

use App\Library\EventSaver;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:fill_fake_data'
)]
class FillFakeDataCommand extends BaseCommand
{
    public function __construct(
        private readonly EventSaver $eventSaver
    )
    {
        parent::__construct();
    }

    public function handle()
    {
        $fruits = ['Abiu', 'Açaí', 'Acerola', 'Akebi', 'Ackee', 'ACO', 'American', 'Apple', 'Apricot', 'Aratiles', 'Araza', 'Atis', 'Avocado'];
        while (1) {
            $metric = 'Fruits.' . $fruits[array_rand($fruits)];
            $value = 1; //mt_rand(1, 10);
            $slices = [];
            for ($i = 1; $i <= 10; $i++) {
                @$slices["slice" . mt_rand(1, 10)] = 'value' . mt_rand(1, 10);
            }
//            $timestamp = mt_rand(1653327297, time());
            $timestamp = time();

            $this->eventSaver->save($metric, $value, $timestamp, $slices, true);
        }
    }
}
