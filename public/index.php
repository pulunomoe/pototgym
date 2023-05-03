<?php

use Com\Pulunomoe\PototGym\Controller\CardioController;
use Com\Pulunomoe\PototGym\Controller\UserController;
use Com\Pulunomoe\PototGym\Middleware\AuthMiddleware;
use Dotenv\Dotenv;
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

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
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
$app->get('/forgot/username', [$userController, 'forgotUsername']);
$app->post('/forgot/username', [$userController, 'forgotUsernamePost']);
$app->get('/forgot/password', [$userController, 'forgotPassword']);
$app->post('/forgot/password', [$userController, 'forgotPasswordPost']);
$app->get('/update/profile', [$userController, 'updateProfile'])->add($auth);
$app->post('/update/profile', [$userController, 'updateProfilePost'])->add($auth);
$app->get('/update/email', [$userController, 'updateEmail'])->add($auth);
$app->post('/update/email', [$userController, 'updateEmailPost'])->add($auth);
$app->get('/update/username', [$userController, 'updateUsername'])->add($auth);
$app->post('/update/username', [$userController, 'updateUsernamePost'])->add($auth);
$app->get('/update/password', [$userController, 'updatePassword'])->add($auth);
$app->post('/update/password', [$userController, 'updatePasswordPost'])->add($auth);
$app->get('/logout', [$userController, 'logout']);
$app->get('/dashboard', [$userController, 'dashboard'])->add($auth);

$cardioController = new CardioController($pdo);
$app->get('/exercises/cardios', [$cardioController, 'index']);
$app->get('/exercises/cardios/table', [$cardioController, 'table']);
$app->get('/exercises/cardios/form[/{code}]', [$cardioController, 'form']);
$app->post('/exercises/cardios/form', [$cardioController, 'formPost']);


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
