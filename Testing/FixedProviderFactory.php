<?php

namespace Omnifilm\Testing;

use Omnifilm\Config;
use Omnifilm\Model\Offer;
use Omnifilm\Model\Work;
use Omnifilm\ProviderFactory;
use Omnifilm\ProviderInterface;

/** FixedProvider's factory: "fixed", answering from the works it holds. */
final class FixedProviderFactory extends ProviderFactory
{
    /**
     * @param list<Work>                                $works
     * @param array<string, array<string, list<Offer>>> $offers
     */
    public function __construct(private readonly array $works = [], private readonly array $offers = [])
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omnifilm.factory_name' => 'fixed', 'omnifilm.factory_title' => 'Fixed works', 'omnifilm.required_options' => []]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedProvider($this->works, $this->offers);
    }
}
