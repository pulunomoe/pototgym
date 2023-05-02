<?php

namespace Com\Pulunomoe\PototGym\Controller;

use Com\Pulunomoe\PototGym\Model\UserModel;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class UserController extends Controller
{
    private UserModel $userModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->userModel = new UserModel($pdo);
    }

    public function dashboard(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/dashboard.twig');
    }

    public function login(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/login.twig', [
            'csrf' => $this->generateCsrfToken(),
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success')
        ]);
    }

    public function loginPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $username = $request->getParam('username');
        $password = $request->getParam('password');

        $user = $this->userModel->login($username, $password);
        if (is_string($user)) {
            $this->setFlash('error', $user);
            return $response->withRedirect('/login');
        }

        $_SESSION['user'] = $user;

        return $response->withRedirect('/dashboard');
    }

    public function register(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/register.twig', [
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function registerPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $email = $request->getParam('email');
        $username = $request->getParam('username');
        $displayname = $request->getParam('displayname');
        $password = $request->getParam('password');
        $confirm = $request->getParam('confirm');

        $result = $this->userModel->register($email, $username, $displayname, $password, $confirm);
        if (is_array($result)) {
            $this->setFlash('errors', $response);
            return $response->withRedirect('/register');
        }

        return $response->withRedirect('/confirm');
    }

    public function confirm(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/confirm.twig', [
            'csrf' => $this->generateCsrfToken(),
            'error' => $this->getFlash('error')
        ]);
    }

    public function confirmPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $code = $request->getParam('code');

        $result = $this->userModel->confirm($code);
        if (!$result) {
            $this->setFlash('error', 'Wrong secret code!');
            return $response->withRedirect('/confirm');
        }

        $this->setFlash('success', 'Thank you for surrendering your soul to Potot, you may login now!');
        return $response->withRedirect('/login');
    }

    public function logout(ServerRequest $request, Response $response): ResponseInterface
    {
        session_destroy();

        return $response->withRedirect('/login');
    }
}
