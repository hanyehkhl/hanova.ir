<?php

require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Router.php';

$router = new Router();
$result = $router->handleRequest();
