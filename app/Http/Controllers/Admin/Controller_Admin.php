<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Model_Admin;

use DateTime;
use DateInterval;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class Controller_Admin extends Controller {
    private $data;
    private $rules = [
        'account' => 'required|min:6',
        'name' => 'required|min:3',
        'l_name' => 'required|min:3',
        'f_name' => 'required|min:3',
        'number' => 'required|min:8|max:11',
        'email' => 'required|email|regex:/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/',
        'address' => 'required',
        'pass' => 'required|min:6',
        'info' => 'required',
        'id_cata_1' => 'required',
        'id_brand' => 'required',
        'price' => 'required|integer|min:1000',
        'amount' => 'required|integer|min:1',
        'img' => 'required|image|mimes:jpeg,png,jpg,gif,webg|max:8192',
    ];
    private $msgs = [
        'account' => 'Vui lòng nhập tên tài khoản, tối thiểu 6 ký tự',
        'name' => 'Vui lòng nhập tên, tối thiểu 3 ký tự.',
        'l_name' => 'Vui lòng nhập Tên, tối thiểu 3 ký tự',
        'f_name' => 'Vui lòng nhập Họ, tối thiểu 3 ký tự',
        'number' => 'Vui lòng nhập Số điện thoại, tối thiểu 8 số',
        'email' => 'Email là bắt buộc và đúng định dạng.',
        'address' => 'Vui lòng nhập địa chỉ.',
        'pass' => 'Vui lòng nhập mật khẩu, tối thiểu 6 ký tự.',
        'info' => 'Vui lòng nhập mô tả sản phẩm.',
        'id_cata_1' => 'Vui lòng chọn phân loại.',
        'id_brand' => 'Vui lòng chọn thương hiệu.',
        'price' => 'Bắt buộc nhập giá, thấp nhất là 1.000.',
        'amount' => 'Vui lòng nhập số lượng, tối thiểu là 1',
        'img' => 'Vui lòng chọn ảnh'
    ];

    function __construct() {
        if (session()->has('admin_log')) {
            $admin = DB::table('User')->where('account',session('admin_log'))->first();
            $this->data['permission'] = $admin->permission;
        }
    }

    function login() {
        return view('admin.login');
    }
    
    function manager(Request $rq, $type='') {
        if ($type != '') {
            if ($type == 'sections') {
                $this->data['list'] = DB::table('Sections')->orderby('id','DESC')->get();
                $this->data['cat1'] = DB::table('Catalog_1')->get();
                $this->data['cat2'] = DB::table('Catalog_2')->get();
                $this->data['mng'] = 'sections';
            }
            else if ($type == 'slidebns') {
                $this->data['list'] = DB::table('Banners')->orderby('id','DESC')->get();
                $this->data['mng'] = 'slidebns';
            }
            else if ($type == 'products') {
                $this->data['mng'] = 'products';
                $this->data['list'] = DB::table('Products')->limit(10)->orderby('id','DESC')->get();
                $this->data['pagin'] = $this->gen_pagin('pd',ceil(DB::table('Products')->count() / 10));
                $this->data['cat1'] = DB::table('Catalog_1')->get();
                $this->data['cat2'] = DB::table('Catalog_2')->get();
                $this->data['grap'] = DB::table('Products')->grap();
                $this->data['brands'] = DB::table('Brands')->get();
            }
            else if ($type == 'catalogs') {
                $this->data['list1'] = DB::table('Catalog_1')->orderby('id','DESC')->get();
                $this->data['list2'] = DB::table('Catalog_2')->orderby('id','DESC')->get();
                $this->data['mng'] = 'catalogs';
            }
            else if ($type == 'usersmng') {
                $this->data['list'] = DB::table('Users')->limit(10)->orderby('id','DESC')->get();
                $this->data['pagin'] = $this->gen_pagin('us',ceil(DB::table('Users')->count() / 10));
                $this->data['mng'] = 'usersmng';
            }
            else if ($type == 'comments') {
                $this->data['list'] = DB::table('Comments')->get_list();
                $this->data['mng'] = 'comments';
            }
            else if ($type == 'invoices') {
                $this->data['list'] = DB::table('Invoices')->limit(10)->orderby('id','DESC')->get();
                $this->data['pagin'] = $this->gen_pagin('in',ceil(DB::table('Invoices')->count() / 10));
                $this->data['mng'] = 'invoices';
            }
            else if ($type == 'offers') {
                $this->data['list'] = DB::table('Vouchers')->orderby('id','DESC')->get();
                $this->data['mng'] = 'offers';
            }
        }
        else {
            $this->data['mng'] = 'home';
            $this->data['revenue'] = Model_Admin::ttrev();
            $this->data['orders'] = Model_Admin::ttord();
            $this->data['members'] = Model_Admin::ttus();
            $this->data['accesses'] = Model_Admin::tttf();
        }
        return view('admin.manager', $this->data);
    }

    function ss_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Sections')->destroy($id);
        } 
        else {
            if (!$rq->has('eb_img') && $type == 'add' || $rq->has('poster') && !$rq->has('eb_img') && $type == 'add') {
                $new_rule['eb_img'] = 'required|image|mimes:jpeg,png,jpg,gif,webg|max:8192';
                $new_msgs['eb_img'] = 'Vui lòng chọn ảnh nền cho sections';
            }
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            unset($new_rule['id_cata_1'],$new_msgs['id_cata_1']);
            $validation = Validator::make($rq->all(), $new_rule, $new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }
            if ($type == 'add' && !$validation->fails()) {
                $dt_crud = $rq->all();

                $eb_img = $rq->file('eb_img')->getClientOriginalName();
                Storage::disk('custom')->putFileAs($rq->file('eb_img'), $eb_img);

                if ($rq->has('poster')) {
                    $poster = $rq->file('poster')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('poster'), $poster);
                }
                
                $dt_crud['eb_img'] = $eb_img;
                $dt_crud['poster'] = (isset($poster)) ? $poster : '';
                DB::table('Sections')->create($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Thêm Thành Công</span>";
            } 
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if (!$rq->has('neweb_img')) $dt_crud['eb_img'] = $rq->input('oldeb_img');
                else {
                    $eb_img = $rq->file('neweb_img')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('neweb_img'), $eb_img);
                    $dt_crud['eb_img'] = $eb_img;
                }
                if (!$rq->has('newposter')) $dt_crud['poster'] = $rq->input('oldposter');
                else {
                    $eb_img = $rq->file('newposter')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('newposter'), $eb_img);
                    $dt_crud['poster'] = $eb_img;
                }

                unset($dt_crud['neweb_img'], $dt_crud['oldeb_img'], $dt_crud['newposter'], $dt_crud['oldposter']);
                DB::table('Sections')->where('id', $rq->input('id'))->update($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
        }
        return response()->json($data);
    }

    function bn_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Banners')->destroy($id);
        } 
        else {
            if (!$rq->has('img') && $type == 'add') {
                $new_rule['img'] = 'required|image|mimes:jpeg,png,jpg,gif,webg|max:8192';
                $new_msgs['img'] = 'Vui lòng chọn ảnh';
            }
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(), $new_rule, $new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }
            if ($type == 'add' && !$validation->fails()) {
                $name = $rq->file('img')->getClientOriginalName();
                Storage::disk('custom')->putFileAs($rq->file('img'), $name);

                $dt_crud = $rq->all();
                $dt_crud['img'] = $name;
                DB::table('Banners')->create($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Thêm Thành Công</span>";
            } 
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if (!$rq->hasFile('newimg')) $dt_crud['img'] = $rq->input('oldimg');
                else {
                    $name = $rq->file('newimg')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('newimg'), $name);
                    $dt_crud['img'] = $name;
                }
                unset($dt_crud['newimg'], $dt_crud['oldimg']);
                DB::table('Banners')->where('id', $rq->input('id'))->update($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
        }
        return response()->json($data);
    }

    function pd_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Products')->destroy($id);
        } 
        else if ($type == 'hid') {
            DB::table('Products')->where('id', $rq->input('id'))->update(['hidden' => $rq->input('data')]);
        }
        else {
            if (!$rq->has('img') && $type == 'add') {
                $new_rule['img'] = 'required|image|mimes:jpeg,png,jpg,gif,webg|max:8192';
                $new_msgs['img'] = 'Vui lòng chọn ảnh';
            }
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(), $new_rule, $new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }
            if ($type == 'add' && !$validation->fails()) {
                $name = $rq->file('img')->getClientOriginalName();
                Storage::disk('custom')->putFileAs($rq->file('img'), $name);

                $dt_crud = $rq->all();
                $dt_crud['img'] = $name;
                DB::table('Products')->create($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Thêm Thành Công</span>";
            } 
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if (!$rq->hasFile('newimg')) $dt_crud['img'] = $rq->input('oldimg');
                else {
                    $name = $rq->file('newimg')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('newimg'), $name);
                    $dt_crud['img'] = $name;
                }
                unset($dt_crud['newimg'], $dt_crud['oldimg']);
                DB::table('Products')->where('id', $rq->input('id'))->update($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
        }
        return response()->json($data);
    }

    function c1_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Catalog_1')->destroy($id);
        }
        else {
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(),$new_rule,$new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }

            if ($type == 'add' && !$validation->fails()) {
                DB::table('Catalog_1')->create($rq->all());
                $data['status'] = true;
                $data['res'] = "<span>Thêm Thành Công</span>";
            }
            else if ($type == 'fix' && !$validation->fails()) {
                DB::table('Catalog_1')->where('id',$rq->input('id'))->update($rq->all());
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
        }
        return response()->json($data);
    }

    function c2_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Catalog_2')->destroy($id);
        }
        else {
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(),$new_rule,$new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }

            if ($type == 'add' && !$validation->fails()) {
                DB::table('Catalog_2')->create($rq->all());
                $data['status'] = true;
                $data['res'] = "<span>Thêm Thành Công</span>";
            }
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if (!$rq->hasFile('newimg')) $dt_crud['img'] = $rq->input('oldimg');
                else {
                    $name = $rq->file('newimg')->getClientOriginalName();
                    Storage::disk('custom')->putFileAs($rq->file('newimg'), $name);
                    $dt_crud['img'] = $name;
                }
                unset($dt_crud['newimg'], $dt_crud['oldimg']);
                DB::table('Catalog_2')->where('id', $rq->input('id'))->update($cdt_crud2);
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
        }
        return response()->json($data);
    }

    function us_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('User')->destroy($id);
        }
        else if ($type == 'hid') {
            DB::table('User')->where('id',$rq->input('id'))->update([ 'lock' => $rq->input('data') ]);
        }
        else {
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(),$new_rule,$new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }

            if ($type == 'add' && !$validation->fails()) {
                $email = DB::table('User')->get_em($rq->input('email'));
                if ($email) $data['res'] .= "<li>Email đã được sử dụng</li>";
                else {
                    $dt_crud = $rq->all();
                    $dt_crud['pass'] = Hash::make($rq->input('pass'));
                    DB::table('User')->create($dt_crud);
                    $data['status'] = true;
                    $data['res'] = "<span>Thêm Thành Công</span>";
                }
            }
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if ($rq->filled('newpass')) $dt_crud['pass'] = Hash::make($rq->input('newpass'));
                else $dt_crud['pass'] = $rq->input('oldpass');
                unset($dt_crud['newpass'], $dt_crud['oldpass']);
                DB::table('User')->where('id', $rq->input('id'))->update($dt_crud);
                $data['status'] = true;
                $data['res'] = "<span>Cập Nhật Thành Công</span>";
            }
            
        }
        return response()->json($data);
    }

    function cm_mng (Request $rq, $type='', $id=null) {
        if ($type == 'detail') {
            $list = DB::table('Comment')->where('id_pd',$rq->input('id'))->get();
            return response()->json($list);
        }
        else if ($type == 'del') {
            DB::table('Comment')->destroy($id);
            return response()->json(['status' => true, 'res' => '']);
        }
    }

    function in_mng (Request $rq, $type='', $id=null) {
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'upd') {
            Model_Admin::invoices_up_stt($rq->input('id'),$rq->input('stt'),$rq->input('pstt'));
        }
        else if ($type == 'del') {
            $data['status'] = true;
            DB::table('Invoice')->destroy($id);
        }
        return response()->json($data);
    }

    function cp_mng (Request $rq, $type='', $id=null) {
        $new_rule = [];
        $new_msgs = [];
        $data['status'] = false;
        $data['res'] = '';

        if ($type == 'del') {
            $data['status'] = true;
            DB::table('Voucher')->destroy($id);
        }
        else {
            foreach ($this->rules as $key => $rule) {
                if ($rq->has($key)) {
                    $new_rule[$key] = $rule;
                    $new_msgs[$key] = $this->msgs[$key];
                }
            }
            $validation = Validator::make($rq->all(),$new_rule,$new_msgs);
            if ($validation->fails()) {
                $errors = $validation->errors();
                foreach ($errors->all() as $error) {
                    $data['res'] .= "<li>$error</li>";
                }
            }

            if ($type == 'add' && !$validation->fails()) {
                $dt_crud = $rq->all();
                $dt_crud['remaining'] = $dt_crud['amount'];
                if ((filled($dt_crud['f_date']) && !filled($dt_crud['t_date'])) || (!filled($dt_crud['f_date']) && filled($dt_crud['t_date']))) {
                    $data['res'] .= "<li>Thời gian bắt đầu và kết thúc khuyến mãi phải được điền đầy đủ hoặc cả hai đều trống</li>";
                }
                else {
                    DB::table('Voucher')->create($dt_crud);
                    $data['status'] = true;
                    $data['res'] = "<span>Thêm Thành Công</span>";
                }
            }
            else if ($type == 'fix' && !$validation->fails()) {
                $dt_crud = $rq->all();
                if ((filled($dt_crud['f_date']) && !filled($dt_crud['t_date'])) || (!filled($dt_crud['f_date']) && filled($dt_crud['t_date']))) {
                    $data['res'] .= "<li>Thời gian bắt đầu và kết thúc khuyến mãi phải được điền đầy đủ hoặc cả hai đều trống</li>";
                }
                else {
                    DB::table('Voucher')->where('id',$rq->input('id'))->update($rq->all());
                    $data['status'] = true;
                    $data['res'] = "<span>Cập Nhật Thành Công</span>";
                }
                    
            }
        }
        return response()->json($data);
    }

    function gen_html($type,$list) {
        $html = '';

        if ($type == 'ss') {
            foreach ($list as $item) {
                $poster = ($item->poster != null) ? "<img src=\"".genurl($item->poster)."\">" : "Chưa Thiết Lập";
                $eb_img = ($item->eb_img != null) ? "<img src=\"".genurl($item->eb_img)."\">" : "Chưa Thiết Lập";
                
                $html .="
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data\" data-fn=\"$item->name\" data-pt=\"$item->poster\" data-ep=\"$item->eb_img\" data-c1=\"$item->id_cata_1\" data-c2=\"$item->id_cata_2\" data-rf=\"$item->reference\" data-or=\"$item->orderby\" data-id=\"$item->index\"></td>
                        <td rowspan=\"2\" class=\"text-center p-0\">$item->id</td>
                        <td rowspan=\"2\" class=\"text-center\">$item->name</td>
                        <td rowspan=\"2\" class=\"text-center\">$poster</td>
                        <td rowspan=\"2\" class=\"text-center\">$eb_img</td>
                        <td class=\"text-center\">$item->id_cata_1</td>
                        <td class=\"text-center\">$item->reference</td>
                        <td rowspan=\"2\" class=\"text-center\">$item->index</td>
                        <td rowspan=\"2\" class=\"text-center\">
                            <button class=\"btn btn-primary btn-mini btn-crud fix suabc\" data-id=\"$item->id\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"ss\"><i class=\"fa-solid fa-trash\"></i></button>
                        </td>
                    </tr>
                    <tr class=\"record\">
                        <td class=\"text-center\">$item->id_cata_2</td>
                        <td class=\"text-center\">$item->orderby</td>
                    </tr>
                ";
            }
        }

        else if ($type == 'bn') {
            foreach ($list as $item) {
                $html .= "
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data\" data-im=\"$item->img\" data-tt=\"$item->tit\" data-ct=\"$item->ctn\"></td>
                        <td class=\"text-center p-0\">$item->id</td>
                        <td class=\"text-center\"><img src=\"".genurl($item->img)."\" alt=\"\"></td>
                        <td class=\"text-center\">$item->tit</td>
                        <td class=\"text-center\">
                            <button class=\"btn btn-primary btn-mini btn-crud fix suabn\" data-id=\"$item->id\" data-type=\"bn\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"bn\"><i class=\"fa-solid fa-trash\"></i></button>
                        </td>
                    </tr>
                "; 
            }
        }

        else if ($type == 'pd') {
            foreach ($list as $item) {
                $button ="";
                if($item->hidden == 0) {
                    $button = "<button class=\"btn btn-warning btn-mini btn-crud hidden hidsp\" data-hid=\"$item->hidden\" data-id=\"$item->id\" data-type=\"pd\"><i class=\"fa-solid fa-eye-slash\"></i></button>";
                } 
                else {
                    $button = "<button class=\"btn btn-success btn-mini btn-crud hidden unhidsp\" data-hid=\"$item->hidden\" data-id=\"$item->id\" data-type=\"pd\"><i class=\"fa-solid fa-eye\"></i></button>";
                }
                $html .= "
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data\" data-fn=\"$item->name\" data-im=\"$item->img\" data-if=\"$item->info\" data-c1=\"$item->id_cata_1\" data-c2=\"$item->id_cata_2\" data-br=\"$item->id_brand \" data-pr=\"$item->price\" data-sl=\"$item->sale\" data-sf=\"$item->f_date\" data-st=\"$item->t_date\"></td>
                        <td rowspan=\"2\" class=\"text-center\">$item->id</td>
                        <td rowspan=\"2\" class=\"text-center\"><img src=\"".genurl($item->img)."\" alt=\"\"></td>
                        <td rowspan=\"2\">$item->name</td>
                        <td rowspan=\"2\" style=\"overflow-hidden\">$item->info</td>
                        <td class=\"text-center\">".gennum($item->price)."</td>
                        <td class=\"text-center\">".gennum($item->sale)."</td>
                        <td rowspan=\"2\" class=\"text-center\">
                            <button class=\"btn btn-primary btn-mini btn-crud fix suasp\" data-id=\"$item->id\" data-type=\"pd\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"pd\"><i class=\"fa-solid fa-trash\"></i></button>
                            $button
                        </td>
                    </tr>
                    <tr class=\"record\">
                        <td colspan=\"2\">Đã bán : $item->saled</td>
                    </tr>
                ";
            }
        }

        else if ($type == 'c1') {
            foreach ($list as $item) {
                $html .= "
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data1\" data-fn=\"$item->name\"></td>
                        <td style=\"text-align: center;\">$item->id</td>
                        <td style=\"text-align: center;\">$item->name</td>
                        <td style=\"text-align: center;\">
                            <button class=\"btn btn-primary btn-mini btn-crud fix suapl\" data-id=\"$item->id\" data-type=\"c1\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"c1\"><i class=\"fa-solid fa-trash\"></i></button>
                        </td>
                    </tr>
                ";
            }
        }

        else if ($type == 'c2') {
            foreach ($list as $item) {
                $img = ($item->img != NULL) ? "<img src=\"".genurl($item->img)."\" alt=\"\">" : '';
                $html .= "
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data2\" data-fn=\"$item->name\" data-c1=\"$item->type\" data-im=\"$item->img\"></td>
                        <td style=\"text-align: center;\">$item->id</td>
                        <td style=\"text-align: center;\">$item->name</td>
                        <td style=\"text-align: center;\">$item->type</td>
                        <td style=\"text-align: center;\">$img</td>
                        <td style=\"text-align: center;\">
                            <button class=\"btn btn-primary btn-mini btn-crud fix suadm\" data-id=\"$item->id\" data-type=\"c2\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"c2\"><i class=\"fa-solid fa-trash\"></i></button>
                        </td>
                    </tr>
                ";
            }
        }

        else if ($type == 'us') {
            foreach ($list as $item) {
                if($item->lock == 0) $button = "<button data-type=\"us\" class=\"btn btn-warning btn-mini btn-crud lock banus\" data-id=\"".$item->id."\"><i class=\"fa-solid fa-ban\"></i></button>";
                else $button = "<button data-type=\"us\" class=\"btn btn-success btn-mini btn-crud lock unbanus\" data-id=\"".$item->id."\"><i class=\"fa-solid fa-check\"></i></button>";

                if ($item->role == 1) {
                    if ($item->permission != NULL) $role = $item->permission;
                    else $role = 'noroot';
                }
                else $role = 'Khách Hàng';
                
                $html .= "
                    <tr class=\"record\">
                        <td  hidden id=\"hidden-data\" data-ac=\"$item->account\" data-fn=\"$item->name\"  data-nb=\"$item->number\" data-em=\"$item->email\" data-ad=\"$item->address\" data-rl=\"$item->role\" data-pm=\"$item->permission\" data-pw=\"$item->pass\"></td>
                        <td class=\"text-center\">$item->id</td>
                        <td id=\"tenus\">".$item->account."</td>
                        <td>$item->name</td>
                        <td id=\"roleus\" class=\"text-center\">$role</td>
                        <td class=\"text-center\">
                            <button data-type=\"us\" class=\"btn btn-primary btn-mini btn-crud fix suaus\" data-id=\"$item->id\">
                                <i class=\"fa-solid fa-gear\"></i>
                            </button>
                            <button data-type=\"us\" class=\"btn btn-danger btn-mini btn-crud del xoaus\" data-id=\"$item->id\">
                                <i class=\"fa-solid fa-trash\"></i>
                            </button>
                            $button
                        </td>
                    </tr>
                ";
            }
        }

        else if ($type == 'cm') {
            foreach ($list as $item) {
                $html .= "
                    <tr class=\"record\">
                        <td>$item->id</td>
                        <td style=\"text-align: left;\">$item->name</td>
                        <td>$item->cmts</td>
                        <td>$item->users</td>
                        <td><button class=\"btn btn-success chitiet chitietbl\" data-id=\"$item->id\">Chi tiết</button></td>
                    </tr>
                ";
            }
        }

        else if ($type == 'in') {
            foreach ($list as $item) {
                $prods = json_decode($item->list, true);
                $rp = is_array($prods) ? count($prods) : 0;
                $coupon = $item->coupon;
                $price = ($item->offers !== null) ?  gennum($item->offers)."<br><span style=\"font-size: 12px; color: red;\">$coupon</span>" : gennum($item->price);

                $html .= "
                    <tr class=\"record\">
                        <td rowspan=\"$rp\" class=\"text-center p-0 id-hd\">$item->id</td>
                        <td rowspan=\"$rp\" class=\"text-start\" style=\"font-size:14px;\">
                            ".$item->name."<br>
                            ".$item->email."<br>
                            0".$item->number."<br>
                            ".$item->address."
                        </td>
                        <td class=\"text-start\">SL: ".$prods[0]->num." | ".$prods[0]->name."</td>
                        <td rowspan=\"$rp\" class=\"text-center p-0\">$price</td>
                        <td rowspan=\"$rp\" class=\"text-center\">
                            <select name=\"trangthai\" class=\"hd-stt form-control mb-1\" id=\"hd-stt\">
                                <option ".(($item->status == 'Đanh chờ xác nhận') ? 'selected' : '')." value=\"Đanh chờ xác nhận\">Đanh chờ xác nhận</option>
                                <option ".(($item->status == 'Chuẩn Bị') ? 'selected' : '')." value=\"Chuẩn Bị\">Chuẩn Bị</option>
                                <option ".(($item->status == 'Đang Giao') ? 'selected' : '')." value=\"Đang Giao\">Đang Giao</option>
                                <option ".(($item->status == 'Hoàn Thành') ? 'selected' : '')." value=\"Hoàn Thành\">Hoàn Thành</option>
                                <option ".(($item->status == 'Hủy') ? 'selected' : '')." value=\"Hủy\">Hủy</option>
                            </select>
                            <select name=\"thanhtoan\" class=\"hd-stt form-control mb-1\" id=\"hd-pstt\">
                                <option ".(($item->p_status == 0) ? 'selected' : '')." value=\"0\">Chưa Thanh Toán</option>
                                <option ".(($item->p_status == 1) ? 'selected' : '')." value=\"1\">Đã Thanh Toán</option>
                            </select>
                            <button class=\"btn btn-success d-block mt-1 mx-auto hd-update\" id=\"hd-update\">Cập Nhật</button>
                        </td>
                    </tr>
                ";
                for ($i = 1; $i < $rp ; ++$i) {
                    $data['res'] .="
                        <tr class=\"record\">
                            <td style=\"text-align: left;\">SL: ".$prods[$i]->num." | ".$prods[$i]->name."</td>
                        </tr>
                    ";
                }
            }
        }

        else if ($type == 'cp') {
            foreach ($list as $item) {
                $price = ($item->type == "number") ? $item->discount." đ" : $item->discount."%";
                $html .= "
                    <tr class=\"record\">
                        <td hidden id=\"hidden-data\" data-fn=\"$item->name\" data-mx=\"$item->amount\" data-rm=\"$item->remaining\" data-fd=\"$item->f_date\" data-td=\"$item->t_date\" data-dc=\"$item->discount\" data-tp=\"$item->type\"></td>
                        <td>$item->id</td>
                        <td>$item->name</td>
                        <td>$item->amount</td>
                        <td>$item->remaining</td>
                        <td>".gendate($item->f_date)."</td>
                        <td>".gendate($item->t_date)."</td>
                        <td>$price</td>
                        <td>
                            <button class=\"btn btn-primary btn-mini btn-crud fix suagg\" data-id=\"$item->id\" data-type=\"cp\"><i class=\"fa-solid fa-gear\"></i></button>
                            <button class=\"btn btn-danger btn-mini btn-crud del\" data-id=\"$item->id\" data-type=\"cp\"><i class=\"fa-solid fa-trash\"></i></button>
                        </td>
                    </tr>
                ";
            }
        }
        return $html;
    }

    function gen_pagin($type,$ttpage,$page = 1) {
        $html= "";
        for ($i = 1; $i <= $ttpage; $i++) {
            if ($i == $page) {
                $html.= "<button data-type=\"$type\" data-page=\"$i\" class=\"button-pagin filter-gr page-records page-act\" >$i</button>";
            }
            else if ($i <= 3 || $i > $ttpage - 3 || ($i >= $page - 1 && $i <= $page + 1)) {
                $html.= "<button data-type=\"$type\" data-page=\"$i\" class=\"button-pagin filter-gr page-records\">$i</button>";
            }
            else if ($i == 4 && $page > 4) {
                $html.= "<button class=\"button-pagin filter-gr deact\">...</button>";
            }
            else if ($i == $ttpage - 3 && $page < $ttpage - 3) {
                $html.= "<button class=\"button-pagin filter-gr deact\">...</button>";
            }
        }
        return $html;
    }

    function check_permission(Request $rq) {
        $data = ['status' => false, 'res' => ''];
        $type = $rq->input('type');
        if ($this->data['permission'] == 'Admin') $data['status'] = true;
        else if ($this->data['permission'] == 'Seller') {
            if ($type == 'pd' || $type == 'c1' || $type == 'c2' || $type == 'in' || $type == 'cp' || $type == 'br') $data['status'] = true;
            else $data['res'] = 'Bạn không có quyền thực hiện hành động này';
        }
        else if ($this->data['permission'] == 'Designer') {
            if ($type == 'ss' || $type == 'bn') $data['status'] = true;
            else $data['res'] = 'Bạn không có quyền thực hiện hành động này';
        }
        return response()->json($data);
    }

    function filter(Request $rq) {
        $data['status'] = true;

        $db = $rq->input('type');
        $page = $rq->input('page');
        $filter = $rq->input('filter');
        $record = $rq->input('records');
        $search = $rq->input('search');
        $order = $rq->input('order');

        if ($db == 'cm') $list = Comment::get_list();
        else {
            $list = Model_Admin::filter($db,$page,$filter,$record,$search,$order,'list');
            $ttpg = Model_Admin::filter($db,$page,$filter,$record,$search,$order,'ttpg');
            $data['pagin'] = $this->gen_pagin($db,$ttpg,$page);
        }

        $data['res'] = $this->gen_html($db,$list);    
        return response()->json($data);
    }
}
