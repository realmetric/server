<?php


namespace App\Models;

/**
 * @property DayModel day
 */

class ModelFactory
{
    protected $cache = [];

    /**
     * @return AbstractModel
     */
    public function __get($name)
    {
        // Caching
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $className = '\App\Models\\' . ucfirst($name) . 'Model';
        if (!class_exists($className)) {
            throw new \Exception("Model {$name} not exist");
        }
        $this->cache[$name] = new $className;
        return $this->cache[$name];
    }
}