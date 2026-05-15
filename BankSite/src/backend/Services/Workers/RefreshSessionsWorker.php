<?php

declare(strict_types=1);

namespace App\Services\Workers;

use App\Services\Workers\Worker;
use App\Models\RefreshSession;

class RefreshSessionsWorker extends Worker
{
    private RefreshSession $refreshSession;

    public function __construct(RefreshSession $refreshSession)
    {
        $this->refreshSession = $refreshSession;
        parent::__construct('refresh_sessions_cleaner', 60 * 60 * 24);
    }

    public function work(): bool
    {
        $sessions = $this->refreshSession->getAll();
        $jtisToDelete = [];

        foreach ($sessions as $session) {
            $expires = $session["expires_at"];
                    
            if ($expires <= time()) {
                $jtisToDelete[] = $session["jti"];
            }
        }

        if (!empty($jtisToDelete)) {
            list("status" => $status, "deleted" => $deleted) = $this->refreshSession->deleteByJTIS($jtisToDelete);
        } else {
            $status = "success";
            $deleted = "0";
        }

        if ($status === "success" && $deleted === 0) {
            $this->log("No expired refresh tokens to delete");
        } else if ($status === "failure") {
            $this->log("Unable to remove " . count($jtisToDelete) . " expired refresh tokens");
            return false;
        } else if ($status === "success" && $deleted !== 0) {
            $this->log("Removed " . $deleted . " out of " . count($jtisToDelete) . " expired refresh tokens");
        }

        return true;
    }
}
