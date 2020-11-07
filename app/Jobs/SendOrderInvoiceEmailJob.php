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
class SendOrderInvoiceEmailJob extends Job
{
     use InteractsWithQueue, Queueable, SerializesModels;
     protected $user_request, $RequestPayment, $storeOrder;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user_request, $RequestPayment, $storeOrder)
    {
       

        $this->user_request = $user_request;
        $this->RequestPayment = $RequestPayment;
        $this->storeOrder = $storeOrder;

        
        \Log::info($this->RequestPayment);
        \Log::info($this->storeOrder);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subject='Order Invoice';
        $templateFile='mails/order_invoice';
        $email = $this->storeOrder->user->email;
        $data = ['body' => $this->user_request, 'payment' => $this->RequestPayment];

        if(count($email) > 0) {
            Helper::send_order_invoice_emails_job($templateFile, $this->storeOrder, $subject, $data);
        }
    }
}
