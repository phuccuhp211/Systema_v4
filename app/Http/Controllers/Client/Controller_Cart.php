<?php

namespace App\Http\Controllers\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Model_Client as Cart;
use DateTime;

class Controller_Cart extends Controller {
    function add(Request $rq) {
        $id = $rq->input('id');
        $quantity = $rq->input('num', 1);
        $cart = session('cart');
        $prod = Cart::products_get_cart($id)->toArray();
        $prod['num'] = $quantity;
        $prod = $this->cal_price($prod);
        $repeated = false;
        foreach ($cart['list'] as &$item) {
            if ($item['id'] == $id) {
                $item['num'] += $quantity;
                $item = $this->cal_price($item);
                $repeated = true;
                break;
            }
        }
        if (!$repeated) $cart['list'][] = $prod;
        $cart = $this->total($cart);
        session(['cart' => $cart]);
        $this->storage_cart();
    }

    function fix(Request $rq) {
        $id = $rq->input('id');
        $quantity = $rq->input('num');
        $cart = session('cart');
        foreach ($cart['list'] as &$item) {
            if ($item['id'] == $id) {
                $item['num'] = $quantity;
                $item = $this->cal_price($item);
                break;
            }
        }
        $cart = $this->total($cart);
        session(['cart' => $cart]);
        $this->storage_cart();
    }

    function del(Request $rq) {
        $key = $rq->input('key');
        $cart = session('cart');
        unset($cart['list'][$key]);
        $cart = $this->total($cart);
        session(['cart' => $cart]);
        $this->storage_cart();
    }

    function dac(Request $rq) {
        session()->forget('cart');
        $this->storage_cart();
    }

    function buy($id) {
        $quantity = 1;
        $cart = session('cart');
        $prod = Cart::products_get_cart($id)->toArray();
        $prod['num'] = $quantity;
        $prod = $this->cal_price($prod);
        $repeated = false;
        foreach ($cart['list'] as &$item) {
            if ($item['id'] == $id) {
                $item['num'] += $quantity;
                $item = $this->cal_price($item);
                $repeated = true;
                break;
            }
        }
        if (!$repeated) array_push($cart['list'], $prod);
        $cart = $this->total($cart);
        session(['cart' => $cart]);
        $this->storage_cart();
        return redirect()->route('cart');
    }

    function total($cart) {
        $total = 0;
        foreach ($cart['list'] as &$item) {
            $item = $this->cal_price($item);
            $total += $item['sum'];
        }
        $cart['total'] = $total;
        return $cart;
    }

    function cal_price($prod) {
        $now = new DateTime();
        if ($prod['f_date'] == NULL && $prod['t_date'] == NULL) {
            $prod['pfn'] = $prod['price'];
            $prod['sum'] = $prod['num'] * $prod['pfn'];
        }
        else {
            $f_date = new DateTime($prod['f_date']);
            $t_date = new DateTime($prod['t_date']);
            if ($f_date > $now || $t_date < $now) {
                $prod['pfn'] = $prod['price'];
                $prod['sum'] = $prod['num'] * $prod['pfn'];
            } 
            else {
                $prod['pfn'] = $prod['sale'];
                $prod['sum'] = $prod['num'] * $prod['pfn'];
            } 
        }
            
        return $prod;
    }

    function storage_cart() {
        if(session()->has('user_log')) Cart::users_update_cart(session('user_log'));
        $this->echo_cart();
    }

    function echo_cart() {
        echo "<pre>";
        var_dump(session('cart'));
        echo "</pre>";
    }

    public function merge_cart($us_cart) {
        $session = session('cart');

        foreach ($us_cart['list'] as $item2) {
            $repeated = false;
            foreach ($session['list'] as &$item1) {
                if ($item1['id'] == $item2['id']) {
                    $item1['num'] += $item2['num'];
                    $item1 = $this->cal_price($item1);
                    $repeated = true;
                    break;
                }
            }
            if (!$repeated) $session['list'][] = $this->cal_price($item2);
        }

        $session = $this->total($session);
        session(['cart' => $session]);
        Cart::users_update_cart(session('user_log'));
    }
}
