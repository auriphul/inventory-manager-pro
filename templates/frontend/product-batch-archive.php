<?php
/**
 * Product batch info template for archive pages
 *
 * Expects $batch_info array and $displayed_fields array
 *
 * @package Inventory_Manager_Pro
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="inventory-batch-info product-archive">
<?php foreach ( $displayed_fields as $field_key => $field ) : ?>
    <?php if ( isset( $batch_info[ $field_key ] ) && ! empty( $batch_info[ $field_key ] ) ) : ?>
        <?php $style = ! empty( $field['color'] ) ? ' style="color:' . esc_attr( $field['color'] ) . ';"' : ''; ?>
        <div class="batch-info-field <?php echo esc_attr( $field_key ); ?>">
            <span class="label"<?php echo $style; ?>><?php echo esc_html( $field['label'] ); ?>: </span>
            <span class="value"<?php echo $style; ?>><?php echo esc_html( $batch_info[ $field_key ] ); ?></span>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
