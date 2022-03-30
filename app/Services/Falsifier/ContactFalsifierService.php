<?php

namespace App\Services\Falsifier;

use App\Contact;

class ContactFalsifierService extends FalsifierService
{
    protected function getTableName(): string
    {
        return (new Contact)->getTable();
    }

    protected function getTableKeyName(): string
    {
        return (new Contact)->getKeyName();
    }

    protected function getFakeAttributes(): array
    {
        $state = $this->faker->state;
        $city = $this->faker->city;
        $country = $this->faker->country;
        $address = $this->faker->address;
        $phone = $this->faker->e164PhoneNumber;
        $email = $this->faker->email;

        return [
            'state' => $state,
            'city' => $city,
            'country' => $country,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
        ];
    }
}
