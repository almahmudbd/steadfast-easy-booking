<?php
/**
 * Settings Page for Steadfast Easy Booking
 */

if (!defined('ABSPATH')) exit;

$api_key = get_option('sfb_api_key', '');
$secret_key = get_option('sfb_secret_key', '');
?>

<div class="wrap sfb-wrap">
    <h1>🔐 Steadfast API Settings</h1>
    
    <div class="sfb-card">
        <div class="sfb-notice sfb-notice-info">
            <p><strong>📌 API Key কোথায় পাবেন?</strong></p>
            <p>Steadfast এর <a href="https://steadfast.com.bd/user/api" target="_blank">API Settings পেজ</a> থেকে আপনার API Key এবং Secret Key সংগ্রহ করুন।</p>
        </div>
        
        <form id="sfb-settings-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sfb_api_key">API Key</label>
                    </th>
                    <td>
                        <input type="text" id="sfb_api_key" name="api_key" class="regular-text" 
                               value="<?php echo esc_attr($api_key); ?>" placeholder="আপনার API Key">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="sfb_secret_key">Secret Key</label>
                    </th>
                    <td>
                        <input type="text" id="sfb_secret_key" name="secret_key" class="regular-text" 
                               value="<?php echo esc_attr($secret_key); ?>" placeholder="আপনার Secret Key">
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary">সেভ করুন</button>
                <button type="button" id="sfb-clear-settings" class="button">মুছে ফেলুন</button>
                <button type="button" id="sfb-reset-plugin" class="button button-danger" 
                        style="background:#dc3232; color:white; border-color:#dc3232;">
                    প্লাগিন রিসেট করুন
                </button>
            </p>
        </form>
        
        <div id="sfb-message" style="display:none; margin-top:15px;"></div>
    </div>
    
    <div class="sfb-card" style="margin-top:20px;">
        <h3>⚙️ Quick Links</h3>
        <p>
            <a href="<?php echo admin_url('admin.php?page=steadfast-booking'); ?>" class="button">📦 Single Booking</a>
            <a href="<?php echo admin_url('admin.php?page=steadfast-bulk-booking'); ?>" class="button">📚 Bulk Booking</a>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#sfb-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: sfbAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'sfb_save_settings',
                nonce: sfbAjax.nonce,
                api_key: $('#sfb_api_key').val(),
                secret_key: $('#sfb_secret_key').val()
            },
            success: function(response) {
                $('#sfb-message').html('<div class="sfb-notice sfb-notice-success"><p>✅ সেটিংস সেভ হয়েছে!</p></div>').show();
                setTimeout(() => $('#sfb-message').fadeOut(), 3000);
            },
            error: function() {
                $('#sfb-message').html('<div class="sfb-notice sfb-notice-error"><p>❌ Error saving settings</p></div>').show();
            }
        });
    });
    
    $('#sfb-clear-settings').on('click', function() {
        if (confirm('Are you sure you want to clear the API keys?')) {
            $('#sfb_api_key, #sfb_secret_key').val('');
            $('#sfb-settings-form').submit();
        }
    });
    
    $('#sfb-reset-plugin').on('click', function() {
        if (confirm('⚠️ সতর্কতা!\n\nআপনি কি নিশ্চিত যে আপনি প্লাগিনটি রিসেট করতে চান?\n\nএটি করলে:\n• API keys মুছে যাবে\n• টিপস আবার দেখাবে\n• সব ডেটা রিসেট হবে')) {
            $.ajax({
                url: sfbAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sfb_reset_plugin',
                    nonce: sfbAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#sfb-message').html('<div class="sfb-notice sfb-notice-success"><p>✅ প্লাগিন সফলভাবে রিসেট হয়েছে!</p><p>পৃষ্ঠাটি রিফ্রেশ হচ্ছে...</p></div>').show();
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                },
                error: function() {
                    $('#sfb-message').html('<div class="sfb-notice sfb-notice-error"><p>❌ Error resetting plugin</p></div>').show();
                }
            });
        }
    });
});
</script>