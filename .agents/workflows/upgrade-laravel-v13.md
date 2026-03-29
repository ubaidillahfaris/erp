---
description: Upgrade Laravel 12 to Laravel 13
---

# Upgrade Laravel 12 to Laravel 13

Follow these steps to upgrade this application from Laravel 12.x to Laravel 13.x.

1. **Update Dependencies**
Update the following dependencies in `composer.json`:
- `laravel/framework` to `^13.0`
- `laravel/boost` to `^2.0`
- `laravel/tinker` to `^3.0`
- `phpunit/phpunit` to `^12.0`
- `pestphp/pest` to `^4.0` (if present)

// turbo
2. Run `composer update` to apply the changes.

3. **Handle Cache Changes**
Update `config/cache.php` to include the `serializable_classes` option. By default, it should be set to `[]` for security, unless you intentionally store objects in cache.

4. **Update Custom Implementations**
Check if you have any custom implementations of the following contracts and add the missing methods:
- `Illuminate\Contracts\Cache\Store` - add `touch($key, $seconds)`
- `Illuminate\Contracts\Bus\Dispatcher` - add `dispatchAfterResponse($command, $handler = null)`
- `Illuminate\Contracts\Routing\ResponseFactory` - add `eventStream()`
- `Illuminate\Contracts\Auth\MustVerifyEmail` - add `markEmailAsUnverified()`

5. **Run Tests**
// turbo
Run the test suite to ensure everything is working as expected.
`php artisan test --compact`
