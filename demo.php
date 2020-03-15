<?php

include('src/Router.php');

use FahrradKrucken\SFRouter\Router\Router;

// First - create the Router's instance.
// You can add your REQUEST_URI and REQUEST_METHOD right here, in constructor.
// You can also add these through $r->dispatch() or through $r->setRequestMethod() and $r->setRequestPath().
// Default RequestPath = $_SERVER['REQUEST_URI'].
// Default RequestMethod = $_SERVER['REQUEST_METHOD'].
$r = new Router();

// Then you should add array of your routes.
// Router has no 'default route' feature, like asterisk - you should handle this situations by yourself.
$r->addRoutes([
    // This is how routes should be added
    Router::route(
        [], // Array of allowed RequestMethods (default is ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])
        '/', // Route path ('/' will be automatically added to the start and removed from the end).
        'Callback for: /', // Your route's callback
        'Callback Before for: /',  // Your route's 'before callback', could be array (request middleware)
        'Callback After for: /' // Your route's 'after callback', could be array (response middleware)
    ),

    Router::routeGet('/info', 'Callback for: /info'),
    Router::routePost('/info', 'Callback for: /info'),
    Router::routeGet('/about', 'Callback for: /about'),
    Router::routePost('/about', 'Callback for: /about'),

    // You can use route groups with any level of complexity
    Router::routeGroup('/user/{id}', [ // You can use named params at routes and on route-groups
        Router::routeGet('/view', 'Callback for: /user/view'),
        Router::routePost('/create', 'Callback for: /user/create'),
        Router::routePatch('/update', 'Callback for: /user/update'),
        Router::routeDelete('/delete', 'Callback for: /user/delete',
            'Callback Before: /user/delete', 'Callback After: /user/delete'),
    ], 'Callback Before: /user/*', 'Callback After: /user/*'),
    Router::routeGroup('/post/{post_id}', [
        Router::routeGet('/view', 'Callback for: /post/view'),
        Router::routeGroup('/comments/{comment_id}', [
            Router::routeGet('/view', 'Callback for: /post/comments/view'),
            Router::routePost('/publish', 'Callback for: /post/comments/publish',
                [], 'Callback After: /post/comments/publish'),
        ], [], 'Callback After: /post/comments/*') // You can use 'before/after' callbacks on groups as well.
    ]),
]);

// Then you should call this method to retrieve current route
$currentRoute = $r->dispatch(
    '/post/john-wick-4/comments/23467/publish', // Request Path
    'post' // Request Method
);
// Response format is the next
// [
//      'status' => @var string Router::STATUS_FOUND | Router::STATUS_NOT_FOUND | Router::STATUS_METHOD_NOT_ALLOWED,
//      'request_path' => @var string Current Request Path.
//      'request_method' => @var string Current Request Method.
//
//      'route_args' => @var array - Current route's arguments/named parameters (if persists),
//      'route_callback' => @var callable - Current route's callable,
//      'route_callbacks_before' => @var callable[] - Current route's callable's before,
//      'route_callbacks_after' => @var callable[] - Current route's callable's after,
// ]

// After that you can do what you need with retrieved info
switch ($currentRoute['status']) {
    case Router::STATUS_NOT_FOUND:
        // Route not found ('route_args'/'route_callback'/'route_callbacks_before/after' will be empty in this case).
        break;
    case Router::STATUS_METHOD_NOT_ALLOWED:
        // Route found, but request method is not allowed on this route.
        break;
    case Router::STATUS_FOUND:
    default:
        // Route found
        break;
}