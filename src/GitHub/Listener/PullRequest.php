<?php

declare(strict_types=1);

namespace App\GitHub\Listener;

use Assert\Assert;

use function array_key_exists;
use function array_shift;

class PullRequest
{
    /** @var array */
    private $payload;

    /** @var null|array */
    private $pullRequest;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function validate(): void
    {
        if (array_key_exists('incomplete_results', $this->payload)) {
            Assert::that($this->payload['incomplete_results'])->false();
        }
        Assert::that($this->payload['items'])->isArray()->notEmpty();
    }

    public function getNumber(): int
    {
        return $this->getPullRequest()['number'];
    }

    public function getTitle(): string
    {
        return $this->getPullRequest()['title'];
    }

    public function getUrl(): string
    {
        return $this->getPullRequest()['html_url'];
    }

    private function getPullRequest(): array
    {
        if ($this->pullRequest) {
            return $this->pullRequest;
        }

        $items             = $this->payload['items'];
        $this->pullRequest = array_shift($items);
        return $this->pullRequest;
    }
}
