<?php

namespace Acacha\ForgePublish\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Class PublishDNS.
 *
 * @package Acacha\ForgePublish\Commands
 */
class PublishDNS extends Command
{
    const ETC_HOSTS = '/etc/hosts';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:dns {type?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check DNS configuration';

    /**
     * The domain name.
     *
     * @var string
     */
    protected $domain;

    /**
     * The ip address.
     *
     * @var string
     */
    protected $ip;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->info('Checking DNS configuration');
        $this->abortCommandExecution();
        $resolved_ip = gethostbyname ($this->domain);
        $this->info("domain: $this->domain | IP : $resolved_ip");
        if ( $resolved_ip != $this->domain && $resolved_ip == $this->ip ) {
            $this->info("DNS resolution is ok. ");
            die();
        }

        $this->info("DNS resolution is not configured ok. Let me help you configure it...");

        $type = $this->argument('type') ?
            $this->argument('type') :
            $this->choice('Which system do you want to use?',['hosts'],0);

        if ($type != 'hosts') {
            $this->error('Type not supported');
            die();
        }

        passthru('sudo true');

        $this->addEntryToEtcHostsFile($this->domain,$this->ip);
        $this->info('File ' . self::ETC_HOSTS . ' configured ok');
    }

    /**
     * Add entry to etc/hosts file.
     *
     * @param $domain
     * @param $ip
     */
    protected function addEntryToEtcHostsFile($domain, $ip)
    {
        $content = "\n# Forge server\n$ip $domain\n";
        File::append(self::ETC_HOSTS,$content);
    }

    /**
     * Abort command execution.
     */
    protected function abortCommandExecution()
    {
        $this->domain = env('ACACHA_FORGE_DOMAIN',null);
        $this->ip = env('ACACHA_FORGE_IP_ADDRESS',null);

        if (env('ACACHA_FORGE_DOMAIN',null) == null ) {
            $this->error('No env var ACACHA_FORGE_DOMAIN found. Please run php artisan publish:init');
            die();
        }
        if (env('ACACHA_FORGE_IP_ADDRESS',null) == null ) {
            $this->error('No env var ACACHA_FORGE_IP_ADDRESS found. Please run php artisan publish:init');
            die();
        }

        if (posix_geteuid() != 0) {
            $this->error('This command needs root permissions. Please use sudo: ');
            $this->info('sudo php artisan publish:dns');
            die();
        }
    }

}
