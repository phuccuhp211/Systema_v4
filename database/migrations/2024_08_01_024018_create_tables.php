<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accesses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('homet')->default(0);
            $table->bigInteger('homef')->default(0);
            $table->bigInteger('sum')->default(0);
            $table->timestamps();
        });
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->text('img');
            $table->text('tit')->nullable();
            $table->text('ctn')->nullable();
        });
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name',30);
            $table->text('img');
        });
        Schema::create('catalog_1', function (Blueprint $table) {
            $table->id();
            $table->string('name',30);
        });
        Schema::create('catalog_2', function (Blueprint $table) {
            $table->id();
            $table->string('name',30);
            $table->unsignedBigInteger('type');
            $table->text('img')->nullable();
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->unsignedBigInteger('id_pd');
            $table->unsignedBigInteger('id_us');
            $table->date('date')->default(date('Y-m-d'));
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('name',50);
            $table->integer('number');
            $table->string('email',50);
            $table->text('address');
            $table->text('list');
            $table->bigInteger('price');
            $table->string('status',30)->default("Đanh chờ xác nhận");
            $table->boolean('p_status');
            $table->date('created')->default(date('Y-m-d'));
            $table->date('submited')->nullable();
            $table->string('in_num',40);
            $table->bigInteger('shipfee');
            $table->string('coupon',30)->nullable();
            $table->bigInteger('offers')->nullable();
            $table->string('method',10);
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name',30);
            $table->text('img');
            $table->text('info');
            $table->text('detail')->nullable();
            $table->unsignedBigInteger('id_cata_1');
            $table->unsignedBigInteger('id_cata_2')->nullable();
            $table->unsignedBigInteger('id_brand');
            $table->integer('price');
            $table->integer('sale')->nullable();
            $table->date('f_date')->nullable();
            $table->date('t_date')->nullable();
            $table->integer('viewed')->default(0);
            $table->integer('saled')->default(0);
            $table->boolean('hidden')->default(0);
        });
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pd');
            $table->integer('stars');
            $table->integer('turns');
        });
        Schema::create('turn_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pd');
            $table->unsignedBigInteger('id_us');
            $table->integer('stars');
        });
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name',40);
            $table->text('poster')->nullable();
            $table->text('eb_img');
            $table->unsignedBigInteger('id_cata_1')->nullable();
            $table->unsignedBigInteger('id_cata_2')->nullable();
            $table->integer('index');
            $table->string('reference',10);
            $table->boolean('orderby');
        });
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('name',30);
            $table->integer('amount');
            $table->integer('remaining');
            $table->date('f_date')->nullable()->default(date('Y-m-d'));
            $table->date('t_date')->nullable()->default(date('Y-m-d'));
            $table->string('type',15);
            $table->float('discount', precision: 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
