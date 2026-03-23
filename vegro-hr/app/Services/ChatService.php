<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function listAvailableUsers(User $sender)
    {
        if ($this->isSuperAdmin($sender)) {
            return User::with('role')
                ->where('id', '!=', $sender->id)
                ->get()
                ->filter(fn (User $user) => $this->isSuperAdmin($user))
                ->values();
        }

        return User::with('role')
            ->where('company_id', $sender->company_id)
            ->where('id', '!=', $sender->id)
            ->get()
            ->filter(fn (User $user) => !$this->isSuperAdmin($user))
            ->values();
    }

    public function listConversations(User $user, int $perPage = 20)
    {
        return ChatConversation::query()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with([
                'participants:id,name,email,role_id,company_id',
                'participants.role:id,title',
                'lastMessage:id,conversation_id,user_id,body,created_at',
                'lastMessage.sender:id,name,email',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function createConversation(User $sender, array $participantIds, ?string $name = null): ChatConversation
    {
        $participantIds = array_values(array_unique(array_map('intval', $participantIds)));
        $participantIds = array_values(array_filter($participantIds, fn (int $id) => $id > 0 && $id !== (int) $sender->id));

        if (empty($participantIds)) {
            throw new \RuntimeException('At least one participant is required.');
        }

        $recipients = User::whereIn('id', $participantIds)->get();
        if ($recipients->count() !== count($participantIds)) {
            throw new \RuntimeException('One or more participants were not found.');
        }

        $this->assertChatPermission($sender, $recipients);

        $allParticipantIds = array_values(array_unique(array_merge([$sender->id], $participantIds)));
        sort($allParticipantIds);
        $isGroup = count($allParticipantIds) > 2;

        if (!$isGroup) {
            $existing = $this->findDirectConversation($allParticipantIds);
            if ($existing) {
                return $existing->load(['participants.role', 'lastMessage.sender']);
            }
        }

        $companyId = $this->isSuperAdmin($sender) ? null : $sender->company_id;

        return DB::transaction(function () use ($sender, $allParticipantIds, $name, $isGroup, $companyId) {
            $conversation = ChatConversation::create([
                'company_id' => $companyId,
                'created_by' => $sender->id,
                'name' => $name,
                'is_group' => $isGroup,
                'last_message_at' => now(),
            ]);

            $attachData = [];
            foreach ($allParticipantIds as $participantId) {
                $attachData[$participantId] = ['joined_at' => now()];
            }
            $conversation->participants()->attach($attachData);

            return $conversation->load(['participants.role', 'lastMessage.sender']);
        });
    }

    public function getConversation(User $user, int $conversationId): ChatConversation
    {
        $conversation = ChatConversation::with([
            'participants:id,name,email,role_id,company_id',
            'participants.role:id,title',
            'lastMessage:id,conversation_id,user_id,body,created_at',
            'lastMessage.sender:id,name,email',
        ])->findOrFail($conversationId);

        $this->assertParticipant($conversation, $user);

        return $conversation;
    }

    public function getMessages(User $user, int $conversationId, int $perPage = 30)
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        $this->assertParticipant($conversation, $user);

        return ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->with(['sender:id,name,email,role_id', 'sender.role:id,title'])
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function sendMessage(User $sender, int $conversationId, string $body): ChatMessage
    {
        $conversation = ChatConversation::findOrFail($conversationId);
        $this->assertParticipant($conversation, $sender);

        $message = DB::transaction(function () use ($conversation, $sender, $body) {
            $message = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'body' => trim($body),
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        return $message->load(['sender:id,name,email,role_id', 'sender.role:id,title']);
    }

    protected function findDirectConversation(array $participantIds): ?ChatConversation
    {
        if (count($participantIds) !== 2) {
            return null;
        }

        $conversation = ChatConversation::query()
            ->where('is_group', false)
            ->whereHas('participants', function ($query) use ($participantIds) {
                $query->whereIn('users.id', $participantIds);
            }, '=', 2)
            ->withCount('participants')
            ->having('participants_count', 2)
            ->first();

        return $conversation;
    }

    protected function assertParticipant(ChatConversation $conversation, User $user): void
    {
        $isMember = $conversation->participants()->where('users.id', $user->id)->exists();
        if (!$isMember) {
            throw new \RuntimeException('You are not part of this conversation.');
        }
    }

    protected function assertChatPermission(User $sender, Collection $recipients): void
    {
        if ($this->isSuperAdmin($sender)) {
            $hasNonSuperAdminRecipient = $recipients->contains(
                fn (User $recipient) => !$this->isSuperAdmin($recipient)
            );

            if ($hasNonSuperAdminRecipient) {
                throw new \RuntimeException('Super Admin can only chat with Super Admin users.');
            }

            return;
        }

        $hasSuperAdminRecipient = $recipients->contains(
            fn (User $recipient) => $this->isSuperAdmin($recipient)
        );
        if ($hasSuperAdminRecipient) {
            throw new \RuntimeException('Users cannot chat with Super Admin.');
        }

        $crossCompany = $recipients->contains(
            fn (User $recipient) => (int) $recipient->company_id !== (int) $sender->company_id
        );
        if ($crossCompany) {
            throw new \RuntimeException('Users can only chat with users in the same company.');
        }
    }

    protected function isSuperAdmin(User $user): bool
    {
        $title = strtolower(trim((string) $user->role?->title));
        $normalized = str_replace([' ', '-', '_'], '', $title);
        return $normalized === 'superadmin';
    }
}

