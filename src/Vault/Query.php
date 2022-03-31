<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Query
{
    private ?string $content = null;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withContent(?string $contentRegex): self
    {
        $clone = clone $this;

        $clone->content = $contentRegex;

        return $clone;
    }

    public function content(): ?string
    {
        return $this->content;
    }
}
