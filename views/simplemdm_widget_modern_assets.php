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
}

#simplemdm-dashboard-grid > .simplemdm-dashboard-item > [class*="col-"] {
    float: none;
    width: 100%;
    padding-left: 0;
    padding-right: 0;
}

.simplemdm-modern-widget.simplemdm-list-scroll .list-group {
    max-height: 260px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 2px;
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
    if (window.simplemdmLayoutModeInit) {
        return;
    }
    window.simplemdmLayoutModeInit = true;

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
            var key = getWidgetKey(root);
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

        if (roots.length < 2) {
            return;
        }

        roots.sort(function(a, b) {
            var am = getSortMeta(a);
            var bm = getSortMeta(b);
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
            var count = w.querySelectorAll('.list-group-item').length;
            if (count > 12) {
                w.classList.add('simplemdm-list-scroll');
            } else {
                w.classList.remove('simplemdm-list-scroll');
            }
        }
    }

    function layoutSimplemdmDashboardGrid() {
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

        var heights = [];
        for (var c = 0; c < cols; c++) {
            heights.push(0);
        }

        function getItemSpan(item, totalCols) {
            if (!item || totalCols <= 1) {
                return 1;
            }
            var key = String(item.getAttribute('data-simplemdm-key') || '');
            if (key === 'simplemdm_resource_types' || key === 'simplemdm_group_top' || key === 'simplemdm_group') {
                return totalCols;
            }

            var inner = null;
            for (var ci = 0; ci < item.children.length; ci++) {
                var child = item.children[ci];
                if (child && child.className && String(child.className).indexOf('col-') !== -1) {
                    inner = child;
                    break;
                }
            }
            var className = inner ? String(inner.className || '') : '';
            if (/col-lg-12|col-md-12|col-sm-12/.test(className)) {
                return totalCols;
            }
            if (totalCols >= 3 && /col-lg-6/.test(className)) {
                return 2;
            }
            return 1;
        }

        function findBestColumnStart(span) {
            if (span >= cols) {
                var maxHeight = 0;
                for (var i = 0; i < heights.length; i++) {
                    if (heights[i] > maxHeight) {
                        maxHeight = heights[i];
                    }
                }
                return { start: 0, y: maxHeight };
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
            return { start: bestStart, y: bestY };
        }

        for (var v = 0; v < visible.length; v++) {
            var item = visible[v];
            var span = getItemSpan(item, cols);
            if (span > cols) {
                span = cols;
            }
            var placement = findBestColumnStart(span);
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
