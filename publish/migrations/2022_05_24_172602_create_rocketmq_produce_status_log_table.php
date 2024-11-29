<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateRocketmqProduceStatusLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rocketmq_produce_status_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('topic', 100);
            $table->string('message_tag', 50)->nullable();
            $table->string('message_key', 40);
            $table->tinyInteger('status')->default(1)->comment('状态 1:待发送 2:发送中 3:已发送');
            $table->text('payload');
            $table->unsignedInteger('retry_num')->default(0);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->default(Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->index('message_key', 'idx_msg_key');
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
