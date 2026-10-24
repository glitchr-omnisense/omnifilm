<?php

namespace Omnifilm\Model;

/**
 * A picture of a work, where its provider serves it. Its rights are the
 * provider's and the rights holder's: an application shows it under the
 * provider's terms (README of each provider), it does not copy it.
 */
final readonly class Image
{
    public function __construct(
        public ImageKind $kind,
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $language = null,
    ) {
    }

    /** @return array{kind: string, url: string, width: ?int, height: ?int, language: ?string} */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'url' => $this->url, 'width' => $this->width, 'height' => $this->height, 'language' => $this->language];
    }
}
