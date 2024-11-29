<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateRocketmqConsumeLogTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rocketmq_consume_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('topic', 100);
            $table->string('message_tag', 50)->nullable();
            $table->string('message_key', 40)->nullable();
            $table->string('message_id');
            $table->text('payload');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->default(Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->index('message_key', 'idx_msg_key');
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
