<?php

use Appwrite\AppwriteException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Appwrite\Faker\Client as FakerClient;
use Symfony\Component\Console\Question\ConfirmationQuestion;

require 'vendor/autoload.php';

class Auth
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

    private function generateTeams(Input $input, Output $output, $users)
    {
        $faker = Faker\Factory::create();

        $helper = new QuestionHelper();
        $question = new Question('How many teams do you want to generate? (Default: 50)', 50);
        $teamsNo = $helper->ask($input, $output, $question);

        $question = new ConfirmationQuestion('Would you like to randomly assign users to teams? (yes/no)', false);
        $randomlyAssignUsers = $helper->ask($input, $output, $question);

        $teams = [];
        for ($i = 0; $i < $teamsNo; $i++) {
            try {
                $teams[] = $this->client->call(FakerClient::METHOD_POST, '/teams', [
                    'content-type' => 'application/json',
                    'x-appwrite-project' => $this->projectId,
                    'x-appwrite-key' => $this->apiKey,
                ], [
                    'teamId' => $faker->uuid,
                    'name' => $faker->company,
                ], false);
            } catch (AppwriteException $e) {
                $output->writeln('Error: ' . $e->getMessage());
            }
        }

        if ($randomlyAssignUsers) {
            foreach ($teams as $team) {
                $teamBody = json_decode($team['body'], true);
                $teamId = $teamBody['$id'];
                $teamUsers = $faker->randomElements($users, $faker->numberBetween(1, count($users)));
                foreach ($teamUsers as $user) {
                    $userBody = json_decode($user['body'], true);
                    try {
                        $this->client->call(FakerClient::METHOD_POST, '/teams/' . $teamId . '/memberships', [
                            'content-type' => 'application/json',
                            'x-appwrite-project' => $this->projectId,
                            'x-appwrite-key' => $this->apiKey,
                        ], [
                            'roles' => ['owner'],
                            'userId' => $userBody['$id'],
                        ], false);
                    } catch (AppwriteException $e) {
                        $output->writeln('Error: ' . $e->getMessage());
                    }
                }
            }
        }
        return $teams;
    }

    private function generateUsers(Input $input, Output $output)
    {
        $faker = Faker\Factory::create();

        $helper = new QuestionHelper();
        $question = new Question('How many users do you want to generate? (Default: 100)', 100);
        $usersNo = $helper->ask($input, $output, $question);

        $users = [];
        for ($i = 0; $i < $usersNo; $i++) {
            try {
                $users[] = $this->client->call(FakerClient::METHOD_POST, '/users', [
                    'content-type' => 'application/json',
                    'x-appwrite-project' => $this->projectId,
                    'x-appwrite-key' => $this->apiKey,
                ], [
                    'userId' => $faker->uuid,
                    'email' => $faker->email,
                    'password' => $faker->password(8, 12),
                    'name' => $faker->firstName . ' ' . $faker->lastName,
                    'phone' => $faker->e164PhoneNumber(),
                ], false);
            } catch (AppwriteException $e) {
                $output->writeln('Error: ' . $e->getMessage());
            }
        }
        return $users;
    }

    public function run(Input $input, Output $output)
    {
        $users = $this->generateUsers($input, $output);
        if (empty($users)) {
            $output->writeln('No users were generated');
        } else {
            $output->writeln('Users generated: ' . count($users));
        }

        $helper = new QuestionHelper();
        $question = new ConfirmationQuestion('Do you want to create fake teams? (yes/no)', false);
        $generateTeams = $helper->ask($input, $output, $question);

        if ($generateTeams) {
            $teams = $this->generateTeams($input, $output, $users);
            if (empty($teams)) {
                $output->writeln('No teams were generated');
            } else {
                $output->writeln('Teams generated: ' . count($teams));
            }
        }
    }
}
