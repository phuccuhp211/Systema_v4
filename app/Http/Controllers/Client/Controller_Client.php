<?php

namespace App\Http\Controllers\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use DateTime;
use DateInterval;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Model_Client as Client;

class Controller_Client extends Controller {
    private $header;
    private $data;

    function __construct() {
        $this->header = $this->header();
        $this->data['header'] = $this->header;
    }

    public function get_base() {
        return $this->data;
    }

    function header() {
        $cat_1 = Client::catalog_get('Catalog_1');
        $cat_2 = Client::catalog_get('Catalog_2');
        if(session()->has('user_log')) {
            $user = user::get_us(session('user_log'));
            return [
                'cat1' => $cat_1,
                'cat2' => $cat_2,
                'user' => $user
            ];
        }
        return [
            'cat1' => $cat_1,
            'cat2' => $cat_2
        ];
    }

    function index() {
        Client::accesses_up(true);
        $ss = Client::sections_get();
        $arr_ss = [];

        foreach ($ss as $value => $item) {
            $sps = Client::products_st_fil_get($item->id_cata_1, $item->id_cata_2, $item->reference, $item->orderby);
            $arr_ss[] = ['title' => $item, 'products' => $sps ];
        }

        $this->data['banners'] = Client::banners_get();
        $this->data['full_ss'] = $arr_ss;
        $this->data['product'] = Client::products_static_get();

        return response()->json($this->data, 200);
    }

    function products(Request $rq, $type=null, $value=null, $page=1) {
        Client::accesses_up(false);

        $value = ($rq->input('data')) ?? $value;
        $page = ($rq->input('page')) ?? $page;
        $limit = ($rq->input('limit')) ?? 16;
        $filters1 = ($rq->input('filters1')) ??  null;
        $filters2 = ($rq->input('filters2')) ??  null;

        if ($type == 'all') $this->data['title'] = 'Tất Cả Sản Phẩm';
        else if ($type == 'search') $this->data['title'] = 'Tìm kiếm: '.$rq->input('search_data');
        else if ($type == 'cat1') $this->data['title'] = 'Phân loại: '.Client::catalog_get('Catalog_1',$value);
        else if ($type == 'cat2') $this->data['title'] = 'Danh Mục: '.Client::catalog_get('Catalog_2',$value);

        if ($rq->input('search_data')) {
            $this->data['prods'] = Client::products_full($type, $rq->input('search_data'), $page, $filters1, $filters2, $rq->input('limit'));
        }
        else if ($rq->input('data') || $rq->input('page') || $rq->input('limit') || $rq->input('filters1') || $rq->input('filters2')) {
            $this->data['col'] = ($rq->input('showsp')) ? $rq->input('showsp') : null;
            $this->data['prods'] =  Client::products_full($type, $value, $page, $filters1, $filters2, $limit);
            $this->data['pagin'] = [
                "type" => $type,
                "value" => $value,
                "pg_count" => Client::products_pagin($type, $value, $filters2),
                "pg_current" => $page,
                "filters1" => $filters1,
                "filters2" => $filters2
            ];
        } 
        else {
            $this->data['brands'] = Client::brands_get();
            $this->data['prods'] = Client::products_full($type, $value, $page, $filters1, $filters2, $limit);
            $this->data['pagin'] = [
                "type" => $type,
                "value" => $value,
                "pg_count" => Client::products_pagin($type, $value, $filters2),
                "pg_current" => $page,
                "filters1" => $filters1,
                "filters2" => $filters2
            ];
            $this->data['filter'] = [
                "type" => $type,
                "value" => $value
            ];
        }
        return response()->json($this->data, 200);
    }

    function detail($data) {
        Client::accesses_up(false);
        $this->data['product_detail'] = Client::products_detail($data);
        $this->data['product_detail']->brand = Client::brands_find($this->data['product_detail']->id_brand);
        $this->data['product_rate'] = Client::ratings_get($data);
        $this->data['products_relate'] = Client::products_others_get($data);
        $this->data['comments_list'] = Client::comments_get($data);
        if (session()->has('user_log')) $this->data['users_rating'] = Client::turn_rating_get($this->data['header']['user']['id'],$data);
        return response()->json($this->data, 200);
    }

    function config() {
        Client::accesses_up(false);
        $this->data['status'] = false;
        if (isset($this->data['header']['user'])) {
            $this->data['status'] = true;
            $this->data['list_ins'] = Client::invoices_list_get($this->data['header']['user']['number']);
        }
        return response()->json($this->data, 200);
    }

    function cart() {
        Client::accesses_up(false);
        $this->data['cart'] = session('cart');
        return response()->json($this->data, 200);
    }

    function pay() {
        Client::accesses_up(false);
        $this->data['status'] = false;
        if (session()->has('cart') && session('cart')['list'] != null) {
            $this->data['cart'] = session('cart');
            if (session()->has('user_log')) $this->data['user'] = session('user_log');
            $this->data['status'] = true;
        }
        return response()->json($this->data, 200);
    }

    function comment(Request $rq) {
        $cmt = $rq->input('cmt');
        $idp = $rq->input('idp');
        $uid = $rq->input('uid');
        $date = $rq->input('date');
        Client::comments_add($cmt, $idp, $uid, $date);
    }

    function rate(Request $rq) {
        $id = $rq->input('idsp');
        $rt = $rq->input('rate');
        $us = $this->data['header']['user']['id'];
        Client::turn_rating_rate($id,$us,$rt);
    }

    function dord() {
        Client::accesses_up(false);
        return response()->json($this->data, 200);
    }

    function inv_check(Request $rq) {
        Client::accesses_up(false);
        $invoice = Client::invoices_get($rq->input('in_num'));
        $this->data['status'] = false;
        if(!$invoice) $this->data['res'] = 'Mã hóa đơn không đúng!';
        else {
            $this->data['res'] = $invoice;
            $this->data['status'] = true;
        }
        return response()->json($this->data);
    }

    function rspw() {
        Client::accesses_up(false);
        return response()->json($this->data, 200);
    }
}