<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\v3\OrderRequest;
use App\Http\Functions\MyHelper;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\OrderStatus;
use App\Models\ModelsTrait;
use Illuminate\Support\Facades\Log;
use Auth;
/**
 * @group  Order Management
 *
 * APIs for managing order
 */

class OrderController extends Controller
{
    use ModelsTrait;

    public function index(Request $request)
    {
        $req = $request->all();
        $orders = new Order;
        $orders = $orders->getDefault($req);
        return MyHelper::response(true,'Successfully',$orders,200);
    }


    public function store(Request $request)
    {
        $groupid = auth::user()->groupid;
        // $image_product = $request->image_product;
        // define contact id
        $id_contact = Contact::checkContact($request->customer_phone,$request->customer_email);
        if (!$id_contact) {
            $contact = new Contact;
            $contact->groupid       = $groupid;
            $contact->fullname      = $request->customer_name;
            $contact->phone         = $request->customer_phone;
            $contact->email         = $request->customer_email;
            $contact->address       = $request->customer_address.'/'.$request->customer_locate;
            $contact->save();
            $id_contact = $contact->id;
        }else{
            $id_contact = $id_contact->id;
        }
        $product_list = $request->products;
        
        $message = [];
        foreach ($product_list as $key => $prd) {
            if (!$prd['code_product'] || !$prd['name_product']) {
                $message[] = 'Product '.($prd['code_product'] ? $prd['code_product'] : $prd['name_product']).' không tồn tại code, không thể tạo sản phẩm này';
                break;
            }
            // check product code
            $id_product = Product::checkProductByCode($prd['code_product'],$groupid);
            if (!$id_product) {
                $product = new Product;
                $product->groupid      = $groupid;
                $product->channel      = 'web';
                $product->product_code = $prd['code_product'];
                $product->product_name = $prd['name_product'];
                $product->product_full_name = $prd['name_product'];
                $product->product_orig_price = $prd['cost_product'];
                $product->product_price = $prd['price_product'];
                $product->product_description = $prd['notes_product'];
                $product->created_by = 'api';
                $product->save();
                $id_product = $product->id;
            }else{
                $id_product = $id_product->id;
            }
        }
        return MyHelper::response(true,(empty($message) ? 'Created Order successfully' : $message ),[],200);
    }


    public function update(Request $request,$ordid)
    {
        $data = [];
        $groupid = auth::user()->groupid;
        $arr_status = OrderStatus::where('groupid',$groupid)->get()->pluck('order_status_name','id')->toArray();

        if (!$request->status) {
            return MyHelper::response(false,'Status is required',[],404);
        }

        $order = new Order;
        $check_order = $order->checkExist($ordid);   
        if (!$check_order) {
            Log::channel('orders_history')->info('Order not found',['status' => 404, 'id'=>$ordid,'request'=>$request->all()]);
            return MyHelper::response(false,'Resource Not Found',[],404);
        }else{
            if (!array_key_exists($request->status, $arr_status)) {
                return MyHelper::response(false,'Status incorrectly!',[],403);
            }
            $check_order->ord_status =  $request->status;
            $check_order->ord_status_value = $arr_status[$request->status];
            $check_order->save();
            return MyHelper::response(true,'Status order update successfully',[],200);
        }
    }
}
