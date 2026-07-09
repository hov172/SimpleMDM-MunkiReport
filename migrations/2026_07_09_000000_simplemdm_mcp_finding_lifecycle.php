<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmMcpFindingLifecycle extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->string('fingerprint')->nullable()->after('finding_type');
            $table->string('status')->default('open')->index()->after('severity');
            $table->unsignedInteger('occurrence_count')->default(1)->after('status');
            $table->string('scan_id')->nullable()->index()->after('occurrence_count');
            $table->dateTime('first_seen_at')->nullable()->after('reported_at');
            $table->dateTime('last_seen_at')->nullable()->after('first_seen_at');
            $table->dateTime('resolved_at')->nullable()->after('last_seen_at');
        });

        // Backfill existing rows so the new unique index has valid, deterministic
        // data. Must match Simplemdm_mcp_finding_model::computeFingerprint()
        // exactly (Task 2) or future pushes will fail to match these rows.
        $rows = Capsule::table('simplemdm_mcp_finding')
            ->select('id', 'source', 'serial_number', 'finding_type', 'reported_at')
            ->get();
        foreach ($rows as $row) {
            $fingerprint = hash(
                'sha256',
                strtolower((string) $row->source) . '|' . strtolower((string) $row->serial_number) . '|' . strtolower((string) $row->finding_type)
            );
            $seenAt = $row->reported_at ?: gmdate('c');
            Capsule::table('simplemdm_mcp_finding')->where('id', $row->id)->update([
                'fingerprint'      => $fingerprint,
                'status'           => 'open',
                'occurrence_count' => 1,
                'first_seen_at'    => $seenAt,
                'last_seen_at'     => $seenAt,
            ]);
        }

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->unique(['source', 'fingerprint'], 'uniq_simplemdm_mcp_finding_source_fingerprint');
        });
    }

    public function down()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();
        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->dropUnique('uniq_simplemdm_mcp_finding_source_fingerprint');
            $table->dropColumn(['fingerprint', 'status', 'occurrence_count', 'scan_id', 'first_seen_at', 'last_seen_at', 'resolved_at']);
        });
    }
}
