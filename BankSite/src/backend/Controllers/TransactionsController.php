<?php

declare(strict_types=1);

namespace App\Controllers;

require_once __DIR__ . "\\..\\..\\..\\vendor\\autoload.php";

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use App\Models\Transaction;
use App\Models\User;
use App\Controller;
use App\Responses\ResponseFactory;

class TransactionsController extends Controller
{
    public function __construct(private Transaction $transaction, private User $user)
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
            $transactions = $this->transaction->getAllByUserId($query["user_id"]);
            $user = $this->user->getById($query["user_id"]);

            $formattedTransactions = [];

            $userIds = array_reduce($transactions, function($carry, $tr) use ($query) {
                if ($tr["user_id"] !== $query["user_id"]) {
                    $carry[] = $tr["user_id"];
                }
                return $carry;
            }, []);

            $sentOrReceived = $this->user->getByIds($userIds);

            foreach ($transactions as $tr) {
                $name = "";

                if ($tr["user_id"] === $query["user_id"]) {
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
                    // add card type to which money was sent
                ];
            }
        }

        return ResponseFactory::create(400)();
    }
}