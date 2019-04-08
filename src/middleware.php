<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $renderer = $this->get('renderer');
    $renderer->addAttribute('uri', $request->getUri());
    return $next($request, $response);
});
