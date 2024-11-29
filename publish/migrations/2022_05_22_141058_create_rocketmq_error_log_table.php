<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateRocketmqErrorLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rocketmq_error_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('mq_info')->comment('队列相关信息');
            $table->text('error_msg')->comment('错误信息');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->default(Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->comment('消费错误日志');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mq_error_log');
    }
}
