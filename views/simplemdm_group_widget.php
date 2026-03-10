<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
#simplemdm-group-widget .simplemdm-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

#simplemdm-group-widget .simplemdm-section-title {
    min-width: 0;
    flex: 1 1 auto;
}

#simplemdm-group-widget .simplemdm-section-toggle {
    margin-left: auto;
    flex: 0 0 auto;
}
</style>

<div class="col-lg-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-group-widget">
        <div class="panel-heading" data-widget="simplemdm_group">
            <h3 class="panel-title">
                <i class="fa fa-users"></i>
                <span data-i18n="simplemdm.widget.group_status"></span>
            </h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-section">
                <div class="simplemdm-section-head">
                    <div class="simplemdm-section-title">Top Groups Chart</div>
                </div>
                <div class="simplemdm-section-body">
                    <div class="svg-container" style="height: 190px;">
                        <svg style="height: 190px;"></svg>
                    </div>
                </div>
            </div>
            <div class="simplemdm-section">
                <div class="simplemdm-section-head">
                    <div class="simplemdm-section-title">Assignment Group List</div>
                    <button type="button" class="btn btn-xs btn-default simplemdm-section-toggle" data-target="#simplemdm-group-list-body"><i class="fa fa-plus"></i> Expand</button>
                </div>
                <div id="simplemdm-group-list-body" class="simplemdm-section-body simplemdm-collapsed">
                    <div class="list-group"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function(e, lang) {
    var widgetId = '#simplemdm-group-widget';
    var collapsedState = true;

    function simplemdmModuleUrl(path) {
        var normalizedPath = String(path || '').replace(/^\/+/, '');
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + '/module/simplemdm/' + normalizedPath;
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?/module/simplemdm/' + normalizedPath;
        }
        return appUrl + '/module/simplemdm/' + normalizedPath;
    }

    function simplemdmListingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function applyListState() {
        var listBody = $('#simplemdm-group-list-body');
        var listGroup = listBody.find('.list-group');
        var listItems = listGroup.children('.list-group-item');
        var toggleBtn = $(widgetId + ' .simplemdm-section-toggle[data-target="#simplemdm-group-list-body"]');
        if (!listBody.length || !toggleBtn.length) {
            return;
        }
        listBody.toggleClass('simplemdm-collapsed', collapsedState);
        listBody.attr('data-collapsed', collapsedState ? '1' : '0');
        listBody.css({
            maxHeight: collapsedState ? '260px' : 'none',
            height: collapsedState ? '260px' : 'auto',
            overflowY: collapsedState ? 'auto' : 'visible',
            overflowX: 'hidden',
            display: 'block'
        });
        listGroup.css({
            maxHeight: 'none',
            overflowY: 'visible',
            overflowX: 'visible'
        });
        if (collapsedState) {
            var visibleHeight = listBody.innerHeight();
            var visibleCount = 0;
            listItems.each(function() {
                var $item = $(this);
                var bottom = Math.round($item.position().top + $item.outerHeight(true));
                if (bottom <= visibleHeight + 1) {
                    visibleCount++;
                }
            });
            var hiddenCount = Math.max(0, listItems.length - visibleCount);
            var expandLabel = hiddenCount > 0 ? ('Expand (' + hiddenCount + ' more)') : 'Expand';
            toggleBtn.html('<i class="fa fa-plus"></i> ' + expandLabel);
        } else {
            toggleBtn.html('<i class="fa fa-minus"></i> Collapse');
        }
    }

    $(widgetId).off('click.simplemdmGroupToggle', '.simplemdm-section-toggle[data-target="#simplemdm-group-list-body"]')
        .on('click.simplemdmGroupToggle', '.simplemdm-section-toggle[data-target="#simplemdm-group-list-body"]', function(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            if (ev && ev.stopImmediatePropagation) {
                ev.stopImmediatePropagation();
            } else if (ev && ev.stopPropagation) {
                ev.stopPropagation();
            }
            collapsedState = !collapsedState;
            applyListState();
            if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                window.simplemdmReflowDashboardGrid();
                setTimeout(window.simplemdmReflowDashboardGrid, 120);
                setTimeout(window.simplemdmReflowDashboardGrid, 420);
            } else {
                window.dispatchEvent(new Event('resize'));
            }
        });

    function renderGroup() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        var barPalette = [
            (palette.info || '#4aa3df'),
            (palette.accentAlt || '#7f8ea3'),
            (palette.warning || '#d18b3f'),
            (palette.s7 || '#d4a15d'),
            (palette.positive || '#5ba35b'),
            (palette.s6 || '#7cbc75'),
            (palette.danger || '#d35b52'),
            (palette.s5 || '#c98b8b')
        ];
        $.getJSON(simplemdmModuleUrl('get_assignment_group_stats'), function(data) {
        var panelBody = $(widgetId + ' .panel-body');
        var listGroup = panelBody.find('.list-group');
        listGroup.empty();

        if (data.length === 0) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        data.forEach(function(item) {
            var label = item.label || 'Unknown';
            var filterUrl = simplemdmListingUrl('group=' + encodeURIComponent(label));
            var row = '<a href="' + filterUrl + '" class="list-group-item">' +
                label +
                '<span class="badge pull-right">' + item.count + '</span>' +
                '</a>';
            listGroup.append(row);
        });
        collapsedState = data.length > 8 ? collapsedState : false;
        applyListState();

        var top = (data || []).slice(0, 8).map(function(item, idx) {
            return {
                label: String(item.label || 'Unknown'),
                value: Number(item.count || 0),
                color: barPalette[idx % barPalette.length]
            };
        });

        nv.addGraph(function() {
            var chart = nv.models.discreteBarChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .color(barPalette)
                .staggerLabels(true)
                .showValues(false)
                .duration(320)
                .margin({ top: 6, right: 8, bottom: 42, left: 44 });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-group-widget svg')
                .datum([{ key: 'Groups', color: (palette.accent || '#0a7fa8'), values: top }])
                .call(chart);

            chart.discretebar.dispatch.on('elementClick.simplemdm', function(e) {
                if (e && e.data && e.data.label) {
                    window.location = simplemdmListingUrl('group=' + encodeURIComponent(String(e.data.label)));
                }
            });
            return chart;
        });
        });
    }

    renderGroup();
    window.addEventListener('simplemdm:modechange', renderGroup);
});
</script>
