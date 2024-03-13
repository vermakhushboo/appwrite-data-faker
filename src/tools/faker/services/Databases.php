<?php

require 'vendor/autoload.php';

use Appwrite\AppwriteException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Appwrite\Faker\Client as FakerClient;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class Databases
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

    private function generateDocuments(Input $input, Output $output, $collections)
    {

    }

    private function generateCollections(Input $input, Output $output, $databases)
    {
        $faker = Faker\Factory::create();

        $helper = new QuestionHelper();
        $question = new Question('How many collections do you want to create per database? (Default: 5)', 5);
        $collectionNos = $helper->ask($input, $output, $question);

        $collections = [];
        // create collectionNos collections for each database
        for ($i=0; $i<count($databases); $i++) {
            $database = $databases[$i];
            $databaseBody = json_decode($database['body'], true);
            $databaseId = $databaseBody['$id'];
            for ($j = 0; $j < $collectionNos; $j++) {
                try {
                    $collections[] = $this->client->call(FakerClient::METHOD_POST, '/databases/' . $databaseId . '/collections', [
                        'content-type' => 'application/json',
                        'x-appwrite-project' => $this->projectId,
                        'x-appwrite-key' => $this->apiKey,
                    ], [
                        'collectionId' => $faker->uuid,
                        'name' => $faker->word,
                    ], false);
                } catch (Exception $e) {
                    $output->writeln('Error: ' . $e->getMessage());
                }
            }
        }
        return $collections;
    }

    private function generateDatabases(Input $input, Output $output)
    {
        $faker = Faker\Factory::create();

        $helper = new QuestionHelper();
        $question = new Question('How many databases do you want to generate? (Default: 10)', 10);
        $databaseNos = $helper->ask($input, $output, $question);

        $databases = [];
        for ($i = 0; $i < $databaseNos; $i++) {
            try {
                $databases[] = $this->client->call(FakerClient::METHOD_POST, '/databases', [
                    'content-type' => 'application/json',
                    'x-appwrite-project' => $this->projectId,
                    'x-appwrite-key' => $this->apiKey,
                ], [
                    'databaseId' => $faker->uuid,
                    'name' => $faker->company,
                ], false);
            } catch (AppwriteException $e) {
                $output->writeln('Error: ' . $e->getMessage());
            }
        }
        return $databases;
    }

    public function run(Input $input, Output $output)
    {
        $databases = $this->generateDatabases($input, $output);
        if (empty($databases)) {
            $output->writeln('No databases were generated');
        } else {
            $output->writeln('Databases generated: ' . count($databases));
        }

        $collections = $this->generateCollections($input, $output, $databases);
        if (empty($collections)) {
            $output->writeln('No collections were generated');
        } else {
            $output->writeln('Collections generated: ' . count($collections));
        }

        // $documents = $this->generateDocuments($input, $output, $collections);
        // if (empty($documents)) {
        //     $output->writeln('No documents were generated');
        // } else {
        //     $output->writeln('Documents generated: ' . count($documents));
        // }
    }
}


