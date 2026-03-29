<?php

declare(strict_types=1);

namespace App\Services\Workers;

use App\Services\Workers\Worker;
use App\Models\RefreshSession;

class RefreshSessionsCleanerWorker extends Worker
{
    private RefreshSession $refreshSession;

    public function __construct(RefreshSession $refreshSession)
    {
        $this->refreshSession = $refreshSession;
        parent::__construct('refresh_sessions_cleaner', 30);
    }

    public function work(): void
    {
        $sessions = $this->refreshSession->getAll();
        $jtisToDelete = [];

        foreach ($sessions as $session) {
            $created = $session["created_at"];
            $expires = $session["expires_at"];

            $expiresAtSeconds = strtotime($created) + $expires;
                    
            if ($expiresAtSeconds >= time()) {
                $jtisToDelete[] = $session["jti"];
            }
        }

        $isSuccessful = $this->refreshSession->deleteByJTIS($jtisToDelete);

        $isSuccessful 
            ? $this->log('Removed expired refresh tokens') 
            : $this->log("Unable to remove expired refresh tokens");
    }
}
