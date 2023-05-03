<?php

namespace Com\Pulunomoe\PototGym\Controller;

use Com\Pulunomoe\PototGym\Model\Interface\FindAllForDataTableInterface;
use Com\Pulunomoe\PototGym\Model\Interface\FindOneForUserInterface;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Slim\Views\Twig;

abstract class Controller
{
    public function __construct(protected PDO $pdo)
    {
    }

    public function render(ServerRequest $request, Response $response, string $template, array $data = []): ResponseInterface
    {
        return Twig::fromRequest($request)->render($response, $template, $data);
    }

    protected function getFlash($key): string|array|null
    {
        if (!empty($_SESSION['flash'][$key])) {
            $flash = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $flash;
        } else {
            return null;
        }
    }

    protected function setFlash(string $key, string|array $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }

    protected function generateCsrfToken(): array
    {
        $key = password_hash(sha1(mt_rand()) . sha1(microtime()), PASSWORD_DEFAULT);
        $value = password_hash(sha1(mt_rand()) . sha1(microtime()), PASSWORD_DEFAULT);

        unset($_SESSION['csrf']);
        $_SESSION['csrf'][$key] = $value;

        return [
            'key' => $key,
            'value' => $value
        ];
    }

    protected function verifyCsrfToken(ServerRequest $request): void
    {
        if ($_ENV['APP_DEBUG']) return;

        $key = $request->getParam('csrf_key');
        $value = $request->getParam('csrf_value');

        if (empty($_SESSION['csrf'][$key]) || $_SESSION['csrf'][$key] != $value) {
            throw new HttpBadRequestException($request);
        }

        unset($_SESSION['csrf']);
    }

    protected function findOneForUserOr404(ServerRequest $request, FindOneForUserInterface $model, string $code): ?array
    {
        $object = $model->findOne($code, $_SESSION['user']['id']);
        if (empty($object)) {
            throw new HttpNotFoundException($request);
        }

        return $object;
    }

    protected function processDataTable(ServerRequest $request, FindAllForDataTableInterface $model, array $filters = []): array
    {
        $draw = $request->getParam('draw');
        $start = $request->getParam('start', 0);
        $length = $request->getParam('length', 10);

        $search = $request->getParam('search');
        $keyword = $search['value'] ?? '%';

        $order = $request->getParam('order');
        $orderCol = $order[0]['column'] ?? 0;
        $orderBy = $order[0]['dir'] ?? 'asc';

        $objects = $model->findAllForDataTable($start, $length, $keyword, $orderCol, $orderBy, $filters);
        $objects['draw'] = $draw;

        return $objects;
    }
}
