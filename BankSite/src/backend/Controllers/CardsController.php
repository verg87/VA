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
use App\Models\Account;

use App\Responses\ResponseFactory;

class CardsController extends Controller
{
    public function __construct(private Card $card, private Account $account)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
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

        if (isset($query["user_id"]) && $query["user_id"] !== "") {
            $cardInfo = $this->card->getByUserId((int) $query["user_id"]);
            $mainAccount = $this->account->getByUserId((int) $query["user_id"]);

            if (gettype($cardInfo) === "array" && gettype($mainAccount) === "array") {
                $cardInfo = array_map(function($card) use ($mainAccount) {
                    $card["is_main"] = $card["id"] === $mainAccount["card_id"];

                    return $card;
                }, $cardInfo);

                return ResponseFactory::create(200)(data: $cardInfo);
            } else if (gettype($cardInfo) === "array" && gettype($mainAccount) !== "array") {
                return ResponseFactory::create(200)(data: $cardInfo);
            }

            return ResponseFactory::create(404)();
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
                "expires_at" => $expiresAt,
                "cvv" => $cvv
            ) = $data;

            if (
                !CardTypes::tryFrom($cardType) &&
                $cardType !== CardTypes::Prepaid->value && $amount > 0
            ) {
                return ResponseFactory::create(400)();
            }

            try {
                $this->card->beginTransaction();

                if ($this->card->create($userId, $cardNumber, $cardType, $amount, $expiresAt, $cvv)) {
                    $cardId = $this->card->getLatestCardId();

                    if ($cardType === CardTypes::Credit->value || $cardType === CardTypes::Debit->value) {
                        $cardExist = $this->account->getByUserId($userId);
                        $cardExist = gettype($cardExist) === "array" ? true : false;

                        if ($this->card->commit() && !$cardExist && $this->account->create($userId, $cardId)) {
                            return ResponseFactory::create(201)(message: "Successfully created banking card");
                        }

                        $this->card->rollBack();
                        return ResponseFactory::create(500)(message: "Failed to register a card");
                    }

                    return ResponseFactory::create(201)(message: "Successfully created banking card");
                }

                $this->card->rollBack();
                return ResponseFactory::create(500)(message: "Failed to register a card");
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());

                return ResponseFactory::create(500)(message: "Failed to register a card");
            } 
        }

        return ResponseFactory::create(400)();
    }
}