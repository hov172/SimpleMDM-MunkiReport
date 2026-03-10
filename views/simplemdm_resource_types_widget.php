<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>
<style>
.simplemdm-cards-scroll-wrap {
    position: relative;
}
.simplemdm-cards-scroll-fade {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 58px;
    pointer-events: none;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0), var(--simplemdm-card-bg));
    display: none;
}
.simplemdm-cards-scroll-hint {
    position: absolute;
    right: 10px;
    bottom: 10px;
    z-index: 2;
    pointer-events: none;
    background: rgba(0, 0, 0, 0.35);
    color: #fff;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    display: none;
}
</style>

<div class="col-lg-12">
    <div class="panel panel-default simplemdm-modern-widget" id="simplemdm-resource-types-widget">
        <div class="panel-heading" data-widget="simplemdm_resource_types">
            <h3 class="panel-title"><i class="fa fa-sitemap"></i> <span data-i18n="simplemdm.widget.resource_types"></span></h3>
        </div>
        <div class="panel-body">
            <div class="simplemdm-section">
                <div class="simplemdm-section-head">
                    <div class="simplemdm-section-title">Resource Type Chart</div>
                </div>
                <div class="simplemdm-section-body">
                    <div class="svg-container" style="height: 240px;">
                        <svg style="height: 240px;"></svg>
                    </div>
                </div>
            </div>
            <div class="simplemdm-section">
                <div class="simplemdm-section-head">
                    <div class="simplemdm-section-title">Resource Cards</div>
                    <button type="button" class="btn btn-xs btn-default simplemdm-section-toggle" data-target="#simplemdm-resource-types-cards-body"><i class="fa fa-plus"></i> Expand</button>
                </div>
                <div id="simplemdm-resource-types-cards-body" class="simplemdm-section-body simplemdm-collapsed">
                    <div class="simplemdm-cards-scroll-wrap">
                        <div id="simplemdm-resource-type-cards" class="row"></div>
                        <div class="simplemdm-cards-scroll-fade"></div>
                        <div class="simplemdm-cards-scroll-hint">Scroll for more</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-resource-types-widget';
    var cardsCollapsed = true;
    var collapsedVisibleRows = 2;

    function collapsedCardsHeight() {
        var cards = $('#simplemdm-resource-type-cards');
        var cardCols = cards.children('div');
        if (!cards.length || !cardCols.length) {
            return 320;
        }
        var rows = {};
        cardCols.each(function() {
            var $col = $(this);
            var top = Math.round($col.position().top);
            var bottom = top + Math.ceil($col.outerHeight(true));
            if (!rows[top] || bottom > rows[top]) {
                rows[top] = bottom;
            }
        });
        var rowTops = Object.keys(rows).map(function(k) { return Number(k); }).sort(function(a, b) { return a - b; });
        if (!rowTops.length) {
            return 320;
        }
        var maxRowIndex = Math.min(collapsedVisibleRows - 1, rowTops.length - 1);
        var firstTop = rowTops[0];
        var lastBottom = rows[rowTops[maxRowIndex]];
        return Math.max(220, Math.ceil((lastBottom - firstTop) + 8));
    }

    function updateCardsScrollHint() {
        var body = $('#simplemdm-resource-types-cards-body');
        var fade = body.find('.simplemdm-cards-scroll-fade');
        var hint = body.find('.simplemdm-cards-scroll-hint');
        if (!cardsCollapsed) {
            fade.hide();
            hint.hide();
            return;
        }
        var el = body.get(0);
        if (!el) {
            fade.hide();
            hint.hide();
            return;
        }
        var hasOverflow = (el.scrollHeight - el.clientHeight) > 6;
        var atBottom = (el.scrollTop + el.clientHeight) >= (el.scrollHeight - 6);
        if (hasOverflow && !atBottom) {
            fade.show();
            hint.show();
        } else {
            fade.hide();
            hint.hide();
        }
    }

    function applyCardsState() {
        var body = $('#simplemdm-resource-types-cards-body');
        var cards = $('#simplemdm-resource-type-cards');
        var cardCols = cards.children('div');
        var btn = $(widgetId + ' .simplemdm-section-toggle[data-target="#simplemdm-resource-types-cards-body"]');
        if (!body.length || !btn.length) {
            return;
        }
        body.toggleClass('simplemdm-collapsed', cardsCollapsed);
        body.attr('data-collapsed', cardsCollapsed ? '1' : '0');
        var targetHeight = collapsedCardsHeight();
        body.css({
            maxHeight: cardsCollapsed ? (targetHeight + 'px') : 'none',
            height: cardsCollapsed ? (targetHeight + 'px') : 'auto',
            overflowY: cardsCollapsed ? 'auto' : 'visible',
            overflowX: 'hidden',
            display: 'block'
        });
        cards.css({
            maxHeight: 'none',
            overflowY: 'visible',
            overflowX: 'visible'
        });
        if (cardsCollapsed) {
            var visibleCount = 0;
            cardCols.each(function() {
                var $col = $(this);
                var bottom = Math.round($col.position().top + $col.outerHeight(true));
                if (bottom <= targetHeight + 1) {
                    visibleCount++;
                }
            });
            var hiddenCount = Math.max(0, cardCols.length - visibleCount);
            var expandLabel = hiddenCount > 0 ? ('Expand (' + hiddenCount + ' more)') : 'Expand';
            btn.html('<i class="fa fa-plus"></i> ' + expandLabel);
        } else {
            btn.html('<i class="fa fa-minus"></i> Collapse');
        }
        updateCardsScrollHint();
    }

    $(widgetId).off('click.simplemdmResourceCardsToggle', '.simplemdm-section-toggle[data-target="#simplemdm-resource-types-cards-body"]')
        .on('click.simplemdmResourceCardsToggle', '.simplemdm-section-toggle[data-target="#simplemdm-resource-types-cards-body"]', function(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            if (ev && ev.stopImmediatePropagation) {
                ev.stopImmediatePropagation();
            } else if (ev && ev.stopPropagation) {
                ev.stopPropagation();
            }
            cardsCollapsed = !cardsCollapsed;
            applyCardsState();
            if (typeof window.simplemdmReflowDashboardGrid === 'function') {
                window.simplemdmReflowDashboardGrid();
                setTimeout(window.simplemdmReflowDashboardGrid, 120);
                setTimeout(window.simplemdmReflowDashboardGrid, 420);
            } else if (window.dispatchEvent && typeof Event === 'function') {
                window.dispatchEvent(new Event('resize'));
            }
        });
    $(widgetId).off('scroll.simplemdmResourceCards', '#simplemdm-resource-types-cards-body')
        .on('scroll.simplemdmResourceCards', '#simplemdm-resource-types-cards-body', function() {
            updateCardsScrollHint();
        });

    function resourcesListingUrl(query) {
        var path = '/show/listing/simplemdm/simplemdm_resources';
        if (appUrl.indexOf('index.php?') !== -1) {
            return appUrl + path + (query ? '?' + query : '');
        }
        if (window.location.pathname.indexOf('/index.php') !== -1) {
            return appUrl + '/index.php?' + path + (query ? '?' + query : '');
        }
        return appUrl + path + (query ? '?' + query : '');
    }

    function renderResourceTypes() {
        var palette = window.simplemdmThemePalette ? window.simplemdmThemePalette() : {};
        var chartPrimary = (window.simplemdmThemeVar ? window.simplemdmThemeVar('--simplemdm-info', '') : '') || palette.info || palette.accentAlt || '#5ec8ff';
        var chartMuted = (window.simplemdmThemeVar ? window.simplemdmThemeVar('--simplemdm-border', '') : '') || palette.border || '#4c5a67';
        var chartSecondary = palette.warning || '#f0b45f';
        $.getJSON(window.simplemdmModuleUrl('get_resource_type_stats'), function(data) {
        var panelBody = $(widgetId + ' .panel-body');
        var cards = $('#simplemdm-resource-type-cards');
        cards.empty();

        if (!data || !data.length) {
            panelBody.html('<p data-i18n="no_data" class="text-center"></p>');
            return;
        }

        data.sort(function(a, b) {
            return Number(b.count || 0) - Number(a.count || 0);
        });

        var maxValue = 0;
        data.forEach(function(item) {
            maxValue = Math.max(maxValue, Number(item.count || 0));
        });
        function colorForCount(value) {
            if (maxValue <= 0) {
                return chartMuted;
            }
            // Log scale keeps small values visually distinguishable when one type dominates.
            var t = Math.log(1 + Math.max(0, Number(value || 0))) / Math.log(1 + maxValue);
            if (t <= 0.6) {
                return d3.interpolateRgb(chartMuted, chartPrimary)(t / 0.6);
            }
            return d3.interpolateRgb(chartPrimary, chartSecondary)((t - 0.6) / 0.4);
        }
        function rgbaFromColor(color, alpha) {
            var c = d3.rgb(color);
            return 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',' + alpha + ')';
        }

        var topRows = data.slice(0, 10);
        var chartRows = topRows.map(function(item) {
            var type = String(item.resource_type || '').trim();
            var label = type.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            var value = Number(item.count || 0);
            var barColor = colorForCount(value);
            return { label: label, value: value, color: barColor };
        });
        var maxLabelLength = 0;
        chartRows.forEach(function(row) {
            maxLabelLength = Math.max(maxLabelLength, String(row.label || '').length);
        });
        // Keep long resource type labels readable without clipping.
        var leftMargin = Math.min(340, Math.max(180, (maxLabelLength * 8) + 24));

        nv.addGraph(function() {
            var chart = nv.models.multiBarHorizontalChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showControls(false)
                .showLegend(false)
                .showValues(false)
                .stacked(false)
                .margin({ top: 6, right: 10, bottom: 18, left: leftMargin })
                .barColor(function(d) { return (d && d.color) ? d.color : chartPrimary; });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-resource-types-widget svg')
                .datum([{ key: 'Resources', values: chartRows }])
                .transition().duration(320)
                .call(chart);

            return chart;
        });

        data.forEach(function(item) {
            var type = String(item.resource_type || '').trim();
            if (!type) {
                return;
            }
            var label = type.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            var filterUrl = resourcesListingUrl('type=' + encodeURIComponent(type));
            var color = colorForCount(item.count);
            var borderColor = rgbaFromColor(color, 0.9);
            var glowColor = rgbaFromColor(color, 0.18);
            cards.append(
                '<div class="col-lg-3 col-md-4 col-sm-6">' +
                    '<div class="simplemdm-resource-type-card" style="border-color:' + borderColor + '; box-shadow: inset 0 2px 0 ' + borderColor + ', 0 6px 24px ' + glowColor + ';">' +
                        '<div class="simplemdm-resource-type-title">' + label + '</div>' +
                        '<div class="simplemdm-resource-type-count" style="color:' + color + ';">' + item.count + '</div>' +
                        '<a class="btn btn-xs btn-primary" href="' + filterUrl + '">View ' + label + '</a>' +
                    '</div>' +
                '</div>'
            );
        });
        cardsCollapsed = data.length > 8 ? cardsCollapsed : false;
        applyCardsState();
        if (typeof window.simplemdmReflowDashboardGrid === 'function') {
            window.simplemdmReflowDashboardGrid();
            setTimeout(window.simplemdmReflowDashboardGrid, 120);
        }
        });
    }

    renderResourceTypes();
    window.addEventListener('simplemdm:modechange', renderResourceTypes);
});
</script>
