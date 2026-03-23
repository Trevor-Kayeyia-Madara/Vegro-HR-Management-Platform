<?php

namespace App\Services;

use App\Models\InAppNotification;

class InAppNotificationService
{
    public function notifyUser(
        int $userId,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?int $companyId = null
    ): InAppNotification {
        return InAppNotification::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }

    public function notifyUsers(
        array $userIds,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?int $companyId = null
    ): void {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        foreach ($userIds as $userId) {
            $this->notifyUser($userId, $title, $message, $type, $data, $companyId);
        }
    }
}

