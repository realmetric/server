<?php


namespace App\Values;


class Format
{
    public function shorten($number)
    {
        $suffixes = array('', 'k', 'M', 'B', 'T');
        $suffixIndex = 0;

        $remainder = $number;
        while (abs($remainder) >= 1000) {
            $suffixIndex++;
            $remainder /= 1000;
        }
        if (!$suffixIndex) {
            return $number;
        }

        $count = round($remainder * 100) / 100;
        return $count . ' ' . $suffixes[$suffixIndex];
    }
}