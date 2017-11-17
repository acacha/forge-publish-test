<?php

namespace Acacha\ForgePublish\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use josegonzalez\Dotenv\Loader;

/**
 * Class PublishLogin.
 *
 * @package Acacha\ForgePublish\Commands
 */
class PublishLogin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Login to acacha forge';

    /**
     * Guzzle Http client
     *
     * @var Client
     */
    protected $http;

    /**
     * PublishLogin constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        parent::__construct();
        $this->http = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkIfCommandHaveToBeSkipped();

        $email = $this->ask('What is your email(username)?');
        $password = $this->secret('What is the password?');

        $url = config('forge-publish.url') . config('forge-publish.token_uri');
        $response = '';
        try {
            $response = $this->http->post($url, [
                'form_params' => [
                    'client_id' => config('forge-publish.client_id'),
                    'client_secret' => config('forge-publish.client_secret'),
                    'grant_type' => 'password',
                    'username' => $email,
                    'password' => $password,
                    'scope' => '*',
                ]
            ]);
        } catch (\Exception $e) {
            $this->error('And error occurs connecting to the api url: ' . $url);
            $this->error('Status code: ' . $e->getResponse()->getStatusCode() . ' | Reason : ' . $e->getResponse()->getReasonPhrase() );
            die();
        }

        $access_token = json_decode( (string) $response->getBody())->access_token ;

        $this->addValueToEnv('ACACHA_FORGE_ACCESS_TOKEN', $access_token);

        $this->info('The access token has been added to file .env with key ACACHA_FORGE_ACCESS_TOKEN');
    }

    /**
     * Check if command have to be skipped.
     */
    protected function checkIfCommandHaveToBeSkipped()
    {
        $this->skipIfNoEnvFileIsFound();
        $this->skipIfTokenIsAlreadyInstalled();
    }

    /**
     * Skip if no .env file found.
     */
    protected function skipIfNoEnvFileIsFound()
    {
        if (! File::exists(base_path('.env')) ) {
            $this->info('No .env file found!');
            $this->info('Skipping...');
            die();
        }
    }

    /**
     * Skip if token is already installed.
     */
    protected function skipIfTokenIsAlreadyInstalled()
    {
        $environment = $this->loadEnv();
        if (array_key_exists( 'ACACHA_FORGE_ACCESS_TOKEN' , $environment)) {
            $this->info('An Access Token already exists in your environment (check for ACACHA_FORGE_ACCESS_TOKEN in .env file).');
            $this->info('Please remove the token an re-execute command if you want to relogin.');
            $this->info('Skipping...');
            die();
        }
    }

    /**
     * Add value to env.
     *
     * @param $key
     * @param $value
     */
    protected function addValueToEnv($key, $value)
    {
        File::append(base_path('.env'), "#ACACHA FORGE");
        File::append(base_path('.env'), "$key=$value");
    }

    /**
     * Load .env file to array.
     *
     * @return array|null
     */
    protected function loadEnv()
    {
        return (new Loader(base_path('.env')))
            ->parse()
            ->toArray();
    }

}
