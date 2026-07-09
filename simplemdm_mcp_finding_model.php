<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_mcp_finding_model extends Eloquent
{
    protected $table = 'simplemdm_mcp_finding';

    protected $fillable = [
        'serial_number',
        'source',
        'finding_type',
        'fingerprint',
        'severity',
        'status',
        'occurrence_count',
        'scan_id',
        'message',
        'data',
        'reported_at',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    public $timestamps = false;

    const STATUS_OPEN = 'open';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    const STATUS_SUPPRESSED = 'suppressed';

    const ACTIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_ACKNOWLEDGED, self::STATUS_IN_PROGRESS];

    /**
     * Deterministic dedup key: same source + serial_number + finding_type
     * always maps to the same fingerprint, so repeated ingest pushes upsert
     * the same row instead of creating duplicates. Must stay byte-for-byte
     * identical to the backfill formula in migration
     * 2026_07_09_000000_simplemdm_mcp_finding_lifecycle.php.
     */
    public static function computeFingerprint($source, $serialNumber, $findingType)
    {
        return hash(
            'sha256',
            strtolower((string) $source) . '|' . strtolower((string) $serialNumber) . '|' . strtolower((string) $findingType)
        );
    }
}
