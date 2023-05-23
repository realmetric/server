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
        $fruits = ['Abiu', 'Açaí', 'Acerola', 'Akebi', 'Ackee', 'ACO', 'American', 'Apple', 'Apricot', 'Aratiles', 'Araza', 'Atis', 'Avocado', 'Banana', 'Bilberry', 'Blackberry', 'Blackcurrant', 'Black sapote', 'Blueberry', 'Boysenberry', 'Breadfruit', 'Buddha\'s hand', 'Cacao', 'Cactus pear', 'Caniste', 'Catmon', 'Cempedak', 'Cherimoya', 'Cherry', 'Chico fruit', 'Cloudberry', 'Coco de mer', 'Coconut', 'Crab apple', 'Cranberry', 'Currant'];
        while (1) {
            $metric = 'Fruits.' . $fruits[array_rand($fruits)];
            $value = mt_rand(1, 10);
            $slices = [];
            for ($i = 1; $i <= 10; $i++) {
                @$slices["slice" . mt_rand(1, 100)] = 'value' . mt_rand(1, 10);
            }
            $timestamp = mt_rand(1653327297, time());

            $timeStart = microtime(true);
            $this->eventSaver->save($metric, $value, $timestamp, $slices);
            if (!mt_rand(0, 9)) {
                echo 'Insert time: ' . round((microtime(true) - $timeStart) * 1000) . " ms\n";
            }
        }
    }
}
