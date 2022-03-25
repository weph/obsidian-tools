<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Note
{
    public function __construct(
        public readonly string $path,
        public readonly array $frontMatter,
        public readonly string $content)
    {
    }
}