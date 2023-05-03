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
        return $this->render($request, $response, 'user/dashboard.twig', [
            'csrf' => $this->generateCsrfToken(),
            'success' => $this->getFlash('success')
        ]);
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

    public function forgotUsername(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/forgotUsername.twig', [
            'csrf' => $this->generateCsrfToken(),
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success')
        ]);
    }

    public function forgotUsernamePost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $email = $request->getParam('email');

        $result = $this->userModel->forgotUsername($email);
        if (!$result) {
            $this->setFlash('error', 'LOL, Potot does not remember either!');
            return $response->withRedirect('/forgot/username');
        }

        $this->setFlash('success', 'Your username is : ' . $result);
        return $response->withRedirect('/forgot/username');
    }

    public function forgotPassword(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/forgotPassword.twig', [
            'csrf' => $this->generateCsrfToken(),
            'error' => $this->getFlash('error'),
            'success' => $this->getFlash('success')
        ]);
    }

    public function forgotPasswordPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $email = $request->getParam('email');

        $result = $this->userModel->forgotUsername($email);
        if (!$result) {
            $this->setFlash('error', 'LOL, Potot does not remember either!');
            return $response->withRedirect('/forgot/password');
        }

        $this->setFlash('success', 'Your new password will be send to your email!');
        return $response->withRedirect('/forgot/password');
    }

    public function updateProfile(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/updateProfile.twig', [
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function updateProfilePost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $displayname = $request->getParam('displayname');
        $description = $request->getParam('description');

        $result = $this->userModel->updateProfile($_SESSION['user']['id'], $displayname, $description);
        if (!empty($result)) {
            $this->setFlash('errors', $result);
            return $response->withRedirect('/update/profile');
        }

        $this->setFlash('success', 'Your data has been saved!');
        return $response->withRedirect('/dashboard');
    }

    public function updateEmail(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/updateEmail.twig', [
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function updateEmailPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $email = $request->getParam('email');

        $result = $this->userModel->updateEmail($_SESSION['user']['id'], $email);
        if (!empty($result)) {
            $this->setFlash('errors', $result);
            return $response->withRedirect('/update/email');
        }

        $this->setFlash('success', 'Your new email has been saved (securely, using Allagan technology)!');
        return $response->withRedirect('/dashboard');
    }

    public function updateUsername(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/updateUsername.twig', [
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function updateUsernamePost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $username = $request->getParam('username');

        $result = $this->userModel->updateUsername($_SESSION['user']['id'], $username);
        if (!empty($result)) {
            $this->setFlash('errors', $result);
            return $response->withRedirect('/update/username');
        }

        $this->setFlash('success', 'Your new username has been saved!');
        return $response->withRedirect('/dashboard');
    }

    public function updatePassword(ServerRequest $request, Response $response): ResponseInterface
    {
        return $this->render($request, $response, 'user/updatePassword.twig', [
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function updatePasswordPost(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $password = $request->getParam('password');
        $confirm = $request->getParam('confirm');

        $result = $this->userModel->updatePassword($_SESSION['user']['id'], $password, $confirm);
        if (!empty($result)) {
            $this->setFlash('errors', $result);
            return $response->withRedirect('/update/password');
        }

        $this->setFlash('success', 'Your new password has been saved!');
        return $response->withRedirect('/dashboard');
    }

    public function logout(ServerRequest $request, Response $response): ResponseInterface
    {
        session_destroy();

        return $response->withRedirect('/login');
    }
}
