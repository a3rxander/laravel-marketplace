<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Log;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function create(array $data): Model
    {
        Log::info('Creating model with data:', $data);
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    public function deleteById(int $id): bool
    {
        return $this->model->destroy($id) > 0;
    }

    public function exists(int $id): bool
    {
        return $this->model->where('id', $id)->exists();
    }

    public function count(): int
    {
        return $this->model->count();
    }

    public function first(): ?Model
    {
        return $this->model->first();
    }

    public function latest(int $limit = 10): Collection
    {
        return $this->model->latest()->limit($limit)->get();
    }

    public function oldest(int $limit = 10): Collection
    {
        return $this->model->oldest()->limit($limit)->get();
    }

    public function whereIn(string $column, array $values): Collection
    {
        return $this->model->whereIn($column, $values)->get();
    }

    public function where(string $column, mixed $value): Collection
    {
        return $this->model->where($column, $value)->get();
    }

    public function whereBetween(string $column, array $values): Collection
    {
        return $this->model->whereBetween($column, $values)->get();
    }

    public function orWhere(string $column, mixed $value): Collection
    {
        return $this->model->orWhere($column, $value)->get();
    }

    public function pluck(string $column, string $key = null): Collection
    {
        return $this->model->pluck($column, $key);
    }

    public function chunk(int $count, callable $callback): bool
    {
        return $this->model->chunk($count, $callback);
    }

    public function insertOrIgnore(array $data): bool
    {
        return $this->model->insertOrIgnore($data);
    }

    public function upsert(array $data, array $uniqueColumns, array $updateColumns = null): int
    {
        return $this->model->upsert($data, $uniqueColumns, $updateColumns);
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function newQuery()
    {
        return $this->model->newQuery();
    }
}