<?php

declare(strict_types=1);

namespace App\Database\Fixture;

use App\Database\Factory\PartnerFactory;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PartnerFixture implements FixtureInterface
{
    public function __construct(private readonly PartnerFactory $partnerFactory)
    {
    }

    public function load(ObjectManager $manager)
    {
        $this->partnerFactory
            ->count(5)
            ->create();
    }
}
