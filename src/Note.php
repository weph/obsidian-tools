<?php
declare(strict_types=1);

namespace Weph\ObsidianTools;

final class Note
{
    public function __construct(
        public readonly string $path,
        public readonly array $frontMatter,
        public readonly string $content)
    {
    }
}
