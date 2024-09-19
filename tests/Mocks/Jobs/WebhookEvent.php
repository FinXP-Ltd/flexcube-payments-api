<?php

namespace Finxp\Flexcube\Tests\Mocks\Jobs;

abstract class WebhookEvent
{
    /** @var int */
    public $webhookId;

    /** @var string */
    public $url = null;

    /** @var array */
    public $headers = [];

    /** @var array */
    public $payload = [];

    /** @var string */
    public $status;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        int $id,
        string $endpoint,
        array $reqHeaders,
        array $reqPayload,
        string $reqStatus
    ) {
        $this->webhookId = $id;
        $this->url = $endpoint;
        $this->headers = $reqHeaders;
        $this->payload = $reqPayload;
        $this->status = $reqStatus;
    }
}
