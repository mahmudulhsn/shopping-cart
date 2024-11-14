<?php

namespace Mahmudulhsn\ShoppingCart\Repositories;

use Illuminate\Support\Facades\Session;

class SessionRepository
{
    public function get(string $key, $default = null)
    {
        return Session::get($key, $default);
    }

    public function put(string $key, $value): void
    {
        Session::put($key, $value);
    }

    public function forget(string $key): void
    {
        Session::forget($key);
    }
}
