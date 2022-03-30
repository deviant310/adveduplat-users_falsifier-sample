<?php

namespace App\Services\Falsifier;

use App\User;

class UserFalsifierService extends FalsifierService
{
    protected function getTableName(): string
    {
        return (new User)->getTable();
    }

    protected function getTableKeyName(): string
    {
        return (new User)->getKeyName();
    }

    protected function getFakeAttributes(): array
    {
        $email = $this->faker->regexify('[a-z0-9]{20}') . '@' . $this->faker->domainName;
        $phone = $this->faker->e164PhoneNumber;
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $secondName = $this->faker->firstName;
        $name = $firstName;
        $fullName = collect([$firstName, $secondName, $lastName])->implode(' ');

        return [
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'patronymic' => $secondName,
            'full_name' => $fullName,
        ];
    }
}
