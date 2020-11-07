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

class SendInvoiceEmailJob extends Job
{
   use InteractsWithQueue, Queueable, SerializesModels;
     protected $userRequest, $payment, $email;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userRequest, $payment, $email)
    {

        $this->userRequest = $userRequest;
        $this->payment = $payment;
        $this->email = $email;

        \Log::info($this->userRequest);
        \Log::info($this->payment);
        \Log::info($this->email);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subject='Ride Invoice';
        $templateFile='mails/invoice';
        $data = ['body' => $this->userRequest, 'payment' => $this->payment];

        if(count($this->email) > 0) {
            Helper::send_invoice_emails_job($templateFile, $this->email, $subject, $data);
        }


    }
}
