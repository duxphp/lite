<?php

namespace Dux\Auth;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Firebase\JWT\JWT;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class Auth {

    static public function middleware(string $app, int $renewal = 43200): \Tuupola\Middleware\JwtAuthentication {
        $secret = \Dux\App::config("app")->get("app.secret");
        return new \Tuupola\Middleware\JwtAuthentication([
            "secret" => $secret,
            "secure" => false,
            "before" => function ($request, $arguments) {
                $token = $arguments["decoded"];
                return $request->withAttribute('auth', $token);
            },
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

    static public function token(string $app, $params = [], int $expire = 86400): string {
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