<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Model_Pay extends Model {
    use HasFactory;

    public static function get_cp($name) {
        return DB::table('vouchers')->where('name',$name)->first();
    }

    public static function devine($name) {
        DB::table('vouchers')->where('name', $name)->decrement('remaining', 1);
    }

    public static function save_inv($name,$mail,$addr,$number,$notice,$mxn,$date,$list,$total,$pmmt,$sfee,$ntotal=null,$coupon=null,$p_stt) {
        $create = [
            'name' => $name,
            'number' => $number,
            'email' => $mail,
            'address' => $addr,
            'list' => $list,
            'price' => $total,
            'p_status' => $p_stt,
            'created' => $date,
            'in_num' => $mxn,
            'shipfee' => $sfee,
            'method' => $pmmt
        ];

        if ($ntotal != null) $create['offers'] = $ntotal;
        if ($coupon != null) $create['coupon'] = $coupon;

        DB::table('invoices')->create($create);
    }

    public static function upcart($name) {
        if(session()->has('cart')) {
            if (!empty(session('cart')['list'])) self::where('account',$name)->update(['cart' => json_encode(session('cart'))]);
        } 
        else DB::table('users')->where('account',$name)->update(['cart' => NULL]);
    }
}
