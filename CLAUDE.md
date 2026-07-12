# Environment

This app runs in Docker (see `docker-compose.yml`). The host machine cannot resolve container hostnames (e.g. `postgres-db`), so `php artisan`, `composer`, and test commands fail if run directly on the host.

Always execute PHP/Laravel commands inside the `workspace` container, e.g.:

```
docker exec workspace php artisan test
docker exec workspace composer install
```

# Testing changes

Do not use `php artisan tinker` to create models or exercise features for verification — it writes to local data and leaves behind records that need manual cleanup. If a change needs to be verified, write a dedicated test for it instead. Do not add tests unless explicitly instructed to.

## Mocking file storage

Prefer `Storage::shouldReceive(...)` for mocking storage failures in tests — it's the standard Laravel pattern and works fine here (unlike `Redis`, `FilesystemManager`'s facade calls are flat single-method proxies, not multiplexed/pipelined, so `shouldReceive()` expectations map cleanly onto them).

If a scenario ever needs a manual mock instead (e.g. asserting behavior that depends on the real fake disk still being active elsewhere in the same test), mock the disk directly rather than the facade root:

```php
$disk = $this->partialMock(\Illuminate\Contracts\Filesystem\Filesystem::class);
$disk->shouldReceive('exists')->andReturn(false);
$disk->shouldReceive('put')->andReturn(false);

app('filesystem')->set(config('filesystems.default'), $disk);
```

`app('filesystem')` is the same `FilesystemManager` singleton the `Storage` facade proxies to. `set($name, $disk)` overwrites its internal disk cache for that name — this is exactly what `Storage::fake()` does internally (`FilesystemManager::set()` registers the fake disk under the default disk name), so registering our mock disk the same way is what makes `Storage::exists()`/`Storage::put()` resolve to it. Skipping this `set()` call leaves the mock inert — the facade will still resolve to whatever real disk is currently registered instead.
