<?php

namespace Omnifilm\Model;

/** A score given somewhere: 8.7 out of 10 from 2.9 million IMDb votes. */
final readonly class Rating
{
    public function __construct(public string $source, public float $value, public float $scale = 10.0, public ?int $votes = null)
    {
    }

    /** @return array{source: string, value: float, scale: float, votes: ?int} */
    public function toArray(): array
    {
        return ['source' => $this->source, 'value' => $this->value, 'scale' => $this->scale, 'votes' => $this->votes];
    }
}
