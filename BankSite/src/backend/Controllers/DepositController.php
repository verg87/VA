<?php

declare(strict_types=1);

namespace App\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\DepositTypes;
use App\Controller;
use App\Models\Card;
use App\Models\Transaction;
use App\Responses\ResponseFactory;

class DepositController extends Controller
{
    public function __construct(private Card $card, private Transaction $transaction)
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
                "card_id" => $cardId,
                "type" => $type,
                "amount" => $amount
            ) = $data;

            if (!DepositTypes::tryFrom($type) && $amount > 0) {
                return ResponseFactory::create(400)();
            }

            try {
                $status = $this->card->beginTransaction();

                if (!$status) {
                    throw new Exception("Can not begin transaction. Returning server error response");
                }

                if ($this->card->deposit($cardId, $amount)) {
                    if ($this->card->commit() && $this->transaction->create($userId, $userId, $cardId, $type, $amount)) {
                        return ResponseFactory::create(201)(message: "Successfully deposited money");
                    }

                    $this->card->rollBack();
                }

                $this->card->rollBack();
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());
                $this->card->rollBack();

                return ResponseFactory::create(500)(message: "Failed to deposit");
            } 
        }

        return ResponseFactory::create(400)();
    }
}