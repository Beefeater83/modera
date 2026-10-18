<?php

declare(strict_types=1);

namespace App\Messenger\Handler;

use App\Messenger\Message\SendLoginEmailMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class SendLoginEmailMessageHandler
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function __invoke(SendLoginEmailMessage $message): void
    {
        $email = (new Email())
            ->from('no-reply@diakonov-it.com.ua')
            ->to($message->email)
            ->subject('Login notification')
            ->html("
                <p style='font-size:14px; color:#333;'>
                Hello <strong>{$message->name}</strong>,
                you have successfully logged-in on modera.diakonov-it.com.ua</p>
            ");

        $this->mailer->send($email);
    }
}
