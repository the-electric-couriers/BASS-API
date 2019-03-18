<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});



// // API group
// $app->group('/api', function () use ($app) {
//   // Version group
//   $app->group('/v1', function () use ($app) {
//     $jwtMiddleware = $this->getContainer()->get('jwt');
//
//     $app->get('/test123', 'verifications');
//   });
// });
