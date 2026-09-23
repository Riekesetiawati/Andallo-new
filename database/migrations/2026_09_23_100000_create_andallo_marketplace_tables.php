<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->string('role', 32)->default('customer')->index()->after('password');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('business_name');
            $table->string('whatsapp', 20);
            $table->text('description')->nullable();
            $table->text('portfolio_summary')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('district')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('service_area')->nullable();
            $table->string('verification_status', 32)->default('draft')->index();
            $table->text('revision_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_demo')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('demo_rating', 3, 2)->nullable();
            $table->unsignedInteger('demo_review_count')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_id')->nullable()->unique()->constrained('providers')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE profiles ADD CONSTRAINT profiles_one_owner CHECK ((user_id IS NOT NULL) + (provider_id IS NOT NULL) = 1)');
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 32);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('price_unit', 40);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->boolean('requires_visit')->default(true);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_demo')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('demo_rating', 3, 2)->nullable();
            $table->unsignedInteger('demo_review_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['provider_id', 'slug']);
            $table->index(['category_id', 'is_active']);
            $table->index('price');
        });

        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->date('specific_date')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->index(['provider_id', 'weekday']);
            $table->index(['provider_id', 'specific_date']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('city');
            $table->text('address');
            $table->text('customer_notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('service_name_snapshot');
            $table->decimal('price_snapshot', 12, 2);
            $table->string('price_unit_snapshot', 40);
            $table->unsignedInteger('duration_snapshot');
            $table->text('includes_snapshot')->nullable();
            $table->text('excludes_snapshot')->nullable();
            $table->boolean('requires_visit_snapshot')->default(true);
            $table->decimal('total', 12, 2);
            $table->string('status', 40)->index();
            $table->string('payment_status', 40)->default('unpaid')->index();
            $table->string('refund_status', 40)->default('not_required')->index();
            $table->timestamp('provider_response_deadline')->nullable();
            $table->timestamp('payment_deadline')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('provider_note')->nullable();
            $table->text('cancellation_policy_snapshot')->nullable();
            $table->string('whatsapp_dispatch_status', 32)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['provider_id', 'scheduled_date']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('booking_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->string('from_payment_status', 40)->nullable();
            $table->string('to_payment_status', 40)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 32)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('provider_slot_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
            $table->index(['provider_id', 'starts_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 32)->default('manual_transfer');
            $table->string('status', 40)->index();
            $table->string('proof_path')->nullable();
            $table->string('proof_original_name')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('review_due_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('proof_version')->default(0);
            $table->timestamps();
        });

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('status', 32);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('stage', 40);
            $table->text('reason');
            $table->string('status', 40)->index();
            $table->decimal('refund_estimate', 12, 2)->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('cancellation_request_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->string('status', 40)->index();
            $table->foreignId('responsible_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('proof_path')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('status', 32)->default('open')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 32);
            $table->string('recipient', 32)->nullable();
            $table->string('template', 64);
            $table->text('body');
            $table->string('status', 32)->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
        });

        Schema::create('action_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 32);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('action_tokens');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('cancellation_requests');
        Schema::dropIfExists('payment_proofs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('provider_slot_reservations');
        Schema::dropIfExists('booking_status_histories');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('services');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('providers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'role', 'address', 'is_active']);
        });
    }
};
