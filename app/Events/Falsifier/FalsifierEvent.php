<?php

namespace App\Events\Falsifier;

use App\Services\Falsifier\FalsifierService;
use Illuminate\Foundation\Events\Dispatchable;

abstract class FalsifierEvent
{
    use Dispatchable;

    public FalsifierService $falsifier;

    public function __construct(FalsifierService $falsifier)
    {
        $this->falsifier = $falsifier;
    }
}
