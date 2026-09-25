<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

/** Email genere a partir d'un modele de l'admin, dans la mise en page sombre du site. */
class TemplatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{path: string, name: string, mime?: string}>  $files
     */
    public function __construct(
        public string $mailSubject,
        public string $bodyHtml,
        public string $bodyText,
        public ?string $replyToAddress = null,
        public ?string $replyToName = null,
        public array $files = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: $this->replyToAddress ? [new Address($this->replyToAddress, $this->replyToName)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: self::renderHtml($this->mailSubject, $this->bodyHtml),
            text: 'mail.layouts.text',
            with: ['plainText' => $this->bodyText],
        );
    }

    /** Mise en page sombre avec styles inlines (Gmail et Outlook ignorent souvent les balises <style>). */
    public static function renderHtml(string $title, string $content): string
    {
        $html = view('mail.layouts.dark', ['content' => $content, 'title' => $title])->render();
        preg_match('/<style>(.*?)<\/style>/s', $html, $style);

        return (new CssToInlineStyles())->convert($html, $style[1] ?? '');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return array_map(
            fn (array $file) => Attachment::fromPath($file['path'])->as($file['name'])->withMime($file['mime'] ?? 'application/pdf'),
            $this->files,
        );
    }
}
