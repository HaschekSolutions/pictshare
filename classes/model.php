<?php

/**
 * Model class for all models
 *
 * @author Christian
 */
class Model // extends SQLQuery
{
    protected $model;

    public function __construct($id = 0)
    {
        $this->model = substr(get_class($this), 0, -5);
    }
}
