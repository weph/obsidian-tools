<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class MatchedNote
{
    /**
     * @param array<array<array-key, string>> $matches
     */
    public function __construct(public readonly Note $note, public readonly array $matches)
    {
    }
}
