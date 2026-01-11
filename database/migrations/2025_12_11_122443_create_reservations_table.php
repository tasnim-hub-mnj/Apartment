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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();//عند حذف الشقة تبقى الجوزات المرتبطة فيها عند صاحب الحجز
            $table->enum('status',['confirmed','cancelled','finished'])->default('confirmed');
            $table->enum('approv_status_reserv',['pending','approved','rejected'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('pay_method',['card','cash']);
            $table->string('card_number')->nullable();
            $table->enum('status_pay',['unpaid','paid'])->default('unpaid');
            $table->decimal('required_amount',10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
