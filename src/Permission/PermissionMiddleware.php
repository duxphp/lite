<?php

namespace Dux\Permission;

use Dux\App;
use Dux\Handlers\ExceptionBusiness;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;

class PermissionMiddleware {

    public function __construct(
        public string $name,
        public string $model
    ) {
    }

    public function __invoke(Request $request, RequestHandler $handler): Response {
        $auth = $request->getAttribute("auth");
        $route = RouteContext::fromRequest($request)->getRoute();
        $routeName = $route->getName();
        $allPermission = App::permission($this->name)->getData();
        if (!$allPermission || !in_array($routeName, $allPermission)) {
            return $handler->handle($request);
        }
        $userInfo = $this->model::query()->find($auth["id"]);
        $permission = (array)$userInfo->permission;
        $status = $route->getArgument("route:permission");
        if ($status && $permission && !in_array($routeName, $permission)) {
            throw new ExceptionBusiness("Forbidden", 403);
        }
        return $handler->handle($request);
    }
}