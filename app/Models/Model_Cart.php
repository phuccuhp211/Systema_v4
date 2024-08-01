<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Model_Cart extends Model {
    use HasFactory;

    public static function get_sc($id) {
        return DB::table('products')->select('id','name','img','price','sale','f_date','t_date')->where('id',$id)->first();
    }

    public static function upcart($name) {
        if(session()->has('cart')) {
            if (!empty(session('cart')['list'])) self::where('account',$name)->update(['cart' => json_encode(session('cart'))]);
        } 
        else DB::table('users')->where('account',$name)->update(['cart' => NULL]);
    }
}
