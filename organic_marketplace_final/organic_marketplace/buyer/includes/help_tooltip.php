<?php
/**
 * Help Tooltip Component
 * Usage: include this file and use help_tooltip($text, $title) function
 */

function help_tooltip($text, $title = 'Help') {
    return '<span class="help-tooltip" title="' . htmlspecialchars($title) . '">
        <span class="help-icon">❓</span>
        <span class="help-text">' . htmlspecialchars($text) . '</span>
    </span>';
}
?>

<style>
.help-tooltip {
    position: relative;
    display: inline-block;
    margin-left: 5px;
    cursor: help;
}

.help-icon {
    font-size: 0.9em;
    color: #4a7c59;
    opacity: 0.7;
    transition: opacity 0.3s;
}

.help-tooltip:hover .help-icon {
    opacity: 1;
}

.help-text {
    visibility: hidden;
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    white-space: nowrap;
    font-size: 0.85em;
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    max-width: 250px;
    white-space: normal;
    text-align: left;
}

.help-text::after {
    content: "";
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 5px solid transparent;
    border-top-color: #333;
}

.help-tooltip:hover .help-text {
    visibility: visible;
    opacity: 1;
}
</style>

