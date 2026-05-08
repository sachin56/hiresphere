<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class S3Service
{
    private S3Client $client;
    private string $bucket;
    private string $recordingsBucket;

    public function __construct()
    {
        $this->client = new S3Client([
            'version' => 'latest',
            'region'  => config('filesystems.disks.s3.region'),
        ]);

        $this->bucket           = config('filesystems.disks.s3.bucket');
        $this->recordingsBucket = env('AWS_RECORDINGS_BUCKET', 'hiresphere-recordings');
    }

    public function uploadSubmission(UploadedFile $file, string $candidateId): array
    {
        $extension = $file->getClientOriginalExtension();
        $key       = "submissions/{$candidateId}/" . Str::uuid() . ".{$extension}";

        $result = $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $file->get(),
            'ContentType' => $file->getMimeType(),
            'ServerSideEncryption' => 'AES256',
        ]);

        return [
            'key'       => $key,
            'url'       => $result['ObjectURL'],
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }

    public function uploadEvaluationReport(string $pdfContent, string $bookingId): string
    {
        $key = "reports/{$bookingId}/" . Str::uuid() . ".pdf";

        $this->client->putObject([
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'Body'        => $pdfContent,
            'ContentType' => 'application/pdf',
            'ServerSideEncryption' => 'AES256',
        ]);

        return $key;
    }

    public function getPresignedDownloadUrl(string $key, int $expiresInMinutes = 60): string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $key,
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expiresInMinutes} minutes");

        return (string) $request->getUri();
    }

    public function getPresignedUploadUrl(string $prefix, string $extension, int $maxSizeMb = 50): array
    {
        $key = "{$prefix}/" . Str::uuid() . ".{$extension}";

        $cmd = $this->client->getCommand('PutObject', [
            'Bucket'      => $this->bucket,
            'Key'         => $key,
            'ContentType' => 'application/octet-stream',
        ]);

        $request = $this->client->createPresignedRequest($cmd, '+15 minutes');

        return [
            'upload_url' => (string) $request->getUri(),
            'key'        => $key,
        ];
    }

    public function deleteObject(string $key): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return true;
        } catch (S3Exception) {
            return false;
        }
    }

    public function getRecordingPresignedUrl(string $key, int $expiresInMinutes = 120): string
    {
        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->recordingsBucket,
            'Key'    => $key,
        ]);

        $request = $this->client->createPresignedRequest($cmd, "+{$expiresInMinutes} minutes");

        return (string) $request->getUri();
    }
}
