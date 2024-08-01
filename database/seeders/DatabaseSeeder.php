<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;

class add_full extends Seeder
{
    public function run()
    {
        function get_cat($id) {
            return DB::table('catalog_1')
                ->join('catalog_2', 'catalog_1.id', '=', 'catalog_2.type')
                ->select('catalog_2.id')
                ->where('catalog_1.id', $id)
                ->pluck('id')
                ->toArray();
        }

        for ($i = 1; $i <= 10; $i++) {
            DB::table('catalog_1')->insert([
                'name' => 'DM Cấp 1 số ' . $i
            ]);
        }

        for ($i = 1; $i <= 30; $i++) {
            DB::table('catalog_2')->insert([
                'name' => 'DM Cấp 2 số ' . $i,
                'type' => mt_rand(1, 10),
                'img' => ''
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            DB::table('brands')->insert([
                'name' => 'Thương hiệu số ' . $i,
                'img' => ''
            ]);
        }

        for ($i = 1; $i <= 500; $i++) {
            $cat1 = mt_rand(1, 10);
            $cat2 = get_cat($cat1);
            if (empty($cat2)) {
                $cat2 = [null];
            }
            $pri1 = mt_rand(100000, 170000000);
            $pri2 = mt_rand(0, $pri1 - 90000);
            DB::table('products')->insert([
                'name' => 'Sản Phẩm số ' . $i,
                'img' => 'unko.jpg',
                'info' => 'Đây là tóm tắt SP số ' . $i,
                'detail' => 'Đây là chi tiết SP số ' . $i,
                'id_cata_1' => $cat1,
                'id_cata_2' => Arr::random($cat2),
                'id_brand' => mt_rand(1, 10),
                'price' => $pri1,
                'sale' => $pri2,
                'viewed' => mt_rand($i, 10000),
                'saled' => mt_rand($i, 10000),
                'hidden' => mt_rand(0, 1)
            ]);
        }

        $ref = ['id', 'name', 'price', 'sale', 'viewed', 'saled'];
        for ($i = 1; $i <= 4; $i++) {
            $cat1 = Arr::random([null, mt_rand(1, 10)]);
            $cat2 = $cat1 ? null : mt_rand(1, 30);
            $pt = mt_rand(0, 4);
            $eb = mt_rand(1, 4);
            $name = $cat1 ? "Ms1 với id= $cat1" : "Ms2 với id= $cat2";
            DB::table('sections')->insert([
                'name' => "SS tham chiếu $name",
                'poster' => ($pt != 0) ? "pt$pt.webp" : "",
                'eb_img' => "rd$eb.jpg",
                'id_cata_1' => $cat1,
                'id_cata_2' => $cat2,
                'index' => $i,
                'reference' => Arr::random($ref),
                'orderby' => mt_rand(1, 3)
            ]);
        }

        for ($i = 1; $i <= 4; $i++) {
            DB::table('banners')->insert([
                'img' => "banner$i.jpg",
                'tit' => "Tiêu đề $i",
                'ctn' => "Nội dung của banner số $i"
            ]);
        }

        $h = ['Nguyễn', 'Lê', 'Đinh', 'Võ', 'Hoàng', 'Phạm', 'Lý', 'Bùi'];
        $d = ['Thị', 'Văn', 'Anh', 'Hoàng', 'Mỹ', 'Thanh', 'Ngọc', 'Gia'];
        $t = ['Hoa', 'Kỳ', 'Tuấn', 'Trúc', 'Thanh', 'Hiền', 'An', 'Kiệt'];
        DB::table('users')->insert([
            'account' => 'root',
            'pass' => Hash::make('hihi'),
            'name' => 'Quản Lý',
            'email' => Str::random(5) . '@gmail.com',
            'address' => 'Tại Website',
            'number' => '999999999',
            'role' => 1,
            'permission' => 'Admin',
            'lock' => 0
        ]);
        for ($i = 2; $i <= 25; $i++) {
            DB::table('users')->insert([
                'account' => 'usernumber' . $i,
                'pass' => Hash::make('hehe'),
                'name' => Arr::random($h) . ' ' . Arr::random($d). ' ' .Arr::random($t),
                'email' => Str::random(5) . '@gmail.com',
                'address' => $i . ' Quốc lộ 1A',
                'number' => mt_rand(100000000, 999999999),
                'role' => 0,
                'lock' => mt_rand(0, 1)
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            $amount = mt_rand(100, 200);
            $type = Arr::random(['number', 'percent']);
            $discount = $type == 'number' ? mt_rand(50000, 500000) : mt_rand(1, 15);
            $f_date = Carbon::now()->addDays(mt_rand(-20, 10));
            $t_date = $f_date->copy()->addDays(mt_rand(-10, 20));
            DB::table('vouchers')->insert([
                'name' => Str::random(15),
                'amount' => $amount,
                'remaining' => $amount,
                'f_date' => $f_date,
                't_date' => $t_date,
                'type' => $type,
                'discount' => $discount
            ]);
        }

        $rating = [];
        $turnrt = [];
        for ($i = 1; $i <= 250; $i++) {
            $id_pd = mt_rand(1, 500);
            $id_us = mt_rand(2, 25);
            $stars = mt_rand(1, 5);

            // Lưu đánh giá của từng người dùng
            $turnrt[] = [
                'id_pd' => $id_pd,
                'id_us' => $id_us,
                'stars' => $stars
            ];

            // Kiểm tra và cập nhật mảng $rating
            if (!isset($rating[$id_pd])) {
                $rating[$id_pd] = [
                    'id_pd' => $id_pd,
                    'stars' => 0,
                    'turns' => 0
                ];
            }

            // Cập nhật tổng số sao và số lượt đánh giá cho sản phẩm
            $rating[$id_pd]['turns'] += 1;
            $rating[$id_pd]['stars'] += $stars;
        }

        $rating = array_values($rating);
        DB::table('ratings')->insert($rating);
        DB::table('turn_ratings')->insert($turnrt);

        /*comment*/
        for ($i = 1; $i <= 500; $i++) {
            $id_pd = DB::table('products')->inRandomOrder()->first()->id ?? 1;
            $id_us = DB::table('users')->inRandomOrder()->first()->id ?? 2;
            DB::table('comments')->insert([
                'content' => 'Nội dung bình luận ' . $i,
                'id_pd' => $id_pd,
                'id_us' => $id_us,
                'date' => Carbon::now()->addDays(mt_rand(-20, 10)),
            ]);
        }
    }
}
