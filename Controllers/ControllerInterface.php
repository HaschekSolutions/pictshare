<?php

declare(strict_types=1);

namespace PictShare\Controllers;

interface ControllerInterface
{
    /**
     * GET action.
     */
    public function get();

    /**
     * POST action.
     */
    public function post();
}
