<?php

namespace App\Notifications;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One generic notification class for every payment-request workflow
 * event that needs to alert a user - edited / submitted / assigned /
 * reviewed / returned / approved / rejected / ready for payment / paid /
 * cancelled / commented / document uploaded. Delivered both in-app
 * (database - backs the notification bell) and by email (toMail()
 * below); actual email delivery still depends on this project's own
 * mail configuration (MAIL_MAILER etc. in .env) - with the default
 * "log" driver, a message is written to storage/logs/laravel.log
 * instead of actually being sent, until real SMTP credentials are set.
 */
class PaymentRequestUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly PaymentRequest $paymentRequest,
        public readonly string $event,
        public readonly string $message,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Request Update — '.$this->paymentRequest->displayReference())
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message)
            ->action('View Request', route('payment-requests.show', $this->paymentRequest))
            ->line('Retirement Portal — Praxis for Health and Development');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'payment_request_id' => $this->paymentRequest->id,
            'reference_number' => $this->paymentRequest->displayReference(),
            'message' => $this->message,
            'url' => route('payment-requests.show', $this->paymentRequest),
        ];
    }
}
