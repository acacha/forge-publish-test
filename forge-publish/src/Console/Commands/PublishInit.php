<?php

namespace Acacha\ForgePublish\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use josegonzalez\Dotenv\Loader;

/**
 * Class PublishInit.
 *
 * @package Acacha\ForgePublish\Commands
 */
class PublishInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Config publish command';

    /**
     * Guzzle Http client
     *
     * @var Client
     */
    protected $http;

    /**
     * Create a new command instance.
     *
     */
    public function __construct(Client $htpp)
    {
        parent::__construct();
        $this->http = $htpp;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->info('Hello! We are going to config Acacha Laravel Forge publish together...');
        $this->info('');
        $this->info('Let me check you have been followed all the previous requirements...');

        $this->info('');
        $this->info('Visit http:://forge.acacha.com');
        $this->info('');
        $this->error('Please use Github Social Login for login!!!');

        while (! $this->confirm('User created at http:://forge.acacha.com?')) {}

        if ( env('ACACHA_FORGE_EMAIL', null) == null) {
            $emails = $this->getPossibleEmails();
            $email = $this->anticipate('Ok! User email?', $emails);
        } else {
            $email = env('ACACHA_FORGE_EMAIL');
            $this->info("Ok! I see you already have a Forge user email configured so let's go on!...");
        }

        $already_logged = false;

        if ( env('ACACHA_FORGE_ACCESS_TOKEN', null) == null) {
            $this->info('I need permissions to operate in Acacha Forge in your name...');
            $this->info('So we need to obtain a valid token. Two options here:');
            $this->info('1) Login: You provide your user credentials and I obtain the token from Laravel Forge');
            $this->info('2) Personal Access Token: You provide a Personal Access token');

            $option = $this->choice('Which on you prefer?', ['Login', 'Personal Access Token'], 0);

            if ($option == 'Login') {
                $this->call('publish:login', [
                    'email' => $email
                ]);
            }
            else {
                $this->call('publish:token');
            }
        } else {
            $this->info("Ok! I see you already have a token for accessing Acacha Laravel Forge so let's go on!...");
            $already_logged = true;
        }

        $servers = $already_logged ? $this->fetchServers() : $this->fetchServers($this->getTokenFromEnvFile());

        if ( env('ACACHA_FORGE_SERVER', null) == null) {

            while (!$this->confirm('Server permissions requested at http:://forge.acacha.com?')) {
            }

            $server_names = collect($servers)->pluck('name')->toArray();

            $server_name = $this->choice('Ok! Server name?', $server_names, 0);

            $forge_id_server = $this->getForgeIdServer($servers, $server_name);
        } else {
            $forge_id_server = env('ACACHA_FORGE_SERVER');
            $server_name = $this->getForgeName($servers, $forge_id_server);
            $this->info("Ok! I see you already have a Forge server configured so let's go on!...");
        }

        if ( env('ACACHA_FORGE_DOMAIN', null) == null) {
            $domain = $this->ask('Domain in production?');
        } else {
            $domain = env('ACACHA_FORGE_DOMAIN');
            $this->info("Ok! I see you already have a domain configured so let's go on!...");
        }

        $this->info('');
        $this->info('Ok! let me resume: ');

        $headers = ['Task/Config name', 'Done/result?'];

        $tasks = [
          [ 'User created at http:://forge.acacha.com?', 'Yes'],
          [ 'Email', $email],
          [ 'Acacha Forge Token obtained ', 'Yes'],
          [ 'Server permissions requested at http:://forge.acacha.com?', 'Yes'],
          [ 'Server name', $server_name],
          [ 'Server Forge id', $forge_id_server],
          [ 'domain', $domain],
        ];

        $this->table($headers, $tasks);

        $this->info('');
        if ( env('ACACHA_FORGE_EMAIL', null) == null) {
            $this->call('publish:email', [
                'email' => $email
            ]);
        }
        if ( env('ACACHA_FORGE_SERVER', null) == null) {
            $this->call('publish:server', [
                'server' => $forge_id_server
            ]);
        }
        if ( env('ACACHA_FORGE_DOMAIN', null) == null) {

            $this->call('publish:domain', [
                'domain' => $domain
            ]);
        }

        $this->info('');
        $this->info('Perfect! All info is saved to your environment. Enjoy Acacha forge publish!');
        $this->info('');
        $this->error('Remember to rerun your server to apply changes in .env file!!!');
    }

    /**
     * Get possible emails
     *
     * @return array
     */
    protected function getPossibleEmails()
    {
        $github_email = null;
        $github_email = str_replace(array("\r", "\n"), '', shell_exec('git config user.email'));

        if(filter_var($github_email, FILTER_VALIDATE_EMAIL)) return [ $github_email ];
        else return [];
    }

    /**
     * Fetch servers
     */
    protected function fetchServers ($token = null)
    {
        if (!$token) $token = env('ACACHA_FORGE_ACCESS_TOKEN');
        $url = config('forge-publish.url') . config('forge-publish.user_servers_uri');
        try {
            $response = $this->http->get($url,[
                'headers' => [
                    'X-Requested-With' => 'XMLHttpRequest',
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);
        } catch (\Exception $e) {
            return [];
        }
        return json_decode((string) $response->getBody());
    }

    /**
     * Get forge id server from server name.
     *
     * @param $servers
     * @param $server_name
     * @return mixed
     */
    protected function getForgeIdServer($servers, $server_name)
    {
        return collect($servers)->filter(function ($server) use ($server_name) {
            return $server->name === $server_name;
        })->first()->forge_id;
    }

    /**
     * Get forge name from forge id.
     *
     * @param $servers
     * @param $server_name
     * @return mixed
     */
    protected function getForgeName($servers, $server_id)
    {
        return collect($servers)->filter(function ($server) use ($server_id) {
            return $server->forge_id === $server_id;
        })->first()->name;
    }

    /**
     * Get token from env file
     *
     * @return mixed
     */
    protected function getTokenFromEnvFile()
    {
        //NOTE: We cannot use env() helper because the .env file has been changes in this request !!!
        return (new Loader(base_path('.env')))
            ->parse()
            ->toArray()['ACACHA_FORGE_ACCESS_TOKEN'];
    }

}
