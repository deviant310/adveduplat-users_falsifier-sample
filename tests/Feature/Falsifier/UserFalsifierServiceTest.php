<?php

namespace Tests\Feature\Falsifier;

use App\Services\Falsifier\UserFalsifierService;
use App\User;
use Illuminate\Database\Eloquent\Model;

class UserFalsifierServiceTest extends FalsifierServiceTest
{

    protected function getModel(): Model
    {
        return new User;
    }

    protected function getServiceClass(): string
    {
        return UserFalsifierService::class;
    }
}
