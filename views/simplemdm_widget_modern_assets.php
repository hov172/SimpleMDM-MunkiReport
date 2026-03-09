<?php
if (defined('SIMPLEMDM_WIDGET_MODERN_ASSETS_LOADED')) {
    return;
}
define('SIMPLEMDM_WIDGET_MODERN_ASSETS_LOADED', true);
?>
<style id="simplemdm-modern-widget-css">
:root {
    --simplemdm-ink: #0b1f35;
    --simplemdm-muted: #4f6276;
    --simplemdm-border: #c8d7e6;
    --simplemdm-border-strong: #b7cde1;
    --simplemdm-hover-border: #b7cde1;
    --simplemdm-panel-bg: linear-gradient(165deg, #ffffff 0%, #f2f8ff 65%, #ebf3fc 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #f4f9ff 0%, #ebf4fd 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
    --simplemdm-accent: #0a7fa8;
    --simplemdm-accent-alt: #2da3cf;
    --simplemdm-accent-soft: #e1f2f9;
    --simplemdm-accent-strong: #075f7d;
    --simplemdm-widget-shadow: 0 14px 28px rgba(10, 31, 53, 0.11);
    --simplemdm-card-shadow: 0 6px 14px rgba(10, 31, 53, 0.07);
    --simplemdm-card-shadow-hover: 0 12px 20px rgba(10, 31, 53, 0.11);
    --simplemdm-item-shadow-hover: 0 6px 14px rgba(10, 31, 53, 0.1);
    --simplemdm-pill-bg: #edf4fb;
    --simplemdm-chart-muted: #dbe7f3;
    --simplemdm-positive: #2f9e44;
    --simplemdm-warning: #f08c00;
    --simplemdm-danger: #c23b3b;
    --simplemdm-info: #1c7ed6;
    --simplemdm-series-4: #6f42c1;
    --simplemdm-series-5: #d63384;
    --simplemdm-series-6: #198754;
    --simplemdm-series-7: #fd7e14;
    --simplemdm-series-8: #6c757d;
    --simplemdm-surface: #ffffff;
    --simplemdm-surface-alt: #f6faff;
    --simplemdm-surface-hover: #f0f6fd;
}

body.simplemdm-layout-comfortable {
    --simplemdm-ink: #0b1f35;
    --simplemdm-muted: #4f6276;
    --simplemdm-border: #c8d7e6;
    --simplemdm-border-strong: #b7cde1;
    --simplemdm-hover-border: #b7cde1;
    --simplemdm-panel-bg: linear-gradient(165deg, #ffffff 0%, #f2f8ff 65%, #ebf3fc 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #f4f9ff 0%, #ebf4fd 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
    --simplemdm-accent: #0a7fa8;
    --simplemdm-accent-alt: #2da3cf;
    --simplemdm-accent-soft: #e1f2f9;
    --simplemdm-accent-strong: #075f7d;
    --simplemdm-widget-shadow: 0 14px 28px rgba(10, 31, 53, 0.11);
    --simplemdm-card-shadow: 0 6px 14px rgba(10, 31, 53, 0.07);
    --simplemdm-card-shadow-hover: 0 12px 20px rgba(10, 31, 53, 0.11);
    --simplemdm-item-shadow-hover: 0 6px 14px rgba(10, 31, 53, 0.1);
    --simplemdm-pill-bg: #edf4fb;
    --simplemdm-chart-muted: #dbe7f3;
    --simplemdm-positive: #2f9e44;
    --simplemdm-warning: #f08c00;
    --simplemdm-danger: #c23b3b;
    --simplemdm-info: #1c7ed6;
    --simplemdm-series-4: #6f42c1;
    --simplemdm-series-5: #d63384;
    --simplemdm-series-6: #198754;
    --simplemdm-series-7: #fd7e14;
    --simplemdm-series-8: #6c757d;
    --simplemdm-surface: #ffffff;
    --simplemdm-surface-alt: #f6faff;
    --simplemdm-surface-hover: #f0f6fd;
}

body.simplemdm-theme-light.simplemdm-layout-comfortable,
body.simplemdm-theme-light {
    --simplemdm-ink: #0b1f35;
    --simplemdm-muted: #4f6276;
    --simplemdm-border: #c8d7e6;
    --simplemdm-border-strong: #b7cde1;
    --simplemdm-hover-border: #b7cde1;
    --simplemdm-panel-bg: linear-gradient(165deg, #ffffff 0%, #f2f8ff 65%, #ebf3fc 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #f4f9ff 0%, #ebf4fd 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%);
    --simplemdm-accent: #0a7fa8;
    --simplemdm-accent-alt: #2da3cf;
    --simplemdm-accent-soft: #e1f2f9;
    --simplemdm-accent-strong: #075f7d;
    --simplemdm-widget-shadow: 0 14px 28px rgba(10, 31, 53, 0.11);
    --simplemdm-card-shadow: 0 6px 14px rgba(10, 31, 53, 0.07);
    --simplemdm-card-shadow-hover: 0 12px 20px rgba(10, 31, 53, 0.11);
    --simplemdm-item-shadow-hover: 0 6px 14px rgba(10, 31, 53, 0.1);
    --simplemdm-pill-bg: #edf4fb;
    --simplemdm-chart-muted: #dbe7f3;
    --simplemdm-positive: #2f9e44;
    --simplemdm-warning: #f08c00;
    --simplemdm-danger: #c23b3b;
    --simplemdm-info: #1c7ed6;
    --simplemdm-series-4: #6f42c1;
    --simplemdm-series-5: #d63384;
    --simplemdm-series-6: #198754;
    --simplemdm-series-7: #fd7e14;
    --simplemdm-series-8: #6c757d;
    --simplemdm-surface: #ffffff;
    --simplemdm-surface-alt: #f6faff;
    --simplemdm-surface-hover: #f0f6fd;
}

body.simplemdm-layout-compact {
    --simplemdm-ink: #081a2b;
    --simplemdm-muted: #3f5569;
    --simplemdm-border: #b6cbde;
    --simplemdm-border-strong: #9fb8cf;
    --simplemdm-hover-border: #90acc5;
    --simplemdm-panel-bg: linear-gradient(170deg, #ffffff 0%, #eef5fc 60%, #e6eef8 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #edf5fd 0%, #e4eef9 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #ffffff 0%, #f4f9ff 100%);
    --simplemdm-accent: #066f95;
    --simplemdm-accent-alt: #2496bf;
    --simplemdm-accent-soft: #d8ebf4;
    --simplemdm-accent-strong: #045a79;
    --simplemdm-widget-shadow: 0 9px 18px rgba(8, 26, 43, 0.12);
    --simplemdm-card-shadow: 0 4px 10px rgba(8, 26, 43, 0.08);
    --simplemdm-card-shadow-hover: 0 8px 14px rgba(8, 26, 43, 0.1);
    --simplemdm-item-shadow-hover: 0 4px 10px rgba(8, 26, 43, 0.09);
    --simplemdm-pill-bg: #e5eef8;
    --simplemdm-chart-muted: #cfdceb;
    --simplemdm-positive: #2b8b3d;
    --simplemdm-warning: #d97706;
    --simplemdm-danger: #ad3434;
    --simplemdm-info: #1a6fbc;
    --simplemdm-series-4: #5f3aac;
    --simplemdm-series-5: #bd2d76;
    --simplemdm-series-6: #157347;
    --simplemdm-series-7: #e06c00;
    --simplemdm-series-8: #5f6973;
    --simplemdm-surface: #ffffff;
    --simplemdm-surface-alt: #f2f7fd;
    --simplemdm-surface-hover: #eaf2fa;
}

body.simplemdm-theme-light.simplemdm-layout-compact {
    --simplemdm-ink: #081a2b;
    --simplemdm-muted: #3f5569;
    --simplemdm-border: #b6cbde;
    --simplemdm-border-strong: #9fb8cf;
    --simplemdm-hover-border: #90acc5;
    --simplemdm-panel-bg: linear-gradient(170deg, #ffffff 0%, #eef5fc 60%, #e6eef8 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #edf5fd 0%, #e4eef9 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #ffffff 0%, #f4f9ff 100%);
    --simplemdm-accent: #066f95;
    --simplemdm-accent-alt: #2496bf;
    --simplemdm-accent-soft: #d8ebf4;
    --simplemdm-accent-strong: #045a79;
    --simplemdm-widget-shadow: 0 9px 18px rgba(8, 26, 43, 0.12);
    --simplemdm-card-shadow: 0 4px 10px rgba(8, 26, 43, 0.08);
    --simplemdm-card-shadow-hover: 0 8px 14px rgba(8, 26, 43, 0.1);
    --simplemdm-item-shadow-hover: 0 4px 10px rgba(8, 26, 43, 0.09);
    --simplemdm-pill-bg: #e5eef8;
    --simplemdm-chart-muted: #cfdceb;
    --simplemdm-positive: #2b8b3d;
    --simplemdm-warning: #d97706;
    --simplemdm-danger: #ad3434;
    --simplemdm-info: #1a6fbc;
    --simplemdm-series-4: #5f3aac;
    --simplemdm-series-5: #bd2d76;
    --simplemdm-series-6: #157347;
    --simplemdm-series-7: #e06c00;
    --simplemdm-series-8: #5f6973;
    --simplemdm-surface: #ffffff;
    --simplemdm-surface-alt: #f2f7fd;
    --simplemdm-surface-hover: #eaf2fa;
}

body.simplemdm-theme-dark.simplemdm-layout-comfortable,
body.simplemdm-theme-dark {
    --simplemdm-ink: #e7f0fb;
    --simplemdm-muted: #a9b9c8;
    --simplemdm-border: #385063;
    --simplemdm-border-strong: #4a667b;
    --simplemdm-hover-border: #5b7991;
    --simplemdm-panel-bg: linear-gradient(165deg, #182633 0%, #162430 60%, #111c26 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #223546 0%, #1d2f3f 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #1a2a39 0%, #162533 100%);
    --simplemdm-accent: #55b7de;
    --simplemdm-accent-alt: #75c8e8;
    --simplemdm-accent-soft: #1f3a4d;
    --simplemdm-accent-strong: #3a9fc9;
    --simplemdm-widget-shadow: 0 16px 28px rgba(0, 0, 0, 0.38);
    --simplemdm-card-shadow: 0 8px 16px rgba(0, 0, 0, 0.25);
    --simplemdm-card-shadow-hover: 0 12px 20px rgba(0, 0, 0, 0.34);
    --simplemdm-item-shadow-hover: 0 8px 14px rgba(0, 0, 0, 0.25);
    --simplemdm-pill-bg: #243748;
    --simplemdm-chart-muted: #39556b;
    --simplemdm-positive: #5bc97e;
    --simplemdm-warning: #ffb357;
    --simplemdm-danger: #ea7070;
    --simplemdm-info: #6cb7f8;
    --simplemdm-series-4: #9f85ff;
    --simplemdm-series-5: #ff7fb2;
    --simplemdm-series-6: #5ecf9a;
    --simplemdm-series-7: #ffbe66;
    --simplemdm-series-8: #a2b3c2;
    --simplemdm-surface: #172635;
    --simplemdm-surface-alt: #1a2b3b;
    --simplemdm-surface-hover: #203446;
}

body.simplemdm-theme-dark.simplemdm-layout-compact {
    --simplemdm-ink: #edf5ff;
    --simplemdm-muted: #b2c0ce;
    --simplemdm-border: #41586a;
    --simplemdm-border-strong: #537086;
    --simplemdm-hover-border: #67859e;
    --simplemdm-panel-bg: linear-gradient(170deg, #16222d 0%, #121d27 60%, #0e1720 100%);
    --simplemdm-heading-bg: linear-gradient(90deg, #1f3040 0%, #1a2938 100%);
    --simplemdm-card-bg: linear-gradient(180deg, #192837 0%, #142230 100%);
    --simplemdm-accent: #4aa8d1;
    --simplemdm-accent-alt: #6cb9dc;
    --simplemdm-accent-soft: #213b4c;
    --simplemdm-accent-strong: #3890b6;
    --simplemdm-widget-shadow: 0 12px 22px rgba(0, 0, 0, 0.4);
    --simplemdm-card-shadow: 0 6px 12px rgba(0, 0, 0, 0.28);
    --simplemdm-card-shadow-hover: 0 10px 16px rgba(0, 0, 0, 0.34);
    --simplemdm-item-shadow-hover: 0 6px 11px rgba(0, 0, 0, 0.24);
    --simplemdm-pill-bg: #253645;
    --simplemdm-chart-muted: #405a6f;
    --simplemdm-positive: #52bb73;
    --simplemdm-warning: #f0a84f;
    --simplemdm-danger: #dc6767;
    --simplemdm-info: #61a9e6;
    --simplemdm-series-4: #967af4;
    --simplemdm-series-5: #ef71a5;
    --simplemdm-series-6: #53bf8f;
    --simplemdm-series-7: #f0b35b;
    --simplemdm-series-8: #99acbc;
    --simplemdm-surface: #162330;
    --simplemdm-surface-alt: #192736;
    --simplemdm-surface-hover: #1f3243;
}

.simplemdm-modern-widget {
    border: 1px solid var(--simplemdm-border);
    border-radius: 16px;
    overflow: hidden;
    background: var(--simplemdm-panel-bg);
    box-shadow: var(--simplemdm-widget-shadow);
    position: relative;
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
}

.simplemdm-modern-widget:before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--simplemdm-accent) 0%, var(--simplemdm-accent-alt) 100%);
}

.simplemdm-modern-widget .panel-heading {
    border-bottom: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-heading-bg);
    color: var(--simplemdm-ink);
    padding: 13px 15px 12px;
}

.simplemdm-modern-widget .panel-title {
    font-family: "Avenir Next", "Segoe UI", "Helvetica Neue", sans-serif;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 0.55px;
    text-transform: uppercase;
}

.simplemdm-modern-widget .panel-title i {
    color: var(--simplemdm-accent);
    margin-right: 5px;
}

.simplemdm-modern-widget .panel-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1 1 auto;
}

.simplemdm-modern-widget .list-group {
    margin-bottom: 0;
}

.simplemdm-modern-widget .list-group-item {
    border-radius: 11px;
    margin-bottom: 8px;
    border: 1px solid var(--simplemdm-border);
    color: var(--simplemdm-ink);
    font-weight: 700;
    background: var(--simplemdm-surface);
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.simplemdm-modern-widget .list-group-item:hover {
    transform: translateY(-1px);
    box-shadow: var(--simplemdm-item-shadow-hover);
    border-color: var(--simplemdm-hover-border);
}

.simplemdm-modern-widget .list-group-item:last-child {
    margin-bottom: 0;
}

.simplemdm-modern-widget .badge {
    background: var(--simplemdm-accent-soft) !important;
    color: var(--simplemdm-ink) !important;
    border: 1px solid var(--simplemdm-border-strong) !important;
    font-weight: 800 !important;
    font-size: 12px !important;
    line-height: 1.15 !important;
    min-width: 24px;
    padding: 3px 8px;
    text-align: center;
    white-space: nowrap;
    display: inline-block;
    text-shadow: none !important;
}

.simplemdm-modern-widget .btn {
    border-radius: 10px;
    font-weight: 700;
    letter-spacing: 0.2px;
}

.simplemdm-modern-widget .btn-primary {
    background: var(--simplemdm-accent);
    border-color: var(--simplemdm-accent);
    box-shadow: 0 4px 10px rgba(10, 127, 168, 0.2);
}

.simplemdm-modern-widget .btn-primary:hover,
.simplemdm-modern-widget .btn-primary:focus {
    background: var(--simplemdm-accent-strong);
    border-color: var(--simplemdm-accent-strong);
}

.simplemdm-modern-widget .btn {
    align-self: flex-start;
}

.simplemdm-modern-widget .btn-default {
    border-color: var(--simplemdm-border);
    color: var(--simplemdm-ink);
    background: var(--simplemdm-surface);
}

.simplemdm-modern-widget .svg-container {
    margin-bottom: 10px;
}

#simplemdm-report-grid {
    column-gap: 18px;
}

#simplemdm-report-grid > [id^="simplemdm-widget-"] {
    display: inline-block;
    width: 100%;
    margin: 0 0 18px;
    break-inside: avoid;
    -webkit-column-break-inside: avoid;
    page-break-inside: avoid;
}

#simplemdm-report-grid > [id^="simplemdm-widget-"] > [class*="col-"] {
    float: none;
    width: 100%;
    padding-left: 0;
    padding-right: 0;
}

#simplemdm-report-grid > [id^="simplemdm-widget-"].simplemdm-widget-hidden {
    display: none !important;
}

@media (min-width: 1200px) {
    #simplemdm-report-grid {
        column-count: 3;
    }
}

@media (min-width: 768px) and (max-width: 1199px) {
    #simplemdm-report-grid {
        column-count: 2;
    }
}

@media (max-width: 767px) {
    #simplemdm-report-grid {
        column-count: 1;
    }
}

#simplemdm-dashboard-grid {
    position: relative;
    min-height: 1px;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item {
    position: absolute;
    margin: 0;
    box-sizing: border-box;
    transition: box-shadow 0.18s ease;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-is-dragging {
    z-index: 110;
    box-shadow: 0 14px 30px rgba(8, 26, 43, 0.3);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-drop-target .simplemdm-modern-widget {
    outline: 2px dashed var(--simplemdm-accent);
    outline-offset: 2px;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item > [class*="col-"] {
    float: none;
    width: 100%;
    padding-left: 0;
    padding-right: 0;
}

.simplemdm-modern-widget .simplemdm-drag-handle {
    float: right;
    font-size: 13px;
    opacity: 0;
    cursor: move;
    margin-left: 8px;
    padding: 2px 4px;
    border-radius: 5px;
    pointer-events: none;
}

.simplemdm-modern-widget .simplemdm-drag-handle:hover {
    background: rgba(0, 0, 0, 0.08);
}

body.simplemdm-theme-dark .simplemdm-modern-widget .simplemdm-drag-handle:hover {
    background: rgba(255, 255, 255, 0.12);
}

.simplemdm-modern-widget .simplemdm-widget-actions {
    float: right;
    display: inline-flex;
    gap: 4px;
    margin-left: 8px;
    align-items: center;
    opacity: 0;
    pointer-events: none;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-widget-actions,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-drag-handle {
    opacity: 1;
    pointer-events: auto;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-modern-widget {
    outline: none;
    box-shadow: 0 0 0 2px var(--simplemdm-accent), var(--simplemdm-widget-shadow);
}

.simplemdm-modern-widget .simplemdm-order-btn {
    border: 1px solid var(--simplemdm-border);
    background: transparent;
    color: var(--simplemdm-muted);
    padding: 0 4px;
    line-height: 1.3;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
}

.simplemdm-modern-widget .simplemdm-order-btn:hover {
    color: var(--simplemdm-ink);
    border-color: var(--simplemdm-accent);
}

.simplemdm-modern-widget .simplemdm-collapse-btn {
    border: 1px solid var(--simplemdm-border);
    background: transparent;
    color: var(--simplemdm-muted);
    padding: 0 4px;
    line-height: 1.3;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
}

.simplemdm-modern-widget .simplemdm-collapse-btn:hover {
    color: var(--simplemdm-ink);
    border-color: var(--simplemdm-accent);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-collapsed .panel-body {
    display: none !important;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-collapsed .simplemdm-widget-resize-handle-right,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-collapsed .simplemdm-widget-resize-handle-bottom,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-collapsed .simplemdm-widget-resize-handle-corner {
    opacity: 0 !important;
    pointer-events: none !important;
}

#simplemdm-layout-reset-btn {
    position: fixed;
    right: 18px;
    bottom: 18px;
    z-index: 1200;
    border: 1px solid var(--simplemdm-border);
    background: var(--simplemdm-surface);
    color: var(--simplemdm-ink);
    border-radius: 8px;
    padding: 7px 11px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.25);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle {
    position: absolute;
    width: 0;
    height: 0;
    right: 0;
    bottom: 0;
    cursor: default;
    z-index: 30;
    pointer-events: none;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-right,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-bottom,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-corner {
    position: absolute;
    opacity: 0;
    transition: opacity 0.15s ease;
    pointer-events: none;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-right {
    top: 12px;
    right: 0;
    width: 12px;
    height: calc(100% - 24px);
    cursor: ew-resize;
    background: rgba(10, 127, 168, 0.12);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-bottom {
    left: 12px;
    bottom: 0;
    width: calc(100% - 24px);
    height: 12px;
    cursor: ns-resize;
    background: rgba(10, 127, 168, 0.12);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-corner {
    right: 0;
    bottom: 0;
    width: 24px;
    height: 24px;
    cursor: nwse-resize;
    border-top-left-radius: 8px;
    background: linear-gradient(135deg, transparent 40%, rgba(0, 0, 0, 0.05) 100%);
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-corner:before {
    content: '';
    position: absolute;
    right: 0;
    bottom: 0;
    width: 12px;
    height: 12px;
    border-right: 2px solid var(--simplemdm-muted);
    border-bottom: 2px solid var(--simplemdm-muted);
    opacity: 0.85;
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-widget-resize-handle-right,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-widget-resize-handle-bottom,
#simplemdm-dashboard-grid > .simplemdm-dashboard-item.simplemdm-widget-selected .simplemdm-widget-resize-handle-corner {
    opacity: 1;
    pointer-events: auto;
}

body.simplemdm-theme-dark #simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-right,
body.simplemdm-theme-dark #simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-bottom {
    background: rgba(117, 200, 232, 0.16);
}

body.simplemdm-theme-dark #simplemdm-dashboard-grid > .simplemdm-dashboard-item .simplemdm-widget-resize-handle-corner {
    background: linear-gradient(135deg, transparent 40%, rgba(255, 255, 255, 0.08) 100%);
}

.simplemdm-modern-widget.simplemdm-list-scroll .list-group {
    max-height: 260px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 2px;
}

.simplemdm-section-body.simplemdm-collapsed {
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

#simplemdm-group-list-body .list-group {
    max-height: none !important;
    overflow-y: visible !important;
    overflow-x: visible !important;
}

#simplemdm-resource-types-cards-body #simplemdm-resource-type-cards {
    max-height: none !important;
    overflow-y: visible !important;
    overflow-x: visible !important;
}

.simplemdm-kpi-value {
    font-size: 40px;
    line-height: 1;
    font-weight: 900;
    color: var(--simplemdm-ink);
    margin-bottom: 12px;
    letter-spacing: -0.8px;
}

.simplemdm-rt-share {
    color: var(--simplemdm-muted) !important;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.2;
}

.simplemdm-resource-type-card {
    border: 1px solid var(--simplemdm-border);
    border-radius: 13px;
    background: var(--simplemdm-card-bg);
    padding: 13px;
    margin-bottom: 12px;
    min-height: 128px;
    box-shadow: var(--simplemdm-card-shadow);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.simplemdm-resource-type-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--simplemdm-card-shadow-hover);
}

.simplemdm-resource-type-title {
    color: var(--simplemdm-muted);
    font-weight: 800;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.7px;
}

.simplemdm-resource-type-count {
    font-size: 34px;
    font-weight: 900;
    color: var(--simplemdm-ink);
    line-height: 1;
    margin-bottom: 10px;
    letter-spacing: -0.6px;
}

.simplemdm-meta-row {
    margin-top: 10px;
}

.simplemdm-meta-pill {
    display: inline-block;
    margin: 0 8px 8px 0;
    padding: 6px 10px;
    border-radius: 999px;
    background: var(--simplemdm-pill-bg);
    color: var(--simplemdm-ink);
    font-size: 12px;
    font-weight: 700;
}

.simplemdm-mini-list {
    margin-top: 10px;
}

.simplemdm-mini-list .list-group-item {
    padding-top: 8px;
    padding-bottom: 8px;
}

.simplemdm-modern-widget .list-group-item.active,
.simplemdm-modern-widget .list-group-item.active:focus,
.simplemdm-modern-widget .list-group-item.active:hover {
    color: var(--simplemdm-ink);
    background: var(--simplemdm-surface-alt);
    border-color: var(--simplemdm-border-strong);
}

/* Keep chart text readable regardless of global dark-mode typography rules */
.simplemdm-modern-widget .nvd3 text {
    fill: var(--simplemdm-ink) !important;
}

.simplemdm-modern-widget .nvd3 .nv-axis path,
.simplemdm-modern-widget .nvd3 .nv-axis line {
    stroke: var(--simplemdm-border-strong) !important;
}

/* Ensure chart primitives are visible even before hover states */
.simplemdm-modern-widget .nvd3 .nv-discretebar .nv-bar,
.simplemdm-modern-widget .nvd3 .nv-discreteBar .nv-bar,
.simplemdm-modern-widget .nvd3 .nv-multibar .nv-bar,
.simplemdm-modern-widget .nvd3 .nv-multibarHorizontal .nv-bar {
    opacity: 1 !important;
}

.simplemdm-modern-widget .nvd3 .nv-discretebar .nv-bar rect,
.simplemdm-modern-widget .nvd3 .nv-discreteBar .nv-bar rect,
.simplemdm-modern-widget .nvd3 .nv-multibar .nv-bar rect,
.simplemdm-modern-widget .nvd3 .nv-multibarHorizontal .nv-bar rect {
    fill-opacity: 0.96 !important;
    stroke: rgba(255, 255, 255, 0.20);
    stroke-width: 1px;
}

.simplemdm-modern-widget .nvd3 .nv-pie .nv-slice path {
    fill-opacity: 0.94 !important;
    stroke: rgba(255, 255, 255, 0.22);
    stroke-width: 1px;
}

.simplemdm-modern-widget .nvd3 .nv-line path {
    stroke-width: 2.4px !important;
    opacity: 1 !important;
}

.simplemdm-modern-widget .nvd3 .nv-point {
    fill-opacity: 1 !important;
    stroke-opacity: 1 !important;
}

#simplemdm-resource-types-widget .nvd3 .nv-multibarHorizontal .nv-bar rect,
#simplemdm-resource-types-widget .nvd3 .nv-multibar .nv-bar rect {
    fill-opacity: 0.96 !important;
    stroke: rgba(255, 255, 255, 0.22);
    stroke-width: 1px;
}

#simplemdm-group-widget .nvd3 .nv-discretebar .nv-bar,
#simplemdm-group-widget .nvd3 .nv-discreteBar .nv-bar {
    opacity: 1 !important;
}

#simplemdm-group-widget .nvd3 .nv-discretebar .nv-bar rect,
#simplemdm-group-widget .nvd3 .nv-discreteBar .nv-bar rect {
    fill-opacity: 0.98 !important;
    stroke: rgba(255, 255, 255, 0.22);
    stroke-width: 1px;
}

.simplemdm-modern-widget .nvd3 .nv-legend .nv-series text {
    fill: var(--simplemdm-ink) !important;
}

.simplemdm-modern-widget .nvd3 .nvtooltip,
.simplemdm-modern-widget .nvtooltip {
    color: var(--simplemdm-ink) !important;
}

.simplemdm-layout-compact .simplemdm-modern-widget {
    border-radius: 13px;
    box-shadow: 0 10px 20px rgba(10, 31, 53, 0.1);
}

.simplemdm-layout-compact .simplemdm-modern-widget .panel-heading {
    padding: 10px 12px 9px;
}

.simplemdm-layout-compact .simplemdm-modern-widget .panel-title {
    font-size: 12px;
    letter-spacing: 0.45px;
}

.simplemdm-layout-compact .simplemdm-modern-widget .panel-body {
    padding: 12px;
}

.simplemdm-layout-compact .simplemdm-modern-widget .btn {
    border-radius: 8px;
    font-size: 11px;
    padding-top: 3px;
    padding-bottom: 3px;
}

.simplemdm-layout-compact .simplemdm-kpi-value {
    font-size: 32px;
    margin-bottom: 8px;
}

.simplemdm-layout-compact .simplemdm-rt-share {
    font-size: 13px;
}

.simplemdm-layout-compact .simplemdm-resource-type-card {
    border-radius: 11px;
    padding: 10px;
    min-height: 110px;
}

.simplemdm-layout-compact .simplemdm-resource-type-title {
    font-size: 10px;
}

.simplemdm-layout-compact .simplemdm-resource-type-count {
    font-size: 28px;
}

.simplemdm-layout-compact .simplemdm-meta-pill {
    font-size: 11px;
    padding: 5px 8px;
    margin-right: 6px;
    margin-bottom: 6px;
}

@media (max-width: 767px) {
    .simplemdm-modern-widget .panel-title {
        font-size: 12px;
    }

    .simplemdm-kpi-value {
        font-size: 34px;
    }
}
</style>
<script>
(function() {
    function isLikelyDashboardPage() {
        var p = String(window.location.pathname || '').toLowerCase();
        var h = String(window.location.hash || '').toLowerCase();
        var q = String(window.location.search || '').toLowerCase();
        if (p.indexOf('/show/dashboard') !== -1 || q.indexOf('/show/dashboard') !== -1 || h.indexOf('/show/dashboard') !== -1) {
            return true;
        }
        if (document.getElementById('dashboard')) {
            return true;
        }
        return false;
    }

    function isDashboardGridEnabled() {
        if (window.SIMPLEMDM_FORCE_DASHBOARD_GRID === true) {
            return true;
        }
        if (window.SIMPLEMDM_DISABLE_DASHBOARD_GRID === true) {
            return false;
        }
        var b = document.body;
        if (b && String(b.getAttribute('data-simplemdm-disable-dashboard-grid') || '') === '1') {
            return false;
        }
        if (!isLikelyDashboardPage()) {
            return false;
        }
        return true;
    }

    if (window.simplemdmLayoutModeInit) {
        return;
    }
    window.simplemdmLayoutModeInit = true;
    var simplemdmGridMetrics = { cols: 1, colWidth: 0, gap: 18 };
    var activeDrag = null;
    var activeResize = null;
    var selectedWidgetKey = '';
    var simplemdmStateSaveTimer = null;
    var SIMPLEMDM_SMALL_WIDGET_MIN_HEIGHT = 300;

    function isFeaturedWidgetKey(key) {
        var k = String(key || '').toLowerCase().trim();
        return k === 'simplemdm_resource_types' || k === 'simplemdm_group_top' || k === 'simplemdm_group';
    }

    function getDashboardLayoutStorageKey() {
        var path = String(window.location.pathname || '').toLowerCase();
        var hash = String(window.location.hash || '').toLowerCase().split('?')[0];
        return 'simplemdm_dashboard_layout_v1_' + path + '_' + hash;
    }

    function sanitizeDashboardLayoutState(raw) {
        var clean = {
            order: [],
            span: {},
            minHeight: {},
            column: {},
            top: {},
            collapsed: {}
        };
        if (!raw || typeof raw !== 'object') {
            return clean;
        }
        if (Array.isArray(raw.order)) {
            for (var i = 0; i < raw.order.length; i++) {
                var key = String(raw.order[i] || '').trim();
                if (!key || clean.order.indexOf(key) !== -1) {
                    continue;
                }
                clean.order.push(key);
            }
        }
        if (raw.span && typeof raw.span === 'object') {
            var spanKeys = Object.keys(raw.span);
            for (var s = 0; s < spanKeys.length; s++) {
                var spanKey = String(spanKeys[s] || '').trim();
                var spanValue = parseInt(raw.span[spanKey], 10);
                if (!spanKey || !spanValue || spanValue < 1 || spanValue > 6) {
                    continue;
                }
                clean.span[spanKey] = spanValue;
            }
        }
        if (raw.minHeight && typeof raw.minHeight === 'object') {
            var minHeightKeys = Object.keys(raw.minHeight);
            for (var h = 0; h < minHeightKeys.length; h++) {
                var minKey = String(minHeightKeys[h] || '').trim();
                var minValue = parseInt(raw.minHeight[minKey], 10);
                if (!minKey || !minValue || minValue < 120 || minValue > 2400) {
                    continue;
                }
                clean.minHeight[minKey] = minValue;
            }
        }
        if (raw.column && typeof raw.column === 'object') {
            var columnKeys = Object.keys(raw.column);
            for (var c = 0; c < columnKeys.length; c++) {
                var columnKey = String(columnKeys[c] || '').trim();
                var columnValue = parseInt(raw.column[columnKey], 10);
                if (!columnKey || isNaN(columnValue) || columnValue < 0 || columnValue > 8) {
                    continue;
                }
                clean.column[columnKey] = columnValue;
            }
        }
        if (raw.top && typeof raw.top === 'object') {
            var topKeys = Object.keys(raw.top);
            for (var t = 0; t < topKeys.length; t++) {
                var topKey = String(topKeys[t] || '').trim();
                var topValue = parseInt(raw.top[topKey], 10);
                if (!topKey || isNaN(topValue) || topValue < 0 || topValue > 50000) {
                    continue;
                }
                clean.top[topKey] = topValue;
            }
        }
        if (raw.collapsed && typeof raw.collapsed === 'object') {
            var collapsedKeys = Object.keys(raw.collapsed);
            for (var co = 0; co < collapsedKeys.length; co++) {
                var collapsedKey = String(collapsedKeys[co] || '').trim();
                if (!collapsedKey) {
                    continue;
                }
                clean.collapsed[collapsedKey] = raw.collapsed[collapsedKey] ? 1 : 0;
            }
        }
        return clean;
    }

    function loadDashboardLayoutState() {
        try {
            var raw = window.localStorage ? window.localStorage.getItem(getDashboardLayoutStorageKey()) : '';
            if (!raw) {
                return sanitizeDashboardLayoutState(null);
            }
            return sanitizeDashboardLayoutState(JSON.parse(raw));
        } catch (e) {
            return sanitizeDashboardLayoutState(null);
        }
    }

    var simplemdmDashboardState = loadDashboardLayoutState();

    function saveDashboardLayoutState() {
        if (!window.localStorage) {
            return;
        }
        try {
            window.localStorage.setItem(getDashboardLayoutStorageKey(), JSON.stringify(simplemdmDashboardState));
        } catch (e) {
            return;
        }
    }

    function scheduleSaveDashboardLayoutState() {
        clearTimeout(simplemdmStateSaveTimer);
        simplemdmStateSaveTimer = setTimeout(saveDashboardLayoutState, 120);
    }

    function getDashboardWidgetKey(item) {
        if (!item || !item.getAttribute) {
            return '';
        }
        return String(item.getAttribute('data-simplemdm-key') || item.id || '').trim();
    }

    function getDashboardWidgetOrder(container) {
        if (!container) {
            return [];
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        var order = [];
        for (var i = 0; i < items.length; i++) {
            var key = getDashboardWidgetKey(items[i]);
            if (key && order.indexOf(key) === -1) {
                order.push(key);
            }
        }
        return order;
    }

    function getVisualWidgetOrder(container) {
        if (!container) {
            return [];
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        var rows = [];
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item.classList.contains('simplemdm-widget-hidden') || item.style.display === 'none') {
                continue;
            }
            var rect = item.getBoundingClientRect();
            rows.push({
                key: getDashboardWidgetKey(item),
                top: Math.round(rect.top),
                left: Math.round(rect.left)
            });
        }
        rows.sort(function(a, b) {
            if (Math.abs(a.top - b.top) > 12) {
                return a.top - b.top;
            }
            return a.left - b.left;
        });
        var ordered = [];
        for (var r = 0; r < rows.length; r++) {
            if (rows[r].key) {
                ordered.push(rows[r].key);
            }
        }
        return ordered;
    }

    function applyDashboardOrderToDom(container) {
        if (!container || !simplemdmDashboardState.order || !simplemdmDashboardState.order.length) {
            return;
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        if (!items.length) {
            return;
        }
        var byKey = {};
        for (var i = 0; i < items.length; i++) {
            var key = getDashboardWidgetKey(items[i]);
            if (key) {
                byKey[key] = items[i];
            }
        }
        for (var o = 0; o < simplemdmDashboardState.order.length; o++) {
            var orderedKey = simplemdmDashboardState.order[o];
            if (byKey[orderedKey] && byKey[orderedKey].parentNode === container) {
                container.appendChild(byKey[orderedKey]);
            }
        }
    }

    function syncDashboardOrderState(container) {
        var current = getDashboardWidgetOrder(container);
        if (!current.length) {
            return;
        }
        var updated = [];
        var i;
        for (i = 0; i < simplemdmDashboardState.order.length; i++) {
            if (current.indexOf(simplemdmDashboardState.order[i]) !== -1) {
                updated.push(simplemdmDashboardState.order[i]);
            }
        }
        for (i = 0; i < current.length; i++) {
            if (updated.indexOf(current[i]) === -1) {
                updated.push(current[i]);
            }
        }
        var changed = updated.length !== simplemdmDashboardState.order.length;
        if (!changed) {
            for (i = 0; i < updated.length; i++) {
                if (updated[i] !== simplemdmDashboardState.order[i]) {
                    changed = true;
                    break;
                }
            }
        }
        if (changed) {
            simplemdmDashboardState.order = updated;
            scheduleSaveDashboardLayoutState();
        }
    }

    function getCustomOrderIndex(key) {
        if (!key || !simplemdmDashboardState.order || !simplemdmDashboardState.order.length) {
            return -1;
        }
        return simplemdmDashboardState.order.indexOf(key);
    }

    function getWidgetPreferredColumn(key, totalCols, span) {
        if (!key || !simplemdmDashboardState.column) {
            return -1;
        }
        var raw = parseInt(simplemdmDashboardState.column[key], 10);
        if (isNaN(raw)) {
            return -1;
        }
        var maxStart = Math.max(0, totalCols - Math.max(1, span || 1));
        return Math.max(0, Math.min(maxStart, raw));
    }

    function setWidgetPreferredColumn(key, col) {
        if (!key || isNaN(col)) {
            return;
        }
        simplemdmDashboardState.column[key] = Math.max(0, Math.round(col));
        scheduleSaveDashboardLayoutState();
    }

    function getWidgetPreferredTop(key) {
        if (!key || !simplemdmDashboardState.top) {
            return -1;
        }
        var raw = parseInt(simplemdmDashboardState.top[key], 10);
        if (isNaN(raw)) {
            return -1;
        }
        return Math.max(0, raw);
    }

    function setWidgetPreferredTop(key, top) {
        if (!key || isNaN(top)) {
            return;
        }
        simplemdmDashboardState.top[key] = Math.max(0, Math.round(top));
        scheduleSaveDashboardLayoutState();
    }

    function isWidgetCollapsed(key) {
        if (!key || !simplemdmDashboardState.collapsed) {
            return false;
        }
        return simplemdmDashboardState.collapsed[key] ? true : false;
    }

    function setWidgetCollapsed(key, collapsed) {
        if (!key) {
            return;
        }
        simplemdmDashboardState.collapsed[key] = collapsed ? 1 : 0;
        scheduleSaveDashboardLayoutState();
    }

    function getDropColumnFromX(container, clientX) {
        if (!container) {
            return 0;
        }
        var cols = Math.max(1, simplemdmGridMetrics.cols || getDashboardColumnCount());
        var colWidth = Math.max(1, simplemdmGridMetrics.colWidth || 220);
        var gap = Math.max(0, simplemdmGridMetrics.gap || 18);
        var rect = container.getBoundingClientRect();
        var relX = Math.max(0, clientX - rect.left);
        var step = Math.max(1, colWidth + gap);
        var col = Math.floor(relX / step);
        return Math.max(0, Math.min(cols - 1, col));
    }

    function getDropTopFromY(container, clientY) {
        if (!container) {
            return 0;
        }
        var rect = container.getBoundingClientRect();
        var relY = Math.max(0, clientY - rect.top - 24);
        return Math.round(relY);
    }

    function ensureDashboardResetControl() {
        if (!isDashboardGridEnabled()) {
            return;
        }
        if (document.getElementById('simplemdm-layout-reset-btn')) {
            return;
        }
        var btn = document.createElement('button');
        btn.id = 'simplemdm-layout-reset-btn';
        btn.type = 'button';
        btn.textContent = 'Reset Layout';
        btn.addEventListener('click', function() {
            if (window.confirm && !window.confirm('Reset dashboard layout to defaults?')) {
                return;
            }
            window.simplemdmResetDashboardLayout();
        });
        document.body.appendChild(btn);
    }

    function getWidgetMinHeight(item) {
        var panel = item ? item.querySelector('.simplemdm-modern-widget') : null;
        if (!panel) {
            return 0;
        }
        var direct = parseInt(panel.style.minHeight, 10);
        if (direct && direct > 0) {
            return direct;
        }
        return Math.round(panel.getBoundingClientRect().height || 0);
    }

    function applyWidgetMinHeight(item) {
        if (!item) {
            return;
        }
        var key = getDashboardWidgetKey(item);
        var panel = item.querySelector('.simplemdm-modern-widget');
        if (!panel || !key) {
            return;
        }
        var collapsed = isWidgetCollapsed(key);
        item.classList.toggle('simplemdm-widget-collapsed', collapsed);
        if (collapsed) {
            panel.style.height = '';
            panel.style.minHeight = '';
            return;
        }
        var value = parseInt(simplemdmDashboardState.minHeight[key], 10);
        if (value && value >= 120) {
            panel.style.height = '';
            panel.style.minHeight = value + 'px';
            return;
        }
        if (!isFeaturedWidgetKey(key)) {
            panel.style.height = '';
            panel.style.minHeight = SIMPLEMDM_SMALL_WIDGET_MIN_HEIGHT + 'px';
            return;
        }
        panel.style.height = '';
        panel.style.minHeight = '';
    }

    function getDashboardWidgetRootFromTarget(target) {
        if (!target || !target.closest) {
            return null;
        }
        return target.closest('#simplemdm-dashboard-grid > .simplemdm-dashboard-item');
    }

    function setSelectedDashboardWidget(item) {
        var container = document.getElementById('simplemdm-dashboard-grid');
        if (container) {
            var selected = container.querySelectorAll('.simplemdm-widget-selected');
            for (var i = 0; i < selected.length; i++) {
                selected[i].classList.remove('simplemdm-widget-selected');
            }
        }
        if (!item) {
            selectedWidgetKey = '';
            return;
        }
        var key = getDashboardWidgetKey(item);
        if (!key) {
            selectedWidgetKey = '';
            return;
        }
        selectedWidgetKey = key;
        item.classList.add('simplemdm-widget-selected');
    }

    function restoreSelectedDashboardWidget(container) {
        if (!container || !selectedWidgetKey) {
            return;
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        for (var i = 0; i < items.length; i++) {
            if (getDashboardWidgetKey(items[i]) === selectedWidgetKey) {
                items[i].classList.add('simplemdm-widget-selected');
                return;
            }
        }
    }

    function beginWidgetResize(resizeItem, clientX, clientY, mode) {
        if (!resizeItem) {
            return false;
        }
        var resizeKey = getDashboardWidgetKey(resizeItem);
        if (!resizeKey) {
            return false;
        }
        activeResize = {
            item: resizeItem,
            key: resizeKey,
            startX: clientX,
            startY: clientY,
            mode: (mode === 'x' || mode === 'y' || mode === 'xy') ? mode : 'xy',
            startSpan: parseInt(resizeItem.getAttribute('data-simplemdm-span'), 10) || 1,
            startMinHeight: getWidgetMinHeight(resizeItem) || Math.round(resizeItem.getBoundingClientRect().height || 180)
        };
        resizeItem.classList.add('simplemdm-is-dragging');
        document.body.style.userSelect = 'none';
        return true;
    }

    function clearDropTargets(container) {
        if (!container) {
            return;
        }
        var targets = container.querySelectorAll('.simplemdm-drop-target');
        for (var i = 0; i < targets.length; i++) {
            targets[i].classList.remove('simplemdm-drop-target');
        }
    }

    function getHoveredDropTarget(container, draggingItem, clientX, clientY) {
        if (!container || !document.elementFromPoint) {
            return null;
        }
        var hoveredNode = document.elementFromPoint(clientX, clientY);
        var target = getDashboardWidgetRootFromTarget(hoveredNode);
        if (!target || target === draggingItem) {
            return null;
        }
        if (target.classList.contains('simplemdm-widget-hidden') || target.style.display === 'none') {
            return null;
        }
        return target;
    }

    function getNearestDropTargetWithin(container, draggingItem, clientX, clientY, maxDistance) {
        if (!container) {
            return null;
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        var best = null;
        var bestDistance = Number.MAX_SAFE_INTEGER;
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item === draggingItem || item.classList.contains('simplemdm-widget-hidden') || item.style.display === 'none') {
                continue;
            }
            var rect = item.getBoundingClientRect();
            // Distance from pointer to rectangle (0 when inside).
            var dx = Math.max(rect.left - clientX, 0, clientX - rect.right);
            var dy = Math.max(rect.top - clientY, 0, clientY - rect.bottom);
            var distance = Math.sqrt((dx * dx) + (dy * dy));
            if (distance < bestDistance) {
                bestDistance = distance;
                best = item;
            }
        }
        if (typeof maxDistance === 'number' && bestDistance > maxDistance) {
            return null;
        }
        return best;
    }

    function resolveDropTarget(container, draggingItem, clientX, clientY) {
        var target = getHoveredDropTarget(container, draggingItem, clientX, clientY);
        if (!target) {
            // Only treat near-edge drops as swap intent; otherwise keep empty-space insertion.
            target = getNearestDropTargetWithin(container, draggingItem, clientX, clientY, 22);
        }
        return target;
    }

    function getDropInsertionIndex(container, draggingKey, clientX, clientY) {
        if (!container) {
            return -1;
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        var rows = [];
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (item.classList.contains('simplemdm-widget-hidden') || item.style.display === 'none') {
                continue;
            }
            var key = getDashboardWidgetKey(item);
            if (!key || key === draggingKey) {
                continue;
            }
            var rect = item.getBoundingClientRect();
            rows.push({
                key: key,
                top: Math.round(rect.top),
                left: Math.round(rect.left)
            });
        }
        rows.push({
            key: '__drag__',
            top: Math.round(clientY),
            left: Math.round(clientX)
        });

        rows.sort(function(a, b) {
            if (Math.abs(a.top - b.top) > 12) {
                return a.top - b.top;
            }
            return a.left - b.left;
        });

        for (var r = 0; r < rows.length; r++) {
            if (rows[r].key === '__drag__') {
                return r;
            }
        }
        return rows.length - 1;
    }

    function moveDashboardWidgetToIndex(key, targetIndex, preferredColumn, preferredTop) {
        if (!key || targetIndex < 0) {
            return;
        }
        var container = document.getElementById('simplemdm-dashboard-grid');
        var order = getVisualWidgetOrder(container);
        if (!order.length) {
            order = simplemdmDashboardState.order.slice();
        }
        if (!order.length) {
            order = getDashboardWidgetOrder(container);
        }
        var from = order.indexOf(key);
        if (from === -1) {
            order.push(key);
            from = order.length - 1;
        }
        order.splice(from, 1);
        var at = Math.max(0, Math.min(order.length, targetIndex));
        order.splice(at, 0, key);
        simplemdmDashboardState.order = order;
        if (!isNaN(preferredColumn)) {
            setWidgetPreferredColumn(key, preferredColumn);
        }
        if (!isNaN(preferredTop)) {
            setWidgetPreferredTop(key, preferredTop);
        }
        applyDashboardOrderToDom(container);
        scheduleSaveDashboardLayoutState();
    }

    function swapDashboardWidgets(firstKey, secondKey) {
        if (!firstKey || !secondKey || firstKey === secondKey) {
            return;
        }
        var order = simplemdmDashboardState.order.slice();
        if (!order.length) {
            var container = document.getElementById('simplemdm-dashboard-grid');
            order = getDashboardWidgetOrder(container);
        }
        if (order.indexOf(firstKey) === -1) {
            order.push(firstKey);
        }
        if (order.indexOf(secondKey) === -1) {
            order.push(secondKey);
        }
        var firstIndex = order.indexOf(firstKey);
        var secondIndex = order.indexOf(secondKey);
        if (firstIndex === -1 || secondIndex === -1 || firstIndex === secondIndex) {
            return;
        }
        var tmp = order[firstIndex];
        order[firstIndex] = order[secondIndex];
        order[secondIndex] = tmp;
        simplemdmDashboardState.order = order;
        var firstCol = simplemdmDashboardState.column[firstKey];
        var secondCol = simplemdmDashboardState.column[secondKey];
        if (firstCol !== undefined || secondCol !== undefined) {
            simplemdmDashboardState.column[firstKey] = secondCol;
            simplemdmDashboardState.column[secondKey] = firstCol;
        }
        var firstTop = simplemdmDashboardState.top[firstKey];
        var secondTop = simplemdmDashboardState.top[secondKey];
        if (firstTop !== undefined || secondTop !== undefined) {
            simplemdmDashboardState.top[firstKey] = secondTop;
            simplemdmDashboardState.top[secondKey] = firstTop;
        }
        applyDashboardOrderToDom(document.getElementById('simplemdm-dashboard-grid'));
        scheduleSaveDashboardLayoutState();
    }

    function moveDashboardWidget(key, step) {
        if (!key || !step) {
            return;
        }
        var container = document.getElementById('simplemdm-dashboard-grid');
        var order = getVisualWidgetOrder(container);
        if (!order.length) {
            order = simplemdmDashboardState.order.slice();
        }
        if (!order.length) {
            order = getDashboardWidgetOrder(container);
        }
        var index = order.indexOf(key);
        if (index === -1) {
            return;
        }
        var next = Math.max(0, Math.min(order.length - 1, index + step));
        if (next === index) {
            return;
        }
        order.splice(index, 1);
        order.splice(next, 0, key);
        simplemdmDashboardState.order = order;
        applyDashboardOrderToDom(document.getElementById('simplemdm-dashboard-grid'));
        scheduleSaveDashboardLayoutState();
    }

    function moveDashboardWidgetToTop(key) {
        if (!key) {
            return;
        }
        var order = simplemdmDashboardState.order.slice();
        if (!order.length) {
            var container = document.getElementById('simplemdm-dashboard-grid');
            order = getDashboardWidgetOrder(container);
        }
        var index = order.indexOf(key);
        if (index <= 0) {
            return;
        }
        order.splice(index, 1);
        order.unshift(key);
        simplemdmDashboardState.order = order;
        applyDashboardOrderToDom(document.getElementById('simplemdm-dashboard-grid'));
        scheduleSaveDashboardLayoutState();
    }

    function ensureDashboardWidgetControls(container) {
        if (!container) {
            return;
        }
        var items = container.querySelectorAll('.simplemdm-dashboard-item');
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var itemKey = getDashboardWidgetKey(item);
            var panel = item.querySelector('.simplemdm-modern-widget');
            if (!panel) {
                continue;
            }
            var heading = panel.querySelector('.panel-heading');
            if (heading && !heading.querySelector('.simplemdm-drag-handle')) {
                if (!heading.querySelector('.simplemdm-widget-actions')) {
                    var actions = document.createElement('span');
                    actions.className = 'simplemdm-widget-actions';
                    actions.innerHTML = ''
                        + '<button type="button" class="simplemdm-collapse-btn" data-simplemdm-collapse="toggle" title="Collapse widget" aria-label="Collapse widget"><i class="fa fa-minus" aria-hidden="true"></i></button>'
                        + '<button type="button" class="simplemdm-order-btn" data-simplemdm-order="top" title="Move widget to top" aria-label="Move widget to top"><i class="fa fa-angle-double-up" aria-hidden="true"></i></button>'
                        + '<button type="button" class="simplemdm-order-btn" data-simplemdm-order="up" title="Move widget up" aria-label="Move widget up"><i class="fa fa-angle-up" aria-hidden="true"></i></button>'
                        + '<button type="button" class="simplemdm-order-btn" data-simplemdm-order="down" title="Move widget down" aria-label="Move widget down"><i class="fa fa-angle-down" aria-hidden="true"></i></button>';
                    heading.appendChild(actions);
                }
                var handle = document.createElement('span');
                handle.className = 'simplemdm-drag-handle';
                handle.setAttribute('title', 'Drag to move widget');
                handle.setAttribute('aria-label', 'Drag to move widget');
                handle.innerHTML = '<i class="fa fa-arrows" aria-hidden="true"></i>';
                heading.appendChild(handle);
            }
            if (!item.querySelector('.simplemdm-widget-resize-handle')) {
                var resizeHandle = document.createElement('div');
                resizeHandle.className = 'simplemdm-widget-resize-handle';
                resizeHandle.innerHTML = ''
                    + '<div class="simplemdm-widget-resize-handle-right" data-resize-dir="x" title="Drag to change width" aria-label="Drag to change width"></div>'
                    + '<div class="simplemdm-widget-resize-handle-bottom" data-resize-dir="y" title="Drag to change height" aria-label="Drag to change height"></div>'
                    + '<div class="simplemdm-widget-resize-handle-corner" data-resize-dir="xy" title="Drag to change width and height" aria-label="Drag to change width and height"></div>';
                item.appendChild(resizeHandle);
            }
            var collapseBtn = heading ? heading.querySelector('.simplemdm-collapse-btn[data-simplemdm-collapse]') : null;
            if (collapseBtn) {
                var collapsed = itemKey ? isWidgetCollapsed(itemKey) : false;
                collapseBtn.setAttribute('title', collapsed ? 'Expand widget' : 'Collapse widget');
                collapseBtn.setAttribute('aria-label', collapsed ? 'Expand widget' : 'Collapse widget');
                collapseBtn.innerHTML = collapsed
                    ? '<i class="fa fa-plus" aria-hidden="true"></i>'
                    : '<i class="fa fa-minus" aria-hidden="true"></i>';
            }
        }
    }

    function bindDashboardInteractions() {
        if (document.documentElement.getAttribute('data-simplemdm-interactions-bound') === '1') {
            return;
        }
        document.documentElement.setAttribute('data-simplemdm-interactions-bound', '1');

        document.addEventListener('mousedown', function(e) {
            if (!isDashboardGridEnabled()) {
                return;
            }
            var resizeHandle = e.target.closest ? e.target.closest('[data-resize-dir]') : null;
            if (resizeHandle) {
                var resizeItem = getDashboardWidgetRootFromTarget(resizeHandle);
                if (resizeItem) {
                    setSelectedDashboardWidget(resizeItem);
                }
                var dir = String(resizeHandle.getAttribute('data-resize-dir') || 'xy').toLowerCase();
                if (resizeItem && beginWidgetResize(resizeItem, e.clientX, e.clientY, dir)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }
            }

            var dragHandle = e.target.closest ? e.target.closest('.simplemdm-drag-handle') : null;
            if (!dragHandle) {
                return;
            }
            var item = getDashboardWidgetRootFromTarget(dragHandle);
            if (!item) {
                return;
            }
            setSelectedDashboardWidget(item);
            var key = getDashboardWidgetKey(item);
            if (!key) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            activeDrag = {
                item: item,
                key: key,
                startX: e.clientX,
                startY: e.clientY,
                lastX: e.clientX,
                lastY: e.clientY,
                target: null
            };
            item.classList.add('simplemdm-is-dragging');
            item.style.pointerEvents = 'none';
            return;
        });

        document.addEventListener('click', function(e) {
            var collapseButton = e.target.closest ? e.target.closest('.simplemdm-collapse-btn[data-simplemdm-collapse]') : null;
            if (collapseButton) {
                var collapseItem = getDashboardWidgetRootFromTarget(collapseButton);
                var collapseKey = getDashboardWidgetKey(collapseItem);
                if (!collapseKey) {
                    return;
                }
                setSelectedDashboardWidget(collapseItem);
                e.preventDefault();
                e.stopPropagation();
                var nextCollapsed = !isWidgetCollapsed(collapseKey);
                setWidgetCollapsed(collapseKey, nextCollapsed);
                ensureDashboardWidgetControls(document.getElementById('simplemdm-dashboard-grid'));
                window.simplemdmReflowDashboardGrid();
                return;
            }

            var button = e.target.closest ? e.target.closest('.simplemdm-order-btn[data-simplemdm-order]') : null;
            if (!button) {
                var clickedItem = getDashboardWidgetRootFromTarget(e.target);
                if (clickedItem) {
                    setSelectedDashboardWidget(clickedItem);
                } else if (!activeDrag && !activeResize) {
                    setSelectedDashboardWidget(null);
                }
                return;
            }
            var item = getDashboardWidgetRootFromTarget(button);
            var key = getDashboardWidgetKey(item);
            if (!key) {
                return;
            }
            setSelectedDashboardWidget(item);
            e.preventDefault();
            e.stopPropagation();
            var action = String(button.getAttribute('data-simplemdm-order') || '').toLowerCase();
            if (action === 'top') {
                moveDashboardWidgetToTop(key);
            } else if (action === 'up') {
                moveDashboardWidget(key, -1);
            } else if (action === 'down') {
                moveDashboardWidget(key, 1);
            } else {
                return;
            }
            window.simplemdmReflowDashboardGrid();
        });

        document.addEventListener('mousemove', function(e) {
            if (activeResize) {
                e.preventDefault();
                var mode = String(activeResize.mode || 'xy');
                if (mode === 'x' || mode === 'xy') {
                    var cols = Math.max(1, simplemdmGridMetrics.cols || getDashboardColumnCount());
                    var unit = Math.max(140, (simplemdmGridMetrics.colWidth || 280) + (simplemdmGridMetrics.gap || 18));
                    var dx = e.clientX - activeResize.startX;
                    var nextSpan = Math.round(((activeResize.startSpan * unit) + dx) / unit);
                    nextSpan = Math.max(1, Math.min(cols, nextSpan));
                    simplemdmDashboardState.span[activeResize.key] = nextSpan;
                }
                if (mode === 'y' || mode === 'xy') {
                    var dy = e.clientY - activeResize.startY;
                    var nextMinHeight = Math.max(120, Math.min(2400, Math.round(activeResize.startMinHeight + dy)));
                    simplemdmDashboardState.minHeight[activeResize.key] = nextMinHeight;
                }
                layoutSimplemdmDashboardGrid();
                return;
            }
            if (!activeDrag) {
                return;
            }
            var dxDrag = e.clientX - activeDrag.startX;
            var dyDrag = e.clientY - activeDrag.startY;
            activeDrag.lastX = e.clientX;
            activeDrag.lastY = e.clientY;
            activeDrag.item.style.transform = 'translate(' + dxDrag + 'px,' + dyDrag + 'px)';
            var container = document.getElementById('simplemdm-dashboard-grid');
            var target = getHoveredDropTarget(container, activeDrag.item, e.clientX, e.clientY);
            clearDropTargets(container);
            activeDrag.target = target;
            if (target) {
                target.classList.add('simplemdm-drop-target');
            }
        });

        document.addEventListener('mouseup', function() {
            if (activeResize) {
                activeResize.item.classList.remove('simplemdm-is-dragging');
                document.body.style.userSelect = '';
                scheduleSaveDashboardLayoutState();
                activeResize = null;
                window.simplemdmReflowDashboardGrid();
                return;
            }
            if (!activeDrag) {
                return;
            }
            var container = document.getElementById('simplemdm-dashboard-grid');
            activeDrag.item.style.transform = '';
            activeDrag.item.style.pointerEvents = '';
            activeDrag.item.classList.remove('simplemdm-is-dragging');
            var finalTarget = activeDrag.target;
            if (!finalTarget && typeof activeDrag.lastX === 'number' && typeof activeDrag.lastY === 'number') {
                finalTarget = resolveDropTarget(container, activeDrag.item, activeDrag.lastX, activeDrag.lastY);
            }
            if (finalTarget) {
                var targetKey = getDashboardWidgetKey(finalTarget);
                swapDashboardWidgets(activeDrag.key, targetKey);
                window.simplemdmReflowDashboardGrid();
            } else if (typeof activeDrag.lastX === 'number' && typeof activeDrag.lastY === 'number') {
                var insertionIndex = getDropInsertionIndex(container, activeDrag.key, activeDrag.lastX, activeDrag.lastY);
                var preferredColumn = getDropColumnFromX(container, activeDrag.lastX);
                var preferredTop = getDropTopFromY(container, activeDrag.lastY);
                if (insertionIndex >= 0) {
                    moveDashboardWidgetToIndex(activeDrag.key, insertionIndex, preferredColumn, preferredTop);
                }
                window.simplemdmReflowDashboardGrid();
            }
            clearDropTargets(container);
            activeDrag = null;
        });
    }

    window.simplemdmResetDashboardLayout = function() {
        simplemdmDashboardState = sanitizeDashboardLayoutState(null);
        saveDashboardLayoutState();
        if (typeof window.simplemdmReflowDashboardGrid === 'function') {
            window.simplemdmReflowDashboardGrid();
        }
    };

    window.simplemdmThemeVar = function(name, fallback) {
        var target = document.body || document.documentElement;
        if (!target || !window.getComputedStyle) {
            return fallback;
        }
        var value = window.getComputedStyle(target).getPropertyValue(name);
        value = value ? String(value).trim() : '';
        return value || fallback;
    };

    window.simplemdmThemePalette = function() {
        return {
            accent: window.simplemdmThemeVar('--simplemdm-accent', '#0a7fa8'),
            accentAlt: window.simplemdmThemeVar('--simplemdm-accent-alt', '#2da3cf'),
            accentStrong: window.simplemdmThemeVar('--simplemdm-accent-strong', '#075f7d'),
            muted: window.simplemdmThemeVar('--simplemdm-chart-muted', '#dbe7f3'),
            positive: window.simplemdmThemeVar('--simplemdm-positive', '#2f9e44'),
            warning: window.simplemdmThemeVar('--simplemdm-warning', '#f08c00'),
            danger: window.simplemdmThemeVar('--simplemdm-danger', '#c23b3b'),
            info: window.simplemdmThemeVar('--simplemdm-info', '#1c7ed6'),
            s4: window.simplemdmThemeVar('--simplemdm-series-4', '#6f42c1'),
            s5: window.simplemdmThemeVar('--simplemdm-series-5', '#d63384'),
            s6: window.simplemdmThemeVar('--simplemdm-series-6', '#198754'),
            s7: window.simplemdmThemeVar('--simplemdm-series-7', '#fd7e14'),
            s8: window.simplemdmThemeVar('--simplemdm-series-8', '#6c757d')
        };
    };

    var simplemdmThemeAccents = {
        cerulean: { accent: '#2fa4e7', accentAlt: '#5bb8ee', accentStrong: '#1f8fd3', accentSoft: '#deeffa' },
        cosmo: { accent: '#2780e3', accentAlt: '#4a95ea', accentStrong: '#1f6fca', accentSoft: '#e1ecfa' },
        cyborg: { accent: '#2a9fd6', accentAlt: '#56b2df', accentStrong: '#1d8ec2', accentSoft: '#213845' },
        darkly: { accent: '#375a7f', accentAlt: '#4d729b', accentStrong: '#2f4d6d', accentSoft: '#263645' },
        default: { accent: '#337ab7', accentAlt: '#5a95c7', accentStrong: '#286090', accentSoft: '#e3edf7' },
        flatly: { accent: '#2c3e50', accentAlt: '#4a5f75', accentStrong: '#22313f', accentSoft: '#dfe6ed' },
        journal: { accent: '#eb6864', accentAlt: '#f08a87', accentStrong: '#d9534f', accentSoft: '#fae3e2' },
        lumen: { accent: '#158cba', accentAlt: '#3aa2c9', accentStrong: '#0f759d', accentSoft: '#e0f1f7' },
        paper: { accent: '#2196f3', accentAlt: '#4dadf6', accentStrong: '#1b80cf', accentSoft: '#e3f2fd' },
        readable: { accent: '#4582ec', accentAlt: '#6a9bf0', accentStrong: '#2f6fdd', accentSoft: '#e5edfb' },
        sandstone: { accent: '#325d88', accentAlt: '#54799f', accentStrong: '#2a4f73', accentSoft: '#e2e9ef' },
        simplex: { accent: '#d9230f', accentAlt: '#e04a3a', accentStrong: '#bf1d0c', accentSoft: '#fbe3e0' },
        slate: { accent: '#7a8288', accentAlt: '#93999e', accentStrong: '#666d73', accentSoft: '#2c3237' },
        solar: { accent: '#b58900', accentAlt: '#c59f2f', accentStrong: '#9f7800', accentSoft: '#3a3423' },
        spacelab: { accent: '#446e9b', accentAlt: '#6285ab', accentStrong: '#365a80', accentSoft: '#e4ebf2' },
        superhero: { accent: '#df691a', accentAlt: '#e78a4d', accentStrong: '#c95a10', accentSoft: '#3f3025' },
        united: { accent: '#e95420', accentAlt: '#ef774d', accentStrong: '#d74717', accentSoft: '#fae5df' },
        yeti: { accent: '#008cba', accentAlt: '#30a2c7', accentStrong: '#00779e', accentSoft: '#dff2f7' }
    };

    function extractThemeNameFromHref(href, names) {
        var src = String(href || '').toLowerCase();
        if (!src) {
            return '';
        }
        for (var n = 0; n < names.length; n++) {
            var name = names[n];
            var re = new RegExp('(^|[\\/_-])' + name + '([\\/_\\.-]|$)');
            if (re.test(src)) {
                return name;
            }
        }
        return '';
    }

    function detectBootswatchThemeName() {
        var names = Object.keys(simplemdmThemeAccents);
        var activeMenu = document.querySelector('.dropdown-menu li.active a, .dropdown-menu .active, .theme-menu .active');
        if (activeMenu) {
            var txt = String(activeMenu.textContent || '').toLowerCase().trim();
            if (names.indexOf(txt) !== -1) {
                return txt;
            }
        }

        var links = document.querySelectorAll('link[rel*="stylesheet"]');
        for (var i = links.length - 1; i >= 0; i--) {
            var id = String(links[i].id || '').toLowerCase();
            if (id.indexOf('simplemdm-modern-widget') !== -1) {
                continue;
            }
            var found = extractThemeNameFromHref(links[i].getAttribute('href') || '', names);
            if (found) {
                return found;
            }
        }
        return '';
    }

    function readCssPropColor(el, prop) {
        if (!el || !window.getComputedStyle) {
            return '';
        }
        var v = String(window.getComputedStyle(el).getPropertyValue(prop) || '').trim();
        if (!v || v === 'transparent' || v === 'rgba(0, 0, 0, 0)' || v === 'inherit' || v === 'initial') {
            return '';
        }
        return v;
    }

    function firstAvailableColor(selectors, prop, fallback) {
        for (var i = 0; i < selectors.length; i++) {
            var node = document.querySelector(selectors[i]);
            var c = readCssPropColor(node, prop);
            if (c) {
                return c;
            }
        }
        return fallback;
    }

    function deriveAccentTokens(defaultTokens) {
        var accent = firstAvailableColor(['.btn.btn-primary', '.navbar-inverse', '.navbar-default'], 'background-color', defaultTokens.accent);
        var accentStrong = firstAvailableColor(['.btn.btn-primary', '.navbar-inverse', '.navbar-default'], 'border-top-color', defaultTokens.accentStrong);
        var soft = firstAvailableColor(['.btn.btn-primary', '.panel-info', '.panel-primary'], 'background-color', defaultTokens.accentSoft);
        return {
            accent: accent || defaultTokens.accent,
            accentAlt: accent || defaultTokens.accentAlt,
            accentStrong: accentStrong || defaultTokens.accentStrong,
            accentSoft: soft || defaultTokens.accentSoft
        };
    }

    function applyThemeAccentTokens(themeName, resolvedTheme) {
        var b = getBody();
        if (!b || !b.style) {
            return;
        }

        var theme = String(themeName || '').toLowerCase();
        var tokens = simplemdmThemeAccents[theme] || null;
        if (!tokens) {
            tokens = resolvedTheme === 'dark'
                ? { accent: '#55b7de', accentAlt: '#75c8e8', accentStrong: '#3a9fc9', accentSoft: '#1f3a4d' }
                : { accent: '#0a7fa8', accentAlt: '#2da3cf', accentStrong: '#075f7d', accentSoft: '#e1f2f9' };
        }
        tokens = deriveAccentTokens(tokens);

        b.style.setProperty('--simplemdm-accent', tokens.accent);
        b.style.setProperty('--simplemdm-accent-alt', tokens.accentAlt);
        b.style.setProperty('--simplemdm-accent-strong', tokens.accentStrong);
        b.style.setProperty('--simplemdm-accent-soft', tokens.accentSoft);
        b.setAttribute('data-simplemdm-theme-name', theme || 'auto');
    }

    function applyThemeSurfaceTokens(resolvedTheme) {
        var b = getBody();
        if (!b || !b.style) {
            return;
        }

        var pageBg = firstAvailableColor(['body', 'html'], 'background-color', resolvedTheme === 'dark' ? '#1f2327' : '#f5f5f5');
        var headingBg = firstAvailableColor(
            ['.panel.panel-default:not(.simplemdm-modern-widget) .panel-heading', '.panel-heading', '.navbar'],
            'background-color',
            resolvedTheme === 'dark' ? '#35393d' : '#f5f5f5'
        );
        var panelBg = firstAvailableColor(
            ['.panel.panel-default:not(.simplemdm-modern-widget)', '.panel.panel-default:not(.simplemdm-modern-widget) .panel-body', 'body'],
            'background-color',
            resolvedTheme === 'dark' ? '#2b2f33' : '#ffffff'
        );
        var textColor = firstAvailableColor(
            ['.panel.panel-default:not(.simplemdm-modern-widget)', '.panel-title', 'body'],
            'color',
            resolvedTheme === 'dark' ? '#e9edf2' : '#333333'
        );
        var borderColor = firstAvailableColor(
            ['.panel.panel-default:not(.simplemdm-modern-widget)', '.panel-default', '.table'],
            'border-top-color',
            resolvedTheme === 'dark' ? '#454b52' : '#dddddd'
        );
        var mutedColor = firstAvailableColor(
            ['.text-muted', '.panel-footer', 'body'],
            'color',
            resolvedTheme === 'dark' ? '#b7bec8' : '#777777'
        );

        b.style.setProperty('--simplemdm-ink', textColor);
        b.style.setProperty('--simplemdm-muted', mutedColor);
        b.style.setProperty('--simplemdm-border', borderColor);
        b.style.setProperty('--simplemdm-border-strong', borderColor);
        b.style.setProperty('--simplemdm-hover-border', borderColor);
        b.style.setProperty('--simplemdm-panel-bg', 'linear-gradient(180deg, ' + panelBg + ' 0%, ' + panelBg + ' 100%)');
        b.style.setProperty('--simplemdm-heading-bg', 'linear-gradient(180deg, ' + headingBg + ' 0%, ' + headingBg + ' 100%)');
        b.style.setProperty('--simplemdm-card-bg', 'linear-gradient(180deg, ' + panelBg + ' 0%, ' + panelBg + ' 100%)');
        b.style.setProperty('--simplemdm-surface', panelBg);
        b.style.setProperty('--simplemdm-surface-alt', pageBg);
        b.style.setProperty('--simplemdm-surface-hover', pageBg);
        b.style.setProperty('--simplemdm-pill-bg', pageBg);
        b.style.setProperty('--simplemdm-chart-muted', borderColor);
    }

    function getBody() {
        return document.body || document.documentElement;
    }

    function getRoot() {
        return document.documentElement || document.body;
    }

    function getAttrAny(attrName) {
        var b = getBody();
        var r = getRoot();
        var bv = b && b.getAttribute ? String(b.getAttribute(attrName) || '') : '';
        if (bv) {
            return bv;
        }
        var rv = r && r.getAttribute ? String(r.getAttribute(attrName) || '') : '';
        return rv;
    }

    function hasClassLike(el, names) {
        if (!el || !el.classList) {
            return false;
        }
        for (var i = 0; i < names.length; i++) {
            if (el.classList.contains(names[i])) {
                return true;
            }
        }
        return false;
    }

    function detectExplicitMode() {
        var b = getBody();
        var r = getRoot();
        if (!b && !r) {
            return '';
        }

        var dataMode = '';
        dataMode = String(getAttrAny('data-layout-mode') || '').toLowerCase();
        if (dataMode === 'compact' || dataMode === 'comfortable') {
            return dataMode;
        }

        if (hasClassLike(b, ['layout-compact', 'compact', 'density-compact']) || hasClassLike(r, ['layout-compact', 'compact', 'density-compact'])) {
            return 'compact';
        }
        if (hasClassLike(b, ['layout-comfortable', 'comfortable', 'density-comfortable']) || hasClassLike(r, ['layout-comfortable', 'comfortable', 'density-comfortable'])) {
            return 'comfortable';
        }
        return '';
    }

    function detectExplicitTheme() {
        var b = getBody();
        var r = getRoot();
        if (!b && !r) {
            return '';
        }

        var keys = ['data-theme', 'data-bs-theme', 'data-color-mode'];
        for (var i = 0; i < keys.length; i++) {
            var v = String(getAttrAny(keys[i]) || '').toLowerCase();
            if (v === 'dark' || v === 'light') {
                return v;
            }
        }

        if (
            hasClassLike(b, ['dark', 'dark-mode', 'theme-dark', 'mode-dark', 'navbar-inverse']) ||
            hasClassLike(r, ['dark', 'dark-mode', 'theme-dark', 'mode-dark', 'navbar-inverse'])
        ) {
            return 'dark';
        }
        if (
            hasClassLike(b, ['light', 'light-mode', 'theme-light', 'mode-light']) ||
            hasClassLike(r, ['light', 'light-mode', 'theme-light', 'mode-light'])
        ) {
            return 'light';
        }
        return '';
    }

    function detectAutoMode() {
        if (window.innerWidth <= 1366) {
            return 'compact';
        }

        var widgets = document.querySelectorAll('.simplemdm-modern-widget');
        if (!widgets.length) {
            return 'comfortable';
        }

        var totalWidth = 0;
        for (var i = 0; i < widgets.length; i++) {
            totalWidth += widgets[i].getBoundingClientRect().width || 0;
        }
        var avg = totalWidth / widgets.length;
        return avg < 420 ? 'compact' : 'comfortable';
    }

    function detectAutoTheme() {
        var b = getBody();
        var r = getRoot();
        var probe = b || r;

        if (probe && window.getComputedStyle) {
            var bg = window.getComputedStyle(probe).backgroundColor || '';
            var match = /rgba?\((\d+),\s*(\d+),\s*(\d+)/i.exec(bg);
            if (match) {
                var rr = Number(match[1] || 0);
                var gg = Number(match[2] || 0);
                var bb = Number(match[3] || 0);
                var luminance = (0.2126 * rr + 0.7152 * gg + 0.0722 * bb) / 255;
                if (luminance <= 0.48) {
                    return 'dark';
                }
                if (luminance >= 0.6) {
                    return 'light';
                }
            }
        }

        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

function resizeChartsForMode(mode) {
        var compact = mode === 'compact';
        var factor = compact ? 0.82 : 1;
        var containers = document.querySelectorAll('.simplemdm-modern-widget .svg-container');
        for (var i = 0; i < containers.length; i++) {
            var c = containers[i];
            if (!c.dataset.baseHeight) {
                var initial = parseInt(c.style.height, 10);
                if (!initial || initial < 40) {
                    initial = Math.round(c.getBoundingClientRect().height || 160);
                }
                c.dataset.baseHeight = String(initial);
            }

            var base = parseInt(c.dataset.baseHeight, 10) || 160;
            var target = Math.max(95, Math.round(base * factor));
            c.style.height = target + 'px';

            var svgs = c.querySelectorAll('svg');
            for (var s = 0; s < svgs.length; s++) {
                svgs[s].style.height = target + 'px';
            }
        }
    }

    function collectSimplemdmDashboardWidgets() {
        if (!isDashboardGridEnabled()) {
            return;
        }
        if (document.getElementById('simplemdm-report-grid')) {
            return;
        }

        function normalizeWidgetKey(key) {
            var k = String(key || '').toLowerCase().trim();
            k = k.replace(/^widget[-_]/, '');
            k = k.replace(/^simplemdm-widget-/, '');
            k = k.replace(/-widget$/, '');
            k = k.replace(/_widget$/, '');
            return k;
        }

        var explicitOrder = {
            simplemdm_resource_types: 1,
            simplemdm_group_top: 2,
            simplemdm_group: 3,
            simplemdm_enrollment: 20,
            simplemdm_dep: 30,
            simplemdm_filevault: 40,
            simplemdm_supervised: 50,
            simplemdm_device_listing: 60,
            simplemdm_resources_listing: 70,
            simplemdm_trend: 110,
            simplemdm_os_security: 120,
            simplemdm_group_top_stats: 130,
            simplemdm_resource_mix: 140,
            simplemdm_command_status: 150,
            simplemdm_compliance: 160,
            simplemdm_sync_health: 170
        };

        function getWidgetKey(root) {
            if (!root) {
                return '';
            }

            var id = String(root.id || '');
            if (id.indexOf('widget-') === 0) {
                return normalizeWidgetKey(id);
            }
            if (id.indexOf('widget_') === 0) {
                return normalizeWidgetKey(id);
            }
            if (id.indexOf('simplemdm-widget-') === 0) {
                return normalizeWidgetKey(id);
            }

            var heading = root.querySelector('.panel-heading[data-widget]');
            if (heading) {
                var dataWidget = heading.getAttribute('data-widget');
                if (dataWidget) {
                    return normalizeWidgetKey(dataWidget);
                }
            }

            var panel = root.querySelector('.simplemdm-modern-widget[id]');
            if (panel && panel.id) {
                return normalizeWidgetKey(panel.id);
            }
            return '';
        }

        function getSortMeta(root) {
            var key = normalizeWidgetKey(getDashboardWidgetKey(root) || getWidgetKey(root));
            var group = 4;
            var order = 9999;
            if (explicitOrder[key] !== undefined) {
                order = explicitOrder[key];
                group = order < 100 ? 1 : 2;
            } else if (key.indexOf('simplemdm_rt_') === 0) {
                group = 3;
                order = 3000;
            }
            return { key: key, group: group, order: order };
        }

        var widgets = document.querySelectorAll('.simplemdm-modern-widget');
        if (!widgets || widgets.length < 2) {
            return;
        }

        var roots = [];
        var seen = {};
        for (var i = 0; i < widgets.length; i++) {
            var w = widgets[i];
            if (w.closest('#simplemdm-report-grid') || w.closest('#simplemdm-dashboard-grid')) {
                continue;
            }

            var root = w.closest('[id^="widget-"], [id^="widget_"], [id^="simplemdm-widget-"]');
            if (!root) {
                var col = w.closest('[class*="col-"]');
                root = col || w.parentElement;
            }
            if (!root || !root.parentNode) {
                continue;
            }

            var widgetKey = getWidgetKey(root) || root.id || ('simplemdm-root-' + i);
            root.setAttribute('data-simplemdm-key', widgetKey);
            if (seen[widgetKey]) {
                continue;
            }
            seen[widgetKey] = true;
            roots.push(root);
        }

        if (roots.length < 1) {
            return;
        }

        roots.sort(function(a, b) {
            var am = getSortMeta(a);
            var bm = getSortMeta(b);
            var aKey = getDashboardWidgetKey(a) || am.key;
            var bKey = getDashboardWidgetKey(b) || bm.key;
            var aCustom = getCustomOrderIndex(aKey);
            var bCustom = getCustomOrderIndex(bKey);
            if (aCustom !== bCustom) {
                if (aCustom === -1) {
                    return 1;
                }
                if (bCustom === -1) {
                    return -1;
                }
                return aCustom - bCustom;
            }
            if (am.group !== bm.group) {
                return am.group - bm.group;
            }
            if (am.order !== bm.order) {
                return am.order - bm.order;
            }
            return am.key.localeCompare(bm.key);
        });

        var container = document.getElementById('simplemdm-dashboard-grid');
        if (!container) {
            container = document.createElement('div');
            container.id = 'simplemdm-dashboard-grid';
            container.className = 'row';
            roots[0].parentNode.insertBefore(container, roots[0]);
        }

        for (var r = 0; r < roots.length; r++) {
            roots[r].classList.add('simplemdm-dashboard-item');
            container.appendChild(roots[r]);
        }
        applyDashboardOrderToDom(container);
        syncDashboardOrderState(container);
        ensureDashboardWidgetControls(container);
        restoreSelectedDashboardWidget(container);
        bindDashboardInteractions();
    }

    function getDashboardColumnCount() {
        var width = Math.max(window.innerWidth || 0, document.documentElement ? document.documentElement.clientWidth : 0);
        if (width >= 1200) {
            return 3;
        }
        if (width >= 768) {
            return 2;
        }
        return 1;
    }

    function markScrollableSimplemdmLists(container) {
        if (!container) {
            return;
        }
        var widgets = container.querySelectorAll('.simplemdm-modern-widget');
        for (var i = 0; i < widgets.length; i++) {
            var w = widgets[i];
            if (w.id === 'simplemdm-group-widget' || w.id === 'simplemdm-resource-types-widget') {
                w.classList.remove('simplemdm-list-scroll');
                continue;
            }
            var count = w.querySelectorAll('.list-group-item').length;
            if (count > 12) {
                w.classList.add('simplemdm-list-scroll');
            } else {
                w.classList.remove('simplemdm-list-scroll');
            }
        }
    }

    function layoutSimplemdmDashboardGrid() {
        if (!isDashboardGridEnabled()) {
            return;
        }
        if (activeDrag) {
            return;
        }
        if (document.getElementById('simplemdm-report-grid')) {
            return;
        }
        var container = document.getElementById('simplemdm-dashboard-grid');
        if (!container) {
            return;
        }

        markScrollableSimplemdmLists(container);

        var items = [];
        for (var ci = 0; ci < container.children.length; ci++) {
            var child = container.children[ci];
            if (child && child.classList && child.classList.contains('simplemdm-dashboard-item')) {
                items.push(child);
            }
        }
        if (!items.length) {
            return;
        }

        var visible = [];
        for (var i = 0; i < items.length; i++) {
            if (items[i].classList.contains('simplemdm-widget-hidden') || items[i].style.display === 'none') {
                continue;
            }
            visible.push(items[i]);
        }
        if (!visible.length) {
            container.style.height = '0px';
            return;
        }

        var cols = getDashboardColumnCount();
        var gap = 18;
        var width = container.clientWidth || container.getBoundingClientRect().width || 0;
        if (width <= 0) {
            return;
        }
        var colWidth = Math.floor((width - ((cols - 1) * gap)) / cols);
        if (colWidth < 220) {
            cols = Math.max(1, Math.floor((width + gap) / (220 + gap)));
            colWidth = Math.floor((width - ((cols - 1) * gap)) / cols);
        }
        simplemdmGridMetrics.cols = cols;
        simplemdmGridMetrics.colWidth = colWidth;
        simplemdmGridMetrics.gap = gap;

        var heights = [];
        for (var c = 0; c < cols; c++) {
            heights.push(0);
        }

        function getItemSpan(item, totalCols) {
            if (!item || totalCols <= 1) {
                return 1;
            }
            var key = String(item.getAttribute('data-simplemdm-key') || '');
            var customSpan = parseInt(simplemdmDashboardState.span[key], 10);
            if (customSpan && customSpan > 0) {
                return Math.max(1, Math.min(totalCols, customSpan));
            }
            if (isFeaturedWidgetKey(key)) {
                return totalCols;
            }
            // Keep small widgets visually even by default.
            return 1;
        }

        function findBestColumnStart(span, itemKey) {
            if (span >= cols) {
                var maxHeight = 0;
                for (var i = 0; i < heights.length; i++) {
                    if (heights[i] > maxHeight) {
                        maxHeight = heights[i];
                    }
                }
                return { start: 0, y: maxHeight };
            }

            var preferred = getWidgetPreferredColumn(itemKey, cols, span);
            var preferredTop = getWidgetPreferredTop(itemKey);
            if (preferred !== -1) {
                var prefY = 0;
                for (var pb = preferred; pb < preferred + span; pb++) {
                    if (heights[pb] > prefY) {
                        prefY = heights[pb];
                    }
                }
                if (preferredTop !== -1) {
                    prefY = Math.max(prefY, preferredTop);
                }
                return { start: preferred, y: prefY };
            }

            var bestStart = 0;
            var bestY = Number.MAX_SAFE_INTEGER;
            for (var start = 0; start <= cols - span; start++) {
                var blockY = 0;
                for (var b = start; b < start + span; b++) {
                    if (heights[b] > blockY) {
                        blockY = heights[b];
                    }
                }
                if (blockY < bestY) {
                    bestY = blockY;
                    bestStart = start;
                }
            }
            if (preferredTop !== -1) {
                bestY = Math.max(bestY, preferredTop);
            }
            return { start: bestStart, y: bestY };
        }

        for (var v = 0; v < visible.length; v++) {
            var item = visible[v];
            applyWidgetMinHeight(item);
            var span = getItemSpan(item, cols);
            if (span > cols) {
                span = cols;
            }
            var itemKey = String(item.getAttribute('data-simplemdm-key') || '');
            var placement = findBestColumnStart(span, itemKey);
            var itemWidth = (span * colWidth) + ((span - 1) * gap);

            item.style.width = itemWidth + 'px';
            item.style.left = '0px';
            item.style.top = '0px';
            item.style.visibility = 'hidden';
            var h = item.offsetHeight || Math.round(item.getBoundingClientRect().height) || 0;
            var x = (colWidth + gap) * placement.start;
            var y = placement.y;

            item.style.left = x + 'px';
            item.style.top = y + 'px';
            item.style.visibility = '';
            item.setAttribute('data-simplemdm-span', String(span));

            for (var u = placement.start; u < placement.start + span; u++) {
                heights[u] = y + h + gap;
            }
        }

        var max = 0;
        for (var m = 0; m < heights.length; m++) {
            if (heights[m] > max) {
                max = heights[m];
            }
        }
        container.style.height = Math.max(0, max - gap) + 'px';
    }

    function scheduleDashboardGridLayout(delay) {
        if (!isDashboardGridEnabled()) {
            return;
        }
        var wait = Number(delay || 0);
        setTimeout(function() {
            if (window.requestAnimationFrame) {
                window.requestAnimationFrame(layoutSimplemdmDashboardGrid);
            } else {
                layoutSimplemdmDashboardGrid();
            }
        }, wait);
    }

    window.simplemdmReflowDashboardGrid = function() {
        if (!isDashboardGridEnabled()) {
            return;
        }
        collectSimplemdmDashboardWidgets();
        layoutSimplemdmDashboardGrid();
    };

    function applyLayoutMode() {
        var b = getBody();
        if (!b || !b.classList) {
            return;
        }

        var nextLayout = detectExplicitMode() || detectAutoMode();
        if (nextLayout !== 'compact' && nextLayout !== 'comfortable') {
            nextLayout = 'comfortable';
        }

        var nextTheme = detectExplicitTheme() || detectAutoTheme();
        if (nextTheme !== 'dark' && nextTheme !== 'light') {
            nextTheme = 'light';
        }

        var prevLayout = String(b.getAttribute('data-simplemdm-layout') || '');
        var prevTheme = String(b.getAttribute('data-simplemdm-theme') || '');
        var changed = prevLayout !== nextLayout || prevTheme !== nextTheme;

        if (prevLayout !== nextLayout) {
            b.classList.remove('simplemdm-layout-compact', 'simplemdm-layout-comfortable');
            b.classList.add(nextLayout === 'compact' ? 'simplemdm-layout-compact' : 'simplemdm-layout-comfortable');
            b.setAttribute('data-simplemdm-layout', nextLayout);
        } else if (!b.classList.contains('simplemdm-layout-' + nextLayout)) {
            b.classList.add('simplemdm-layout-' + nextLayout);
        }

        if (prevTheme !== nextTheme) {
            b.classList.remove('simplemdm-theme-dark', 'simplemdm-theme-light');
            b.classList.add(nextTheme === 'dark' ? 'simplemdm-theme-dark' : 'simplemdm-theme-light');
            b.setAttribute('data-simplemdm-theme', nextTheme);
        } else if (!b.classList.contains('simplemdm-theme-' + nextTheme)) {
            b.classList.add('simplemdm-theme-' + nextTheme);
        }

        var activeThemeName = detectBootswatchThemeName();
        applyThemeAccentTokens(activeThemeName, nextTheme);
        applyThemeSurfaceTokens(nextTheme);
        collectSimplemdmDashboardWidgets();
        ensureDashboardResetControl();
        resizeChartsForMode(nextLayout);
        scheduleDashboardGridLayout(0);
        scheduleDashboardGridLayout(350);
        scheduleDashboardGridLayout(900);

        if (window.dispatchEvent) {
            if (typeof Event === 'function') {
                window.dispatchEvent(new Event('resize'));
            } else if (document.createEvent) {
                var evt = document.createEvent('Event');
                evt.initEvent('resize', true, true);
                window.dispatchEvent(evt);
            }
        }

        if (changed && window.dispatchEvent) {
            var detail = { layout: nextLayout, theme: nextTheme };
            if (typeof CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('simplemdm:modechange', { detail: detail }));
            } else if (document.createEvent) {
                var ce = document.createEvent('CustomEvent');
                ce.initCustomEvent('simplemdm:modechange', true, true, detail);
                window.dispatchEvent(ce);
            }
        }
    }

    var applyScheduled = false;
    function scheduleApply() {
        if (applyScheduled) {
            return;
        }
        applyScheduled = true;
        window.requestAnimationFrame(function() {
            applyScheduled = false;
            applyLayoutMode();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyLayoutMode);
    } else {
        applyLayoutMode();
    }

    if (window.jQuery && window.jQuery.fn) {
        window.jQuery(document).on('appReady.simplemdmLayout', scheduleApply);
    }

    window.addEventListener('resize', scheduleApply);
    if (window.matchMedia) {
        var darkMedia = window.matchMedia('(prefers-color-scheme: dark)');
        if (darkMedia && darkMedia.addEventListener) {
            darkMedia.addEventListener('change', scheduleApply);
        } else if (darkMedia && darkMedia.addListener) {
            darkMedia.addListener(scheduleApply);
        }
    }

    var bodyObserverTimer = null;
    function bodyObserverHandler() {
        clearTimeout(bodyObserverTimer);
        bodyObserverTimer = setTimeout(scheduleApply, 40);
    }

    var obsTarget = getBody();
    if (window.MutationObserver && obsTarget) {
        var observer = new MutationObserver(bodyObserverHandler);
        observer.observe(obsTarget, {
            attributes: true,
            attributeFilter: ['class', 'data-layout-mode', 'data-theme', 'data-bs-theme', 'data-color-mode']
        });
    }

    var rootTarget = getRoot();
    if (window.MutationObserver && rootTarget && rootTarget !== obsTarget) {
        var rootObserver = new MutationObserver(bodyObserverHandler);
        rootObserver.observe(rootTarget, {
            attributes: true,
            attributeFilter: ['class', 'data-layout-mode', 'data-theme', 'data-bs-theme', 'data-color-mode']
        });
    }
})();
</script>
