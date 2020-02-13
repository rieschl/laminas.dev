<?php

declare(strict_types=1);

namespace App;

use Laminas\Http\Client\Adapter\Curl;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Twitter\Twitter as TwitterClient;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\Swoole\TaskWorker\DeferredListenerDelegator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'getlaminas' => [
                'token' => '',
            ],
            'monolog' => [
                'handlers' => [
                    [
                        'type'   => StreamHandler::class,
                        'stream' => 'data/log/app-{date}.log',
                        'level'  => Logger::DEBUG,
                    ],
                ],
            ],
            'twitter' => [
                'access_token' => [
                    'token'  => '',
                    'secret' => '',
                ],
                'oauth_options' => [
                    'consumerKey'    => '',
                    'consumerSecret' => '',
                ],
                'http_client_options' => [
                    'adapter' => Curl::class,
                    'curloptions' => [
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases' => [
                EventDispatcherInterface::class  => EventDispatcher::class,
                ListenerProviderInterface::class => AttachableListenerProvider::class,
            ],
            'delegator_factories' => [
                AttachableListenerProvider::class => [
                    GitHub\ListenerProviderDelegatorFactory::class,
                    Slack\ListenerProviderDelegatorFactory::class,
                ],
                GitHub\Listener\GitHubIssueListener::class                => [DeferredListenerDelegator::class],
                GitHub\Listener\GitHubPullRequestListener::class          => [DeferredListenerDelegator::class],
                GitHub\Listener\GitHubPushListener::class                 => [DeferredListenerDelegator::class],
                GitHub\Listener\GitHubReleaseTweetListener::class         => [DeferredListenerDelegator::class],
                GitHub\Listener\GitHubReleaseWebsiteUpdateListener::class => [DeferredListenerDelegator::class],
                GitHub\Listener\GitHubStatusListener::class               => [DeferredListenerDelegator::class],
                Slack\Message\DeployMessageHandler::class                 => [DeferredListenerDelegator::class],
            ],
            'factories' => [
                ErrorHandler::class                                       => Factory\ErrorHandlerFactory::class,
                GitHub\Listener\GitHubIssueListener::class                => GitHub\Listener\GitHubIssueListenerFactory::class,
                GitHub\Listener\GitHubPullRequestListener::class          => GitHub\Listener\GitHubPullRequestListenerFactory::class,
                GitHub\Listener\GitHubPushListener::class                 => GitHub\Listener\GitHubPushListenerFactory::class,
                GitHub\Listener\GitHubReleaseTweetListener::class         => GitHub\Listener\GitHubReleaseTweetListenerFactory::class,
                GitHub\Listener\GitHubReleaseWebsiteUpdateListener::class => GitHub\Listener\GitHubReleaseWebsiteUpdateListenerFactory::class,
                GitHub\Listener\GitHubStatusListener::class               => GitHub\Listener\GitHubStatusListenerFactory::class,
                GitHub\Middleware\GithubRequestHandler::class             => GitHub\Middleware\GithubRequestHandlerFactory::class,
                GitHub\Middleware\VerificationMiddleware::class           => GitHub\Middleware\VerificationMiddlewareFactory::class,
                Handler\HomePageHandler::class                            => Handler\HomePageHandlerFactory::class,
                LoggerInterface::class                                    => Factory\LoggerFactory::class,
                ProblemDetailsMiddleware::class                           => Factory\ProblemDetailsMiddlewareFactory::class,
                Slack\Message\DeployMessageHandler::class                 => Slack\Message\DeployMessageHandlerFactory::class,
                Slack\Middleware\VerificationMiddleware::class            => Slack\Middleware\VerificationMiddlewareFactory::class,
                Slack\Middleware\DeployHandler::class                     => Slack\Middleware\DeployHandlerFactory::class,
                Slack\SlackClientInterface::class                         => Slack\SlackClientFactory::class,
                TwitterClient::class                                      => Factory\TwitterClientFactory::class,
            ],
        ];
    }
}
