<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $zipFile;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($zipFileName)
    {
        $this->zipFile = $zipFileName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.export')
            ->subject('EZproxy export from Costanza')
            ->attach($this->zipFile);
    }
}
