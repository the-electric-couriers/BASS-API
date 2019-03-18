<?php

use Slim\Http\Request;
use Slim\Http\Response;

use BASS\Controllers\Auth\RegisterController;
use BASS\Controllers\User\UserController;

// Routes
$app->get('/[{name}]', function (Request $request, Response $response, array $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->get('/user/create', function (Request $request, Response $response, array $args) use ($app) {
    $this->logger->info("New User Create Route");
    return $this->renderer->render($response, 'user.phtml', array('container' => $app->getContainer()));
});



// API group
$app->group('/api', function () use ($app) {
  // Version group
  $app->group('/v1', function () use ($app) {
    $jwtMiddleware = $this->getContainer()->get('jwt');

    $app->post('/user/create', RegisterController::class . ':register');
    $app->post('/user/login', UserController::class . ':login');
    $app->post('/user/device', UserController::class . ':device');
  });
});
