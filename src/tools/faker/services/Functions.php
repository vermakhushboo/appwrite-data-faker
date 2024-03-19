<?php

require 'vendor/autoload.php';

use Appwrite\Faker\Client as FakerClient;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Question\Question;

class Functions
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

    private function generateFunctions(Input $input, Output $output)
    {
        $helper = new QuestionHelper();
        $question = new Question('How many functions do you want to generate? (Default: 10)', 10);
        $functionsNo = $helper->ask($input, $output, $question);

        $functions = [];
        return $functions;
    }

    public function run(Input $input, Output $output)
    {
        $functions = $this->generateFunctions($input, $output);
    }
}


