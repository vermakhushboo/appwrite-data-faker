<?php

require 'vendor/autoload.php';

use Appwrite\Faker\Client as FakerClient;

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

    public function run()
    {
    }
}


