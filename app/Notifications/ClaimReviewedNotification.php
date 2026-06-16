<?php

namespace App\Notifications;

use App\Models\ItemClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClaimReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ItemClaim $claim)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->claim->status === 'approved';

        return (new MailMessage)
            ->subject('Your Campus Found claim was '.($approved ? 'approved' : 'reviewed'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your claim for '.$this->claim->item->title.' was '.$this->claim->status.'.')
            ->when($approved, fn (MailMessage $mail) => $mail->line('Open your dashboard to continue coordinating with the reporter.'))
            ->action('View my claims', route('account.show').'#my-claims');
    }
}
