<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\DepositTypes;
use App\Controller;
use App\Models\Card;
use App\Models\Transaction;
use App\Responses\ResponseFactory;
use App\Responses\LoggedResponse;

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

        if (!$this->validateBankRequest($data, $attributes)) {
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
                $this->card->beginTransaction();

                if ($this->card->deposit($cardId, $amount)) {
                    if ($this->card->commit() && $this->transaction->create($userId, $userId, $cardId, $type, $amount)) {
                        return ResponseFactory::create(201)(message: "Successfully deposited money");
                    }

                    $this->card->rollBack();
                }

                $this->card->rollBack();
            } catch (\Throwable $e) {
                $this->card->rollBack();

                return (new LoggedResponse($e, $request))(message: "Failed to deposit");
            } 
        }

        return ResponseFactory::create(400)();
    }
}