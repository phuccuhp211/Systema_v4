<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Users';
    protected $primaryKey = 'id';
    protected $fillable = [ 'account', 'pass', 'name', 'email', 'address', 'number', 'img', 'cart', 'role', 'permission', 'lock' ];
    public $timestamps = true;

    public static function get_us($name) {
        return self::where([['account', $name],['role', 0]])->first();
    }
    public static function get_em($mail) {
        return self::where('email', $mail)->first();
    }
    public static function get_pn($phone) {
        return self::where([['number', $phone],['role', 0]])->first();
    }

    public static function get_ad($name) {
        return self::where([['account', $name],['role', 1]])->first();
    }

    public static function add($user,$pass,$name,$email,$addr,$phone,$role=0,$lock=0) {
        self::create([
            'account' => $user,
            'pass' => $pass,
            'name' => $name,
            'email' => $email,
            'address' => $addr,
            'number' => $phone,
            'role' => $role,
            'lock' => $lock
        ]);
    }

    public static function fix($id,$user=null,$pass=null,$name=null,$email=null,$addr=null,$phone=null,$role=null,$lock=null) {
        $dt_update = [ ];

        if ($user != null) $dt_update['account'] = $user;
        if ($pass != null) $dt_update['pass'] = $pass;
        if ($lname != null) $dt_update['name'] = $name;
        if ($email != null) $dt_update['email'] = $email;
        if ($addr != null) $dt_update['address'] = $addr;
        if ($phone != null) $dt_update['number'] = $phone;
        if ($role != null) $dt_update['role'] = $role;
        if ($lock != null) $dt_update['lock'] = $lock;

        self::where('id', $id)->update($dt_update);
    }

    public static function del($id) {
        self::destroy($id);
    }

    public static function newpw($mail,$pass) {
        self::where('email',$mail)->update(['pass' => $pass]);
    }
}