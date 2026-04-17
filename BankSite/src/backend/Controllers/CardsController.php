<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\CardTypes;
use App\Controller;
use App\Models\Card;
use App\Responses\ResponseFactory;

class CardsController extends Controller
{
    public function __construct(private Card $card)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        switch (strtoupper($request->getMethod())) {
            case "GET":
                return $this->get($request);
            case "POST":
                return $this->post($request);
        }

        return ResponseFactory::create(405)();
    }

    private function get(ServerRequestInterface $request): ResponseInterface
    {
        list("query" => $query) = $this->requestInfo($request);
        $userId = $query["user_id"];

        if ($userId !== "") {
            $cardInfo = $this->card->getByUserId((int) $userId);

            if (gettype($cardInfo) === "array") {
                return ResponseFactory::create(200)(data: $cardInfo);
            }
        }

        return ResponseFactory::create(400)();
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        list("data" => $data) = $this->requestInfo($request);

        if (Functions::array_all($data, fn($value) => $value !== "")) {
            list(
                "user_id" => $userId,
                "card_number" => $cardNumber,
                "card_type" => $cardType,
                "amount" => $amount,
                "expires_at" => $expiresAt
            ) = $data;

            if (ucfirst($cardType) !== CardTypes::Prepaid->name && $amount !== 0) {
                return ResponseFactory::create(400)();
            }

            try {
                if ($this->card->create($userId, $cardNumber, $cardType, $amount, $expiresAt)) {
                    return ResponseFactory::create(201)(message: "Successfully created banking card");
                }
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());

                return ResponseFactory::create(500)(message: "Failed to register a card");
            } 

            $cardInfo = $this->card->getById($this->card->getLatestId());
            return ResponseFactory::create(200)(data: $cardInfo);
        }

        return ResponseFactory::create(400)();
    }
}