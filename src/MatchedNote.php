<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

final class MatchedNote
{
    public function __construct(public readonly Note $note, public readonly array $matches)
    {
    }
}
