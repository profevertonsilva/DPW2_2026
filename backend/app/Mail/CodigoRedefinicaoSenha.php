<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** E-mail com o código de redefinição de senha (RF #06). Em desenvolvimento, vai para o log. */
class CodigoRedefinicaoSenha extends Mailable
{
    public function __construct(
        public readonly string $nome,
        public readonly string $codigo,
        public readonly int $minutos,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'AgendAki: código para redefinir sua senha');
    }

    public function content(): Content
    {
        return new Content(text: 'emails.codigo-senha');
    }
}
