<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddForeignKeyToSemestersTable extends AbstractMigration
{
    /**
     * academic_sessions.id is int UNSIGNED. semesters.academic_session_id must be the same
     * (signed int vs int unsigned → MySQL error 3780 incompatible columns).
     */
    public function up(): void
    {
        $semesters = $this->table('semesters');

        if ($semesters->hasForeignKey(['academic_session_id'])) {
            $semesters->dropForeignKey(['academic_session_id'])->update();
        }

        $semesters
            ->changeColumn('academic_session_id', 'integer', [
                'signed' => false,
                'null' => false,
            ])
            ->update();

        $semesters
            ->addForeignKey(
                'academic_session_id',
                'academic_sessions',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                ]
            )
            ->update();
    }

    public function down(): void
    {
        $semesters = $this->table('semesters');

        if ($semesters->hasForeignKey(['academic_session_id'])) {
            $semesters->dropForeignKey(['academic_session_id'])->update();
        }
    }
}
