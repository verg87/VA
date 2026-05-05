<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\DepositTypes;
use App\Controller;
use App\Models\Card;
use App\Responses\ResponseFactory;

class DepositController extends Controller
{
    public function __construct(private Card $card)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            case "POST":
                return $this->post($request);
        }

        return ResponseFactory::create(405)();
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        list("data" => $data) = $this->requestInfo($request);

        if (Functions::array_all($data, fn($value) => $value !== "")) {
            list(
                "user_id" => $userId,
                "card_number" => $cardNumber,
                "type" => $type,
                "amount" => $amount
            ) = $data;

            if (!DepositTypes::tryFrom($type) && $amount > 0) {
                return ResponseFactory::create(400)();
            }

            try {
                if ($this->card->create($userId, $cardNumber, $type, $amount)) {
                    return ResponseFactory::create(201)(message: "Successfully created banking card");
                }
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());

                return ResponseFactory::create(500)(message: "Failed to register a card");
            } 
        }

        return ResponseFactory::create(400)();
    }
}