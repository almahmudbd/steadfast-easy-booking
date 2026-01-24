<?php
/**
 * Single Booking Page for Steadfast Easy Booking
 */

if (!defined('ABSPATH')) exit;

$hide_tip = get_option('sfb_hide_tip', 'no');
?>

<div class="wrap sfb-wrap">
    <h1>📦 Single Parcel Booking</h1>
    
    <?php if ($hide_tip !== 'yes') : ?>
    <div class="sfb-notice sfb-notice-info" style="margin-bottom:15px; position:relative;">
        <button type="button" class="sfb-hide-tip-btn" style="position:absolute; top:5px; right:5px; background:none; border:none; cursor:pointer; font-size:16px;" title="Don't show again">
            ✕
        </button>
        <p>💡 <strong>টিপস:</strong> প্রথমে <a href="<?php echo admin_url('admin.php?page=steadfast-settings'); ?>">সেটিংস পেজ</a> থেকে API Key সেটআপ করুন।</p>
    </div>
    <?php endif; ?>
    
    <div class="sfb-card">
        <h3>অর্ডার ডেটা প্রসেস করুন</h3>
        
        <div class="sfb-form-group">
            <label>অর্ডারের ডেটা মেসেজ (৩-৪ লাইনে)</label>
            <textarea id="sfb-message-input" rows="5" placeholder="Customer Name (নাম)
Address Example, Dhaka. (ঠিকানা)
01312345678 (মোবাইল)
500 (সিওডি টাকা)"></textarea>
            
            <div style="margin-top:10px;">
                <button type="button" id="sfb-process-btn" class="button button-primary">মেসেজ প্রসেস করুন</button>
                <button type="button" id="sfb-clear-msg-btn" class="button">ক্লিয়ার</button>
            </div>
        </div>
        
        <div id="sfb-parse-error" class="sfb-notice sfb-notice-error" style="display:none; margin-top:10px;">
            <p>মেসেজ প্যাটার্ন ভুল।</p>
        </div>
    </div>
    
    <div class="sfb-card" style="margin-top:20px;">
        <h3>অর্ডার ফর্ম</h3>
        
        <form id="sfb-order-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label>ইনভয়েস (Auto)</label></th>
                    <td><input type="text" id="sfb-invoice" class="regular-text" readonly></td>
                </tr>
                <tr>
                    <th scope="row"><label>ক্রেতার নাম</label></th>
                    <td><input type="text" id="sfb-name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label>ক্রেতার ঠিকানা</label></th>
                    <td><input type="text" id="sfb-address" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label>ক্রেতার ফোন</label></th>
                    <td><input type="text" id="sfb-phone" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label>COD টাকার পরিমাণ</label></th>
                    <td><input type="number" id="sfb-cod" class="regular-text" min="0" required></td>
                </tr>
                <tr>
                    <th scope="row"><label>ডেলিভারি ধরন</label></th>
                    <td>
                        <select id="sfb-delivery">
                            <option value="0">0. হোম ডেলিভারি</option>
                            <option value="1">1. পয়েন্ট পিকআপ</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>নোট (Optional)</label></th>
                    <td><input type="text" id="sfb-note" class="regular-text"></td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" id="sfb-confirm-btn" class="button button-primary" disabled>বুকিং কনফার্ম</button>
            </p>
        </form>
    </div>
    
    <div id="sfb-response" class="sfb-card" style="margin-top:20px; display:none;">
        <h3>রেসপন্স</h3>
        <div id="sfb-response-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // টিপস হাইড করার ফাংশন
    $('.sfb-hide-tip-btn').on('click', function() {
        $.ajax({
            url: sfbAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'sfb_hide_tip',
                nonce: sfbAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.sfb-notice-info').fadeOut(300);
                }
            }
        });
    });
    
    function cleanNum(str) {
        if (!str) return '';
        let cleaned = String(str).replace(/[^\d০-৯]/g, '');
        const bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        const en = ['0','1','2','3','4','5','6','7','8','9'];
        bn.forEach((b, i) => cleaned = cleaned.replace(new RegExp(b, 'g'), en[i]));
        return cleaned;
    }
    
    function parseMessage(msg) {
        const lines = msg.split('\n').map(l => l.trim()).filter(Boolean);
        if (lines.length < 3) return null;
        
        const phoneRx = /^01\d{9}$/;
        const name = lines[0];
        const c1 = cleanNum(lines[1] || '');
        const c2 = cleanNum(lines[2] || '');
        
        let phone = null, addr = null;
        if (phoneRx.test(c1)) {
            phone = c1;
            addr = lines[2];
        } else if (phoneRx.test(c2)) {
            phone = c2;
            addr = lines[1];
        } else {
            for (let l of lines) {
                let p = cleanNum(l);
                if (phoneRx.test(p)) {
                    phone = p;
                    break;
                }
            }
            addr = lines[1];
        }
        
        let cod = 0;
        for (let l of lines) {
            if (l.toLowerCase().includes('tk') || l.includes('টাকা') || l.toLowerCase().includes('cod')) {
                cod = parseFloat(cleanNum(l)) || 0;
                break;
            }
        }
        if (cod === 0 && lines[3]) cod = parseFloat(cleanNum(lines[3])) || 0;
        
        return { name, addr, phone, cod };
    }
    
    $('#sfb-process-btn').on('click', function() {
        const parsed = parseMessage($('#sfb-message-input').val());
        if (parsed) {
            $('#sfb-parse-error').hide();
            $('#sfb-invoice').val('INV-' + Date.now().toString().slice(-6));
            $('#sfb-name').val(parsed.name);
            $('#sfb-address').val(parsed.addr);
            $('#sfb-phone').val(parsed.phone);
            $('#sfb-cod').val(parsed.cod);
            $('#sfb-confirm-btn').prop('disabled', false);
            $('#sfb-response').hide();
        } else {
            $('#sfb-parse-error').show();
            $('#sfb-invoice, #sfb-name, #sfb-address, #sfb-phone, #sfb-cod').val('');
            $('#sfb-confirm-btn').prop('disabled', true);
        }
    });
    
    $('#sfb-clear-msg-btn').on('click', function() {
        $('#sfb-message-input, #sfb-invoice, #sfb-name, #sfb-address, #sfb-phone, #sfb-cod, #sfb-note').val('');
        $('#sfb-confirm-btn').prop('disabled', true);
        $('#sfb-response').hide();
    });
    
    $('#sfb-order-form').on('submit', function(e) {
        e.preventDefault();
        
        $('#sfb-response-content').html('<p>বুকিং প্রক্রিয়া চলছে...</p>');
        $('#sfb-response').show();
        
        $.ajax({
            url: sfbAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'sfb_create_order',
                nonce: sfbAjax.nonce,
                invoice: $('#sfb-invoice').val(),
                recipient_name: $('#sfb-name').val(),
                recipient_address: $('#sfb-address').val(),
                recipient_phone: $('#sfb-phone').val(),
                cod_amount: $('#sfb-cod').val(),
                note: $('#sfb-note').val(),
                delivery_type: $('#sfb-delivery').val()
            },
            success: function(response) {
                if (response.success && response.data.status === 200) {
                    const cid = response.data.consignment?.consignment_id || 'N/A';
                    const track = response.data.consignment?.tracking_code || '';
                    
                    $('#sfb-response-content').html(`
                        <div class="sfb-notice sfb-notice-success">
                            <p>✅ Consignment created successfully!</p>
                        </div>
                        <p><strong>Parcel ID:</strong> #${cid}</p>
                        <p><strong>Tracking:</strong> ${track}</p>
                        <p><a href="https://steadfast.com.bd/t/${track}" target="_blank" class="button">View Tracking</a></p>
                        <details style="margin-top:15px;">
                            <summary>Raw Response</summary>
                            <pre style="background:#f9f9f9; padding:10px; border-radius:5px; font-size:11px;">${JSON.stringify(response.data, null, 2)}</pre>
                        </details>
                    `);
                } else {
                    $('#sfb-response-content').html(`
                        <div class="sfb-notice sfb-notice-error">
                            <p>❌ বুকিং ব্যর্থ: ${response.data.message || 'Unknown error'}</p>
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#sfb-response-content').html(`
                    <div class="sfb-notice sfb-notice-error">
                        <p>❌ নেটওয়ার্ক ত্রুটি</p>
                    </div>
                `);
            }
        });
    });
});
</script>