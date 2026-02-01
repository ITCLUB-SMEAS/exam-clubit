<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HighRiskStudentsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The notification data
     */
    public array $data;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $examTitle = $this->data['exam']->title ?? 'Ujian';
        $count = $this->data['high_count'] ?? 0;

        return new Envelope(
            subject: "[Peringatan] {$count} Siswa Berisiko Tinggi - {$examTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.high-risk-students',
            with: [
                'exam' => $this->data['exam'],
                'predictions' => $this->data['predictions'],
                'criticalCount' => $this->data['critical_count'],
                'highCount' => $this->data['high_count'],
                'dashboardUrl' => route('admin.analytics.at-risk'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
