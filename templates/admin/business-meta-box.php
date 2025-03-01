<?php
// Add nonce for security
wp_nonce_field('business_meta_box', 'business_meta_box_nonce');

// Get current values
$business_name = get_post_meta($post->ID, 'business_name', true);
$business_status = get_post_meta($post->ID, 'business_status', true);
$web_address = get_post_meta($post->ID, 'web_address', true);
$linkedin_profile = get_post_meta($post->ID, 'linkedin_profile', true);
$phone_number = get_post_meta($post->ID, 'phone_number', true);
$country = get_post_meta($post->ID, 'country', true);
$prospect_prospector = get_post_meta($post->ID, 'prospect_prospector', true);
$prospect_closer = get_post_meta($post->ID, 'prospect_closer', true);
$general_notes = get_post_meta($post->ID, 'general_notes', true);
?>

<table class="form-table">
    <tr>
        <th><label for="business_name"><?php _e('Business Name', 'claims-angel'); ?></label></th>
        <td><input type="text" name="business_name" id="business_name" value="<?php echo esc_attr($business_name); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="business_status"><?php _e('Business Status', 'claims-angel'); ?></label></th>
        <td><input type="text" name="business_status" id="business_status" value="<?php echo esc_attr($business_status); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="web_address"><?php _e('Web Address', 'claims-angel'); ?></label></th>
        <td><input type="text" name="web_address" id="web_address" value="<?php echo esc_attr($web_address); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="linkedin_profile"><?php _e('LinkedIn Profile', 'claims-angel'); ?></label></th>
        <td><input type="text" name="linkedin_profile" id="linkedin_profile" value="<?php echo esc_attr($linkedin_profile); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="phone_number"><?php _e('Phone Number', 'claims-angel'); ?></label></th>
        <td><input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr($phone_number); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="country"><?php _e('Country', 'claims-angel'); ?></label></th>
        <td><input type="text" name="country" id="country" value="<?php echo esc_attr($country); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="prospect_prospector"><?php _e('Prospect Prospector', 'claims-angel'); ?></label></th>
        <td><input type="number" name="prospect_prospector" id="prospect_prospector" value="<?php echo esc_attr($prospect_prospector); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="prospect_closer"><?php _e('Prospect Closer', 'claims-angel'); ?></label></th>
        <td><input type="number" name="prospect_closer" id="prospect_closer" value="<?php echo esc_attr($prospect_closer); ?>" class="regular-text" /></td>
    </tr>
    <tr>
        <th><label for="general_notes"><?php _e('General Notes', 'claims-angel'); ?></label></th>
        <td><textarea name="general_notes" id="general_notes" class="large-text"><?php echo esc_textarea($general_notes); ?></textarea></td>
    </tr>
</table>