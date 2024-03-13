<?php

require 'vendor/autoload.php';

use Appwrite\Client;
use Appwrite\Faker\Client as FakerClient;
use Appwrite\InputFile;
use Appwrite\Services\Storage as AppwriteStorage;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Question\Question;

class Storage
{
    protected ?FakerClient $client = null;
    protected string $endpoint;
    protected string $apiKey;
    protected string $projectId;

    public function __construct()
    {
        $this->client = new FakerClient();
        $this->endpoint = $GLOBALS['APPWRITE_ENDPOINT'];
        $this->apiKey = $GLOBALS['APPWRITE_API_KEY'];
        $this->projectId = $GLOBALS['APPWRITE_PROJECT_ID'];
    }

    // private function uploadChunkToAppwrite($chunk, $bucketId, $fileId, $chunkIndex, $totalChunks) {
    //     $data = [
    //       'file' => [
    //         'content' => base64_encode($chunk),
    //         'filename' => "chunk_{$chunkIndex}.jpg",
    //       ],
    //     ];

    //     $client = new Client();

    //     $client
    //         ->setEndpoint($this->endpoint)
    //         ->setProject($this->projectId)
    //         ->setKey($this->apiKey);

    //     $storage = new AppwriteStorage($client);
    //     $inputFile = new InputFile($data['file']['content'], $data['file']['filename']);

    //     $response = $storage->createFile($bucketId, $fileId, $inputFile);

    //     // Handle response
    //     if (isset($response['error'])) {
    //       // Handle errors (exception, logging, etc.)
    //       var_dump("there is an error");
    //       return false;
    //     }

    //     return $response;
    //   }


    // private function fetchChunk($url, $startByte, $endByte) {
    //     $headers = [
    //       "Range" => "bytes=$startByte-$endByte",
    //     ];

    //     $contextOptions = [
    //       'http' => [
    //         'method' => 'GET',
    //         'header' => implode("\r\n", $headers),
    //       ],
    //     ];

    //     $context = stream_context_create($contextOptions);
    //     $response = file_get_contents($url, false, $context);

    //     if ($response === false) {
    //       return false;
    //     }

    //     return $response;
    //   }

    // private function getTotalSize($url) {
    //     $headers = get_headers($url, true);
    //     if (!isset($headers['Content-Length'])) {
    //       return false;
    //     }
    //     return (int) $headers['Content-Length'];
    //   }

    // private function streamUploadFromURL($fileUrl, $bucketId)
    // {
    //     $faker = Faker\Factory::create();
    //     $chunk_size = 5 * 1024 * 1024; // 5MB
    //     $total_size = $this->getTotalSize($fileUrl);
    //     $fileId = null;

    //     for ($currentChunk = 0; $currentChunk < $total_size; $currentChunk += $chunk_size) {
    //         $endByte = min($currentChunk + $chunk_size - 1, $total_size - 1);
    //         $chunk = $this->fetchChunk($fileUrl, $currentChunk, $endByte);
    //         $response = $this->uploadChunkToAppwrite($chunk, $bucketId, $fileId || $faker->uuid, floor($currentChunk / $chunk_size), ceil($total_size / $chunk_size));
    //         if (!$fileId) {
    //             $fileId = json_decode($response['body'], true)['$id'];
    //         }
    //     }
    //     return $fileId;
    // }

    private function generateFakeFileUrl()
    {
        $faker = Faker\Factory::create();

        $type = $faker->randomElement(['image', 'audio', 'video', 'text', 'binary']);
        $type = 'image'; //testing for images
        switch ($type) {
            case 'image':
                $categories = ['cats', 'dogs', 'nature', 'bird', 'vegetables'];
                $category = $faker->randomElement($categories);
                $dimensions = [
                    ['width' => 320, 'height' => 240],
                    ['width' => 640, 'height' => 480],
                    ['width' => 800, 'height' => 600],
                ];
                $dimension = $faker->randomElement($dimensions);
                $width = $dimension['width'];
                $height = $dimension['height'];
                $imageUrl = "https://loremflickr.com/$width/$height/$category";
                return $imageUrl;
        }
        return '';
    }

    private function generateFiles(Input $input, Output $output, $buckets)
    {
        $faker = Faker\Factory::create();
        $helper = new QuestionHelper();
        $question = new Question('How many files would you like to generate per bucket? (Default: 25)', 25);
        $fileNos = $helper->ask($input, $output, $question);

        for ($i = 0; $i < count($buckets); $i++) {
            $bucket = $buckets[$i];
            $bucketBody = json_decode($bucket['body'], true);
            $bucketId = $bucketBody['$id'];

            $files = [];

            for ($j = 0; $j < $fileNos; $j++) {
                $fileUrl = $this->generateFakeFileUrl();
                // $this->streamUploadFromURL($fileUrl, $bucketId);
                $fileData = [
                    'file' => [
                        'content' => file_get_contents($fileUrl),
                        'filename' => $faker->name . '.jpg',
                    ],
                ];
                $inputFile = InputFile::withData($fileData['file']['content'], 'image/jpeg', $fileData['file']['filename']);

                $client = new Client();
                $client
                    ->setEndpoint($this->endpoint)
                    ->setProject($this->projectId)
                    ->setKey($this->apiKey);

                $storage = new AppwriteStorage($client);

                $files[] = $storage->createFile($bucketId, $faker->uuid, $inputFile);

                if (isset($response['error'])) {
                    var_dump("there is an error");
                    return false;
                }
            }
        }
        return $files;
    }

    private function generateBuckets(Input $input, Output $output)
    {
        $faker = Faker\Factory::create();

        $helper = new QuestionHelper();
        $question = new Question('How many buckets do you want to generate? (Default: 5)', 5);
        $bucketNos = $helper->ask($input, $output, $question);

        $buckets = [];
        for ($i = 0; $i < $bucketNos; $i++) {
            try {
                $buckets[] = $this->client->call(FakerClient::METHOD_POST, '/storage/buckets', [
                    'content-type' => 'application/json',
                    'x-appwrite-project' => $this->projectId,
                    'x-appwrite-key' => $this->apiKey,
                ], [
                    'bucketId' => $faker->uuid,
                    'name' => $faker->word,
                ], false);
            } catch (Exception $e) {
                $output->writeln('Error: ' . $e->getMessage());
            }
        }

        return $buckets;
    }

    public function run(Input $input, Output $output)
    {
        $buckets = $this->generateBuckets($input, $output);
        if (empty($buckets)) {
            $output->writeln('No buckets were generated');
        } else {
            $output->writeln('Buckets generated: ' . count($buckets));
        }

        $files = $this->generateFiles($input, $output, $buckets);
        if (empty($files)) {
            $output->writeln('No files were generated');
        } else {
            $output->writeln('Files generated: ' . count($files));
        }
    }
}
