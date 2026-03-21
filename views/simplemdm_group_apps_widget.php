<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-group-apps-widget .simplemdm-group-apps-summary {
    margin-bottom: 10px;
}

#simplemdm-group-apps-widget .simplemdm-group-apps-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

#simplemdm-group-apps-widget .simplemdm-group-apps-controls .simplemdm-section-toggle {
    margin-left: auto;
}

#simplemdm-group-apps-widget .simplemdm-group-apps-width-toggle {
    margin-left: 8px;
}

#simplemdm-group-apps-widget .simplemdm-group-apps-empty {
    color: var(--simplemdm-muted);
    text-align: center;
    padding: 18px 10px;
}

#simplemdm-group-apps-widget .simplemdm-group-app-title {
    min-width: 0;
    color: var(--simplemdm-ink);
    font-weight: 800;
}

#simplemdm-group-apps-widget .simplemdm-group-app-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

#simplemdm-group-apps-widget .simplemdm-group-app-card {
    border: 1px solid var(--simplemdm-border);
    border-radius: 12px;
    background: var(--simplemdm-surface);
    margin-bottom: 10px;
    overflow: hidden;
}

#simplemdm-group-apps-widget .simplemdm-group-app-card .simplemdm-section-head {
    padding: 10px 12px;
}

#simplemdm-group-apps-widget .simplemdm-group-app-table-wrap {
    padding: 0 12px 12px;
    width: 100%;
}

#simplemdm-group-apps-widget .simplemdm-group-app-table {
    width: 100%;
    border-collapse: collapse;
}

#simplemdm-group-apps-widget .simplemdm-group-app-table th,
#simplemdm-group-apps-widget .simplemdm-group-app-table td {
    padding: 8px 6px;
    border-bottom: 1px solid var(--simplemdm-border);
    color: var(--simplemdm-ink);
    vertical-align: top;
}

#simplemdm-group-apps-widget .simplemdm-group-app-table th {
    color: var(--simplemdm-muted);
    font-weight: 800;
}

#simplemdm-group-apps-widget .simplemdm-group-app-table tbody tr:last-child td {
    border-bottom: 0;
}

#simplemdm-group-apps-widget #simplemdm-group-apps-groups-wrap.simplemdm-collapsed {
    max-height: 520px;
    overflow: hidden;
}
</style>

<div class="col-lg-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-group-apps-widget">
        <div class="panel-heading" data-widget="simplemdm_group_apps">
            <h3 class="panel-title">
                <i class="fa fa-th-large"></i>
                <span>Assignment Group Apps</span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-group-apps-summary simplemdm-list-pills">
                <span class="simplemdm-device-chip"><strong>Groups:</strong>&nbsp;<span id="simplemdm-group-apps-group-count">-</span></span>
                <span class="simplemdm-device-chip"><strong>Unique Apps:</strong>&nbsp;<span id="simplemdm-group-apps-unique-total">-</span></span>
                <span class="simplemdm-device-chip"><strong>Missing Metadata:</strong>&nbsp;<span id="simplemdm-group-apps-missing-total">-</span></span>
            </div>
            <div class="simplemdm-group-apps-controls">
                <div class="text-muted small">Showing assignment groups and their synced assigned apps.</div>
                <div>
                    <button type="button" class="btn btn-xs btn-default simplemdm-group-apps-width-toggle" id="simplemdm-group-apps-width-toggle">Regular Width</button>
                    <button type="button" class="btn btn-xs btn-default simplemdm-section-toggle" id="simplemdm-group-apps-toggle-all">+ Expand</button>
                </div>
            </div>
            <div id="simplemdm-group-apps-groups-wrap" class="simplemdm-collapsed">
                <div id="simplemdm-group-apps-groups">
                    <div class="simplemdm-group-apps-empty">Loading assignment-group app data...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-group-apps-widget';
    var widgetKey = 'simplemdm_group_apps';
    var $widget = $(widgetId);
    var groupsCollapsed = true;
    var defaultVisibleGroups = 3;
    var fullWidthClass = 'col-lg-12';
    var regularWidthClass = 'col-lg-6';

    function esc(value) {
        return $('<div>').text(String(value === null || value === undefined ? '' : value)).html();
    }

    function listingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm_resources';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function getWidgetColumn() {
        return $(widgetId).closest('[class*="col-"]');
    }

    function getDashboardItem() {
        return $(widgetId).closest('.simplemdm-dashboard-item');
    }

    function getWidgetRoot() {
        return $(widgetId).closest('[id^="simplemdm-widget-"], [id^="widget-"], [id^="widget_"]');
    }

    function isFullWidth() {
        var $item = getDashboardItem();
        if (! $item.length) {
            $item = getWidgetRoot();
        }
        var renderedSpan = parseInt($item.attr('data-simplemdm-span'), 10);
        if (renderedSpan && typeof window.simplemdmGetGridColumnCount === 'function') {
            return renderedSpan >= window.simplemdmGetGridColumnCount();
        }
        var forcedSpan = parseInt($item.attr('data-simplemdm-force-span'), 10);
        if (forcedSpan && typeof window.simplemdmGetGridColumnCount === 'function') {
            return forcedSpan >= window.simplemdmGetGridColumnCount();
        }
        if (typeof window.simplemdmGetGridColumnCount === 'function' && typeof window.simplemdmGetWidgetSpan === 'function') {
            return window.simplemdmGetWidgetSpan(widgetKey) >= window.simplemdmGetGridColumnCount();
        }
        return getWidgetColumn().hasClass(fullWidthClass) && !getWidgetColumn().hasClass(regularWidthClass);
    }

    function updateWidthToggleLabel() {
        $('#simplemdm-group-apps-width-toggle').text(isFullWidth() ? 'Regular Width' : 'Full Width');
    }

    function triggerImmediateGridReflow() {
        if (typeof window.simplemdmReflowDashboardGrid === 'function') {
            window.simplemdmReflowDashboardGrid();
            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(function() {
                    window.simplemdmReflowDashboardGrid();
                    window.requestAnimationFrame(function() {
                        window.simplemdmReflowDashboardGrid();
                    });
                });
            }
        }
        if (window.dispatchEvent && typeof Event === 'function') {
            window.dispatchEvent(new Event('resize'));
        }
    }

    function applyWidgetWidth(fullWidth) {
        var $col = getWidgetColumn();
        if (! $col.length) {
            return;
        }
        var $item = getDashboardItem();
        if (! $item.length) {
            $item = getWidgetRoot();
        }
        if ($item.length && typeof window.simplemdmGetGridColumnCount === 'function') {
            var nextSpan = fullWidth ? window.simplemdmGetGridColumnCount() : 1;
            $item.attr('data-simplemdm-force-span', String(nextSpan));
            $item.attr('data-simplemdm-span', String(nextSpan));
        }
        $col.removeClass(fullWidthClass + ' ' + regularWidthClass).addClass(fullWidth ? fullWidthClass : regularWidthClass);
        if (typeof window.simplemdmGetGridColumnCount === 'function' && typeof window.simplemdmSetWidgetSpan === 'function') {
            window.simplemdmSetWidgetSpan(widgetKey, fullWidth ? window.simplemdmGetGridColumnCount() : 1);
        }
        updateWidthToggleLabel();
        triggerImmediateGridReflow();
    }

    function applyGroupsState() {
        var $wrap = $('#simplemdm-group-apps-groups-wrap');
        var $cards = $(widgetId + ' .simplemdm-group-app-card');
        var $btn = $('#simplemdm-group-apps-toggle-all');
        if (! $wrap.length || ! $btn.length) {
            return;
        }

        var shouldCollapse = groupsCollapsed && $cards.length > defaultVisibleGroups;
        $wrap.toggleClass('simplemdm-collapsed', shouldCollapse);

        if (!shouldCollapse) {
            $btn.text('- Collapse');
            return;
        }

        var hiddenCount = Math.max(0, $cards.length - defaultVisibleGroups);
        $btn.text(hiddenCount > 0 ? ('+ Expand (' + hiddenCount + ' more groups)') : '+ Expand');
    }

    function renderWidget() {
        $.getJSON(window.simplemdmModuleUrl('get_assignment_group_app_stats'), function(payload) {
            var groups = payload && Array.isArray(payload.groups) ? payload.groups : [];
            var $wrap = $(widgetId + ' #simplemdm-group-apps-groups').empty();
            $('#simplemdm-group-apps-group-count').text(groups.length);
            $('#simplemdm-group-apps-unique-total').text(Number(payload.global_unique_app_count || 0));
            $('#simplemdm-group-apps-missing-total').text(Number(payload.missing_metadata_count || 0));

            if (!groups.length) {
                $.getJSON(window.simplemdmModuleUrl('get_assignment_group_app_debug'), function(debug) {
                    var emptyText = payload && payload.has_assignment_groups === false
                        ? 'No assignment group resource data has been synced yet.'
                        : 'No assignment-group app data found.';
                    var details = [
                        'Devices with SimpleMDM IDs: ' + Number(debug.device_count_with_ids || 0),
                        'Assignment Group rows: ' + Number(debug.assignment_group_row_count || 0),
                        'App rows: ' + Number(debug.app_row_count || 0)
                    ];
                    if (Array.isArray(debug.assignment_group_resource_types) && debug.assignment_group_resource_types.length) {
                        details.push('Assignment group resource types: ' + debug.assignment_group_resource_types.join(', '));
                    }
                    $wrap.html(
                        '<div class="simplemdm-group-apps-empty">' + esc(emptyText) + '<br><small>' + esc(details.join(' | ')) + '</small></div>'
                    );
                    applyGroupsState();
                }).fail(function() {
                    var emptyText = payload && payload.has_assignment_groups === false
                        ? 'No assignment group resource data has been synced yet.'
                        : 'No assignment-group app data found.';
                    $wrap.html('<div class="simplemdm-group-apps-empty">' + esc(emptyText) + '</div>');
                    applyGroupsState();
                });
                return;
            }

            groups.forEach(function(group, index) {
                var groupId = 'simplemdm-group-apps-body-' + index;
                var rows = '';
                (group.apps || []).forEach(function(app) {
                    var resourceType = String(app.resource_type || 'installed_app');
                    var href = listingUrl('type=' + encodeURIComponent(resourceType) + '&resource_id=' + encodeURIComponent(String(app.resource_id || '')));
                    var appName = String(app.name || ('App #' + String(app.resource_id || '')));
                    var resolved = Boolean(app.is_resolved);
                    var appCell = resolved
                        ? ('<a href="' + href + '">' + esc(appName) + '</a>')
                        : ('<span class="text-muted">' + esc(appName) + '</span>');
                    var idCell = resolved
                        ? ('<a href="' + href + '">' + esc(app.resource_id || '-') + '</a>')
                        : ('<span class="text-muted">' + esc(app.resource_id || '-') + '</span>');
                    var statusBadge = resolved
                        ? '<span class="badge pull-right">Assigned</span>'
                        : '<span class="badge pull-right" style="background:#9aa6b2;">Metadata Missing</span>';
                    rows += '' +
                        '<tr>' +
                            '<td>' + appCell + '</td>' +
                            '<td style="width:140px;">' + idCell + '</td>' +
                            '<td style="width:140px;">' + statusBadge + '</td>' +
                        '</tr>';
                });

                if (!rows) {
                    rows = '<tr><td colspan="3" class="text-muted">No assigned apps found for this group.</td></tr>';
                }

                $wrap.append(
                    '<div class="simplemdm-group-app-card">' +
                        '<div class="simplemdm-section-head" data-section-toggle="' + esc(groupId) + '">' +
                            '<div class="simplemdm-group-app-title">' + esc(group.label || 'No Assignment Group') + '</div>' +
                            '<div class="simplemdm-group-app-meta">' +
                                '<span class="simplemdm-device-chip">Devices: ' + Number(group.device_count || 0) + '</span>' +
                                '<span class="simplemdm-device-chip">Apps: ' + Number(group.unique_app_count || 0) + '</span>' +
                                '<button type="button" class="btn btn-xs btn-default simplemdm-section-toggle">+ Expand</button>' +
                            '</div>' +
                        '</div>' +
                        '<div class="simplemdm-section-body" id="simplemdm-section-' + esc(groupId) + '" style="display:none;">' +
                            '<div class="simplemdm-group-app-table-wrap">' +
                                '<table class="simplemdm-group-app-table">' +
                                    '<thead>' +
                                        '<tr>' +
                                            '<th>App</th>' +
                                            '<th style="width:140px;">Resource ID</th>' +
                                            '<th style="width:140px;">Status</th>' +
                                        '</tr>' +
                                    '</thead>' +
                                    '<tbody>' + rows + '</tbody>' +
                                '</table>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            });

            groupsCollapsed = groups.length > defaultVisibleGroups;
            applyGroupsState();
        }).fail(function() {
            $(widgetId + ' #simplemdm-group-apps-groups').html(
                '<div class="simplemdm-group-apps-empty text-danger">Failed to load assignment-group app data.</div>'
            );
            $('#simplemdm-group-apps-group-count').text('0');
            $('#simplemdm-group-apps-unique-total').text('0');
            $('#simplemdm-group-apps-missing-total').text('0');
            applyGroupsState();
        });
    }

    if (! $widget.data('simplemdmGroupAppsBound')) {
        $widget.data('simplemdmGroupAppsBound', 1);

        $('#simplemdm-group-apps-toggle-all').off('click.simplemdmGroupApps').on('click.simplemdmGroupApps', function(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            groupsCollapsed = !groupsCollapsed;
            applyGroupsState();
            if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                window.simplemdmReflowDashboardGrid();
                setTimeout(window.simplemdmReflowDashboardGrid, 120);
                setTimeout(window.simplemdmReflowDashboardGrid, 420);
            }
        });

        $('#simplemdm-group-apps-width-toggle').off('click.simplemdmGroupApps').on('click.simplemdmGroupApps', function(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            applyWidgetWidth(!isFullWidth());
        });

        $(document).off('click.simplemdmGroupAppsToggle', widgetId + ' [data-section-toggle]')
            .on('click.simplemdmGroupAppsToggle', widgetId + ' [data-section-toggle]', function(ev) {
                if (ev && ev.preventDefault) {
                    ev.preventDefault();
                }
                var sectionId = String($(this).attr('data-section-toggle') || '');
                if (!sectionId) {
                    return;
                }
                var $body = $('#simplemdm-section-' + sectionId);
                var $btn = $(this).find('.simplemdm-section-toggle');
                var expand = !$body.is(':visible');
                $body.stop(true, true).slideToggle(160);
                $btn.text(expand ? '- Collapse' : '+ Expand');
                if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                    window.simplemdmReflowDashboardGrid();
                    setTimeout(window.simplemdmReflowDashboardGrid, 120);
                    setTimeout(window.simplemdmReflowDashboardGrid, 420);
                }
            });

        window.addEventListener('simplemdm:modechange', renderWidget);
        window.addEventListener('resize', updateWidthToggleLabel);
    }

    updateWidthToggleLabel();
    renderWidget();
});
</script>
