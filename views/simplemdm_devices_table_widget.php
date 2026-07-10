<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-devices-table-widget .simplemdm-devices-table-scroll {
    max-height: 340px;
    overflow-y: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    /* Vertical wheel scrolling for this container is JS-driven -- see the
       bindWheelScroll block in simplemdm_widget_modern_assets.php. */
    border: 1px solid var(--simplemdm-border);
    border-radius: 11px;
    background: var(--simplemdm-surface);
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini {
    min-width: 760px;
    table-layout: fixed;
    width: 100%;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th,
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 8px 10px;
    border-bottom: 1px solid var(--simplemdm-border);
    vertical-align: middle;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini thead th {
    background: var(--simplemdm-surface-alt);
    color: var(--simplemdm-muted);
    font-weight: 800;
    position: sticky;
    top: 0;
    z-index: 1;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini tbody tr:last-child td {
    border-bottom: 0;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini tbody tr:hover td {
    background: var(--simplemdm-surface-hover);
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th:nth-child(1),
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td:nth-child(1) {
    width: 270px;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th:nth-child(2),
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td:nth-child(2) {
    width: 170px;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th:nth-child(3),
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td:nth-child(3) {
    width: 95px;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th:nth-child(4),
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td:nth-child(4) {
    width: 85px;
}

#simplemdm-devices-table-widget #simplemdm-devices-table-mini th:nth-child(5),
#simplemdm-devices-table-widget #simplemdm-devices-table-mini td:nth-child(5) {
    width: 180px;
}

#simplemdm-devices-table-widget .simplemdm-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

#simplemdm-devices-table-widget .simplemdm-section-title {
    color: var(--simplemdm-ink);
    font-weight: 800;
}

#simplemdm-devices-table-widget .simplemdm-section-toggle {
    margin-left: auto;
    flex: 0 0 auto;
}
</style>

<div class="col-lg-8 col-md-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-devices-table-widget">
        <div class="panel-heading" data-widget="simplemdm_devices_table">
            <h3 class="panel-title">
                <i class="fa fa-list"></i>
                <span>SimpleMDM Devices Table</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-list-pills" style="margin-bottom:8px;">
                <span class="simplemdm-device-chip"><strong>Total Devices:</strong>&nbsp;<span id="simplemdm-devices-table-total">-</span></span>
            </div>
            <div class="simplemdm-section">
                <div class="simplemdm-section-head">
                    <div class="simplemdm-section-title">Device Rows</div>
                    <button type="button" class="btn btn-xs btn-default simplemdm-section-toggle" data-target="#simplemdm-devices-table-body"><i class="fa fa-plus"></i> Expand</button>
                </div>
                <div id="simplemdm-devices-table-body" class="simplemdm-section-body simplemdm-collapsed">
                    <div class="simplemdm-table-wrap simplemdm-devices-table-scroll">
                        <table class="simplemdm-table-modern" id="simplemdm-devices-table-mini">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Serial</th>
                                    <th>Status</th>
                                    <th>OS</th>
                                    <th>Group</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-muted">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div style="margin-top:10px;">
                <a class="btn btn-xs btn-primary" id="simplemdm-open-devices-table-full" href="#">Open Full Device Listing</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-devices-table-widget';
    var rowsCollapsed = true;
    function listingUrl(path, query) {
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    $(widgetId + ' #simplemdm-open-devices-table-full').attr('href', listingUrl('/show/listing/simplemdm/simplemdm'));

    function applyRowsState() {
        var body = $(widgetId + ' #simplemdm-devices-table-body');
        var scroll = body.find('.simplemdm-devices-table-scroll');
        var rows = body.find('#simplemdm-devices-table-mini tbody tr');
        var btn = $(widgetId + ' .simplemdm-section-toggle[data-target="#simplemdm-devices-table-body"]');
        if (!body.length || !scroll.length || !btn.length) {
            return;
        }
        body.toggleClass('simplemdm-collapsed', rowsCollapsed);
        body.attr('data-collapsed', rowsCollapsed ? '1' : '0');
        scroll.css({
            maxHeight: rowsCollapsed ? '340px' : 'none',
            height: rowsCollapsed ? '340px' : 'auto',
            overflowY: rowsCollapsed ? 'auto' : 'visible',
            overflowX: 'auto',
            display: 'block'
        });
        if (rowsCollapsed) {
            var visibleHeight = scroll.innerHeight();
            var visibleCount = 0;
            rows.each(function() {
                var $row = $(this);
                var bottom = Math.round($row.position().top + $row.outerHeight(true));
                if (bottom <= visibleHeight + 1) {
                    visibleCount++;
                }
            });
            var hiddenCount = Math.max(0, rows.length - visibleCount);
            var label = hiddenCount > 0 ? ('Expand (' + hiddenCount + ' more)') : 'Expand';
            btn.html('<i class="fa fa-plus"></i> ' + label);
        } else {
            btn.html('<i class="fa fa-minus"></i> Collapse');
        }
    }

    $(widgetId).off('click.simplemdmDevicesTableToggle', '.simplemdm-section-toggle[data-target="#simplemdm-devices-table-body"]')
        .on('click.simplemdmDevicesTableToggle', '.simplemdm-section-toggle[data-target="#simplemdm-devices-table-body"]', function(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            if (ev && ev.stopImmediatePropagation) {
                ev.stopImmediatePropagation();
            } else if (ev && ev.stopPropagation) {
                ev.stopPropagation();
            }
            rowsCollapsed = !rowsCollapsed;
            applyRowsState();
            if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                window.simplemdmReflowDashboardGrid();
                setTimeout(window.simplemdmReflowDashboardGrid, 120);
                setTimeout(window.simplemdmReflowDashboardGrid, 420);
            } else if (window.dispatchEvent && typeof Event === 'function') {
                window.dispatchEvent(new Event('resize'));
            }
        });

    function esc(v) {
        return $('<div>').text(String(v === null || v === undefined ? '' : v)).html();
    }

    function statusBadge(status) {
        var s = String(status || '').toLowerCase();
        if (s === 'enrolled') {
            return '<span class="simplemdm-state simplemdm-state-yes">enrolled</span>';
        }
        if (!s) {
            return '<span class="simplemdm-state simplemdm-state-na">-</span>';
        }
        return '<span class="simplemdm-state simplemdm-state-no">' + esc(s) + '</span>';
    }

    function renderRows(rows) {
        var $tbody = $(widgetId + ' #simplemdm-devices-table-mini tbody').empty();
        var data = (rows || []).slice();
        data.sort(function(a, b) {
            return String(a.device_name || '').localeCompare(String(b.device_name || ''));
        });
        $(widgetId + ' #simplemdm-devices-table-total').text(data.length);

        if (!data.length) {
            $tbody.append('<tr><td colspan="5" class="text-muted">No devices found.</td></tr>');
            return;
        }

        data.slice(0, 50).forEach(function(row) {
            var serial = String(row.serial_number || '');
            var deviceUrl = appUrl + '/module/simplemdm/device/' + encodeURIComponent(serial);
            $tbody.append(
                '<tr>' +
                    '<td><a href="' + deviceUrl + '">' + esc(row.device_name || '-') + '</a></td>' +
                    '<td>' + (serial ? '<a href="' + deviceUrl + '">' + esc(serial) + '</a>' : '-') + '</td>' +
                    '<td>' + statusBadge(row.status) + '</td>' +
                    '<td>' + esc(row.os_version || '-') + '</td>' +
                    '<td>' + esc(row.assignment_group || '-') + '</td>' +
                '</tr>'
            );
        });
        rowsCollapsed = data.length > 10 ? rowsCollapsed : false;
        applyRowsState();
    }

    $.getJSON(appUrl + '/module/simplemdm/get_data', function(data) {
        renderRows(data || []);
    }).fail(function() {
        var $tbody = $(widgetId + ' #simplemdm-devices-table-mini tbody').empty();
        $tbody.append('<tr><td colspan="5" class="text-danger">Failed to load devices.</td></tr>');
        $(widgetId + ' #simplemdm-devices-table-total').text('0');
        applyRowsState();
    });
});
</script>
