<?php

namespace App\Services;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Str;

class DynamoDBService
{
    private DynamoDbClient $client;
    private Marshaler $marshaler;
    private string $messagesTable;
    private string $conversationsTable;

    public function __construct()
    {
        $this->client = new DynamoDbClient([
            'version' => 'latest',
            'region'  => config('services.dynamodb.region', env('AWS_DEFAULT_REGION', 'us-east-1')),
        ]);

        $this->marshaler           = new Marshaler();
        $this->messagesTable       = env('DYNAMODB_MESSAGES_TABLE', 'hiresphere-messages');
        $this->conversationsTable  = env('DYNAMODB_CONVERSATIONS_TABLE', 'hiresphere-conversations');
    }

    // ─── Messages ─────────────────────────────────────────────────────────────

    public function sendMessage(string $conversationId, string $senderId, string $content, string $type = 'text'): array
    {
        $messageId = (string) Str::uuid();
        $timestamp = now()->toIso8601String();

        $item = [
            'message_id'      => $messageId,
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'content'         => $content,
            'type'            => $type,
            'is_read'         => false,
            'created_at'      => $timestamp,
        ];

        $this->client->putItem([
            'TableName' => $this->messagesTable,
            'Item'      => $this->marshaler->marshalItem($item),
        ]);

        // Update conversation last message
        $this->updateConversationLastMessage($conversationId, $content, $senderId, $timestamp);

        return $item;
    }

    public function getMessages(string $conversationId, int $limit = 50, ?string $lastKey = null): array
    {
        $params = [
            'TableName'                => $this->messagesTable,
            'KeyConditionExpression'   => 'conversation_id = :cid',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':cid' => $conversationId]),
            'ScanIndexForward'         => false,
            'Limit'                    => $limit,
        ];

        if ($lastKey) {
            $params['ExclusiveStartKey'] = $this->marshaler->marshalItem(['conversation_id' => $conversationId, 'message_id' => $lastKey]);
        }

        $result   = $this->client->query($params);
        $messages = [];

        foreach ($result['Items'] as $item) {
            $messages[] = $this->marshaler->unmarshalItem($item);
        }

        return [
            'messages'     => array_reverse($messages),
            'last_key'     => isset($result['LastEvaluatedKey'])
                ? $this->marshaler->unmarshalItem($result['LastEvaluatedKey'])['message_id']
                : null,
        ];
    }

    public function markMessagesAsRead(string $conversationId, string $userId): void
    {
        $result = $this->client->query([
            'TableName'                => $this->messagesTable,
            'KeyConditionExpression'   => 'conversation_id = :cid',
            'FilterExpression'         => 'sender_id <> :uid AND is_read = :false',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':cid'  => $conversationId,
                ':uid'  => $userId,
                ':false' => false,
            ]),
        ]);

        foreach ($result['Items'] as $item) {
            $msg = $this->marshaler->unmarshalItem($item);
            $this->client->updateItem([
                'TableName' => $this->messagesTable,
                'Key'       => $this->marshaler->marshalItem([
                    'conversation_id' => $conversationId,
                    'message_id'      => $msg['message_id'],
                ]),
                'UpdateExpression'          => 'SET is_read = :true, read_at = :now',
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':true' => true,
                    ':now'  => now()->toIso8601String(),
                ]),
            ]);
        }
    }

    // ─── Conversations ────────────────────────────────────────────────────────

    public function getOrCreateConversation(string $userId1, string $userId2): array
    {
        $conversationId = $this->buildConversationId($userId1, $userId2);

        $result = $this->client->getItem([
            'TableName' => $this->conversationsTable,
            'Key'       => $this->marshaler->marshalItem(['conversation_id' => $conversationId]),
        ]);

        if (isset($result['Item'])) {
            return $this->marshaler->unmarshalItem($result['Item']);
        }

        $conversation = [
            'conversation_id'  => $conversationId,
            'participants'     => [$userId1, $userId2],
            'last_message'     => '',
            'last_sender_id'   => '',
            'last_message_at'  => now()->toIso8601String(),
            'created_at'       => now()->toIso8601String(),
        ];

        $this->client->putItem([
            'TableName' => $this->conversationsTable,
            'Item'      => $this->marshaler->marshalItem($conversation),
        ]);

        return $conversation;
    }

    public function getUserConversations(string $userId): array
    {
        $result = $this->client->scan([
            'TableName'        => $this->conversationsTable,
            'FilterExpression' => 'contains(participants, :uid)',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':uid' => $userId]),
        ]);

        $conversations = [];
        foreach ($result['Items'] as $item) {
            $conversations[] = $this->marshaler->unmarshalItem($item);
        }

        usort($conversations, fn($a, $b) => strcmp($b['last_message_at'], $a['last_message_at']));

        return $conversations;
    }

    private function updateConversationLastMessage(string $conversationId, string $message, string $senderId, string $timestamp): void
    {
        $this->client->updateItem([
            'TableName'                => $this->conversationsTable,
            'Key'                      => $this->marshaler->marshalItem(['conversation_id' => $conversationId]),
            'UpdateExpression'         => 'SET last_message = :msg, last_sender_id = :sid, last_message_at = :ts',
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                ':msg' => mb_substr($message, 0, 100),
                ':sid' => $senderId,
                ':ts'  => $timestamp,
            ]),
        ]);
    }

    private function buildConversationId(string $id1, string $id2): string
    {
        $ids = [$id1, $id2];
        sort($ids);
        return implode('_', $ids);
    }
}
