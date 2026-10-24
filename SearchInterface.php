<?php

namespace Omnifilm;

use Omnifilm\Model\Query;
use Omnifilm\Model\Work;

interface SearchInterface
{
    /**
     * Works whose title matches, best first, as short as the provider's
     * search answers them (look one up for its whole record).
     *
     * @return list<Work>
     */
    public function search(Query $query): array;
}
