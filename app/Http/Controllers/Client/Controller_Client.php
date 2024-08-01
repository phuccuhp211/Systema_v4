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

    function genfil($data_type = null, $data = null) {
        $options = [
            '1' => 'Cũ Nhất',
            '2' => 'Mới Nhất',
            '3' => 'Giá Tăng Dần',
            '4' => 'Giá Giảm Dần'
        ];

        $op_gen = '';
        foreach ($options as $key => $value) {
            $op_gen .= "<option value=\"$key\">$value</option>";
        }

        $dataAttr = isset($data) ? "data=\"$data\"" : '';

        $bl = "<div class=\"col-3 phan-boloc\">
            <select id=\"filter\" class=\"form-select boloc-act\" data-type=\"products/$data_type\" $dataAttr>
                $op_gen
            </select>
        </div>";
        return $bl;
    }

    function pagin($type=null, $data=null, $pg_count=null, $pg_cr, $filters1=null, $filters2=null) {
        $lpt= "";
        $filters1 = ($filters1) ? "type=\"$filters1\"" : '';
        $data = ($data) ? "data=\"$data\"" : '';
        for ($i = 1; $i <= $pg_count; $i++) {
            if ($i == $pg_cr) {
                $lpt.= "<button class=\"a-pt a-move act\" data-type=\"products/$type\" page=\"$i\" $data $filters1>$i</button>";
            }
            else if ($i <= 3 || $i > $pg_count - 3 || ($i >= $pg_cr - 1 && $i <= $pg_cr + 1)) {
                $lpt.= "<button class=\"a-pt a-move\" data-type=\"products/$type\" page=\"$i\" $data $filters1>$i</button>";
            }
            else if ($i == 4 && $pg_cr > 4) {
                $lpt.= "<button class=\"a-pt deact\">...</button>";
            }
            else if ($i == $pg_count - 3 && $pg_cr < $pg_count - 3) {
                $lpt.= "<button class=\"a-pt deact\">...</button>";
            }
        }
        return $lpt;
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
        else if ($type == 'search') $this->data['title'] = $this->data['pgpd'] = 'Tìm kiếm: '.$rq->input('search_data');
        else if ($type == 'cat1') $this->data['title'] = $this->data['pgpd'] = 'Phân loại: '.Client::catalog_get('Catalog_1',$value);
        else if ($type == 'cat2') $this->data['title'] = $this->data['pgpd'] = 'Danh Mục: '.Client::catalog_get('Catalog_2',$value);

        if ($rq->input('search_data')) {
            $this->data['res'] = Client::products_full($type, $rq->input('search_data'), $page, $filters1, $filters2, $rq->input('limit'));
        } 
        else if ($rq->input('data') || $rq->input('page') || $rq->input('limit') || $rq->input('filters1') || $rq->input('filters2')) {
            $res = Client::products_full($type, $value, $page, $filters1, $filters2, $limit);
            $col = ($rq->input('showsp')) ? $rq->input('showsp') : null;
            $this->data['prods'] = showsp($res, $col);
            $this->data['pagin'] = $this->pagin($type, $value, Client::products_pagin($type, $value, $filters2), $page, $filters1, $filters2);
        } 
        else {
            $this->data['brands'] = Client::brands_get();
            $this->data['dtpd'] = Client::products_full($type, $value, $page, $filters1, $filters2, $limit);
            $this->data['pagin'] = $this->pagin($type, $value, Client::products_pagin($type, $value, $filters2), $page, $filters1, $filters2);
            $this->data['filter'] = $this->genfil($type, $value);
        }
        return response()->json($this->data, 200);
    }

    function detail($data) {
        Client::accesses_up(false);
        $this->data['dtpd'] = Client::products_detail($data);
        $this->data['dtpd']->brand = Client::brands_find($this->data['dtpd']->id_brand);
        $this->data['lcmt'] = Client::comments_get($data);
        $this->data['rlpd'] = Client::products_others_get($data);

        $rated = Client::ratings_get($data);
        $idsp = $this->data['dtpd']['id'];

        if (session()->has('user_log')) {
            $usrt = Client::turn_rating_get($this->data['header']['user']['id'],$data);
            $stars_btn = "";
            if($usrt) {
                $offset = $usrt->stars;
                $class_btn = "";

                for ($i=1; $i <= 5; $i++) {
                    if ($i == $offset) $class_btn = "select-star";
                    else $class_btn = "";
                    $stars_btn.= "<div class=\"btn-stars $class_btn\" data-rate=\"$i\" data-idsp=\"$idsp\">$i Sao</div>";
                }
            }
            else {
                for ($i=1; $i <= 5; $i++) {
                    $stars_btn.= "<div class=\"btn-stars\" data-rate=\"$i\" data-idsp=\"$idsp\">$i Sao</div>";
                }
            }
            $this->data['btrt'] = "<div class=\"box-btn-stars\">$stars_btn</div>";
        }
        if ($rated) {
            $ss = $rated->stars/$rated->turns;
            $list_stars = "";
            $num_star = floor($ss);
            $class_star = "color-star";

            for ($i=1; $i <= 5; $i++) {
                if ($i > $num_star) $class_star = "";
                $list_stars.= "
                <i class=\"fa-regular fa-star $class_star\"></i>
                ";
            }

            $sps = "
                <div class=\"sum-stars\">
                    <h4>$ss trên 5 ($rated->turns Lượt)</h4>
                    <h5>$list_stars</h5>        
                </div>
            ";
            $this->data['pds'] = $sps;
        }
        else {
            $sps = "
                <div class=\"sum-stars\">
                    <h5 style=\"color: #ee4d2d;\">Sản phẩm chưa được đánh giá</h5>        
                </div>
            ";
            $this->data['pds'] = $sps;
        }

        return view('client.detail', $this->data);
    }

    function config() {
        Access::accesses_up(false);
        if (isset($this->data['header']['user'])) {
            $this->data['list_ins'] = Client::invoices_list_get($this->data['header']['user']['number']);
            return view('client.config', $this->data);
        }
        else {
            return redirect()->route('home');
        }
    }

    function cart() {
        Access::accesses_up(false);
        return view('client.cart', $this->data);
    }

    function pay() {
        Access::accesses_up(false);
        if (!session()->has('cart') || session('cart')['list'] == null) return redirect()->route('home');
        else return view('client.pay', $this->data);
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
        Access::accesses_up(false);
        return view('client.complete_order', $this->data);
    }

    function inv_check(Request $rq) {
        Access::accesses_up(false);
        if ($rq->input('in_num')) {
            $invoice = Client::invoices_get($rq->input('in_num'));
            $data['status'] = false;

            if(!$invoice) $data['res'] = 'Mã hóa đơn không đúng!';
            else {
                $created = new DateTime($invoice->created);

                if ($invoice->submited != null) {
                    $submited = new DateTime($invoice->submited);
                    $warranty = clone $submited;
                    $warranty->add(new DateInterval('P3Y'));

                    $content_w = "
                        <h5>Đơn hàng được bảo hành từ : 
                            <strong style=\"color:red;\">".$submited->format('d/m/Y')."</strong> đến 
                            <strong style=\"color:red;\">".$warranty->format('d/m/Y')."</strong>
                        </h5>";

                    $submited = $submited->format('d/m/Y');
                }
                else {
                    $submited = "Đang chờ xác nhận";
                    $content_w = "<h5>Đơn hàng đang chờ được xác nhận bởi quản trị viên</h5>";
                }

                $html = "";
                $list = json_decode($invoice->list,true);

                foreach ($list as $value => $item) {
                    $html .= "
                        <tr> 
                            <td style=\"font-size: 16px; padding: 5px 0;text-align: center;\">".($value+1)."</td>
                            <td style=\"font-size: 16px; padding: 5px 0 5px 10px;\">".$item['name']."</td>
                            <td style=\"font-size: 16px; padding: 5px 0;text-align: center;\">".$item['num']."</td>
                            <td style=\"font-size: 16px; padding: 5px; text-align: right;\">".gennum( $item['pfn'] )."</td>
                            <td style=\"font-size: 16px; padding: 5px; text-align: right;\">".gennum( $item['sum'] )."</td>
                        </tr>
                    ";
                }

                if ($invoice->offers != null) {
                    $final = "
                        <tr>
                            <td colspan=\"3\" class=\"tc-dssp\"><strong>Tạm Tính :</strong></td>
                            <td colspan=\"2\" class=\"tc-dssp\"><strong>".gennum( $invoice->price )."</strong></td>
                        </tr>
                        <tr>
                            <td colspan=\"3\" class=\"tc-dssp\"><strong>Giá Giảm :</strong></td>
                            <td colspan=\"2\" class=\"tc-dssp\"><strong>".gennum( $invoice->price-$invoice->offers )."</strong></td>
                        </tr>
                        <tr>
                            <td colspan=\"3\" class=\"tc-dssp\"><strong>Tổng Cộng :</strong></td>
                            <td colspan=\"2\" class=\"tc-dssp\"><strong>".gennum( $invoice->offers )."</strong></td>
                        </tr>
                    ";
                }
                else {
                    $final = "
                        <tr>
                            <td colspan=\"3\" class=\"tc-dssp\"><strong>Tổng Cộng :</strong></td>
                            <td colspan=\"2\" class=\"tc-dssp\"><strong>".gennum( $invoice->price )."</strong></td>
                        </tr>
                    ";
                }

                $data['res'] = "
                    <div class=\"col-8 offset-2 cthd\">
                        <h2>Thông Tin Hóa Đơn</h2>
                        <div class=\"tt-ngmua\">
                            <table style=\"margin: 0 0 20px; font-size: 18px;\">
                                <tr>
                                    <td style=\"padding: 5px 0;width: 50%; border-bottom: 1px solid gray\" colspan=\"2\">
                                        Mã Hóa Đơn : <strong style=\"color: #6246a8;\">".$invoice->in_num."</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td style=\"padding: 5px 0;width:50%;\">Ngày Tạo Đơn : 
                                        <strong style=\"color: #6246a8;\">".$created->format('d/m/Y')."</strong>
                                    </td>
                                    <td style=\"padding: 5px 0;\">Ngày Xác Nhận Đơn : 
                                        <strong style=\"color: #6246a8;\">$submited</strong>
                                    </td>
                                </tr>
                            </table>
                            <table class=\"tt-user\">
                                <tr>
                                    <td class=\"ttct-user\">Tên Người mua :</td>
                                    <td><strong>".$invoice->name."</strong></td>
                                </tr>
                                <tr>
                                    <td class=\"ttct-user\">Số Điện Thoại :</td>
                                    <td><strong>".$invoice->number."</strong></td>
                                </tr>
                                <tr>
                                    <td class=\"ttct-user\">Email : </td>
                                    <td><strong>".$invoice->email."</strong></td>
                                </tr>
                                <tr>
                                    <td class=\"ttct-user\">Địa Chỉ :</td>
                                    <td><strong>".$invoice->address."</strong></td>
                                </tr>
                            </table>
                        </div>
                        <table class=\"dssp\">
                            <tr>
                                <th style=\"width: 8%;\">STT</th>
                                <th style=\"width: 50%;\">Tên Hàng Hóa, Dịch Vụ</th>
                                <th style=\"width: 8%;\">SL</th>
                                <th style=\"width: 17%;\">Đơn Giá</th>
                                <th style=\"width: 17%;\">Thành Tiền</th>
                            </tr>
                            $html
                            <tr>
                                <td style=\"font-size: 16px; padding: 5px 0;text-align: center;\">X</td>
                                <td style=\"font-size: 16px; padding: 5px 0 5px 10px\">Phí vận chuyển</td>
                                <td style=\"font-size: 16px; padding: 5px 0;text-align: center;\">X</td>
                                <td style=\"font-size: 16px; padding: 5px; text-align:right;\">".$invoice->shipfee."</td>
                                <td style=\"font-size: 16px; padding: 5px; text-align:right;\">".$invoice->shipfee."</td>
                            </tr>
                            $final
                        </table>
                        $content_w
                        <h6>Lưu ý : Bảo hành áp dụng cho toàn bộ sản phẩm có trong đơn hàng, khi đi bảo hành, quý khách vui lòng mang theo hộp (hoặc bao bì) của sản phẩm và kèm theo hóa đơn.</h6>
                    </div>
                ";

                $data['status'] = true;
            }
            return response()->json($data);
        }
        return view('client.invoice_check', $this->data);
    }

    function rspw() {
        Access::accesses_up(false);
        return view('client.reset_pw', $this->data);
    }
}