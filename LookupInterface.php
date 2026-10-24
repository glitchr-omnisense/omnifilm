<?php

namespace Omnifilm;

use Omnifilm\Exception\NotSupportedException;
use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;

interface LookupInterface
{
    /**
     * The work's whole record, null when the provider does not know it.
     *
     * @param ?string $language the language of the titles and texts, where the provider translates
     *
     * @throws NotSupportedException when the provider resolves neither the register nor the type (Capabilities::resolves())
     */
    public function work(WorkRef $ref, ?string $language = null): ?Work;
}
