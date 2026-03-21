<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSettingsTable extends AbstractMigration
{
    public function change(): void
    {
        if ($this->hasTable('settings')) {
            return;
        }

        $table = $this->table('settings', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('category', 'string', ['limit' => 100])
            ->addColumn('setting_key', 'string', ['limit' => 255])
            ->addColumn('setting_value', 'text')
            ->addColumn('data_type', 'enum', [
                'values' => ['string', 'integer', 'boolean', 'json', 'array'],
                'default' => 'string',
            ])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('updated_by', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['setting_key'], ['unique' => true])
            ->addIndex(['category'])
            ->addForeignKey('updated_by', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
            ->create();

        $defaults = [
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_r',
                'setting_value' => '255',
                'data_type' => 'integer',
                'description' => 'Target passport background red channel (0–255)',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_g',
                'setting_value' => '0',
                'data_type' => 'integer',
                'description' => 'Target passport background green channel (0–255)',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_bg_color_b',
                'setting_value' => '0',
                'data_type' => 'integer',
                'description' => 'Target passport background blue channel (0–255)',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_tolerance',
                'setting_value' => '120',
                'data_type' => 'integer',
                'description' => 'Euclidean color distance tolerance for background match (0–441)',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_min_width',
                'setting_value' => '300',
                'data_type' => 'integer',
                'description' => 'Minimum image width in pixels',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_min_height',
                'setting_value' => '400',
                'data_type' => 'integer',
                'description' => 'Minimum image height in pixels',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_skip_ratio',
                'setting_value' => '1',
                'data_type' => 'boolean',
                'description' => 'When true, aspect ratio is not enforced (only minimum dimensions)',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_aspect_ratio',
                'setting_value' => '7:9',
                'data_type' => 'string',
                'description' => 'Required width:height ratio when aspect check is enabled',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_match_percentage',
                'setting_value' => '60',
                'data_type' => 'integer',
                'description' => 'Minimum percentage of edge samples matching background color',
            ],
            [
                'category' => 'image_validation',
                'setting_key' => 'image_validation.passport_edge_sample_divisor',
                'setting_value' => '100',
                'data_type' => 'integer',
                'description' => 'Edge sampling step uses min(width,height) / this value (higher = fewer samples)',
            ],
        ];

        $this->table('settings')->insert($defaults)->save();
    }
}
