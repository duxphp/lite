<?php

namespace Dux\Services;

use Dux\App;
use Dux\Coroutine\ContextManage;
use Exception;
use Swow\Coroutine;
use Swow\Errno;
use Swow\Http\Protocol\ProtocolException;
use Swow\Psr7\Server\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


ini_set('memory_limit', '1G');

class WebCommand extends Command
{

    protected static $defaultName = 'web';
    protected static $defaultDescription = 'start async web service';


    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $server = new Server();
        $server->bind('0.0.0.0', 8080)->listen();

        ContextManage::init();

        while (true) {
            try {
                $connection = $server->acceptConnection();
                Coroutine::run(static function () use ($connection): void {
                    try {
                        $request = null;
                        try {
                            $request = $connection->recvHttpRequest();
                            $response = App::app()->handle($request);
                            $connection->respond($response->getHeaders(), (string)$response->getBody(), $response->getStatusCode());
                        } catch (ProtocolException $exception) {
                            dump($exception->getCode() . ' : ' . $exception->getMessage());
                            $connection->error($exception->getCode(), $exception->getMessage(), close: true);
                        }
                    } catch (Exception $e) {
                        dump($e->getMessage());
                        // you can log error here
                    } finally {
                        ContextManage::destroy();
                        $connection->close();
                    }
                });
            } catch (Exception $exception) {
                if (in_array($exception->getCode(), [Errno::EMFILE, Errno::ENFILE, Errno::ENOMEM], true)) {
                    sleep(1);
                } else {
                    dump($exception->getCode() . ' : ' . $exception->getMessage());
                    break;
                }
            }
        }

        return Command::SUCCESS;
    }
}
