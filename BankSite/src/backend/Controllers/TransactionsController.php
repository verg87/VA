<?php

declare(strict_types=1);

namespace App\Controllers;

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
                var_dump($e->getMessage());
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

        $senderIds = array_reduce($transactions, function($carry, $tr) use ($userId) {
            if ($tr["user_id"] !== $userId) {
                $carry[] = $tr["user_id"];
            }
            return $carry;
        }, []);

        $receiverIds = array_reduce($transactions, function($carry, $tr) use ($userId) {
            if ($tr["receiver_user_id"] !== $userId) {
                $carry[] = $tr["receiver_user_id"];
            }
            return $carry;
        }, []);

        $cardIds = [];

        foreach ($transactions as $tr) {
            $cardIds[] = $this->getUserCardId($tr, $userId);
        }

        $senders = $this->user->getByIds($senderIds);
        $receivers = $this->user->getByIds($receiverIds);
        $sendersCards = $this->card->getByIds(array_values(array_unique($cardIds)));

        if (gettype($sendersCards) === "boolean") {
            throw new Exception("No transactions to parse");
        }

        $cardsById = array_reduce($sendersCards, function ($carry, $card) {
            $carry[$card["id"]] = $card;
            return $carry;
        }, []);

        foreach ($transactions as $tr) {
            $name = "";
            $amount = "";
            $cardType = "";
            list($date, $time) = explode(" ", $tr["created_at"]);

            $cardId = $this->getUserCardId($tr, $userId);

            if ($cardId && isset($cardsById[$cardId])) {
                $card = $cardsById[$cardId];
                $cardType = $card["card_type"];
            }

            if ($tr["user_id"] === $userId && $tr["receiver_user_id"] === $userId) {
                $name = $user["first_name"] . " " . $user["last_name"];
            } else if ($tr["user_id"] === $userId && $tr["receiver_user_id"] !== $userId) {
                $receiver = array_filter($receivers, function($user) use ($tr) {
                    return $user["id"] === $tr["receiver_user_id"];
                })[0];

                $name = $receiver["first_name"] . " " . $receiver["last_name"];
            } else {
                $sender = array_filter($senders, function($user) use ($tr) {
                    return $user["id"] === $tr["user_id"];
                })[0];

                $name = $sender["first_name"] . " " . $sender["last_name"];
            }

            if ($tr["user_id"] !== $tr["receiver_user_id"] && $tr["user_id"] === $userId) {
                $amount = "-" . $tr["amount"];
            } else if ($tr["user_id"] !== $tr["receiver_user_id"] && $tr["user_id"] !== $userId) {
                $amount = "+" . $tr["amount"];
            } else if ($tr["user_id"] === $tr["receiver_user_id"] && $tr["card_id"] === null) {
                $amount = "+" . $tr["amount"];
            } else if ($tr["user_id"] === $tr["receiver_user_id"] && $tr["card_id"] !== null) {
                $amount = $tr["amount"];
            }

            $formattedTransactions[$date][] = [
                "name" => $name,
                "type" => $tr["type"],
                "amount" => $amount,
                "card_type" => $cardType,
                "time" => $time
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