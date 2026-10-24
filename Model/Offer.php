<?php

namespace Omnifilm\Model;

/**
 * Where a work can be watched in one country: the service, how (stream,
 * rent, buy...), and the page that says so.
 */
final readonly class Offer
{
    public function __construct(
        public OfferKind $kind,
        public string $service,
        public string $country,
        public ?string $url = null,
        public ?string $logo = null,
    ) {
    }

    /** @return array{kind: string, service: string, country: string, url: ?string, logo: ?string} */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'service' => $this->service, 'country' => $this->country, 'url' => $this->url, 'logo' => $this->logo];
    }
}
