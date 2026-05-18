<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\CardTypes;

use App\Models\Account;
use App\Models\Card;

use App\Controller;

use App\Responses\ResponseFactory;

class ChangeMainAccountController extends Controller
{
    public function __construct(private Card $card, private Account $account)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->post($request);
    }

    private function post(ServerRequestInterface $request): ResponseInterface
    {
        list("data" => $data, "attributes" => $attributes) = $this->requestInfo($request);

        if (
            !isset($attributes["user"]) || 
            gettype($attributes["user"]) !== "array" || 
            $attributes["user"]["id"] !== (int) ($data["user_id"] ?? -1)
        ) {
            return ResponseFactory::create(401)();
        } 

        if (Functions::array_all($data, fn($value) => $value !== "")) {
            list(
                "user_id" => $userId,
                "card_id" => $cardId
            ) = $data;

            try {
                $cardExists = $this->card->getById($cardId);
                $currentAccount = $this->account->getByUserId($userId);

                if (gettype($cardExists) !== "array") {
                    return ResponseFactory::create(404)(message: "There is no such card");
                }

                if ($currentAccount["card_id"] === $cardId) {
                    return ResponseFactory::create(400)(message: "This is already a main card");
                }

                if ($cardExists["card_type"] !== CardTypes::Credit->value && $cardExists["card_type"] !== CardTypes::Debit->value) {
                    return ResponseFactory::create(400)(message: "Can not switch to non credit or debit card type");
                }

                if ($this->account->update($userId, $cardId)) {
                    return ResponseFactory::create(200)(message: "Changed main bank account successfully");
                }

                return ResponseFactory::create(500)(message: "Failed to change main card");
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());

                return ResponseFactory::create(500)(message: "Failed to change main card");
            } 
        }

        return ResponseFactory::create(400)();
    }
}