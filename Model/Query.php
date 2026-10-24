<?php

namespace Omnifilm\Model;

use Omnifilm\Exception\InvalidArgumentException;

/** A search by title: the words, and what narrows them. */
final readonly class Query
{
    public string $text;

    /**
     * @param ?WorkType $type     films or series only (an episode is reached through its series)
     * @param ?int      $year     the year of release (a series: of its first episode)
     * @param ?string   $language the language of the titles and overviews answered, "fr" or "fr-FR", where the provider translates
     * @param int       $limit    at most this many works (1 to 50)
     */
    public function __construct(string $text, public ?WorkType $type = null, public ?int $year = null, public ?string $language = null, public int $limit = 10)
    {
        $text = trim($text);
        if ('' === $text) {
            throw new InvalidArgumentException('A search needs words.');
        }
        if (WorkType::Episode === $type) {
            throw new InvalidArgumentException('An episode is not searched for: ask its series for its episodes.');
        }
        if ($limit < 1 || $limit > 50) {
            throw new InvalidArgumentException('A search answers 1 to 50 works.');
        }
        $this->text = $text;
    }
}
