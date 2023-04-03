<?php

namespace Dux\Auth;

use Dux\App;
use Exception;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthService
{

    public function __construct(public string $app)
    {
    }

    public function user(Request $request): ?object
    {
        try {
            $jwt = JWT::decode($request->getHeaderLine('Authorization'), App::config("use")->get("app.secret"), ["HS256", "HS512", "HS384"]);
        } catch (Exception $e) {
            return null;
        }
        if (!$jwt->sub || !$jwt->id) {
            return null;
        }
        if ($jwt->sub !== $this->app) {
            return null;
        }
        return $jwt;
    }

    public function check(Request $request): bool
    {
        return $this->user($request) !== null;
    }

    public function id(Request $request): int
    {
        return (int)$this->user($request)?->id;
    }
}