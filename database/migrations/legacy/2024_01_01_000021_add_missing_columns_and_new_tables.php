<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- Add missing columns to existing tables ---

        // Prayer Requests: add description, phone, is_urgent
        if (Schema::hasTable('prayer_requests')) {
            Schema::table('prayer_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('prayer_requests', 'description')) {
                    $table->text('description')->nullable()->after('request');
                }
                if (!Schema::hasColumn('prayer_requests', 'phone')) {
                    $table->string('phone', 20)->nullable()->after('email');
                }
                if (!Schema::hasColumn('prayer_requests', 'is_urgent')) {
                    $table->boolean('is_urgent')->default(false)->after('is_anonymous');
                }
            });
        }

        // Sermons: add is_published, duration, thumbnail, tags
        if (Schema::hasTable('sermons')) {
            Schema::table('sermons', function (Blueprint $table) {
                if (!Schema::hasColumn('sermons', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('sermons', 'duration')) {
                    $table->string('duration', 20)->nullable()->after('duration_minutes');
                }
                if (!Schema::hasColumn('sermons', 'thumbnail')) {
                    $table->string('thumbnail')->nullable()->after('image');
                }
                if (!Schema::hasColumn('sermons', 'tags')) {
                    $table->text('tags')->nullable()->after('meta_description');
                }
            });
        }

        // Books: add publisher, is_free, is_published
        if (Schema::hasTable('books')) {
            Schema::table('books', function (Blueprint $table) {
                if (!Schema::hasColumn('books', 'publisher')) {
                    $table->string('publisher')->nullable()->after('isbn');
                }
                if (!Schema::hasColumn('books', 'is_free')) {
                    $table->boolean('is_free')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('books', 'is_published')) {
                    $table->boolean('is_published')->default(true)->after('is_active');
                }
                if (!Schema::hasColumn('books', 'tags')) {
                    $table->text('tags')->nullable()->after('meta_description');
                }
            });
            // Make pdf_file nullable for books without PDF
            if (Schema::hasColumn('books', 'pdf_file')) {
                Schema::table('books', function (Blueprint $table) {
                    $table->string('pdf_file')->nullable()->change();
                });
            }
        }

        // Bible Studies: add difficulty, is_published, cover_image, attachment, author, tags
        if (Schema::hasTable('bible_studies')) {
            Schema::table('bible_studies', function (Blueprint $table) {
                if (!Schema::hasColumn('bible_studies', 'difficulty')) {
                    $table->string('difficulty')->nullable()->after('difficulty_level');
                }
                if (!Schema::hasColumn('bible_studies', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('bible_studies', 'cover_image')) {
                    $table->string('cover_image')->nullable()->after('image');
                }
                if (!Schema::hasColumn('bible_studies', 'attachment')) {
                    $table->string('attachment')->nullable()->after('pdf_attachment');
                }
                if (!Schema::hasColumn('bible_studies', 'author')) {
                    $table->string('author')->nullable()->after('author_id');
                }
                if (!Schema::hasColumn('bible_studies', 'tags')) {
                    $table->text('tags')->nullable()->after('meta_description');
                }
            });
        }

        // Contact Messages: add read_at, reply_message
        if (Schema::hasTable('contact_messages')) {
            Schema::table('contact_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('contact_messages', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('is_read');
                }
                if (!Schema::hasColumn('contact_messages', 'reply_message')) {
                    $table->text('reply_message')->nullable()->after('admin_reply');
                }
            });
        }

        // Newsletter Subscribers: add subscribed_at, unsubscribed_at
        if (Schema::hasTable('newsletter_subscribers')) {
            Schema::table('newsletter_subscribers', function (Blueprint $table) {
                if (!Schema::hasColumn('newsletter_subscribers', 'subscribed_at')) {
                    $table->timestamp('subscribed_at')->nullable()->after('token');
                }
                if (!Schema::hasColumn('newsletter_subscribers', 'unsubscribed_at')) {
                    $table->timestamp('unsubscribed_at')->nullable()->after('subscribed_at');
                }
            });
        }

        // Users: add role_id
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role_id')) {
                    $table->unsignedBigInteger('role_id')->nullable()->after('is_admin');
                }
                if (!Schema::hasColumn('users', 'provider')) {
                    $table->string('provider')->nullable()->after('remember_token');
                }
                if (!Schema::hasColumn('users', 'provider_id')) {
                    $table->string('provider_id')->nullable()->after('provider');
                }
            });
        }

        // Posts: add parent_id, template, page_type for pages support
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (!Schema::hasColumn('posts', 'page_type')) {
                    $table->string('page_type')->default('post')->after('status');
                }
                if (!Schema::hasColumn('posts', 'parent_id')) {
                    $table->unsignedBigInteger('parent_id')->nullable()->after('page_type');
                }
                if (!Schema::hasColumn('posts', 'template')) {
                    $table->string('template')->nullable()->after('parent_id');
                }
                if (!Schema::hasColumn('posts', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('category');
                }
                if (!Schema::hasColumn('posts', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('view_count');
                }
            });
        }

        // --- Create new tables ---

        // Announcements
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content')->nullable();
                $table->enum('type', ['general', 'urgent', 'event', 'blog'])->default('general');
                $table->string('link')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // Categories (for posts, pages, sermons, books, etc.)
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type')->default('post'); // post, page, sermon, book, bible-study
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            });
        }

        // Menus
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('location')->default('header'); // header, footer, sidebar
                $table->json('items')->nullable(); // JSON array of menu items
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Roles
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->json('permissions')->nullable(); // JSON array of permission strings
                $table->timestamps();
            });
        }

        // Pages (separate from posts for cleaner management)
        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content')->nullable();
                $table->text('excerpt')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('template')->nullable();
                $table->enum('status', ['draft', 'published'])->default('draft');
                $table->string('featured_image')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->integer('sort_order')->default(0);
                $table->unsignedBigInteger('author_id')->nullable();
                $table->timestamps();

                $table->foreign('parent_id')->references('id')->on('pages')->nullOnDelete();
                $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('announcements');

        // Remove added columns (reverse order)
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                $cols = ['page_type', 'parent_id', 'template', 'category_id', 'sort_order'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('posts', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $cols = ['role_id', 'provider', 'provider_id'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
