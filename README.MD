# SFRouter

### Info

This is the simple router, implemented in one file, that could be used 
through traditional composer-dependency or through `require` statement.

### Installation

* Through Composer: `composer require fahrradkrucken/sf-router`.
* As a separate file: `require('path/to/sf-router.php')`.

### Features

Basically, it allows us to (almost) do everything we usually do with routers:
* Add GET/POST/PUT/PATCH/DELETE routes (or several request methods at once);
* Add route groups;
* Add callbacks to routes/route-groups as well as add before/after actions
(callbacks);
* Set custom URI and REQUEST_METHOD (as FastRoute allows us to do);
* Set simple named params inside your route, smth like  
`my-api/v2/user/{user_id}/actions/{action_id}/view`.

What it can't do for you:
* Handle your route's callback;
* Get you the GET and POST request parameters.

So the only thing it does is takes your URI/REQUEST_METHOD and returns 
correct route, based on list of routes, provided by you.

### Usage

See the `demo.php` file inside this repo to see how to use it.