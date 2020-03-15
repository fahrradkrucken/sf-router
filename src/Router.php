<?php

namespace FahrradKrucken\SFRouter\Router;


/**
 * Class Router
 * @package FahrradKrucken\SFRouter\Router
 */
class Router
{
    /**
     * Routing dispatch statuses
     */
    const
        STATUS_FOUND = 'STATUS_FOUND',
        STATUS_NOT_FOUND = 'STATUS_NOT_FOUND',
        STATUS_METHOD_NOT_ALLOWED = 'STATUS_METHOD_NOT_ALLOWED';

    /**
     * @var string
     * Current request's path/query, with '/' at the start and without '/' at the end.
     * Default = $_SERVER['QUERY_STRING']
     */
    protected $requestPath = '';

    /**
     * @var string
     * Current request's method, in uppercase.
     * Default = $_SERVER['REQUEST_METHOD']
     */
    protected $requestMethod = '';

    /**
     * @var array
     * Routes multidimensional array.
     */
    protected $routes = [];

    /**
     * Router constructor.
     *
     * @param string $requestPath
     * @param string $requestMethod
     */
    public function __construct(string $requestPath = '', string $requestMethod = '')
    {
        $this->setRequestPath($requestPath);
        $this->setRequestMethod($requestMethod);
    }

    /**
     * @param string $requestPath
     */
    public function setRequestPath(string $requestPath = '')
    {
        $this->requestPath = '/' . trim(($requestPath ?? $_SERVER['REQUEST_URI']), ' /');
    }

    /**
     * @param string $requestMethod
     */
    public function setRequestMethod(string $requestMethod = '')
    {
        $this->requestMethod = strtoupper($requestMethod ?? $_SERVER['REQUEST_METHOD']);
    }

    public function addRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param array      $methods
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     */
    public static function route(array $methods, string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return [
            'methods'          => !empty($methods) ?
                array_map('strtoupper', $methods) : ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'path'             => '/' . trim($path, ' /'),
            'callback'         => $callback,
            'callbacks_before' => is_array($callbacksBefore) ? $callbacksBefore : [$callbacksBefore],
            'callbacks_after'  => is_array($callbacksAfter) ? $callbacksAfter : [$callbacksAfter],
        ];
    }

    /**
     * @param string           $path - Child Routes path prefix
     * @param array            $routes - Child Routes
     * @param array|callable[] $callbacksBefore - Callbacks Before the all child routes
     * @param array|callable[] $callbacksAfter - Callbacks After the all child routes
     *
     * @return array - New route array
     */
    public static function routeGroup(string $path, array $routes, $callbacksBefore = [], $callbacksAfter = [])
    {
        return [
            'path'             => '/' . trim($path, ' /'),
            'routes'           => $routes,
            'callbacks_before' => is_array($callbacksBefore) ? $callbacksBefore : [$callbacksBefore],
            'callbacks_after'  => is_array($callbacksAfter) ? $callbacksAfter : [$callbacksAfter],
        ];
    }

    /**
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     * @see @Router::route()
     */
    public static function routeGet(string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return self::route(['GET'], $path, $callback, $callbacksBefore, $callbacksAfter);
    }

    /**
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     * @see @Router::route()
     */
    public static function routePost(string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return self::route(['POST'], $path, $callback, $callbacksBefore, $callbacksAfter);
    }

    /**
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     * @see @Router::route()
     */
    public static function routePut(string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return self::route(['PUT'], $path, $callback, $callbacksBefore, $callbacksAfter);
    }

    /**
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     * @see @Router::route()
     */
    public static function routePatch(string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return self::route(['PATCH'], $path, $callback, $callbacksBefore, $callbacksAfter);
    }

    /**
     * @param string     $path
     * @param callable   $callback
     * @param callable[] $callbacksBefore
     * @param callable[] $callbacksAfter
     *
     * @return array
     * @see @Router::route()
     */
    public static function routeDelete(string $path, $callback, $callbacksBefore = [], $callbacksAfter = [])
    {
        return self::route(['DELETE'], $path, $callback, $callbacksBefore, $callbacksAfter);
    }

    /**
     * @param string $requestPath
     * @param string $requestMethod
     *
     * @return array - Current Route
     *
     * [
     *      'status' => @var string Router::STATUS_FOUND | Router::STATUS_NOT_FOUND | Router::STATUS_METHOD_NOT_ALLOWED,
     *      'request_path' => @var string Current Request Path.
     *      'request_method' => @var string Current Request Method.
     *
     *      'route_args' => @var array - Current route's arguments (if persists),
     *      'route_callback' => @var callable - Current route's callable,
     *      'route_callbacks_before' => @var callable[] - Current route's callable's before,
     *      'route_callbacks_after' => @var callable[] - Current route's callable's after,
     * ]
     */
    public function dispatch(string $requestPath = '', string $requestMethod = ''): array
    {
        if (!empty($requestPath)) $this->setRequestPath($requestPath);
        if (!empty($requestMethod)) $this->setRequestMethod($requestMethod);
        $routesList = $this->routesArrayToRoutesList($this->routes); // Routes array to flat list
        $routesList = array_map(function ($route) { // Update RouteList
            // Fix callbacks_before order (parent goes first)
            $route['callbacks_before'] = array_reverse($route['callbacks_before']);
            // Route should be checked directly (by default)
            $route['direct_comparison'] = true;
            // Route has named args?
            if (strpos($route['path'], '{') !== false) {
                // In this case route should be checked through regex
                $route['direct_comparison'] = false;
                // Create route regex 'pattern' (and extract named args)
                $route['pattern'] = preg_replace_callback('/({[a-z0-9_]+})/', function ($match) {
                    return "(?'" . trim($match[0], '{}') . "'[a-z0-9\-]+)";
                }, $route['path']);
                $route['pattern'] = '/' . str_replace('/', '\/', $route['pattern']) . '/';
            }
            return $route;
        }, $routesList);

        // Current Route schema
        $currentRoute = [
            'status'                 => self::STATUS_NOT_FOUND,
            'request_path'           => $this->requestPath,
            'request_method'         => $this->requestMethod,
            'route_args'             => [],
            'route_callback'         => null,
            'route_callbacks_before' => [],
            'route_callbacks_after'  => [],
        ];

        // Check Routes
        foreach ($routesList as $route) {
            if ($route['direct_comparison']) { // Compare routes directly
                if ($route['path'] === $this->requestPath) {
                    $currentRoute['route_callback'] = $route['callback'];
                    $currentRoute['route_callbacks_before'] = $route['callbacks_before'];
                    $currentRoute['route_callbacks_after'] = $route['callbacks_after'];
                    if (in_array($this->requestMethod, $route['methods'])) {
                        $currentRoute['status'] = self::STATUS_FOUND;
                        break;
                    }
                    $currentRoute['status'] = self::STATUS_METHOD_NOT_ALLOWED;
                }
            } else { // Compare routes through RegEx
                if (preg_match($route['pattern'], $this->requestPath, $routeArgsMatches) !== false) {
                    if (!empty($routeArgsMatches) && is_array($routeArgsMatches)) {
                        $currentRoute['route_callback'] = $route['callback'];
                        $currentRoute['route_callbacks_before'] = $route['callbacks_before'];
                        $currentRoute['route_callbacks_after'] = $route['callbacks_after'];
                        foreach ($routeArgsMatches as $routeArgName => $routeArgVal) // Extract named 'route_args'
                            if (is_string($routeArgName))
                                $currentRoute['route_args'][$routeArgName] = $routeArgVal;
                        if (in_array($this->requestMethod, $route['methods'])) {
                            $currentRoute['status'] = self::STATUS_FOUND;
                            break;
                        }
                        $currentRoute['status'] = self::STATUS_METHOD_NOT_ALLOWED;
                    }
                }
            }
        }

        return $currentRoute;
    }

    /**
     * @param array  $routesArray
     * @param string $routePath
     * @param array  $routeCallbacksBefore
     * @param array  $routeCallbacksAfter
     *
     * @return array - Flat array, created from the multi-dimensional routes array
     */
    protected function routesArrayToRoutesList(
        array $routesArray, string $routePath = '', array $routeCallbacksBefore = [], array $routeCallbacksAfter = []
    )
    {
        $routesList = [];
        foreach ($routesArray as $route) {
            if (!empty($route['routes'])) {
                $routesList = array_merge(
                    $routesList,
                    $this->routesArrayToRoutesList(
                        $route['routes'],
                        $routePath . $route['path'],
                        $route['callbacks_before'],
                        $route['callbacks_after']
                    )
                );
            } else {
                $routesListItem = $route;
                $routesListItem['path'] = $routePath . $route['path'];
                $routesListItem['callbacks_before'] = array_merge($route['callbacks_before'], $routeCallbacksBefore);
                $routesListItem['callbacks_after'] = array_merge($route['callbacks_after'], $routeCallbacksAfter);
                $routesList[] = $routesListItem;
            }
        }
        return $routesList;
    }
}