<?php

namespace App\Http\Controllers\V1\Order\Admin\Resource;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Order\StoreOrder;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct() {}

    public function index(Request $request)
    {
        $storeOrder = StoreOrder::with(['user', 'provider']);
        if ($request->input('status')) {
            $storeOrder = $storeOrder->whereIn('status', explode(',', $request->input('status')));
        }
        $data = $storeOrder->get();
        return Helper::getResponse(['data' => $data]);
    }

    public function updateProvider(Request $request, $id)
    {
        $storeOrder = StoreOrder::where('id', $id)->update(['provider_id' => $request->input('provider_id'), 'status' => 'PROCESSING']);
        return Helper::getResponse(['data' => []]);
    }
}
