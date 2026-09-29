<?php

namespace Pano\Foundation;

use Pano\Kernel\BaseBag;

class Bag extends BaseBag
{
    private const PATH_SEPARATOR = '.';

    public function merge(iterable|BaseBag $items): static
    {
        return new static(
            array_merge(
                $this->items,
                $this->normalize($items)
            )
        );
    }

    public function replace(iterable|BaseBag $items): static
    {
        return new static(
            array_replace(
                $this->items,
                $this->normalize($items)
            )
        );
    }

    /* =========================
     |  Helpers
     ========================= */

    public function only(array $keys): static
    {
        return new static(
            array_intersect_key(
                $this->items,
                array_flip($keys)
            )
        );
    }

    public function except(array $keys): static
    {
        return new static(
            array_diff_key(
                $this->items,
                array_flip($keys)
            )
        );
    }

    public function map(callable $callback): static
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return new static($result);
    }

    public function filter(callable $callback): static
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return new static($result);
    }

    public function find(mixed $value, bool $strict = true): ?string
    {
        return $this->searchFirst(
            $this->items,
            $value,
            $strict
        );
    }

    public function findAll(mixed $value, bool $strict = true): array
    {
        $results = [];

        $this->searchValues(
            $this->items,
            $value,
            $strict,
            $results
        );

        return $results;
    }

    public function findKey(string|int $key): ?string
    {
        return $this->searchFirstKey(
            $this->items,
            $key
        );
    }

    public function findAllKeys(string|int $key): array
    {
        $results = [];

        $this->searchKeys(
            $this->items,
            $key,
            $results
        );

        return $results;
    }

    /* =========================
     |  Search Values
     ========================= */

    protected function searchFirst(
        array $items,
        mixed $value,
        bool $strict,
        string $path = ''
    ): ?string {
        foreach ($items as $key => $item) {

            $currentPath = $this->path(
                $path,
                $key
            );

            if (
                ($strict && $item === $value) ||
                (!$strict && $item == $value)
            ) {
                return $currentPath;
            }

            if (is_array($item)) {

                $result = $this->searchFirst(
                    $item,
                    $value,
                    $strict,
                    $currentPath
                );

                if ($result !== null) {
                    return $result;
                }
            }

            if ($item instanceof BaseBag) {

                $result = $this->searchFirst(
                    $item->all(),
                    $value,
                    $strict,
                    $currentPath
                );

                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    protected function searchValues(
        array $items,
        mixed $value,
        bool $strict,
        array &$results,
        string $path = ''
    ): void {
        foreach ($items as $key => $item) {

            $currentPath = $this->path(
                $path,
                $key
            );

            if (
                ($strict && $item === $value) ||
                (!$strict && $item == $value)
            ) {
                $results[] = $currentPath;
            }

            if (is_array($item)) {
                $this->searchValues(
                    $item,
                    $value,
                    $strict,
                    $results,
                    $currentPath
                );
            }

            if ($item instanceof BaseBag) {
                $this->searchValues(
                    $item->all(),
                    $value,
                    $strict,
                    $results,
                    $currentPath
                );
            }
        }
    }

    /* =========================
     |  Search Keys
     ========================= */

    protected function searchFirstKey(
        array $items,
        string|int $searchKey,
        string $path = ''
    ): ?string {
        foreach ($items as $key => $item) {

            $currentPath = $this->path(
                $path,
                $key
            );

            if ($key === $searchKey) {
                return $currentPath;
            }

            if (is_array($item)) {

                $result = $this->searchFirstKey(
                    $item,
                    $searchKey,
                    $currentPath
                );

                if ($result !== null) {
                    return $result;
                }
            }

            if ($item instanceof BaseBag) {

                $result = $this->searchFirstKey(
                    $item->all(),
                    $searchKey,
                    $currentPath
                );

                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    protected function searchKeys(
        array $items,
        string|int $searchKey,
        array &$results,
        string $path = ''
    ): void {
        foreach ($items as $key => $item) {

            $currentPath = $this->path(
                $path,
                $key
            );

            if ($key === $searchKey) {
                $results[] = $currentPath;
            }

            if (is_array($item)) {
                $this->searchKeys(
                    $item,
                    $searchKey,
                    $results,
                    $currentPath
                );
            }

            if ($item instanceof BaseBag) {
                $this->searchKeys(
                    $item->all(),
                    $searchKey,
                    $results,
                    $currentPath
                );
            }
        }
    }

    /* =========================
     |  Internal
     ========================= */

    protected function normalize(
        iterable|BaseBag $items
    ): array {
        if ($items instanceof BaseBag) {
            return $items->all();
        }

        return is_array($items)
            ? $items
            : iterator_to_array($items);
    }

    protected function path(
        string $path,
        string|int $key
    ): string {
        return $path === ''
            ? (string) $key
            : $path . self::PATH_SEPARATOR . $key;
    }
}