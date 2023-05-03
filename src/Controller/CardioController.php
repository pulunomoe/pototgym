<?php

namespace Com\Pulunomoe\PototGym\Controller;

use Com\Pulunomoe\PototGym\Model\CardioModel;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

class CardioController extends Controller
{
    private CardioModel $cardioModel;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->cardioModel = new CardioModel($pdo);
    }

    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $this->render($request, $response, 'cardios/index.twig', [
            'success' => $this->getFlash('success')
        ]);
    }

    public function table(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->withJson($this->processDataTable($request, $this->cardioModel, [
            'user_id' => $_SESSION['user']['id']
        ]));
    }

    public function form(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $code = $args['code'] ?? null;
        if (!empty($code)) {
            $cardio = $this->findOneForUserOr404($request, $this->cardioModel, $code);
        }

        return $this->render($request, $response, 'cardios/form.twig', [
            'cardio' => $cardio ?? null,
            'csrf' => $this->generateCsrfToken(),
            'errors' => $this->getFlash('errors')
        ]);
    }

    public function formPost(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $this->verifyCsrfToken($request);

        $code = $request->getParam('code');
        $name = $request->getParam('name');
        $date = $request->getParam('date');
        $time = $request->getParam('time');
        $calories = $request->getParam('calories');
        $description = $request->getParam('description');

        if (!empty($code)) {
            $this->findOneForUserOr404($request, $this->cardioModel, $code);
        }

        if (empty($code)) {
            $result = $this->cardioModel->create($_SESSION['user']['id'], $date, $name, $time, $calories, $description);
            if (is_array($result)) {
                $this->setFlash('errors', $response);
                return $response->withRedirect('/exercises/cardios/form');
            }
        }

        $this->setFlash('success', 'Cardio exercise saved');
        return $response->withRedirect('/exercises/cardios');
    }

}
