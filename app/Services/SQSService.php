<?php

namespace App\Services;

use Aws\Sqs\SqsClient;

class SQSService
{
    private SqsClient $client;
    private string $queueUrl;

    public function __construct()
    {
        $this->client = new SqsClient([
            'version' => 'latest',
            'region'  => env('AWS_SQS_REGION', 'us-east-1'),
        ]);

        $this->queueUrl = env('SQS_PREFIX') . '/' . env('SQS_QUEUE', 'hiresphere-events');
    }

    public function dispatch(string $eventType, array $payload): string
    {
        $message = [
            'event_type' => $eventType,
            'payload'    => $payload,
            'timestamp'  => now()->toIso8601String(),
        ];

        $result = $this->client->sendMessage([
            'QueueUrl'               => $this->queueUrl,
            'MessageBody'            => json_encode($message),
            'MessageGroupId'         => $eventType,
            'MessageDeduplicationId' => md5($eventType . json_encode($payload) . time()),
            'MessageAttributes'      => [
                'EventType' => [
                    'DataType'    => 'String',
                    'StringValue' => $eventType,
                ],
            ],
        ]);

        return $result['MessageId'];
    }

    public function dispatchBookingCreated(array $bookingData): void
    {
        $this->dispatch('booking.created', $bookingData);
    }

    public function dispatchBookingAccepted(array $bookingData): void
    {
        $this->dispatch('booking.accepted', $bookingData);
    }

    public function dispatchBookingRejected(array $bookingData): void
    {
        $this->dispatch('booking.rejected', $bookingData);
    }

    public function dispatchEvaluationReady(array $evaluationData): void
    {
        $this->dispatch('evaluation.ready', $evaluationData);
    }

    public function dispatchPaymentProcessed(array $paymentData): void
    {
        $this->dispatch('payment.processed', $paymentData);
    }
}
