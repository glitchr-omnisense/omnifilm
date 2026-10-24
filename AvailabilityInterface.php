<?php

namespace Omnifilm;

use Omnifilm\Model\Offer;
use Omnifilm\Model\WorkRef;

interface AvailabilityInterface
{
    /**
     * Where the work can be watched in a country (ISO 3166-1 alpha-2), as the
     * provider knows it today.
     *
     * @return list<Offer>
     */
    public function offers(WorkRef $ref, string $country): array;
}
