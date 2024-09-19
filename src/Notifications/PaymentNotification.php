<?php

namespace Finxp\Flexcube\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use NotificationChannels\WebPush\WebPushChannel;
use Finxp\Flexcube\Notifications\Channel\CustomWebPushMessage;

class PaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;

    public function __construct( $data )
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via( $notifiable )
    {
        return [WebPushChannel::class];
    }

    public function toWebPush( $notifiable, $notification )
    {
        return ( new CustomWebPushMessage() )
            ->title( 'Payment Notification' )
            ->icon( config( 'flexcube-soap.fc_app') . '/assets/icons/logo.png' )
            ->action( 'View App', 'notification_click' )
            ->body( 'A transfer has been made by to your account.' )
            ->data([
                'onActionClick' => [
                    'default' => [
                        'operation' => 'openWindow'
                    ],
                    'notification_click' => [
                        'operation' => 'openWindow',
                        'url'       => config('flexcube-soap.fc_app')
                    ]
                ]
            ]);
    }
}