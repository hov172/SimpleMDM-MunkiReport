<?php include_once __DIR__ . '/simplemdm_widget_modern_assets.php'; ?>

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
                    <div id="simplemdm-resource-type-cards" class="row"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('appReady', function() {
    var widgetId = '#simplemdm-resource-types-widget';
    var cardsCollapsed = true;

    function applyCardsState() {
        var body = $('#simplemdm-resource-types-cards-body');
        var cards = $('#simplemdm-resource-type-cards');
        var btn = $(widgetId + ' .simplemdm-section-toggle[data-target="#simplemdm-resource-types-cards-body"]');
        if (!body.length || !btn.length) {
            return;
        }
        body.toggleClass('simplemdm-collapsed', cardsCollapsed);
        body.attr('data-collapsed', cardsCollapsed ? '1' : '0');
        body.css({
            maxHeight: cardsCollapsed ? '320px' : 'none',
            overflowY: cardsCollapsed ? 'auto' : 'visible',
            overflowX: 'hidden',
            display: 'block'
        });
        cards.css({
            maxHeight: cardsCollapsed ? '320px' : 'none',
            overflowY: cardsCollapsed ? 'auto' : 'visible',
            overflowX: 'hidden'
        });
        btn.html(cardsCollapsed ? '<i class="fa fa-plus"></i> Expand' : '<i class="fa fa-minus"></i> Collapse');
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
        $.getJSON(appUrl + '/module/simplemdm/get_resource_type_stats', function(data) {
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

        var chartRows = data.slice(0, 10).map(function(item) {
            var type = String(item.resource_type || '').trim();
            var label = type.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
            return { label: label, value: Number(item.count || 0) };
        });

        nv.addGraph(function() {
            var chart = nv.models.multiBarHorizontalChart()
                .x(function(d) { return d.label; })
                .y(function(d) { return d.value; })
                .showControls(false)
                .showValues(false)
                .stacked(false)
                .margin({ top: 6, right: 10, bottom: 18, left: 168 });

            chart.yAxis.tickFormat(d3.format('d'));

            d3.select('#simplemdm-resource-types-widget svg')
                .datum([{ key: 'Resources', color: chartPrimary, values: chartRows }])
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
            cards.append(
                '<div class="col-lg-3 col-md-4 col-sm-6">' +
                    '<div class="simplemdm-resource-type-card">' +
                        '<div class="simplemdm-resource-type-title">' + label + '</div>' +
                        '<div class="simplemdm-resource-type-count">' + item.count + '</div>' +
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
