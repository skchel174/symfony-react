<?php

namespace App\Database\Factory;

use App\Entity\Partner;
use App\Service\EntityFactory\EntityFactory;

class PartnerFactory extends EntityFactory
{
    protected function getClass(): string
    {
        return Partner::class;
    }

    protected function getDefinition(): array
    {
        return [
            'email' => $this->faker->email(),
            'password' => '5ebe2294ecd0e0f08eab7690d2a6ee69', // secret
        ];
    }
}
