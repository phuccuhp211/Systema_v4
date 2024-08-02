<?php

namespace App\Http\Controllers\Client;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller\Client\Controller_Account;
use App\Mail\Mail_Invoices as Inv_M;
use App\Models\Model_Client as Pay;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Mail;

class Controller_Pay extends Controller {
    protected $vnp_TmnCode;
    protected $vnp_HashSecret;

    function __construct() {
        $this->vnp_TmnCode = "8NGMGKIN";
        $this->vnp_HashSecret = "USXUFCDMVGRSFYCCCQVFBJOMDAVVOZON";
    }

    function vnpay_payment(Request $rq) {
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = route('vnpay.result');
        
        $vnp_TxnRef = mt_rand(100000,999999);
        $vnp_OrderInfo = 'Thanh Toán Mua Hàng';
        $vnp_OrderType = 'Systema';
        $vnp_Amount = $rq->input('amount') * 100;
        $vnp_Locale = 'VN';
        $vnp_BankCode = $rq->input('bankCode');
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $this->vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
        );
        
        if (isset($vnp_BankCode) && $vnp_BankCode != "") $inputData['vnp_BankCode'] = $vnp_BankCode;
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash =   hash_hmac('sha512', $hashdata, $this->vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
      
        $returnData = array('code' => '00' , 'message' => 'success' , 'data' => $vnp_Url);

        header('Location: ' . $vnp_Url);
        die();
    }

    function vnpay_result(Request $rq) {
        $Account = new Controller_Account; 
        $data = $Account->get_base();
        $data['vnp_SecureHash'] = $_GET['vnp_SecureHash'];
        $data['inputData'] = [];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $data['inputData'][$key] = $value;
            }
        }
        unset($data['inputData']['vnp_SecureHash']);
        ksort($data['inputData']);
        $i = 0;
        $hashData = "";
        foreach ($data['inputData'] as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        $data['secureHash'] = hash_hmac('sha512', $hashData, $this->vnp_HashSecret);

        if (session()->has('user-temp')) {
            if ($data['secureHash'] == $data['vnp_SecureHash']) {
                $pay_ctrl = new pay_controller;
                $pay_ctrl->order($rq);
            }
        }
        return view('payment.vnpay_result', $data);
    }

    function validation(Request $rq) {
        $dtip = [
            'name' => $rq->input('name'),
            'email' => $rq->input('email'),
            'number' => $rq->input('number'),
            'address' => $rq->input('address'),
            'notice' => $rq->input('notice')
        ];

        $msg = [
            'name.required' => 'Tên là bắt buộc.',
            'name.min' => 'Tên yêu cầu ít nhất :min ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.regex' => 'Email không đúng định dạng.',
            'number.required' => 'Số điện thoại là bắt buộc.',
            'number.min' => 'Số điện thoại yêu cầu ít nhất :min ký tự.',
            'number.max' => 'Số điện thoại không thể dài quá :max ký tự.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'address.min' => 'Địa chỉ yêu cầu ít nhất :min ký tự.'
        ];

        $rule = [ 
            'name' => 'required|min:3',
            'email' => 'required|email|regex:/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/',
            'number' => 'required|min:8|max:10',
            'address' => 'required|min:6'
        ];

        $check = Validator::make($dtip, $rule, $msg);
        if ($check->fails()) return response()->json(['status' => false, 'res' => $check->errors()]);
        else return response()->json(['status' => true, 'res' => '']);
    }

    function applycoupon(Request $rq) {
        $data = $rq->input('coupon');
        $response['status'] = false;
        $now = new DateTime;

        $coupon = Pay::coupons_get($data);

        if (!$coupon) $response['res'] = 'Mã không tồn tại!';
        else {
            if ($coupon->f_date != NULL && $coupon->t_date != NULL) {
                $f_date = new DateTime($coupon->f_date);
                $t_date = new DateTime($coupon->t_date);

                if ($now < $f_date) $response['res'] = 'Mã không khả dụng vào lúc này!';
                else if ($now > $t_date) $response['res'] = 'Mã đã hết hạn sử dụng!';
                else if ($coupon->remaining == 0) $response['res'] = 'Mã đã hết lượt sử dụng!';
                else if (!session()->has('user_log')) $response['res'] = 'Vui lòng đăng nhập hoặc đăng ký để sử dụng khuyến mãi!';
                else {
                    $response['status'] = true;
                    $response['type'] = $coupon->type;
                    $response['disc'] = $coupon->discount;
                }
            } 
            else {
                $response['status'] = true;
                $response['type'] = $coupon->type;
                $response['disc'] = $coupon->discount;
            }
        }
        return response()->json($response);
    }

    function order(Request $rq) {
        $name = ($rq->ajax()) ? $rq->input('name') : session('user-temp')['name'];
        $mail = ($rq->ajax()) ? $rq->input('mail') : session('user-temp')['mail'];
        $addr = ($rq->ajax()) ? $rq->input('addr') : session('user-temp')['addr'];
        $number = ($rq->ajax()) ? $rq->input('number') : session('user-temp')['number'];
        $notice = ($rq->ajax()) ? $rq->input('notice') : session('user-temp')['notice'];
        $mxn = ($rq->ajax()) ? $rq->input('mxn') : session('user-temp')['mxn'];
        $date = ($rq->ajax()) ? $rq->input('date') : session('user-temp')['date'];
        $pmmt = ($rq->ajax()) ? $rq->input('pmmt') : session('user-temp')['pmmt'];
        $sfee = ($rq->ajax()) ? $rq->input('ship') : session('user-temp')['ship'];

        $ntotal = ($rq->ajax()) ? (($rq->input('newtt')) ?? 0) : ((session('user-temp')['newtt']) ?? 0);
        $coupon = ($rq->ajax()) ? (($rq->input('magg')) ?? '') : ((session('user-temp')['magg']) ?? '');
        $p_stt = ($pmmt != 'COD') ? 1 : 0;

        $list = json_encode(session('cart')['list']);
        $total = ($rq->input('magg')) ? session('cart')['total'] : session('cart')['total']+$sfee;

        if ($coupon != '') Pay::coupons_devine($coupon); 
        Pay::invoices_add($name,$mail,$addr,$number,$notice,$mxn,$date,$list,$total,$pmmt,$sfee,$ntotal,$coupon,$p_stt);
        Mail::mailer('smtp')->to($mail)->send( new Inv_M($name,$mail,$addr,$number,$notice,$mxn,$date,$pmmt,$sfee,$total,$ntotal,$coupon) );

        session()->forget('cart');
        if(session()->has('user_log')) Pay::users_update_cart(session('user_log'));
        if(session()->has('user-temp')) session()->forget('user-temp');
        if ($rq->ajax()) return route('dord');
    }

    function store(Request $rq) {
        $data = $rq->all();
        unset($data['randomParam']);
        session(['user-temp'=> $data]);
        return true;
    }
}
