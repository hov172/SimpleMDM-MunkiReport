<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Capsule\Manager as Capsule;

class SimplemdmMcpFindingCategory extends Migration
{
    public function up()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();

        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->string('category')->nullable()->after('finding_type');
        });

        // Recompute fingerprints so the new category dimension is reflected.
        // Every existing row has no category, so this hashes against ''
        // for all of them -- byte-for-byte identical to what a future
        // ingest push without a category will also hash against, per
        // Simplemdm_mcp_finding_model::computeFingerprint()'s 4th
        // parameter default. This is what keeps existing dedup behavior
        // unchanged for publishers that don't send category.
        $rows = Capsule::table('simplemdm_mcp_finding')
            ->select('id', 'source', 'serial_number', 'finding_type')
            ->get();
        foreach ($rows as $row) {
            $fingerprint = hash(
                'sha256',
                strtolower((string) $row->source) . '|' . strtolower((string) $row->serial_number) . '|' . strtolower((string) $row->finding_type) . '|'
            );
            Capsule::table('simplemdm_mcp_finding')->where('id', $row->id)->update([
                'fingerprint' => $fingerprint,
            ]);
        }
    }

    public function down()
    {
        $capsule = new Capsule();
        $schema = $capsule::schema();
        $schema->table('simplemdm_mcp_finding', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
}
