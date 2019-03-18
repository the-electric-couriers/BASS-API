<?php

use Slim\Http\Request;
use Slim\Http\Response;
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);

// $app->add(new \Tuupola\Middleware\JwtAuthentication([
//     "path" => "/api",
//     "attribute" => "decoded_token_data",
//     "secret" => "supersecretkeyyoushouldnotcommittogithub",
//     "algorithm" => ["HS256"],
//     "error" => function ($response, $arguments) {
//         $data["status"] = "error";
//         $data["message"] = $arguments["message"];
//         return $response
//             ->withHeader("Content-Type", "application/json")
//             ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
//     }
// ]));

$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $renderer = $this->get('renderer');
    $renderer->addAttribute('uri', $request->getUri());
    return $next($request, $response);
});
