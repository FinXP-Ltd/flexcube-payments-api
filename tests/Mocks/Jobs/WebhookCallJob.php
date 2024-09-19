<?php

namespace Finxp\Flexcube\Tests\Mocks\Jobs;

use Throwable;
use HttpClient;
use Finxp\Flexcube\Tests\Mocks\Models\Webhook;
use Illuminate\Bus\Queueable;
use Finxp\Flexcube\Tests\Mocks\Jobs\WebhookCallSuccess;
use Finxp\Flexcube\Tests\Mocks\Jobs\WebhookCallFailed;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WebhookCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $webhookId;

    /** @var string */
    public $queue;

    /** @var string */
    public $url = null;

    /** @var array */
    public $headers = [];

    /** @var array */
    public $payload = [];

    /** @var int */
    public $retry = 3;

    /** @var int */
    public $retryThreshold = 15000;

    /** @var string */
    private $status;

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle()
    {
        try {
            $request = HttpClient::retry($this->retry, $this->retryThreshold)
                ->withHeaders($this->headers)
                ->post(
                    $this->url,
                    $this->payload
                );

            if ($request->serverError() || $request->clientError() || !$request->successful()) {
                $request->throw();
            }

            $this->status = Webhook::STATUS_SUCCESS;

            $this->_dispatchEvent(WebhookCallSuccess::class);
        } catch (Throwable $err) {
            info($err);

            $this->status = Webhook::STATUS_FAILED;

            $this->_dispatchEvent(WebhookCallFailed::class);
        }
    }

    private function _dispatchEvent(string $eventClass)
    {
        event(new $eventClass(
            $this->webhookId,
            $this->url,
            $this->headers,
            $this->payload,
            $this->status
        ));
    }
}
