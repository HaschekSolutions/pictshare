<?php

namespace App\Models;

/**
 * Class Model
 * @package App\Models
 */
class Model
{
    /**
     * @var bool|string
     */
    protected $model;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var int
     */
    protected $id;

    /**
     * Model constructor.
     *
     * @param int $id
     */
    public function __construct($id)
    {
        $this->model = substr(get_class($this), 0, -5);
        $this->table = strtolower($this->model);
        $this->id    = $id;
    }

    /**
     * @return void
     */
    public function __destruct()
    {
    }
}
