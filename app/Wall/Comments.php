<?php

namespace App\Wall;

use Illuminate\Support\Facades\File;

/**
 * The comments, in a JSON file under storage/.
 *
 * A file and not a table so the demo needs no database and the only code worth reading is
 * the moderation. In a real app this is an Eloquent model; nothing else depends on it.
 */
class Comments
{
    /**
     * @return string
     */
    private function path(): string
    {
        return storage_path('app/comments.json');
    }

    /**
     * Published comments, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function published(): array
    {
        return array_values(array_reverse(array_filter($this->read(), fn (array $c) => $c['status'] === 'published')));
    }

    /**
     * The id the next comment will get, so the call to ToxicFilter can carry it.
     *
     * @return int
     */
    public function nextId(): int
    {
        return max([0, ...array_keys($this->read())]) + 1;
    }

    /**
     * @param int $id
     * @param string $name
     * @param string $body
     * @param string $status `published` or `held`.
     * @param string|null $verdictId
     * @return void
     */
    public function add(int $id, string $name, string $body, string $status, ?string $verdictId): void
    {
        $comments = $this->read();
        $comments[$id] = compact('id', 'name', 'body', 'status') + ['verdict' => $verdictId, 'created_at' => now()->toAtomString()];
        $this->write($comments);
    }

    /**
     * @param int $id
     * @return void
     */
    public function publish(int $id): void
    {
        $comments = $this->read();

        if (isset($comments[$id])) {
            $comments[$id]['status'] = 'published';
            $this->write($comments);
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function remove(int $id): void
    {
        $comments = $this->read();
        unset($comments[$id]);
        $this->write($comments);
    }

    /**
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->read()[$id] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function read(): array
    {
        return File::exists($this->path()) ? (json_decode(File::get($this->path()), true) ?: []) : [];
    }

    /**
     * @param array<int, array<string, mixed>> $comments
     * @return void
     */
    private function write(array $comments): void
    {
        File::put($this->path(), json_encode($comments, JSON_PRETTY_PRINT), lock: true);
    }
}
