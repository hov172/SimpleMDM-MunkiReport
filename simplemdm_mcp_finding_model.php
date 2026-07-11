<?php

use munkireport\models\MRModel as Eloquent;

class Simplemdm_mcp_finding_model extends Eloquent
{
    protected $table = 'simplemdm_mcp_finding';

    protected $fillable = [
        'serial_number',
        'source',
        'finding_type',
        'category',
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
     * Deterministic dedup key: same source + serial_number + finding_type +
     * category always maps to the same fingerprint, so repeated ingest
     * pushes upsert the same row instead of creating duplicates. Must stay
     * byte-for-byte identical to the backfill formula in migration
     * 2026_07_09_100000_simplemdm_mcp_finding_category.php. A missing/empty
     * category hashes as '', matching the backfill of every pre-existing
     * row -- this is what keeps dedup behavior unchanged for publishers
     * that don't send category.
     */
    public static function computeFingerprint($source, $serialNumber, $findingType, $category = '')
    {
        return hash(
            'sha256',
            strtolower((string) $source) . '|' . strtolower((string) $serialNumber) . '|' . strtolower((string) $findingType) . '|' . strtolower((string) $category)
        );
    }

    /**
     * Validates and normalizes one raw finding object from an ingest_mcp_findings
     * payload. Returns null for anything that should be skipped (missing/empty
     * finding_type or message) -- mirrors the validation ingest_mcp_findings
     * performed inline before this extraction, byte-for-byte.
     */
    public static function normalizeFinding($finding, $metadataMaxBytes)
    {
        $type = isset($finding['finding_type']) ? trim((string) $finding['finding_type']) : '';
        $message = isset($finding['message']) ? trim((string) $finding['message']) : '';
        if ($type === '' || $message === '') {
            return null;
        }

        $validSeverities = ['danger', 'warning', 'info'];
        $severity = isset($finding['severity']) ? strtolower(trim((string) $finding['severity'])) : 'info';
        if (! in_array($severity, $validSeverities, true)) {
            $severity = 'info';
        }

        $extra = '';
        if (isset($finding['data']) && $finding['data'] !== null && $finding['data'] !== '') {
            $extra = is_string($finding['data']) ? $finding['data'] : json_encode($finding['data']);
            if ($extra === false) {
                $extra = '';
            }
            if (strlen($extra) > $metadataMaxBytes) {
                $extra = substr($extra, 0, $metadataMaxBytes);
            }
        }

        $serialNumber = isset($finding['serial_number']) ? substr(trim((string) $finding['serial_number']), 0, 64) : null;
        $findingType = substr($type, 0, 128);
        $category = isset($finding['category']) ? substr(trim((string) $finding['category']), 0, 128) : null;
        $category = $category === '' ? null : $category;

        return [
            'serial_number' => $serialNumber,
            'category'      => $category,
            'finding_type'  => $findingType,
            'message'       => substr($message, 0, 1000),
            'severity'      => $severity,
            'data'          => $extra,
        ];
    }

    /**
     * Decides what to write when an ingest push matches an existing row by
     * fingerprint, and whether it counts as updated/reopened/unchanged for the
     * ingest_mcp_findings response counters. Does not touch the database --
     * the caller still performs the actual fill()/save().
     */
    public static function computeUpsertUpdate($existing, $normalized, $scanId, $now)
    {
        $wasResolved = $existing->status === self::STATUS_RESOLVED;
        $isSuppressedOrIgnored = in_array($existing->status, [
            self::STATUS_SUPPRESSED,
            self::STATUS_IGNORED,
        ], true);

        $update = [
            'serial_number'    => $normalized['serial_number'],
            'category'         => $normalized['category'],
            'severity'         => $normalized['severity'],
            'message'          => $normalized['message'],
            'data'             => $normalized['data'],
            'reported_at'      => $now,
            'last_seen_at'     => $now,
            'scan_id'          => $scanId,
            'occurrence_count' => $existing->occurrence_count + 1,
        ];

        if ($wasResolved) {
            $update['status'] = self::STATUS_OPEN;
            $update['resolved_at'] = null;
            $kind = 'reopened';
        } elseif (! $isSuppressedOrIgnored) {
            $kind = 'updated';
        } else {
            $kind = 'unchanged';
        }

        return ['update' => $update, 'kind' => $kind];
    }

    /**
     * Parses the id/ids field from an admin-action request body into a
     * deduped list of positive integer ids. Mirrors applyFindingStatusAction's
     * original inline parsing byte-for-byte.
     */
    public static function parseFindingIds($data)
    {
        $rawIds = [];
        if (isset($data['ids']) && is_array($data['ids'])) {
            $rawIds = $data['ids'];
        } elseif (isset($data['id'])) {
            $rawIds = [$data['id']];
        }

        $ids = [];
        foreach ($rawIds as $rawId) {
            if (is_numeric($rawId) && (int) $rawId > 0) {
                $ids[] = (int) $rawId;
            }
        }

        return array_values(array_unique($ids));
    }

    public static function buildStatusUpdate($targetStatus)
    {
        $update = ['status' => $targetStatus];
        $update['resolved_at'] = $targetStatus === self::STATUS_RESOLVED ? gmdate('c') : null;
        return $update;
    }

    /**
     * Splits a comma-separated query-param value (severity/status/category
     * filters) into a trimmed, empty-filtered list. Was duplicated inline
     * verbatim across get_mcp_findings/get_mcp_finding_stats/export_mcp_findings.
     */
    public static function parseMultiValueParam($raw)
    {
        if ($raw === '' || $raw === null) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Map active-finding severity counts to a single MunkiReport Events
     * summary (PRD section 13.1). Returns null when there is nothing worth an
     * event (clear it). Severity model is the module's 3-value taxonomy.
     *
     * @param array $sevCounts   ['danger'=>int,'warning'=>int,'info'=>int]
     * @param int   $warnThreshold warnings needed before a warning-level event
     * @return array|null ['type'=>string,'message'=>string]
     **/
    public static function summarizeFindingsForEvent($sevCounts, $warnThreshold)
    {
        $danger  = max(0, (int) ($sevCounts['danger'] ?? 0));
        $warning = max(0, (int) ($sevCounts['warning'] ?? 0));
        $info    = max(0, (int) ($sevCounts['info'] ?? 0));
        $warnThreshold = max(1, (int) $warnThreshold);

        if ($danger > 0) {
            return [
                'type'    => 'danger',
                'message' => sprintf(
                    'SimpleMDM MCP: %d danger finding%s require%s immediate attention.',
                    $danger, $danger === 1 ? '' : 's', $danger === 1 ? 's' : ''
                ),
            ];
        }
        if ($warning >= $warnThreshold) {
            return [
                'type'    => 'warning',
                'message' => sprintf('SimpleMDM MCP: %d warnings detected across the fleet.', $warning),
            ];
        }
        if ($warning > 0 || $info > 0) {
            return [
                'type'    => 'info',
                'message' => sprintf(
                    'SimpleMDM MCP: informational findings available (%d warnings below threshold, %d info).',
                    $warning, $info
                ),
            ];
        }
        return null;
    }

    /**
     * Rank devices by open-finding weight: 3*danger + 2*warning + 1*info.
     * Ties break danger-count-first, then warning count, then serial asc.
     *
     * @param array $rows  [['serial_number'=>string,'severity'=>string],...]
     * @param int   $limit
     * @return array
     **/
    public static function computeDeviceRiskRows($rows, $limit)
    {
        $devices = [];
        foreach ($rows as $row) {
            $serial = trim((string) ($row['serial_number'] ?? ''));
            if ($serial === '') { continue; }
            $sev = in_array($row['severity'] ?? '', ['danger', 'warning', 'info'], true) ? $row['severity'] : 'info';
            if (! isset($devices[$serial])) {
                $devices[$serial] = ['serial_number' => $serial, 'score' => 0, 'danger' => 0, 'warning' => 0, 'info' => 0];
            }
            $devices[$serial][$sev]++;
            $devices[$serial]['score'] += ($sev === 'danger' ? 3 : ($sev === 'warning' ? 2 : 1));
        }
        $out = array_values($devices);
        usort($out, function ($a, $b) {
            if ($a['score'] !== $b['score']) { return $b['score'] - $a['score']; }
            if ($a['danger'] !== $b['danger']) { return $b['danger'] - $a['danger']; }
            if ($a['warning'] !== $b['warning']) { return $b['warning'] - $a['warning']; }
            return strcmp($a['serial_number'], $b['serial_number']);
        });
        return array_slice($out, 0, max(1, (int) $limit));
    }

    /**
     * Bucket first_seen_at/resolved_at into daily counts for the last $days
     * days ending at $today (UTC date string). Pure and DB-agnostic: dates
     * compare via their ISO-8601 10-char prefix.
     *
     * @param array  $rows [['first_seen_at'=>string,'resolved_at'=>?string],...]
     * @param int    $days
     * @param string $today 'YYYY-MM-DD'
     * @return array ['labels'=>[], 'new'=>[], 'resolved'=>[]]
     **/
    public static function bucketFindingDates($rows, $days, $today)
    {
        $days = max(1, (int) $days);
        $labels = [];
        $base = strtotime($today . 'T00:00:00Z');
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = gmdate('Y-m-d', $base - $i * 86400);
        }
        $index = array_flip($labels);
        $new = array_fill(0, $days, 0);
        $resolved = array_fill(0, $days, 0);
        foreach ($rows as $row) {
            $first = substr((string) ($row['first_seen_at'] ?? ''), 0, 10);
            if (isset($index[$first])) { $new[$index[$first]]++; }
            $res = substr((string) ($row['resolved_at'] ?? ''), 0, 10);
            if ($res !== '' && isset($index[$res])) { $resolved[$index[$res]]++; }
        }
        return ['labels' => $labels, 'new' => $new, 'resolved' => $resolved];
    }
}
