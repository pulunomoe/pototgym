<?php

use Com\Pulunomoe\PototGym\Controller\UserController;
use Com\Pulunomoe\PototGym\Middleware\AuthMiddleware;
use Slim\Exception\HttpNotFoundException;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\DebugExtension;
use Twig\TwigFilter;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

function debug(mixed $object): void
{
    header('Content-type: application/json');
    die(json_encode($object));
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$pdo = new PDO($_ENV['DB_URL'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
$app = AppFactory::create();
$app->addErrorMiddleware(filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOL), true, true);

$twig = Twig::create(__DIR__ . '/../templates/', ['debug' => $_ENV['APP_DEBUG']]);
$twig->addExtension(new DebugExtension());
$twig->getEnvironment()->addGlobal('session', $_SESSION);

$twig->getEnvironment()->addFilter(new TwigFilter('normalize', function (string $s): string {
    $s = str_replace('_', ' ', $s);
    return strtolower($s);
}));

$app->add(TwigMiddleware::create($app, $twig));

// Middlewares

$auth = new AuthMiddleware();

// Routes

$userController = new UserController($pdo);
$app->get('/', [$userController, 'login']);
$app->get('/login', [$userController, 'login']);
$app->post('/login', [$userController, 'loginPost']);
$app->get('/register', [$userController, 'register']);
$app->post('/register', [$userController, 'registerPost']);
$app->get('/confirm', [$userController, 'confirm']);
$app->post('/confirm', [$userController, 'confirmPost']);
$app->get('/logout', [$userController, 'logout']);
$app->get('/dashboard', [$userController, 'dashboard'])->add($auth);

// CORS

$app->add(function ($request, $handler) {
    return $handler->handle($request)
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

$app->run();
