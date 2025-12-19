<?php
/**
 * Centralized Seller Type Configuration
 * Use this for consistent seller type labels, icons, colors, and descriptions across the system
 */

// Seller type configuration
define('SELLER_TYPES', [
    'farmer' => [
        'label' => 'Farmer',
        'full_label' => 'Farmer (Organic Crops)',
        'icon' => '🌾',
        'color' => '#4caf50',
        'bg_color' => '#e8f5e9',
        'description' => 'Organic crop producers (vegetables, fruits, cacao, spices)',
        'allowed_categories' => ['Vegetables', 'Fruits', 'Cacao', 'Spices']
    ],
    'poultry_egg' => [
        'label' => 'Poultry/Egg Raiser',
        'full_label' => 'Poultry/Egg Raiser',
        'icon' => '🥚',
        'color' => '#ff9800',
        'bg_color' => '#fff3e0',
        'description' => 'Poultry and egg producers',
        'allowed_categories' => ['Eggs']
    ],
    'fisherfolk' => [
        'label' => 'Fisherfolk',
        'full_label' => 'Fisherfolk',
        'icon' => '🐟',
        'color' => '#2196f3',
        'bg_color' => '#e3f2fd',
        'description' => 'Fish and fishery products',
        'allowed_categories' => ['Fish']
    ]
]);

/**
 * Get seller type configuration
 * @param string $type Seller type (farmer, poultry_egg, fisherfolk)
 * @return array|null Configuration array or null if not found
 */
function get_seller_type_config($type) {
    return SELLER_TYPES[$type] ?? null;
}

/**
 * Get formatted seller type label with icon
 * @param string $type Seller type
 * @param bool $show_full Show full label instead of short
 * @return string Formatted label
 */
function get_seller_type_label($type, $show_full = false) {
    $config = get_seller_type_config($type);
    if (!$config) return ucfirst($type);
    
    $label = $show_full ? $config['full_label'] : $config['label'];
    return $config['icon'] . ' ' . $label;
}

/**
 * Get seller type badge HTML
 * @param string $type Seller type
 * @param string $size Size: 'small', 'medium', 'large'
 * @return string HTML badge
 */
function get_seller_type_badge($type, $size = 'medium') {
    $config = get_seller_type_config($type);
    if (!$config) {
        return '<span class="seller-type-badge seller-type-' . $size . '">' . ucfirst($type) . '</span>';
    }
    
    $size_class = 'seller-type-' . $size;
    return sprintf(
        '<span class="seller-type-badge %s" style="background: %s; color: %s; padding: 4px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 500; display: inline-flex; align-items: center; gap: 5px;">%s %s</span>',
        $size_class,
        $config['bg_color'],
        $config['color'],
        $config['icon'],
        htmlspecialchars($config['label'])
    );
}

/**
 * Get all seller types for dropdown
 * @return array Array of [value => label] for select options
 */
function get_seller_type_options() {
    $options = [];
    foreach (SELLER_TYPES as $key => $config) {
        $options[$key] = $config['icon'] . ' ' . $config['full_label'];
    }
    return $options;
}

/**
 * Get allowed categories for a seller type
 * @param string $type Seller type (farmer, poultry_egg, fisherfolk)
 * @return array Array of allowed category names
 */
function get_allowed_categories($type) {
    $config = get_seller_type_config($type);
    if (!$config || !isset($config['allowed_categories'])) {
        return [];
    }
    return $config['allowed_categories'];
}

