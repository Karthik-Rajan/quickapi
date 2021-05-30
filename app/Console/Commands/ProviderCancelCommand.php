<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\V1\Order\Shop\Auth\AdminController;
use App\Services\V1\Common\ProviderServices;
use App\Models\Common\RequestFilter;
use App\Services\SendPushNotification;
use App\Models\Order\StoreOrder;
use App\Helpers\Helper;
use App\Models\Common\Provider;
use App\Models\Service\ServiceRequest;
use Carbon\Carbon;
use Setting;
use DB;


class ProviderCancelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:providercancel'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating the Provider status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info("testing provider cancel");
        $settings = Helper::setting();
        $siteConfig = $settings->site;  
            $IncomingRequests = [];
                $IncomingRequests = RequestFilter::with(['request.user', 'request.service', 'request.service', 'request'])
                ->whereHas('request', function($query) {
                    $query->where('status','<>', 'CANCELLED');
                    $query->where('status','<>', 'SCHEDULED');
                    $query->where('status','<>', 'PROVIDEREJECTED');
                })
                ->whereIn('admin_service', ['TRANSPORT', 'ORDER', 'SERVICE', 'DELIVERY'])
                ->where('assigned', '0')
                ->get();


            if(!empty($IncomingRequests)){
\Log::info($IncomingRequests);
                (new ProviderServices)->provider_cancel($IncomingRequests, $settings);
            }

     }   
}
