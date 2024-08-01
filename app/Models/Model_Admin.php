<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Model_Admin extends Model {
    use HasFactory;

    public static function ttrev() {
        $result = DB::table('invoices')->
                select(
                    DB::raw('SUM(CASE WHEN offers != 0 THEN offers ELSE price END) as expect'),
                    DB::raw('SUM(CASE WHEN status = "Hoàn Thành" THEN (CASE WHEN offers != 0 THEN offers ELSE price END) ELSE 0 END) as revenue')
                )
                ->first();

        return [
            'expect' => $result->expect,
            'total' => $result->revenue,
        ];
    }

    public static function ttord() {
        $sum = DB::table('invoices')->count();
        $done = DB::table('invoices')->where('status', 'Hoàn Thành')->count();

        return [
            'sum' => $sum,
            'done' => $done,
        ];
    }

    public static function ttus() {
        $sut = DB::table('Users')->where('role', 0)->count();
        $suf = DB::table('invoices')->select('number')
                ->whereNotIn('number', function($query) {$query->select('number')->from('users');})
                ->groupBy('number')
                ->get()
                ->count();

        return [
            'sut' => $sut,
            'suf' => $suf,
        ];
    }

    public static function tttf() {
        return DB::table('accesses')->first();
    }

    public static function invoices_up_stt($id,$stt,$pstt) {
        $date = now()->format('Y-m-d');
        $hoadon = DB::table('invoices')->find($id);
        if ($hoadon->submited == "0000-00-00") {
            $hoadon->where('id', $id)->update([ 'status' => $stt, 'submited' => $date ,'p_status' => $pstt]);
        } 
        else $hoadon->where('id', $id)->update(['status' => $stt, 'p_status' => $pstt]);
    }

    public static function filter($db, $page, $fil, $rcs, $sch, $ord, $target) {
        $table = [
            'bn' => 'banners',
            'br' => 'brands',
            'c1' => 'catalog_1',
            'c2' => 'catalog_2',
            'cm' => 'comments',
            'in' => 'invoices',
            'pd' => 'products',
            'ss' => 'sections',
            'us' => 'users',
            'cp' => 'Vouchers'
        ];

        $rule = [
            'pd' => [
                1 => ['column' => 'id'],
                2 => ['column' => 'saled'],
                3 => ['column' => 'viewed'],
                4 => ['column' => 'id', 'condition' => ['sale' => '!= '.null]],
                5 => ['column' => 'id', 'condition' => ['hidden' => 1]]
            ],
            'us' => [
                1 => ['column' => 'id'],
                2 => ['column' => 'id', 'condition' => ['lock' => 1]],
                3 => ['column' => 'id', 'condition' => ['lock' => 0]],
                4 => ['column' => 'id', 'condition' => ['role' => 0]],
                5 => ['column' => 'id', 'condition' => ['role' => 1]]
            ],
            'in' => [
                1 => ['column' => 'id'],
                2 => ['column' => 'id', 'condition' => ['status' => 'Đang chờ xác nhận']],
                3 => ['column' => 'id', 'condition' => ['status' => 'Chuẩn Bị']],
                4 => ['column' => 'id', 'condition' => ['status' => 'Đang Giao']],
                5 => ['column' => 'id', 'condition' => ['status' => 'Hoàn Thành']],
                6 => ['column' => 'id', 'condition' => ['status' => 'Đã Hủy']]
            ]
        ];

        $query = DB::table($table[$db]);
        if ($sch) $query->where('name', 'like', "%$sch%");

        if (isset($rule[$db])) {
            if (isset($rule[$db][$fil]['condition'])) $query->where($rule[$db][$fil]['condition']);
            $query->orderBy($rule[$db][$fil]['column'], $ord);
        }
        else $query->orderBy('id', 'DESC');

        if ($target == 'list') {
            $query->offset(($page*$rcs)-$rcs)->limit($rcs);
            return $query->get();
        }
        else return ceil($query->count() / $rcs);
    }
}
