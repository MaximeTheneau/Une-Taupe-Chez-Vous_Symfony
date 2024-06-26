<?php

namespace App\MessageHandler;

use App\Message\TriggerNextJsBuild;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

#[AsMessageHandler]
final class TriggerNextJsBuildHandler
{    
    private $notifier;

    public function __construct(
        NotifierInterface $notifier,
        )
    {
        $this->notifier = $notifier;
    }

    public function __invoke(TriggerNextJsBuild $message)
    {

        try {
            $url = 'https://' . $_ENV['NGINX_DOMAIN'] . '/api/webhook';
            $data = [
                'name' => 'NextJsBuild',
                'project' => $_ENV['NGINX_DOMAIN'],
                'force' => true,
            ];
    
            $calculatedSignature =  hash_hmac('sha256', json_encode($data), $_ENV['APP_AUTHTOKEN']);
            $headers = [
                'Content-Type: application/json',
                'x-hub-signature-256: ' .'sha256=' . $calculatedSignature,
                'x-taupe-event: ' . 'build',
            ];
    
            $client = HttpClient::create();
            $response = $client->request('POST', $url, [
                'headers' => $headers,
                'json' => $data,
                'timeout' => 120,
            ]);
    
            $statusCode = $response->getStatusCode();
            $content = $response->getContent();
            $message->setContent($content);

            return new Response($content, $statusCode);
        } catch (\Exception $e) {
            return new Response('Une erreur est survenue lors de la requête.', $e->getCode());
        }
    }
}
