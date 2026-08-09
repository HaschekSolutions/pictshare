<?php
// tests/Support/FakeRedis.php

/**
 * Minimal in-memory Redis mock covering only the methods exercised by
 * PictShare's Redis-backed code paths (stats cache, view tracking).
 */
class FakeRedis
{
    private array $strings = [];
    private array $hashes  = [];

    public function get(string $key): string|null
    {
        return $this->strings[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->strings[$key] = (string)$value;
    }

    public function incr(string $key): int
    {
        $value = ((int)($this->strings[$key] ?? 0)) + 1;
        $this->strings[$key] = (string)$value;
        return $value;
    }

    public function del(string $key): void
    {
        unset($this->strings[$key], $this->hashes[$key]);
    }

    public function hset(string $key, string $field, string $value): void
    {
        $this->hashes[$key][$field] = $value;
    }

    public function hgetall(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }

    /** Simulate MGET: returns values in same order as keys, null for missing. */
    public function mget(array $keys): array
    {
        return array_map(fn($k) => $this->strings[$k] ?? null, $keys);
    }

    /**
     * Simulate phpredis SCAN closely enough to exercise the standard
     * `while (($batch = $redis->scan($it, $pattern)) !== false)` idiom:
     * returns every matching key on the first call, then false.
     */
    public function scan(&$iterator, string $pattern): array|false
    {
        if ($iterator === 'done') return false;
        $iterator = 'done';
        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#';
        return array_values(array_filter(array_keys($this->strings), fn($k) => preg_match($regex, $k)));
    }

    /** Minimal pipeline: collect hset calls and flush on exec(). Mirrors phpredis multi(Redis::PIPELINE). */
    public function multi(int $mode = 0): FakeRedisPipeline
    {
        return new FakeRedisPipeline($this);
    }
}

class FakeRedisPipeline
{
    private FakeRedis $redis;
    private array $ops = [];

    public function __construct(FakeRedis $redis) { $this->redis = $redis; }

    public function hset(string $key, string $field, string $value): void
    {
        $this->ops[] = [$key, $field, $value];
    }

    public function exec(): void
    {
        foreach ($this->ops as [$key, $field, $value]) {
            $this->redis->hset($key, $field, $value);
        }
    }
}
