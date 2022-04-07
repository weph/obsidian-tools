<?php
declare(strict_types=1);

namespace Weph\ObsidianTools\Vault;

final class Note
{
    public readonly string $name;

    public function __construct(
        public readonly string $path,
        public readonly array $frontMatter,
        public readonly string $content)
    {
        $this->name = str_replace('.md', '', basename($this->path));
    }

    public function withFrontMatterField(string $field, mixed $value): self
    {
        return new self($this->path, array_merge($this->frontMatter, [$field => $value]), $this->content);
    }
}
