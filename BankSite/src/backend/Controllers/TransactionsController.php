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
                $formattedTransactions = $this->formatTransactions((int) $query["user_id"]);

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

    private function formatTransactions(int $userId): array
    {
        $transactions = $this->transaction->getAllByUserId($userId);
        $user = $this->user->getById($userId);

        $formattedTransactions = [];

        $userIds = array_reduce($transactions, function($carry, $tr) use ($userId) {
            if ($tr["user_id"] !== $userId) {
                $carry[] = $tr["user_id"];
            }
            return $carry;
        }, []);

        $cardIds = [];

        foreach ($transactions as $tr) {
            $cardIds[] = $this->getUserCardId($tr, $userId);
        }

        $sentOrReceived = $this->user->getByIds($userIds);
        $sentOrReceivedCards = $this->card->getByIds(array_values(array_unique($cardIds)));

        $cardsById = array_reduce($sentOrReceivedCards, function ($carry, $card) {
            $carry[$card["id"]] = $card;
            return $carry;
        }, []);

        foreach ($transactions as $tr) {
            $name = "";
            $amount = "";
            $cardType = "";
            $date = explode(" ", $tr["created_at"])[0];

            $cardId = $this->getUserCardId($tr, $userId);

            if ($cardId && isset($cardsById[$cardId])) {
                $card = $cardsById[$cardId];
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

            if ($tr["user_id"] !== $tr["receiver_user_id"] && $tr["user_id"] === $userId) {
                $amount = "-" . $tr["amount"];
            } else if ($tr["user_id"] === $tr["receiver_user_id"]) {
                $amount = "+" . $tr["amount"];
            }

            $formattedTransactions[$date][] = [
                "name" => $name,
                "type" => $tr["type"],
                "amount" => $amount,
                "card_type" => $cardType
            ];
        }

        return $formattedTransactions;
    }

    private function getUserCardId(array $transaction, int $userId): int
    {
        if ($transaction["user_id"] === $userId && $transaction["receiver_user_id"] !== $userId) {
            return $transaction["card_id"];
        } else if ($transaction["user_id"] !== $userId && $transaction["receiver_user_id"] === $userId) {
            return $transaction["receiver_card_id"];
        }

        return $transaction["receiver_card_id"];
    }
}