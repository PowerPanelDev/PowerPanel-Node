<?php

namespace app\command;

use app\client\Panel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use function Co\run;

class Setup extends Command
{
    protected static $defaultName = 'setup';
    protected static $defaultDescription = 'Get config from panel and setup';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('endpoint', InputArgument::REQUIRED, 'Panel Endpoint');
        $this->addArgument('token', InputArgument::REQUIRED, 'Panel Token');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        run(function () use ($input, $output) {
            $client = new Panel($input->getArgument('endpoint'), $input->getArgument('token'));

            $config = $client->get('/api/node/config')['attributes'];
            $config['panel_endpoint'] = $input->getArgument('endpoint');
            $config['panel_token'] = $input->getArgument('token');

            ksort($config);

            file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        });

        $output->writeln('Done.');

        return self::SUCCESS;
    }
}
