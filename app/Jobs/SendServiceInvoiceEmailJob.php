<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Foundation\Bus\Dispatchable;
//use Illuminate\Foundation\Bus\DispatchesJobs;

use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;

class SendServiceInvoiceEmailJob extends Job
{
    use InteractsWithQueue, Queueable, SerializesModels;
     protected $serveRequest, $RequestPayment, $email;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($serveRequest, $RequestPayment, $email)
    {
        $this->serveRequest = $serveRequest;
        $this->RequestPayment = $RequestPayment;
        $this->email = $email;

        
        \Log::info($this->RequestPayment);
        \Log::info($this->email);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subject='Service Invoice';
        $templateFile='mails/service_invoice';
        $email = $this->email;
        $data = ['body' => $this->serveRequest, 'payment' => $this->RequestPayment];

        if(count($email) > 0) {
            Helper::send_service_invoice_emails_job($templateFile, $this->email, $subject, $data);
        }
    }
}
