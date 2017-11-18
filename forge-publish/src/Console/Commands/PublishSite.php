<?php

namespace Acacha\ForgePublish\Commands;

/**
 * Class PublishSite.
 *
 * @package Acacha\ForgePublish\Commands
 */
class PublishSite extends SaveEnvVariable
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publish:site {site?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save acacha forge site';

    /**
     * Env var to set.
     *
     * @return mixed
     */
    protected function envVar()
    {
        return 'ACACHA_FORGE_SITE';
    }

    /**
     * Argument key.
     *
     * @return mixed
     */
    protected function argKey()
    {
        return 'site';
    }

    /**
     * Question text.
     *
     * @return mixed
     */
    protected function questionText()
    {
        return 'Acacha forge site id?';
    }
}
