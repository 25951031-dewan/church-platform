<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // social_posts indexes
        if (Schema::hasTable('social_posts')) {
            Schema::table('social_posts', function (Blueprint $table) {
                if (! $this->hasIndex('social_posts', 'social_posts_community_id_created_at_index')) {
                    $table->index(['community_id', 'created_at']);
                }
                if (! $this->hasIndex('social_posts', 'social_posts_church_id_created_at_index')) {
                    $table->index(['church_id', 'created_at']);
                }
            });
        }

        // churches indexes
        if (Schema::hasTable('churches')) {
            Schema::table('churches', function (Blueprint $table) {
                if (! $this->hasIndex('churches', 'churches_status_is_featured_index')) {
                    $table->index(['status', 'is_featured']);
                }
                if (! $this->hasIndex('churches', 'churches_city_country_index')) {
                    $table->index(['city', 'country']);
                }
            });
        }

        // page_views indexes
        if (Schema::hasTable('page_views')) {
            Schema::table('page_views', function (Blueprint $table) {
                if (! $this->hasIndex('page_views', 'page_views_created_at_index')) {
                    $table->index('created_at');
                }
                if (! $this->hasIndex('page_views', 'page_views_url_created_at_index')) {
                    $table->index(['url', 'created_at']);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('social_posts')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->dropIndex(['community_id', 'created_at']);
                $table->dropIndex(['church_id', 'created_at']);
            });
        }

        if (Schema::hasTable('churches')) {
            Schema::table('churches', function (Blueprint $table) {
                $table->dropIndex(['status', 'is_featured']);
                $table->dropIndex(['city', 'country']);
            });
        }

        if (Schema::hasTable('page_views')) {
            Schema::table('page_views', function (Blueprint $table) {
                $table->dropIndex(['created_at']);
                $table->dropIndex(['url', 'created_at']);
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list(`{$table}`)"))
                ->contains(fn ($row) => $row->name === $indexName);
        }

        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($row) => $row->Key_name === $indexName);
    }
};
