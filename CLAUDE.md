# Environment

This app runs in Docker (see `docker-compose.yml`). The host machine cannot resolve container hostnames (e.g. `postgres-db`), so `php artisan`, `composer`, and test commands fail if run directly on the host.

Always execute PHP/Laravel commands inside the `workspace` container, e.g.:

```
docker exec workspace php artisan test
docker exec workspace composer install
```

# Testing changes

Do not use `php artisan tinker` to create models or exercise features for verification — it writes to local data and leaves behind records that need manual cleanup. If a change needs to be verified, write a dedicated test for it instead. Do not add tests unless explicitly instructed to.
