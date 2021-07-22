<?php

namespace App\Services\V1\Order;

use App\Models\Common\AdminWallet;
use App\Models\Common\Provider;
use App\Models\Common\ProviderWallet;
use App\Models\Common\User;
use App\Models\Order\Store;
use App\Models\Order\StoreOrder;
use App\Models\Order\StoreOrderDispute;
use App\Models\Order\StoreOrderInvoice;
use App\Models\Order\StoreOrderStatus;
use App\Models\Order\StoreWallet;
use App\Services\SendPushNotification;
use App\Services\Transactions;
use App\Services\V1\Common\UserServices;
use App\Traits\Actions;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Validator;

class Order
{

    use Actions;

    /**
     * Get a validator for a tradepost.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'location' => 'required',
        ];

        $messages = [
            'location.required' => 'Location Required!',
        ];

        return Validator::make($data, $rules, $messages);
    }

    public function cancelOrder($request)
    {

        try {

            $orderRequest = StoreOrder::findOrFail($request->id);

            if ('CANCELLED' == $orderRequest->status) {
                return ['status' => 404, 'message' => trans('api.order.ride_cancelled')];
            }

            if (in_array($orderRequest->status, ['ORDERED', 'STORECANCELLED'])) {
                $orderRequest->status = 'CANCELLED';

                if ('ot' == $request->cancel_reason) {
                    $orderRequest->cancel_reason = $request->cancel_reason_opt;
                } else {
                    $orderRequest->cancel_reason = $request->cancel_reason;
                }

                $orderRequest->cancelled_by = $request->cancelled_by;
                $orderRequest->save();

                $RequestDispute = StoreOrderDispute::where('store_order_id', $orderRequest->id)->update(['status' => 'closed']);

                $this->userAmountRefund($orderRequest);

                (new UserServices())->cancelRequest($orderRequest);

                (new SendPushNotification)->ShopCancelRequest($orderRequest->store_id, $orderRequest->admin_service);

                return ['status' => 200, 'message' => trans('api.order.ride_cancelled')];
            } else {

                return ['status' => 403, 'message' => trans('api.ride.already_onride')];
            }
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        }
    }

    public function shopAccept(Request $request)
    {

        try {
            $storeorder = StoreOrder::findorfail($request->store_order_id);
            $user       = User::findorfail($storeorder->user_id);
            $timezone   = $storeorder->timezone;
            if ($request->has('cooking_time')) {
                $storeorder->order_ready_time = $request->cooking_time;
            }

            if ($request->has('delivery_date')) {
                $delivery_date             = (Carbon::createFromFormat('Y-m-d H:i:s', (Carbon::parse($request->delivery_date)->format('Y-m-d H:i:s')), $timezone))->setTimezone('Asia/Kolkata');
                $storeorder->delivery_date = $delivery_date;
            }
            $storeorder->status = 'RECEIVED';
            $storeorder->save();
            (new SendPushNotification)->orderShopAccepted($storeorder, 'order', 'Order Accepted');

            $storeorderstatus                 = new StoreOrderStatus;
            $storeorderstatus->store_order_id = $request->store_order_id;
            $storeorderstatus->status         = 'RECEIVED';
            $storeorderstatus->company_id     = Auth::guard('shop')->user()->company_id;
            $storeorderstatus->save();
            $requestData = ['type' => 'ORDER', 'room' => 'room_' . Auth::guard('shop')->user()->company_id, 'id' => $request->store_order_id, 'shop' => Auth::guard('shop')->user()->id, 'user' => $request->user_id];

            app('redis')->publish('checkOrderRequest', json_encode($requestData));
            return ['status' => 200, 'message' => 'Accepted  Succesfully'];
        } catch (\Throwable $e) {
            return ['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()];
        }
    }

    public function shopCancel(Request $request)
    {

        $request->request->add(['cancelled_by' => 'SHOP']);

        $orderRequest                = StoreOrder::findOrFail($request->id);
        $orderRequest->status        = 'CANCELLED';
        $orderRequest->cancel_reason = $request->cancel_reason;
        $orderRequest->cancelled_by  = $request->cancelled_by;
        $orderRequest->save();

        (new UserServices())->cancelRequest($orderRequest);

        (new SendPushNotification)->StoreCanlled($orderRequest, 'order');

        // $storedispute =  new StoreOrderDispute;
        // $storedispute->dispute_type='system';
        // $storedispute->user_id=$request->user_id;
        // $storedispute->store_id=$request->store_id;
        // $storedispute->store_order_id=$request->id;
        // $storedispute->dispute_name="Store Cancelled";
        // $storedispute->dispute_type_comments="Store Cancelled";
        // $storedispute->status="open";
        // $storedispute->company_id=Auth::guard('shop')->user()->company_id;
        // $storedispute->save();
        $storeorderstatus                 = new StoreOrderStatus;
        $storeorderstatus->store_order_id = $request->id;
        $storeorderstatus->status         = 'CANCELLED';
        $storeorderstatus->company_id     = Auth::guard('shop')->user()->company_id;
        $storeorderstatus->save();

        $this->userAmountRefund($orderRequest);
        //Send message to socket
        $requestData = ['type' => 'ORDER', 'room' => 'room_' . Auth::guard('shop')->user()->company_id, 'id' => $request->id, 'shop' => $request->store_id, 'user' => $request->user_id];
        app('redis')->publish('checkOrderRequest', json_encode($requestData));
        $data = ['status' => 200, 'message' => 'Cancelled Succesfully'];

        return $data;
    }

    public function userAmountRefund($orderRequest)
    {
        if (1 == $orderRequest->paid) {
            if ($orderRequest->invoice->payable > 0 && 'CARD' == $orderRequest->invoice->payment_mode) {
                $transaction['message']    = 'Order amount refund';
                $transaction['amount']     = $orderRequest->invoice->payable;
                $transaction['company_id'] = $orderRequest->company_id;
                $transaction['id']         = $orderRequest->user_id;
                (new Transactions)->disputeCreditDebit($transaction);
            }
        }
        if ($orderRequest->invoice->wallet_amount > 0) {
            $transaction['message']    = 'Order amount refund';
            $transaction['amount']     = $orderRequest->invoice->wallet_amount;
            $transaction['company_id'] = $orderRequest->company_id;
            $transaction['id']         = $orderRequest->user_id;
            (new Transactions)->disputeCreditDebit($transaction);
        }
        return true;
    }

    public function createDispute($request)
    {
        $storedispute                        = new StoreOrderDispute;
        $storedispute->dispute_type          = 'system';
        $storedispute->user_id               = $request->user_id;
        $storedispute->provider_id           = $request->provider_id;
        $storedispute->store_id              = $request->store_id;
        $storedispute->store_order_id        = $request->id;
        $storedispute->dispute_name          = "Provider Changed";
        $storedispute->dispute_title         = $request->reason;
        $storedispute->dispute_type_comments = "Provider Changed";
        $storedispute->status                = "open";
        $storedispute->company_id            = $request->company_id;
        $storedispute->save();

        $orderRequest->status = 'PROVIDEREJECTED';
        $orderRequest->save();
    }

    public function callTransaction($store_order_id)
    {

        $StoreOrder = StoreOrder::findOrFail($store_order_id);

        if (1 == $StoreOrder->paid) {
            $transation                      = [];
            $transation['admin_service']     = 'ORDER';
            $transation['company_id']        = $StoreOrder->company_id;
            $transation['transaction_id']    = $StoreOrder->id;
            $transation['country_id']        = $StoreOrder->country_id;
            $transation['transaction_alias'] = $StoreOrder->store_order_invoice_id;

            $paymentsStore = StoreOrderInvoice::where('store_order_id', $store_order_id)->first();

            $admin_commision = $credit_amount = 0;

            $credit_amount = $paymentsStore->total_amount - $paymentsStore->commision_amount - $paymentsStore->delivery_amount;

            //admin,shop,provider calculations
            if (!empty($paymentsStore->commision_amount)) {
                $admin_commision      = $paymentsStore->commision_amount;
                $transation['id']     = $StoreOrder->store_id;
                $transation['amount'] = $admin_commision;
                //add the commission amount to admin
                $this->adminCommission($transation);
            }

            if (!empty($paymentsStore->delivery_amount)) {
                //credit the deliviery amount to provider wallet
                if ('DELIVERY' == $StoreOrder->order_type) {
                    $transation['id']     = $StoreOrder->provider_id;
                    $transation['amount'] = $paymentsStore->delivery_amount;
                    $this->providerCredit($transation);
                }
            }

            if ($credit_amount > 0) {
                //credit the amount to shop wallet
                $transation['id']     = $StoreOrder->store_id;
                $transation['amount'] = $credit_amount;
                $this->shopCreditDebit($transation);
            }

            return true;
        } else {

            return true;
        }
    }

    protected function adminCommission($request)
    {
        $request['transaction_desc'] = 'Shop Commission added';
        $request['transaction_type'] = 1;
        $request['type']             = 'C';
        $this->createAdminWallet($request);
    }

    protected function shopCreditDebit($request)
    {

        $amount                      = $request['amount'];
        $ad_det_amt                  = -1 * abs($request['amount']);
        $request['transaction_desc'] = 'Order amount sent';
        $request['transaction_type'] = 10;
        $request['type']             = 'D';
        $request['amount']           = $ad_det_amt;
        $this->createAdminWallet($request);

        $request['transaction_desc'] = 'Order amount recevied';
        $request['id']               = $request['id'];
        $request['type']             = 'C';
        $request['amount']           = $amount;
        $this->createShopWallet($request);

        $request['transaction_desc'] = 'Order amount recharge';
        $request['transaction_type'] = 11;
        $request['type']             = 'C';
        $request['amount']           = $amount;
        $this->createAdminWallet($request);

        return true;
    }

    protected function providerCredit($request)
    {

        $request['transaction_desc'] = 'Order deliviery amount sent';
        $request['id']               = $request['id'];
        $request['type']             = 'C';
        $request['amount']           = $request['amount'];
        $this->createProviderWallet($request);

        $ad_det_amt                  = -1 * abs($request['amount']);
        $request['transaction_desc'] = 'Order deliviery amount recharge';
        $request['transaction_type'] = 9;
        $request['type']             = 'D';
        $request['amount']           = $ad_det_amt;
        $this->createAdminWallet($request);

        return true;
    }

    protected function createAdminWallet($request)
    {

        $admin_data = AdminWallet::orderBy('id', 'DESC')->first();

        $adminwallet             = new AdminWallet;
        $adminwallet->company_id = $request['company_id'];
        if (!empty($request['admin_service'])) {
            $adminwallet->admin_service = $request['admin_service'];
        }

        if (!empty($request['country_id'])) {
            $adminwallet->country_id = $request['country_id'];
        }

        $adminwallet->transaction_id    = $request['transaction_id'];
        $adminwallet->transaction_alias = $request['transaction_alias'];
        $adminwallet->transaction_desc  = $request['transaction_desc'];
        $adminwallet->transaction_type  = $request['transaction_type'];
        $adminwallet->type              = $request['type'];
        $adminwallet->amount            = $request['amount'];

        if (empty($admin_data->close_balance)) {
            $adminwallet->open_balance = 0;
        } else {
            $adminwallet->open_balance = $admin_data->close_balance;
        }

        if (empty($admin_data->close_balance)) {
            $adminwallet->close_balance = $request['amount'];
        } else {
            $adminwallet->close_balance = $admin_data->close_balance + ($request['amount']);
        }

        $adminwallet->save();

        return $adminwallet;
    }

    protected function createProviderWallet($request)
    {

        $provider = Provider::findOrFail($request['id']);

        $providerWallet              = new ProviderWallet;
        $providerWallet->provider_id = $request['id'];
        $providerWallet->company_id  = $request['company_id'];
        if (!empty($request['admin_service'])) {
            $providerWallet->admin_service = $request['admin_service'];
        }

        $providerWallet->transaction_id    = $request['transaction_id'];
        $providerWallet->transaction_alias = $request['transaction_alias'];
        $providerWallet->transaction_desc  = $request['transaction_desc'];
        $providerWallet->type              = $request['type'];
        $providerWallet->amount            = $request['amount'];

        if (empty($provider->wallet_balance)) {
            $providerWallet->open_balance = 0;
        } else {
            $providerWallet->open_balance = $provider->wallet_balance;
        }

        if (empty($provider->wallet_balance)) {
            $providerWallet->close_balance = $request['amount'];
        } else {
            $providerWallet->close_balance = $provider->wallet_balance + ($request['amount']);
        }

        $providerWallet->save();

        //update the provider wallet amount to provider table
        $provider->wallet_balance = $provider->wallet_balance + ($request['amount']);
        $provider->save();

        return $providerWallet;
    }

    protected function createShopWallet($request)
    {

        $store = Store::findOrFail($request['id']);

        $storeWallet             = new StoreWallet;
        $storeWallet->store_id   = $request['id'];
        $storeWallet->company_id = $request['company_id'];
        if (!empty($request['admin_service'])) {
            $storeWallet->admin_service = $request['admin_service'];
        }

        $storeWallet->transaction_id    = $request['transaction_id'];
        $storeWallet->transaction_alias = $request['transaction_alias'];
        $storeWallet->transaction_desc  = $request['transaction_desc'];
        $storeWallet->type              = $request['type'];
        $storeWallet->amount            = $request['amount'];

        if (empty($store->wallet_balance)) {
            $storeWallet->open_balance = 0;
        } else {
            $storeWallet->open_balance = $store->wallet_balance;
        }

        if (empty($store->wallet_balance)) {
            $storeWallet->close_balance = $request['amount'];
        } else {
            $storeWallet->close_balance = $store->wallet_balance + ($request['amount']);
        }

        $storeWallet->save();

        //update the provider wallet amount to provider table
        $store->wallet_balance = $store->wallet_balance + ($request['amount']);
        $store->save();

        return $storeWallet;
    }
}
