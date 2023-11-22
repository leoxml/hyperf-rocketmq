<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateRocketmqProduceStatusLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rocketmq_produce_status_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('status')->default(1)->comment('状态。1：待发送；2：发送中；3：已发送');
            $table->string('message_key', 40)->comment('消息唯一标识');
            $table->text('mq_info')->comment('队列相关信息');
            $table->unsignedInteger('retry_num')->default(0)->comment('重试次数');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->default(\Hyperf\DbConnection\Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->index('message_key', 'idx_message_key');
            $table->index(['updated_at', 'status', 'retry_num'], 'idx_usr');

            $table->comment('消息生产状态表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mq_produce_status_log');
    }
}
