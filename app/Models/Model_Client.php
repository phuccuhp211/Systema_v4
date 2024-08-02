<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Model_Client extends Model {
    use HasFactory;

    public static function accesses_up($home=null) {
        $check_data = DB::table('accesses')->first();
        if ($home) {
            if (!$check_data) $check_data = DB::table('accesses')->insert(['homet' => 1, 'homef' => 0, 'sum' => 0]);
            else DB::table('accesses')->increment('homet');
        }
        else {
            if (!$check_data) $check_data = DB::table('accesses')->insert(['homet' => 0, 'homef' => 1, 'sum' => 0]);
            else DB::table('accesses')->increment('homef');
        }
    }

    public static function catalog_get($type='',$id=null) {
        if ($id == null) return DB::table($type)->get();
        else return DB::table($type)->where('id',$id)->get();
    }

    public static function sections_get() {
        return DB::table('sections')->where('index', '>' ,0)->orderBy('id','ASC')->get();
    }

    public static function banners_get() {
        return DB::table('banners')->orderBy('id','ASC')->get();
    }

    public static function brands_get() {
        return DB::table('brands')->select('name')->orderBy('id','ASC')->get();
    }

    public static function brands_find($data) {
        return DB::table('brands')->select('name')->where('id',$data)->value('name');
    }

    public static function comments_get($data) {
        return DB::table('comments')->join('users','comments.id_us','=','users.id')
            ->select('comments.*','users.name','users.img')
            ->where('id_pd',$data)
            ->orderBy('comments.id','DESC')
            ->get();
    }

    public static function comments_add($cmt, $idp, $uid, $date) {
        DB::table('Comments')->insert([
            'content' => $cmt,
            'id_pd' => $idp,
            'id_us' => $uid,
            'date' => $date
        ]);
    }

    public static function coupons_get($name) {
        return DB::table('vouchers')->where('name',$name)->first();
    }

    public static function coupons_devine($name) {
        DB::table('vouchers')->where('name', $name)->decrement('remaining', 1);
    }

    public static function products_get_cart($id) {
        return DB::table('products')->select('id','name','img','price','sale','f_date','t_date')->where('id',$id)->first();
    }

    public static function products_static_get() {
        return DB::table('products')->where('hidden',0)->limit(10)->get();
    }

    public static function products_st_fil_get($cat1=null, $cat2=null,$ref,$ord) {
        $query = DB::table('products');

        if ($cat1 && !$cat2) $query->where([['hidden',0],['id_cata_1',$cat1]]);
        else if (!$cat1 && $cat2) $query->where([['hidden',0],['id_cata_2',$cat2]]);

        if ($ord == 1) $query->orderBy($ref, 'ASC');
        else if ($ord == 2) $query->orderBy($ref, 'DESC');
        else if ($ord == 3) $query->inRandomOrder();

        return $query->limit(20)->get();
    }

    public static function products_full($type=null,$data=null,$page,$ord,$filter,$limit) {
        $query = DB::table('products');
        $where = '';

        if ($type == 'all') $query->where('hidden',0);
        else if ($type == 'cat1') $query->where([['id_cata_1',$data],['hidden',0]]);
        else if ($type == 'cat2') $query->where([['id_cata_2',$data],['hidden',0]]);
        else if ($type == 'search') $query->where([['name','like',"%$data%"],['hidden','=',0]]);

        if ($filter) {
            if ($filter['brand'] != '') $query->where('id_brand', $filter['brand']);
            if ($filter['to'] != '') $query->where('price', '<=', $filter['to']);
            if ($filter['from'] != '') $query->where('price', '>=', $filter['from']);
        }
            
        if ($ord) {
            if ($ord == 1) $query->orderBy('id', 'ASC');
            else if ($ord == 2) $query->orderBy('id', 'DESC');
            else if ($ord == 3) $query->orderBy('price','ASC');
            else if ($ord == 4) $query->orderBy('price','DESC');
        }
            
        return $query->offset(($page*$limit)-$limit)->limit($limit)->get();
    }

    public static function products_pagin($type=null,$data=null,$filter) {
        $query = DB::table('products');
        
        if ($type == 'all') $query->where('hidden',0);
        else if ($type == 'cat1') $query->where([['id_cata_1',$data],['hidden',0]]);
        else if ($type == 'cat2') $query->where([['id_cata_2',$data],['hidden',0]]);
        else if ($type == 'search') $query->where([['name','like',"%$data%"],['hidden',0]]);
        else $query->get();

        if ($filter) {
            if ($filter['brand'] != '') $query->where('id_brand', $filter['brand']);
            if ($filter['to'] != '') $query->where('price', '<=', $filter['to']);
            if ($filter['from'] != '') $query->where('price', '>=', $filter['from']);
        }
        return ceil($query->count() / 16);
    }

    public static function products_detail($data) {
        $prod = DB::table('products')->where('id',$data)->first();
        if ($prod->id_cata_1 == 3) {
            $asd = $prod->detail;
            $qwe = json_decode(stripslashes($asd));
            $prod->html = (is_array($qwe)) ? htmlspecialchars_decode($qwe[1]) : '';
        }
        else {
            $prod->html = htmlspecialchars_decode($prod->detail);
        }
        return $prod;
    }

    public static function products_others_get($data) {
        $dt = DB::table('products')->where('id',$data)->first();
        return DB::table('Products')->where([
                    ['id','!=',$data],
                    ['hidden',0],
                    ['id_cata_2',$dt->id_cata_2]
                ])
            ->inRandomOrder()
            ->limit(5)
            ->get();
    }

    public static function ratings_get ($id_pd) {
        return DB::table('ratings')->where('id_pd',$id_pd)->first();
    }

    public static function turn_rating_get ($id_us,$id_pd) {
        return DB::table('turn_ratings')->where([['id_us',$id_us],['id_pd',$id_pd]])->first();
    }

    public static function turn_rating_rate($pd,$us,$stars) {
        $check = DB::table('turn_ratings')->where([['id_us', $us], ['id_pd', $pd]])->first();
        $type = true;
        $old = 0;

        if (!$check) DB::table('turn_ratings')->insert([ 'id_pd' => $pd, 'id_us' => $us, 'stars' => $stars ]);
        else {
            $type = false;
            $old = $check->stars;
            $check->update(['stars' => $stars]);
        }
        Rating::rate($pd, $stars, $type, $old);
    }

    public static function invoices_get($number) {
        return DB::table('invoices')->where('in_num',$number)->first();
    }

    public static function invoices_add($name,$mail,$addr,$number,$notice,$mxn,$date,$list,$total,$pmmt,$sfee,$ntotal=null,$coupon=null,$p_stt) {
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

    public static function invoices_list_get($number) {
        return DB::table('invoices')->where('number',$number)->orderBy('id','DESC')->get();
    }

    public static function users_update_cart($name) {
        if(session()->has('cart')) {
            if (!empty(session('cart')['list'])) DB::table('users')->where('account',$name)->update(['cart' => json_encode(session('cart'))]);
        } 
        else DB::table('users')->where('account',$name)->update(['cart' => NULL]);
    }
}