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
<?php
$batch_list = isset( $batches_info ) ? $batches_info : array( $batch_info );
?>
<div class="batch-container">
<?php foreach ( $batch_list as $batch_info ) : ?>
    <table class="inventory-batch-info product-archive batch-column">
    <?php foreach ( $displayed_fields as $field_key => $field ) : ?>
        <?php if ( isset( $batch_info[ $field_key ] ) && ! empty( $batch_info[ $field_key ] ) ) : ?>
            <?php $style = ! empty( $field['color'] ) ? ' style="color:' . esc_attr( $field['color'] ) . ';"' : ''; ?>
            <tr class="batch-info-field <?php echo esc_attr( $field_key ); ?>">
                <td class="label"<?php echo $style; ?>><?php echo esc_html( $field['label'] ); ?>:</td>
                <td class="value"<?php echo $style; ?>><?php echo esc_html( $batch_info[ $field_key ] ); ?></td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </table>
<?php endforeach; ?>
</div>
