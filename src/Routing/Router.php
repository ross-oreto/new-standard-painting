<?php

namespace Alex\Nsp\Routing;

use JetBrains\PhpStorm\Pure;

class Router {
    public static string $GET = "GET";
    public static string $POST = "POST";
    public static string $PUT = "PUT";
    public static string $DELETE = "DELETE";

    /**
     * @param Routes[] $routes
     * @return Router
     */
    #[Pure] public static function of(array $routes): Router {
        return new Router($routes);
    }

    /* @var $controllerRoutes array<string, Route> */
    protected array $routes = [];

    /* @var $controllerRoutes array<string, Routes> */
    protected array $controllerRoutes = [];

    /**
     * @param Routes[] $routes
     */
    #[Pure] protected function __construct(array $routes) {
        foreach ($routes as $classRoutes) {
            $this->controllerRoutes[$classRoutes->getClass()] = $classRoutes;
            /** @var Route $route */
            foreach ($classRoutes->getRoutes() as $route) {
                $this->routes[$route->getName()] = $route;
            }
        }
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array {
       return array_values($this->routes);
    }
}