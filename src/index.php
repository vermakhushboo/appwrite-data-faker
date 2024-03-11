<?php

require 'vendor/autoload.php';
require 'src/client.php';
require 'src/tools/faker/AppwriteFakerTool.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Appwrite\Faker\Client as FakerClient;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\ConfirmationQuestion;

$GLOBALS['APPWRITE_ENDPOINT'];
$GLOBALS['APPWRITE_API_KEY'];
$GLOBALS['APPWRITE_PROJECT_ID'];

class AppInitializer extends Application
{
    protected ?FakerClient $client = null;
    protected string $endpoint = 'http://localhost/v1';

    public function __construct()
    {
        parent::__construct();
        $this->setAutoExit(false);
        $this->client = new FakerClient();
        $this->registerCommands();
    }

    private function registerCommands()
    {
        $this->register('start')
            ->setDescription('initializes the application')
            ->setCode([$this, 'start']);
    }

    public function start(InputInterface $input, OutputInterface $output)
    {
        $banner = <<<EOF
                                    _ _         _______          _ _    _ _   
     /\                            (_) |       |__   __|        | | |  (_) |  
    /  \   _ __  _ ____      ___ __ _| |_ ___     | | ___   ___ | | | ___| |_ 
   / /\ \ | '_ \| '_ \ \ /\ / / '__| | __/ _ \    | |/ _ \ / _ \| | |/ / | __|
  / ____ \| |_) \| |_) \| V  V /| |  | | ||  __/    | | (_) | (_) | |   <| | |_ 
 /_/    \_\ .__/| .__/ \_/\_/ |_|  |_|\__\___|    |_|\___/ \___/|_|_|\_\_|\__|
          | |   | |                                                           
          |_|   |_|                                                           


EOF;
        $output->write($banner);

        $helper = new Symfony\Component\Console\Helper\QuestionHelper();
        $question = new ChoiceQuestion(
            'Which tool do you want to use?',
            ['Generate Fake Data', 'Initialise Appwrite Project']
        );
        $tool = $helper->ask($input, $output, $question);

        switch ($tool) {
            case 'Generate Fake Data':
                $output->writeln('Generating fake data...');
                break;
            case 'Initialise Appwrite Project':
                $this->initializeAppwrite($input, $output);
                break;
            default:
                $output->writeln('Invalid option selected.');
        }
    }

    private function initializeAppwrite(InputInterface $input, OutputInterface $output)
    {
        $helper = new Symfony\Component\Console\Helper\QuestionHelper();
        $question = new ChoiceQuestion(
            'How do you want to authenticate?',
            ['Sign in with email and password', 'Register a new user']
        );
        $authenticationMethod = $helper->ask($input, $output, $question);

        switch ($authenticationMethod) {
            case 'Sign in with email and password':
                $this->signInWithEmailAndPassword($input, $output);
                break;
            case 'Register a new user':
                $this->registerUser($input, $output);
                break;
            default:
                $output->writeln('Invalid option selected.');
        }
    }

    private function signInWithEmailAndPassword(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('You are signed in');
    }

    private function registerUser(InputInterface $input, OutputInterface $output)
    {
        $helper = new Symfony\Component\Console\Helper\QuestionHelper();
        $question = new Question(
            'What is your Appwrite endpoint? (Default: http://localhost/v1)',
            'http://localhost/v1'
        );
        $endpoint = $helper->ask($input, $output, $question);
        $output->writeln('Your endpoint is ' . $endpoint);

        $question = new Question(
            'What email do you want to use? (Default: admin@appwrite.io)',
            'admin@appwrite.io'
        );
        $email = $helper->ask($input, $output, $question);

        $output->writeln('Your email is ' . $email);

        $question = new Question(
            'What password do you want? (Default: password)',
            'password'
        );
        $password = $helper->ask($input, $output, $question);

        $output->writeln('Your password is ' . $password);

        $question = new Question(
            'What username do you want? (Default: admin)',
            'admin'
        );
        $username = $helper->ask($input, $output, $question);

        $output->writeln('Your user name is ' . $username);

        $question = new Question(
            'What teamId do you want? (Default: test)',
            'test'
        );
        $teamId = $helper->ask($input, $output, $question);

        $output->writeln('Your teamId is ' . $teamId);

        $question = new Question(
            'What team name do you want? (Default: Test Team)',
            'Test Team'
        );
        $teamName = $helper->ask($input, $output, $question);

        $output->writeln('Your team name is ' . $teamName);

        $question = new Question(
            'What projectId do you want? (Default: test)',
            'test'
        );
        $projectId = $helper->ask($input, $output, $question);

        $output->writeln('Your projectId is ' . $projectId);

        $question = new Question(
            'What project name do you want? (Default: Test Project)',
            'Test Project'
        );
        $projectName = $helper->ask($input, $output, $question);

        $output->writeln('Your project name is ' . $projectName);

        $this->client->setEndpoint($this->endpoint);

        // create account
        $root = $this->client->call(FakerClient::METHOD_POST, '/account', [
            'content-type' => 'application/json',
            'x-appwrite-project' => 'console',
        ], [
            'userId' => 'admin',
            'email' => $email,
            'password' => $password,
            'name' => $username,
        ]);

        if ($root['headers']['status-code'] === 201) {
            $output->writeln('Account created successfully');
        } else {
            $output->writeln('Failed to create console account');
        }

        // create session
        $session = $this->client->call(FakerClient::METHOD_POST, '/account/sessions/email', [
            'content-type' => 'application/json',
            'x-appwrite-project' => 'console',
        ], [
            'email' => $email,
            'password' => $password,
        ]);

        $session = $session['cookies']['a_session_console'];

        if ($session) {
            $output->writeln('Session created successfully');
        } else {
            $output->writeln('Failed to create console session');
        }

        // create team
        $team = $this->client->call(FakerClient::METHOD_POST, '/teams', [
            'content-type' => 'application/json',
            'cookie' => 'a_session_console=' . $session,
            'x-appwrite-project' => 'console',
        ], [
            'teamId' => $teamId,
            'name' => $teamName,
        ]);

        if ($team['headers']['status-code'] === 201) {
            $output->writeln('Team created successfully');
        } else {
            $output->writeln('Failed to create team');
        }

        // create project
        $project = $this->client->call(FakerClient::METHOD_POST, '/projects', [
            'content-type' => 'application/json',
            'cookie' => 'a_session_console=' . $session,
            'x-appwrite-project' => 'console',
        ], [
            'projectId' => $projectId,
            'name' => $projectName,
            'teamId' => $teamId,
            'region' => "default",
        ]);

        if ($project['headers']['status-code'] === 201) {
            $output->writeln('Project created successfully');
        } else {
            $output->writeln('Failed to create project');
        }

        // create API key
        $key = $this->client->call(FakerClient::METHOD_POST, '/projects/' . $projectId . '/keys', [
            'origin' => 'http://localhost',
            'content-type' => 'application/json',
            'cookie' => 'a_session_console=' . $session,
            'x-appwrite-project' => 'console',
        ], [
            'name' => $projectName . ' Project Key',
            'scopes' => [
                'users.read',
                'users.write',
                'teams.read',
                'teams.write',
                'databases.read',
                'databases.write',
                'collections.read',
                'collections.write',
                'documents.read',
                'documents.write',
                'files.read',
                'files.write',
                'buckets.read',
                'buckets.write',
                'functions.read',
                'functions.write',
                'execution.read',
                'execution.write',
                'locale.read',
                'avatars.read',
                'health.read',
                'rules.read',
                'rules.write'
            ],
        ]);

        if ($key['headers']['status-code'] === 201) {
            $output->writeln('API key created successfully');
        } else {
            $output->writeln('Failed to create API key');
        }

        $output->writeln('Successfully instantiated Appwrite project!');
        $table = new Table($output);
        $table->setHeaders(['(index)', 'Values']);
        $table->addRow(['Email', $email]);
        $table->addRow(['Password', $password]);
        $table->addRow(['Project Id', $projectId]);
        $table->addRow(['Endpoint', $endpoint]);
        $table->addRow(['API Key', $key['body']['secret']]);

        $table->render();

        $question = new ConfirmationQuestion('Do you want to save the configuration to .env? (yes/no)', false);
        $saveConfiguration = $helper->ask($input, $output, $question);
        if ($saveConfiguration) {
            $filePath = './.env';
            $content = "export APPWRITE_ENDPOINT=$endpoint\nexport APPWRITE_API_KEY={$key['body']['secret']}\nexport APPWRITE_PROJECT_ID=$projectId\n";
            file_put_contents($filePath, $content);
            $GLOBALS['APPWRITE_ENDPOINT'] = $endpoint;
            $GLOBALS['APPWRITE_API_KEY'] = $key['body']['secret'];
            $GLOBALS['APPWRITE_PROJECT_ID'] = $projectId;
            $output->writeln('Configuration saved to .env');

            $question = new ConfirmationQuestion('Do you want to generate fake data? (yes/no)', false);
            $runFaker = $helper->ask($input, $output, $question);
            if ($runFaker) {
                $output->writeln('Initialising Appwrite Faker Tool...');
                $fakerTool = new AppwriteFakerTool();
                $fakerTool->run($input, $output);
            }
        }
    }
}

$appInitializer = new AppInitializer();
$appInitializer->run();
