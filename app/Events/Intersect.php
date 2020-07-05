<?php


namespace App\Events;


class Intersect
{
    private $result = [];
    private $resultStrings = '';
    private $fillStrings = '';
    private $colsLimit = 0;
    private $original = [];
    public $combsCycles = 0;


    public function getIntersect(array $values, int $colsLimit = 0): array
    {
        // cleanup
        $this->result = [];
        $this->resultStrings = '';
        $this->fillStrings = '';
        $this->colsLimit = 0;
        $this->original = [];
        $this->combsCycles = 0;


        // data preparation
        $original = array_unique($values);
        sort($original);
        if (count($original) < $colsLimit) {
            $colsLimit = count($original);
        }
        $this->colsLimit = $colsLimit;
        $this->original = $original;

        // process
        $this->addResult($original);
        $this->fillAllCombs($original);
        return $this->result;
    }

    // del any elements
    private function fillAllCombs($data)
    {
        $nextCombs = [];
        $this->combsCycles++;


        // del every item in data
        // from left for earlier existInFirstPart


        for ($delItemsCount = 1; $delItemsCount < count($data); $delItemsCount++) {
            for ($delItemId = 0; $delItemId < count($data) - $delItemsCount; $delItemId++) {
                $noItemData = $data;
                array_splice($noItemData, $delItemId, $delItemsCount);
                $this->addResult($noItemData);


                if (strpos($this->fillStrings, '|' . implode(',', $noItemData)) !== false) {
                    continue;
                }
                $this->fillStrings .= '|' . implode(',', $noItemData);

                $nextCombs[] = $noItemData;
                //                $level = count($this->original) - count($noItemData);
//                echo str_repeat("\t", $level) . json_encode($noItemData) . ' ' . array_values(array_diff($data, $noItemData))[0] . "\n";

            }
//            echo "                       -------------------------------\n";
        }

        if ($this->combsCycles > 50) {
            die;
        }


        // recursion n!
        foreach ($nextCombs as $id => $nextCombData) {
            if (count($nextCombData) == 1) {
                continue; // will be empty array
            }

            $level = count($this->original) - count($nextCombData);
            echo str_repeat("\t", $level) . json_encode($nextCombData) . ' ' . join('.', array_diff($this->original, $nextCombData)) . "\n";
        }
//        echo "----------------------------------------------------------\n";
        foreach ($nextCombs as $nextCombData) {
            $this->fillAllCombs($nextCombData);
        }
    }

    private function addResult($data)
    {
        if ($this->existInFirstPart($data)) {
            return;
        }
        if (count($data) <= $this->colsLimit) {
            $this->addAddResult($data);
        }

        // slicing data by $colsLimit
        for ($sliceOffset = 0; $sliceOffset < count($data) - $this->colsLimit; $sliceOffset++) {
            $dataSlice = array_slice($data, $sliceOffset, $this->colsLimit);
            if ($this->existInFirstPart($dataSlice)) {
                continue;
            }
            $this->addAddResult($dataSlice);
        }
    }

    private function addAddResult($data)
    {
        $this->result[] = $data;
        $this->resultStrings .= '|' . implode(',', $data);
    }

    private function existInFirstPart($data)
    {
        // fast check via string
        if (strpos($this->resultStrings, '|' . implode(',', $data)) !== false) {
            return true;
        }

        return false;

        // slow check via array
//        foreach ($this->result as $existData) {
//            if (count($data) > count($existData)) {
//                continue;
//            }
//            foreach ($data as $id => $value) {
//                if ($value != $existData[$id]) {
//                    continue 2; // not match
//                }
//            }
//            return true; // match
//        }
//
//
//        return false;
    }
}