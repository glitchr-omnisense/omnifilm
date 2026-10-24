<?php

namespace Omnifilm;

use Omnifilm\Model\Work;
use Omnifilm\Model\WorkRef;

interface EpisodesInterface
{
    /**
     * A season's episodes in order (season 0: the specials), empty when the
     * series or the season is unknown.
     *
     * @return list<Work>
     */
    public function episodes(WorkRef $series, int $season, ?string $language = null): array;
}
