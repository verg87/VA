<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Helpers\Functions;
use App\Helpers\DepositTypes;
use App\Controller;

use App\Models\User;
use App\Models\Card;
use App\Models\Account;
use App\Models\Transaction;

use App\Responses\ResponseFactory;

class TransferController extends Controller
{
    public function __construct(
        private User $user, private Account $account, private Card $card, private Transaction $transaction
    )
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
                "card_id" => $userCardId,
                "receiver_phone_number" => $receiverPhoneNumber,
                "amount" => $amount
            ) = $data;

            try {
                $status = $this->card->beginTransaction();

                if (!$status) {
                    throw new Exception("Can not begin transaction. Returning server error response");
                }

                $receiverUser = $this->user->getByPhone($receiverPhoneNumber);

                if (gettype($receiverUser) !== "array") {
                    return ResponseFactory::create(404)(message: "There is no such user with that phone number");
                }

                $receiverId = $receiverUser["id"];

                $receiverCard = $this->account->getByUserId($receiverId);

                if (gettype($receiverCard) !== "array") {
                    return ResponseFactory::create(404)(message: "Failed to find a banking account attached to this phone number");
                }

                $receiverCardId = $receiverCard["card_id"];

                if ($this->card->transfer($userCardId, $receiverCardId, $amount)) {
                    if ($this->card->commit() && $this->transaction->create($userId, $receiverId, $receiverCardId, DepositTypes::Transfer->value, $amount, $userCardId)) {
                        return ResponseFactory::create(201)(message: "Successfully transfered money");
                    }

                    $this->card->rollBack();
                }

                $this->card->rollBack();
            } catch (\Throwable $e) {
                // Maybe log it to some file
                var_dump($e->getMessage());
                $this->card->rollBack();

                return ResponseFactory::create(500)(message: "Failed to transfer");
            } 
        }

        return ResponseFactory::create(400)();
    }
}