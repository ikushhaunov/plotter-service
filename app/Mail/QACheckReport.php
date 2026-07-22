<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QACheckReport extends Mailable
{
    use Queueable, SerializesModels;

    public $fileName;
    public $filePath;

    public function __construct($filePath, $fileName)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function build()
    {
        return $this->subject('📊 Ежедневный отчет: Устройства на проверке ОТК (' . date('d.m.Y') . ')')
                    ->view('emails.qa-check-report')
                    ->attach($this->filePath, [
                        'as' => $this->fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
    }
}