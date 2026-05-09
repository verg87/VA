<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Card;
use App\Controller;
use App\Responses\ResponseFactory;

class TransactionsController extends Controller
{
    public function __construct(private Transaction $transaction, private User $user, private Card $card)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->get($request);
    }

    private function get(ServerRequestInterface $request): ResponseInterface
    {
        list("query" => $query) = $this->requestInfo($request);

        if (isset($query["user_id"]) && $query["user_id"] !== "") {
            try {
                $userId = (int) $query["user_id"];

                $transactions = $this->transaction->getAllByUserId($userId);
                $user = $this->user->getById($userId);

                $formattedTransactions = [];

                $userIds = array_reduce($transactions, function($carry, $tr) use ($userId) {
                    if ($tr["user_id"] !== $userId) {
                        $carry[] = $tr["user_id"];
                    }
                    return $carry;
                }, []);

                $cardIds = array_map(function($tr) use ($userId) {
                    if ($tr["user_id"] === $tr["receiver_user_id"]) {
                        return $tr["receiver_card_id"];
                    } else if ($tr["user_id"] === $userId && $tr["receiver_user_id"] !== $userId) {
                        return $tr["card_id"];
                    } else if ($tr["user_id"] !== $userId && $tr["receiver_user_id"] === $userId) {
                        return $tr["receiver_card_id"];
                    }
                }, $transactions);

                $sentOrReceived = $this->user->getByIds($userIds);
                $sentOrReceivedCards = $this->card->getByIds($cardIds);

                foreach ($transactions as $index => $tr) {
                    $name = "";
                    $amount = "";
                    $cardType = "";

                    $card = $sentOrReceivedCards[$index];

                    if (
                        $tr["card_id"] === $card["id"] ||
                        $tr["receiver_card_id"] === $card["id"]
                    ) {
                        $cardType = $card["card_type"];
                    }

                    if ($tr["user_id"] === $userId) {
                        $name = $user["first_name"] . " " . $user["last_name"];
                    } else {
                        $sent = array_filter($sentOrReceived, function($user) use ($tr) {
                            return $user["id"] === $tr["user_id"];
                        })[0];

                        $name = $sent["first_name"] . " " . $sent["last_name"];
                    }

                    $formattedTransactions[] = [
                        "name" => $name,
                        "type" => $tr["type"],
                        "amount" => $tr["amount"],
                        "card_type" => $cardType
                    ];
                }

                if (!empty($formattedTransactions)) {
                    return ResponseFactory::create(200)(data: $formattedTransactions);
                }

                return ResponseFactory::create(404)();
            } catch (Exception $e) {
                return ResponseFactory::create(500)(message: "Failed to parse transactions");
            }
        }

        return ResponseFactory::create(400)();
    }
}