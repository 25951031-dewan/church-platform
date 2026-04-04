<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('marketplace_categories')
                ->onDelete('set null');
        });

        // Listings
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('condition')->default('good'); // new, like_new, good, fair, poor
            $table->string('status')->default('available'); // available, pending, sold, expired
            
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('church_id')->nullable();

            $table->boolean('is_negotiable')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            
            $table->json('images')->nullable();
            $table->json('specifications')->nullable();
            $table->string('location')->nullable();
            $table->string('contact_method')->default('chat'); // chat, email, phone
            
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('marketplace_categories');
            
            $table->index(['status', 'is_active']);
            $table->index(['category_id', 'status']);
            $table->index('user_id');
        });

        // Offers
        Schema::create('marketplace_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('counter_amount', 10, 2)->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, rejected, countered, withdrawn
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['listing_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Favorites
        Schema::create('marketplace_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at');

            $table->foreign('listing_id')->references('id')->on('marketplace_listings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['listing_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_favorites');
        Schema::dropIfExists('marketplace_offers');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('marketplace_categories');
    }
};
