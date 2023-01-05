<?php

namespace Dux\Auth;

use Firebase\JWT\JWT;

class Auth {

    static public function middleware(string $app, int $renewal = 43200): \Tuupola\Middleware\JwtAuthentication {
        $secret = \Dux\App::config("app")->get("app.secret");
        return new \Tuupola\Middleware\JwtAuthentication([
            "secret" => $secret,
            "secure" => false,
            "after" => function ($response, $arguments) use ($renewal, $secret, $app) {
                $token = $arguments["decoded"];
                if ($app != $token["sub"]) {
                    throw new \Dux\Handlers\ExceptionBusiness("Authorization app error", 401);
                }
                $renewalTime = $token["iat"] + $renewal;
                $time = time();
                if ($renewalTime <= $time) {
                    $token["exp"] = $time + $token["exp"] - $token["iat"];
                    $auth = JWT::encode($token, $secret);
                    return $response->withHeader("Authorization", "Bearer $auth");
                }
                return $response;
            },
            "error" => function ($response, $arguments) {
                throw new \Dux\Handlers\ExceptionBusiness($arguments["message"], 401);
            }
        ]);
    }

    static public function token(string $app, int $expire = 86400, array $params = []): string {
        $time = time();
        $payload = [
            'sub' => $app,
            'iat' => $time,
            'exp' => $time + $expire,
        ];
        $payload = [...$payload, ...$params];
        return JWT::encode($payload, \Dux\App::config("app")->get("app.secret"));
    }
}