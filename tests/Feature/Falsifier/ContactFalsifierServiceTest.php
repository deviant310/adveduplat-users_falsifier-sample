<?php

namespace Tests\Feature\Falsifier;

use App\Contact;
use App\Services\Falsifier\ContactFalsifierService;
use Illuminate\Database\Eloquent\Model;

class ContactFalsifierServiceTest extends FalsifierServiceTest
{

    protected function getModel(): Model
    {
        return new Contact;
    }

    protected function getServiceClass(): string
    {
        return ContactFalsifierService::class;
    }
}
