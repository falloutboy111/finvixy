<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned knowledge blocks injected into the agent's system prompt.
 * DB-backed (not a config file) so content is editable without a redeploy —
 * bump `version` on every edit; the service cache keys on it.
 *
 * Seeds the SA retailer + product-normalisation block used by the WhatsApp
 * agent and the price-check tool.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true);
            $table->json('content');
            $table->timestamps();
        });

        DB::table('knowledge_blocks')->insert([
            'key'        => 'sa_retail',
            'version'    => 1,
            'active'     => true,
            'created_at' => now(),
            'updated_at' => now(),
            'content'    => json_encode([
                // Merchants that sell directly — prices are actionable.
                'retailers' => [
                    ['name' => 'Checkers',            'aka' => ['Sixty60', 'Checkers Sixty60'], 'notes' => 'Shoprite Group premium banner; Sixty60 is its delivery app'],
                    ['name' => 'Woolworths',          'aka' => ['Woolies'],                     'notes' => 'Premium food retailer'],
                    ['name' => 'Pick n Pay',          'aka' => ['PnP', 'asap!'],                'notes' => 'asap! is its delivery app'],
                    ['name' => 'Spar',                'aka' => ['SUPERSPAR', 'KWIKSPAR'],       'notes' => 'Franchise — prices vary by store'],
                    ['name' => 'Shoprite',            'aka' => [],                              'notes' => 'Value banner of the Shoprite Group'],
                    ['name' => 'Makro',               'aka' => [],                              'notes' => 'Bulk/wholesale; often multipack pricing'],
                    ['name' => 'Game',                'aka' => [],                              'notes' => 'General merchandise and electronics'],
                    ['name' => "Food Lover's Market", 'aka' => ['Food Lovers'],                 'notes' => 'Fresh produce focus'],
                ],
                // Comparison/aggregator sites — prices are indicative only and
                // must be labelled as such, never presented as a store price.
                'aggregators' => [
                    ['name' => 'Troli',        'notes' => 'Grocery price-comparison site'],
                    ['name' => 'PriceCheck',   'notes' => 'General price-comparison site'],
                    ['name' => 'CompareGuru',  'notes' => 'Comparison site'],
                ],
                // Receipt-label → searchable-term hints. Keys are matched
                // case-insensitively as whole words in receipt item names.
                'normalisations' => [
                    'RC'         => 'rice cakes',
                    'F/C'        => 'full cream',
                    'L/F'        => 'low fat',
                    'FF'         => 'fat free',
                    'UHT'        => 'long life milk',
                    'CHKN'       => 'chicken',
                    'BF'         => 'beef',
                    'S/MEAL'     => 'ready meal',
                    'VEG'        => 'vegetables',
                    'W/W'        => 'whole wheat',
                    'DBL CRM'    => 'double cream',
                    'YOG'        => 'yoghurt',
                    'MARG'       => 'margarine',
                    'POLONY'     => 'polony',
                    'DRY WORS'   => 'droëwors',
                    'BILTONG'    => 'biltong',
                ],
                // Brand → category mappings so bare brand labels map to a
                // searchable product term.
                'brands' => [
                    'Douglasdale'  => 'milk',
                    'Clover'       => 'dairy',
                    'Albany'       => 'bread',
                    'Sasko'        => 'bread',
                    'Jungle'       => 'oats',
                    'Koo'          => 'canned food',
                    'Lucky Star'   => 'canned fish',
                    'Rainbow'      => 'chicken',
                    'Eskort'       => 'pork',
                    'Kellogg\'s'   => 'cereal',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_blocks');
    }
};
