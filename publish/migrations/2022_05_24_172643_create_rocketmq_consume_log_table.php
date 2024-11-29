<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateRocketmqConsumeLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rocketmq_consume_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('topic', 100)->comment('topic');
            $table->string('message_key', 40)->nullable()->comment('message_key');
            $table->string('message_tag', 50)->comment('message_tag');
            $table->string('message_id')->comment('message_id');
            $table->text('payload')->comment('payload');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->default(\Hyperf\DbConnection\Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新时间');

            $table->comment('消费记录');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mq_consume_log');
    }
}
