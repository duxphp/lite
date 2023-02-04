<?php

namespace Dux\Server;

use Dux\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use Swow\Coroutine;
use Swow\CoroutineException;
use Swow\Errno;
use Swow\Http\Protocol\ProtocolException as HttpProtocolException;
use Swow\Socket;
use Swow\SocketException;

class SwowCommand extends Command {

    protected static $defaultName = 'server:swow';
    protected static $defaultDescription = 'Use swow to start the web service';


    protected function configure(): void {
        $this->addOption(
            'port',
            null,
            InputOption::VALUE_REQUIRED,
            'set the service port number'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int {
        $port = $input->getOption("port") ?: 8080;

        $server = new \Swow\Psr7\Server\Server();
        $server->bind('0.0.0.0', $port)->listen(Socket::DEFAULT_BACKLOG);
        $output->writeln("<info>server start http://0.0.0.0:".$port."</info>");
        while (true) {
            try {
                $connection = null;
                $connection = $server->acceptConnection();
                Coroutine::run(static function () use ($connection, $output): void {
                    try {
                        while (true) {
                            $request = null;
                            try {
                                $request = $connection->recvHttpRequest();
                                $response = App::app()->handle($request);
                                $connection->respond($response->getHeaders(), (string)$response->getBody(), $response->getStatusCode());
                            } catch (HttpProtocolException $exception) {
                                $connection->error($exception->getCode(), $exception->getMessage(), close: true);
                                break;
                            }
                            if (!$connection->shouldKeepAlive()) {
                                break;
                            }
                        }
                    } catch (Exception $exception) {
                        $output->writeln("<error>" . $exception->getMessage() . "</error>");
                    } finally {
                        $connection->close();
                    }
                });
            } catch (SocketException|CoroutineException $exception) {
                if (in_array($exception->getCode(), [Errno::EMFILE, Errno::ENFILE, Errno::ENOMEM], true)) {
                    sleep(1);
                } else {
                    break;
                }
            }
        }

        return Command::SUCCESS;
    }
}