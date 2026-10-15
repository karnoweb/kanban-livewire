<?php

declare(strict_types=1);

namespace Karnoweb\Kanban\Realtime;

final class RefreshCoalescer
{
    /** @var array<string, true> */
    private array $hints = [];

    public function push(string $hint): void
    {
        $this->hints[$hint] = true;
    }

    /** @return list<string> */
    public function flush(): array
    {
        $hints = array_keys($this->hints);
        $this->hints = [];

        return $hints;
    }
}
