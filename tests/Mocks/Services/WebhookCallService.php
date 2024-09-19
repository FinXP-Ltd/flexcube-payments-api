<?php

namespace Finxp\Flexcube\Tests\Mocks\Services;

use Finxp\Flexcube\Tests\Mocks\Jobs\WebhookCallJob;
use Finxp\Flexcube\Tests\Mocks\DefaultSigner;
use Finxp\Flexcube\Tests\Mocks\Jobs\WebhookException;

class WebhookCallService
{
    /** @var \App\Jobs\WebhookCallJob */
    protected $webhookJob;

    /** @var int */
    public $webhookId;

    /** @var string */
    protected $secret;

    /** @var \App\Libraries\Signer\Signer */
    protected $signer;

    /** @var string */
    public $url;

    /** @var array */
    public $headers = [];

    /** @var array */
    public $payload = [];

    public static function create(): self
    {
        return (new static())
            ->setQueue(config('queue.name'))
            ->signUsing(DefaultSigner::class);
    }

    public function __construct()
    {
        $this->webhookJob = app(WebhookCallJob::class);
    }

    public function setWebhookId(int $webhookId): self
    {
        $this->webhookId = $webhookId;

        $this->webhookJob->webhookId = $this->webhookId;

        return $this;
    }

    public function setQueue(string $queue): self
    {
        $this->webhookJob->queue = $queue;

        return $this;
    }

    public function setBody(array $body): self
    {
        $this->payload = $body;

        $this->webhookJob->payload = $this->payload;

        return $this;
    }

    public function setHeaders(array $requestHeaders): self
    {
        $this->headers = $requestHeaders;

        return $this;
    }

    public function signUsing(string $signerClass): self
    {
        $this->signer = app($signerClass);

        return $this;
    }

    public function setUrl(string $webhookUrl): self
    {
        $this->url = $webhookUrl;

        $this->webhookJob->url = $this->url;

        return $this;
    }

    public function setSecret(string $secretKey): self
    {
        $this->secret = $secretKey;

        return $this;
    }

    public function dispatch()
    {
        if (! $this->webhookJob->url) {
            throw WebhookException::urlNotSet();
        }

        if (empty($this->secret)) {
            throw WebhookException::secretNotSet();
        }

        if (empty($this->webhookJob->webhookId)) {
            throw WebhookException::webhookIdNotSet();
        }

        $this->webhookJob->headers = $this->_getHeaders();

        dispatch($this->webhookJob);
    }

    private function _getHeaders(): array
    {
        $requestHeaders = $this->headers;

        $signature = $this->signer->calculateSignature($this->payload, $this->secret);

        $requestHeaders[$this->signer->signatureHeaderName()] = $signature;

        return $requestHeaders;
    }
}