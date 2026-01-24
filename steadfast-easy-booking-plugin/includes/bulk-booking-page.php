<?php
/**
 * Bulk Booking Page for Steadfast Easy Booking
 */

if (!defined('ABSPATH'))
    exit;

$hide_tip = get_option('sfb_hide_tip', 'no');
?>

<div class="wrap sfb-wrap">
    <h1>📚 Bulk Parcel Booking</h1>

    <?php if ($hide_tip !== 'yes'): ?>
        <div class="sfb-notice sfb-notice-info" style="margin-bottom:15px; position:relative;">
            <button type="button" class="sfb-hide-tip-btn"
                style="position:absolute; top:5px; right:5px; background:none; border:none; cursor:pointer; font-size:16px;"
                title="Don't show again">
                ✕
            </button>
            <p>💡 <strong>টিপস:</strong> প্রথমে <a
                    href="<?php echo admin_url('admin.php?page=steadfast-settings'); ?>">সেটিংস পেজ</a> থেকে API Key সেটআপ
                করুন।</p>
        </div>
    <?php endif; ?>

    <div class="sfb-card">
        <h3>নিচের স্টাইলে একাধিক মেসেজ দিন</h3>
        <p class="description">প্রতিটি মেসেজের মাঝে <code>---</code> (তিনটি ড্যাশ) ব্যবহার করুন</p>

        <textarea id="sfb-bulk-input" rows="10" style="width:100%; max-width:100%; box-sizing:border-box;" placeholder="যায়েদ
রোড-১, মিরপুর-১০, ঢাকা
01700000000
500
---
আব্দুর রহিম
নিউমার্কেট, যশোর
01800000000
1000"></textarea>

        <div style="margin-top:10px;">
            <button type="button" id="sfb-extract-btn" class="button button-primary">ডেটা এক্সট্রাক্ট করুন</button>
            <button type="button" id="sfb-bulk-clear-btn" class="button">ক্লিয়ার</button>
        </div>
    </div>

    <div id="sfb-bulk-table-wrap" style="display:none; margin-top:20px;">
        <div class="sfb-card">
            <h3>অর্ডার লিস্ট (<span id="sfb-order-count">0</span>)</h3>

            <table class="wp-list-table widefat fixed striped" id="sfb-order-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>ইনভয়েস</th>
                        <th>নাম</th>
                        <th>ঠিকানা</th>
                        <th>ফোন</th>
                        <th>COD</th>
                        <th>মুছুন</th>
                        <th>স্ট্যাটাস / Parcel ID</th>
                    </tr>
                </thead>
                <tbody id="sfb-order-body"></tbody>
            </table>

            <div style="margin-top:20px; padding:15px; background:#f9f9f9; border-radius:8px;">
                <label><strong>কমন নোট (সবগুলো অর্ডারের জন্য)</strong></label>
                <input type="text" id="sfb-common-note" name="common_note" autocomplete="on" class="regular-text"
                    placeholder="যেমন: সাবধানে ডেলিভারি করবেন">

                <p class="submit">
                    <button type="button" id="sfb-submit-bulk-btn" class="button button-primary button-large">সবগুলো
                        একসাথে বুকিং দিন</button>
                </p>
            </div>
        </div>
    </div>

    <div id="sfb-bulk-summary" style="margin-top:15px; text-align:center; font-weight:600;"></div>
</div>

<script>
    jQuery(document).ready(function ($) {
        // টিপস হাইড করার ফাংশন
        $('.sfb-hide-tip-btn').on('click', function () {
            $.ajax({
                url: sfbAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'sfb_hide_tip',
                    nonce: sfbAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $('.sfb-notice-info').fadeOut(300);
                    }
                }
            });
        });

        let orders = [];

        function cleanNum(str) {
            if (!str) return '';
            let cleaned = String(str).replace(/[^\d০-৯]/g, '');
            const bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
            const en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            bn.forEach((b, i) => cleaned = cleaned.replace(new RegExp(b, 'g'), en[i]));
            return cleaned;
        }

        function cleanPrefixes(text) {
            if (!text) return '';
            return text.replace(/^(নাম|Name|Customer|ঠিকানা|Address|ফোন|Phone|Mobile|মোবাইল|নাম্বার|Number|টাকা|COD|Amount|TK|টাক|টঃ)[:ঃ\-\s]*/gi, '').trim();
        }

        function parseSingle(block) {
            const lines = block.split('\n').map(l => l.trim()).filter(Boolean);
            if (lines.length < 3) return null;

            let name = cleanPrefixes(lines[0]);
            let phone = '', addr = '', cod = 0;
            const phoneRx = /^01\d{9}$/;

            for (let l of lines) {
                let p = cleanNum(cleanPrefixes(l));
                if (phoneRx.test(p)) {
                    phone = p;
                    break;
                }
            }

            addr = cleanPrefixes(lines[1]);

            for (let l of lines) {
                if (l.toLowerCase().includes('tk') || l.includes('টাকা') || l.toLowerCase().includes('cod')) {
                    cod = parseFloat(cleanNum(l)) || 0;
                    break;
                }
            }
            if (cod === 0 && lines[3]) cod = parseFloat(cleanNum(lines[3])) || 0;

            return {
                invoice: 'B-' + Math.random().toString(36).substring(7).toUpperCase(),
                recipient_name: name,
                recipient_address: addr,
                recipient_phone: phone,
                cod_amount: cod,
                status: 'Pending'
            };
        }

        $('#sfb-extract-btn').on('click', function () {
            const raw = $('#sfb-bulk-input').val();
            const blocks = raw.split('---').map(b => b.trim()).filter(Boolean);
            orders = blocks.map(b => parseSingle(b)).filter(Boolean);
            renderTable();
        });

        function renderTable() {
            const body = $('#sfb-order-body');
            body.empty();

            orders.forEach((o, idx) => {
                body.append(`
                <tr>
                    <td style="font-weight:bold;">${idx + 1}</td>
                    <td>${o.invoice}</td>
                    <td>${o.recipient_name}</td>
                    <td>${o.recipient_address}</td>
                    <td>${o.recipient_phone}</td>
                    <td>${o.cod_amount}</td>
                    <td><button class="button button-small sfb-delete-row" data-index="${idx}" style="color:red; border-color:red;">✕</button></td>
                    <td id="sfb-status-${idx}" class="sfb-status-pending">${o.status}</td>
                </tr>
            `);
            });

            $('#sfb-order-count').text(orders.length);
            $('#sfb-bulk-table-wrap').toggle(orders.length > 0);
        }

        $('#sfb-submit-bulk-btn').on('click', async function () {
            const note = $('#sfb-common-note').val().trim();
            $(this).prop('disabled', true);
            $('#sfb-bulk-summary').text('বুকিং শুরু হয়েছে...');

            for (let i = 0; i < orders.length; i++) {
                const statusCell = $(`#sfb-status-${i}`);
                statusCell.text('⏳ Processing...').removeClass().addClass('sfb-status-pending');

                try {
                    const response = await $.ajax({
                        url: sfbAjax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'sfb_bulk_create_orders',
                            nonce: sfbAjax.nonce,
                            order_data: JSON.stringify({
                                invoice: orders[i].invoice,
                                recipient_name: orders[i].recipient_name,
                                recipient_address: orders[i].recipient_address,
                                recipient_phone: orders[i].recipient_phone,
                                cod_amount: orders[i].cod_amount,
                                note: note,
                                delivery_type: 0
                            })
                        }
                    });

                    if (response.success && response.data.status === 200) {
                        const cid = response.data.consignment?.consignment_id || 'N/A';
                        statusCell.html(`
                        <div style="background:#d1fae5; padding:5px; border-radius:5px;">
                            <strong>ID: #${cid}</strong>
                        </div>
                    `).removeClass().addClass('sfb-status-success');
                    } else {
                        statusCell.text('❌ Failed: ' + (response.data.message || 'Error')).removeClass().addClass('sfb-status-error');
                    }
                } catch (e) {
                    statusCell.text('⚠️ Network Error').removeClass().addClass('sfb-status-error');
                }

                await new Promise(r => setTimeout(r, 600));
            }

            $('#sfb-bulk-summary').text('সবগুলো প্রসেস শেষ হয়েছে।');
            $(this).prop('disabled', false);
        });

        $('#sfb-bulk-clear-btn').on('click', function () {
            $('#sfb-bulk-input').val('');
            $('#sfb-bulk-table-wrap').hide();
            orders = [];
        });

        // Delete individual row
        $(document).on('click', '.sfb-delete-row', function () {
            const index = $(this).data('index');
            orders.splice(index, 1);
            renderTable();
        });
    });
</script>