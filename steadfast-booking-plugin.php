<?php
/**
 * Plugin Name: Steadfast Booking Tool
 * Description: Securely book Steadfast orders directly from the WordPress admin.
 * Version: 1.7
 * Author: almahmud
 * Author URI: https://thealmahmud.blogspot.com/
 */

// Block direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------
// 0. API KEY & SECRET CONFIGURATION (REQUIRED)
// -----------------------------------------------------------------
// IMPORTANT: Replace these dummy values with your actual Steadfast API credentials.
define('STEADFAST_API_KEY', 'l14567jg3vuvlivcfuedsmhu6ghuwn2d');
define('STEADFAST_SECRET_KEY', 'no46464fgifekz0gqmmd5evab');

// -----------------------------------------------------------------
// 1. ADMIN MENU AND ACCESS CONTROL
// -----------------------------------------------------------------
function steadfast_booking_admin_menu() {
    add_menu_page(
        'Steadfast Booking Tool',
        'Steadfast Booking',
        'edit_others_shop_orders',
        'steadfast-booking-app',
        'steadfast_booking_admin_page_content',
        'dashicons-cart',
        60
    );
}
add_action( 'admin_menu', 'steadfast_booking_admin_menu' );

// Enqueue Poppins font for this page
function steadfast_booking_admin_enqueue_styles( $hook ) {
    if ( isset($_GET['page']) && $_GET['page'] === 'steadfast-booking-app' ) {
        wp_enqueue_style( 'steadfast-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap', array(), '1.0' );
    }
}
add_action( 'admin_enqueue_scripts', 'steadfast_booking_admin_enqueue_styles' );

/**
 * Renders the admin HTML/JS. Keep markup and JS self-contained for simplicity.
 */
function steadfast_booking_admin_page_content() {
    // Using esc_js for the admin-ajax URL and a transient nonce for AJAX security
    $ajax_url = esc_js( admin_url( 'admin-ajax.php' ) );
    $ajax_nonce = wp_create_nonce( 'steadfast_booking_nonce' );
    ?>
    <div class="wrap">
        <style>
            :root { --txt-neutral-900: #1a202c; }
            .wrap { background-color: #f6f7f9; padding: 0; }
            .container { max-width: 900px; margin: 18px auto; background: #fff; padding: 18px 22px; border-radius: 8px; box-shadow: 0 6px 18px rgba(18,24,38,0.06); font-family: "Poppins", sans-serif; }
            h1 { font-size: 20px; margin: 0 0 12px; color: #1f2937; text-align: left; }
            h2 { font-size: 16px; margin: 6px 0 12px; color: #374151; }
            .section { margin-bottom: 18px; padding-bottom: 14px; border-bottom: 1px solid #eef2f6; }
            .section:last-child { border-bottom: none; }
            label { display: block; font-size: 14px; color: #374151; }
            input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 8px 10px; margin-bottom: 6px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 14px; }
            textarea { min-height: 90px; resize: vertical; }
            .btn { background-color: #16a34a; color: #fff; padding: 9px 14px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
            .btn:disabled { background: #9ca3af; cursor: not-allowed; }

            /* --- Compact layout specifically for section 2 --- */
            .compact-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; align-items: end; }
            .compact-grid .full { grid-column: 1 / -1; }
            
            .compact-note { font-size: 12px; color: #6b7280; margin-bottom: 8px; }

            /* Response area */
            .response-container { margin-top: 10px; padding: 12px; background: #e9ecef; border: 1px solid #e6eef6; border-radius: 6px; font-size: 13px; }
            #response h6 { font-size: 20px; line-height: 24px; font-weight: 600; color: var(--txt-neutral-900); display:flex; align-items:left; justify-content:space-between; margin:8px 0; }
            .copy-btn { padding:6px 10px; font-size:12px; border-radius:6px; background:#0ea5e9; color:#fff; border:none; cursor:pointer; flex-shrink:0; }
            .error { color: #dc2626; font-weight:600; }
            .success { color: #059669; font-weight:600; }

            /* Small helpers */
            .muted { color:#6b7280; font-size:12px; }
        </style>

        <div class="container">
            <h1>Steadfast Courier Booking Tool</h1>

            <div class="section">
                <h2>১. মেসেজ ইনপুট করুন</h2>
                <label for="messageInput">কাস্টমার ডেটা (৩–৪ লাইন):</label>
                <textarea id="messageInput" placeholder="Name
Address or Phone
Phone or Address
100tk (optional)"></textarea>
                <button id="processMessageBtn" class="btn">মেসেজ প্রসেস করুন</button>
                <button id="clearMessageBtn" class="btn" style="margin-left:8px;background:#ef4444;">ক্লিয়ার</button>
            </div>

            <div class="section">
                <h2>২. অর্ডারের তথ্য যাচাই করুন</h2>
                <form id="orderForm">
                    <div class="compact-grid">
                        <div>
                            <label for="invoice">ইনভয়েস (Auto):</label>
                            <input type="text" id="invoice" readonly>
                        </div>
					
                        <div>
                            <label for="delivery_type">ডেলিভারি ধরন:</label>
                            <select id="delivery_type">
                                <option value="0">0. হোম ডেলিভারি</option>
                                <option value="1">1. পয়েন্ট/হাব ডেলিভারি</option>
                            </select>
                        </div>

                        <div class="full">
                            <label for="recipient_name">ক্রেতার নাম:</label>
                            <input type="text" id="recipient_name" required>
                        </div>

                        <div class="full">
                            <label for="recipient_address">ক্রেতার ঠিকানা:</label>
                            <input type="text" id="recipient_address" required>
                        </div>

                        <div>
                            <label for="recipient_phone">ক্রেতার ফোন:</label>
                            <input type="text" id="recipient_phone" required>
                        </div>

                        <div>
                            <label for="cod_amount">COD (টাকা):</label>
                            <input type="number" id="cod_amount" min="0" required>
                        </div>

                        <div>
                            <label for="item_description">পণ্যের বিবরণ (ঐচ্ছিক):</label>
                            <input type="text" id="item_description">
                        </div>

                        <div>
                            <label for="note">নোট (ঐচ্ছিক):</label>
                            <input type="text" id="note">
                        </div>

                        <div class="full" style="display:flex; justify-content:flex-end;">
                            <button type="submit" id="confirmBookingBtn" class="btn" disabled>কনফার্ম বুকিং</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section">
                <h2>৩. API রেসপন্স</h2>
                <div id="response" class="response-container">এখানে API থেকে আসা রেসপন্স দেখা যাবে...</div>
            </div>

            <p class="muted">Tool version: 1.7</p>
        </div>
    </div>

    <script>
    // Inline JS kept for simplicity. We pass admin-ajax and a nonce to the script.
    const TRACKING_BASE_URL = 'https://steadfast.com.bd/t/';
    const AJAX_URL = '<?php echo $ajax_url; ?>';
    const AJAX_NONCE = '<?php echo $ajax_nonce; ?>';

    // DOM refs
    const messageInput = document.getElementById('messageInput');
    const processMessageBtn = document.getElementById('processMessageBtn');
    const clearMessageBtn = document.getElementById('clearMessageBtn');
    const orderForm = document.getElementById('orderForm');
    const invoiceInput = document.getElementById('invoice');
    const nameInput = document.getElementById('recipient_name');
    const addressInput = document.getElementById('recipient_address');
    const phoneInput = document.getElementById('recipient_phone');
    const codAmountInput = document.getElementById('cod_amount');
    const deliveryTypeSelect = document.getElementById('delivery_type');
    const itemDescriptionInput = document.getElementById('item_description');
    const noteInput = document.getElementById('note');
    const confirmBookingBtn = document.getElementById('confirmBookingBtn');
    const responseDiv = document.getElementById('response');
    const parseErrorDiv = document.getElementById('parseError');

    // Helper: copy to clipboard with small UX
    function copyToClipboard(text, element) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                const old = element.textContent;
                element.textContent = 'কপি হয়েছে!';
                setTimeout(() => element.textContent = old, 1400);
            }).catch(() => fallbackCopy(text));
        } else {
            fallbackCopy(text);
        }

        function fallbackCopy(t) {
            const ta = document.createElement('textarea');
            ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
            const box = document.createElement('div'); box.textContent = 'কপি হয়েছে!'; box.style.cssText = 'position:fixed;left:50%;top:40%;transform:translate(-50%,-50%);background:#111;color:#fff;padding:8px;border-radius:6px;z-index:10000;'; document.body.appendChild(box);
            setTimeout(() => document.body.removeChild(box), 1200);
        }
    }

    // convert bengali digits and strip nondigits
    function cleanNumberString(str) {
        if (!str) return '';
        // remove everything except English digits and Bengali digits
        let cleaned = String(str).replace(/[^\d০-৯]/g, '');
        // map bengali digits to english
        const bengali = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        const eng = ['0','1','2','3','4','5','6','7','8','9'];
        for (let i=0;i<bengali.length;i++) {
            cleaned = cleaned.replace(new RegExp(bengali[i], 'g'), eng[i]);
        }
        return cleaned;
    }

    function parseOrderMessage(message) {
        const lines = message.split('\n').map(l => l.trim()).filter(Boolean);
        if (lines.length < 3) return null;
        const phoneValidator = /^01\d{9}$/;
        const recipient_name = lines[0];
        let recipient_phone = null;
        let recipient_address = null;
        const cleaned1 = cleanNumberString(lines[1]);
        const cleaned2 = cleanNumberString(lines[2]);
        if (phoneValidator.test(cleaned1)) {
            recipient_phone = cleaned1; recipient_address = lines[2];
        } else if (phoneValidator.test(cleaned2)) {
            recipient_phone = cleaned2; recipient_address = lines[1];
        } else return null;

        let cod_amount = 0;
        if (lines.length >= 4) {
            const codLine = cleanNumberString(lines[3].replace(/tk/ig, ''));
            const p = parseFloat(codLine);
            if (!isNaN(p)) cod_amount = p;
        }

        return { recipient_name, recipient_address, recipient_phone, cod_amount };
    }

    processMessageBtn.addEventListener('click', () => {
        const parsed = parseOrderMessage(messageInput.value);
        if (parsed && parsed.recipient_phone) {
            invoiceInput.value = 'INV-' + Date.now();
            nameInput.value = parsed.recipient_name;
            addressInput.value = parsed.recipient_address;
            phoneInput.value = parsed.recipient_phone;
            codAmountInput.value = parsed.cod_amount;
            confirmBookingBtn.disabled = false;
            responseDiv.innerHTML = '';
        } else {
            responseDiv.innerHTML = '<div class="error">মেসেজের প্যাটার্ন ঠিক নেই বা ডেটা অসম্পূর্ণ।</div>';
            invoiceInput.value = nameInput.value = addressInput.value = phoneInput.value = codAmountInput.value = '';
            confirmBookingBtn.disabled = true;
        }
    });

    async function createOrder(orderData) {
        responseDiv.innerHTML = '<div class="muted">বুকিং প্রক্রিয়া চলছে…</div>';
        const fd = new FormData();
        fd.append('action', 'steadfast_booking_api_call');
        fd.append('nonce', AJAX_NONCE);
        fd.append('order_data', JSON.stringify(orderData));

        try {
            const res = await fetch(AJAX_URL, { method: 'POST', body: fd, credentials: 'same-origin' });
            const json = await res.json();
            if (json.success && json.data && json.data.status === 200) {
                const cid = json.data.consignment?.consignment_id || 'N/A';
                const trackingCode = json.data.consignment?.tracking_code || '';
                const trackingLink = trackingCode ? TRACKING_BASE_URL + trackingCode : 'N/A';
                const parcelText = `Parcel Id : #${cid}`;
                const trackingText = `Tracking Link: ${trackingLink}`;
                responseDiv.innerHTML = `<div class="success">✅ বুকিং সফল হয়েছে!</div>
<h6><span>${parcelText}</span> 
<button class="copy-btn" onclick="(function(el){ copyToClipboard('${parcelText.replace(/'/g, "\\'")}', el); })(this)">কপি করুন</button></h6>
<h6><span>Tracking Link: </span> 
<button class="copy-btn" onclick="(function(el){ copyToClipboard('${trackingText.replace(/'/g, "\\'")}', el); })(this)">কপি করুন</button></h6>
<hr>
<details><summary>সম্পূর্ণ API রেসপন্স</summary><pre style="white-space:pre-wrap; font-size:13px;">${JSON.stringify(json.data, null, 2)}</pre></details>`;
            } else {
                const err = (json.data && (json.data.message || json.data.error)) || json.message || 'Unknown error';
                responseDiv.innerHTML = `<div class="error">বুকিং ব্যর্থ: ${err}</div><pre style="font-size:12px;">${JSON.stringify(json.data || json, null, 2)}</pre>`;
            }
        } catch (e) {
            responseDiv.innerHTML = `<div class="error">Network or AJAX error: ${e.message}</div>`;
        }
    }

    window.copyResponseData = function(el, text) { copyToClipboard(text, el); };

    // Clear message button handler (separate, so it doesn't interfere with parsing)
    if (typeof clearMessageBtn !== 'undefined' && clearMessageBtn) {
        clearMessageBtn.addEventListener('click', () => {
            messageInput.value = '';
            responseDiv.innerHTML = '';
            invoiceInput.value = '';
            nameInput.value = '';
            addressInput.value = '';
            phoneInput.value = '';
            codAmountInput.value = '';
            confirmBookingBtn.disabled = true;
            messageInput.focus();
        });
    };

    orderForm.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const orderData = {
            invoice: invoiceInput.value,
            recipient_name: nameInput.value.trim(),
            recipient_address: addressInput.value.trim(),
            recipient_phone: phoneInput.value.trim(),
            cod_amount: Number(codAmountInput.value) || 0,
            delivery_type: parseInt(deliveryTypeSelect.value, 10) || 0,
            item_description: itemDescriptionInput.value.trim(),
            note: noteInput.value.trim(),
        };

        if (!orderData.invoice || !orderData.recipient_name || !orderData.recipient_address || !/^01\d{9}$/.test(orderData.recipient_phone)) {
            responseDiv.innerHTML = '<div class="error">সব টেক্সট ফিল্ড ঠিক করে দিন (মোবাইল ১১ সংখ্যার হবে)।</div>';
            return;
        }

        confirmBookingBtn.disabled = true;
        await createOrder(orderData);
        confirmBookingBtn.disabled = false;
    });
    </script>
    <?php
}

// -----------------------------------------------------------------
// 2. SERVER-SIDE PHP AJAX HANDLER (Secure API Call + Validation)
// -----------------------------------------------------------------
function steadfast_booking_api_call() {
    // Only logged-in users with capability
    if ( ! is_user_logged_in() || ! current_user_can('edit_others_shop_orders') ) {
        wp_send_json_error( array( 'message' => 'Authentication failed. Please log in with sufficient privileges.' ), 401 );
    }

    // Check nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'steadfast_booking_nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Security check failed (invalid nonce).' ), 403 );
    }

    $api_key = STEADFAST_API_KEY;
    $secret_key = STEADFAST_SECRET_KEY;
    $base_url = 'https://portal.packzy.com/api/v1/create_order';

    $order_data_json = isset($_POST['order_data']) ? wp_unslash( $_POST['order_data'] ) : '';
    $order_data = json_decode( $order_data_json, true );

    if ( empty( $order_data ) || ! is_array( $order_data ) ) {
        wp_send_json_error( array( 'message' => 'Order data not found or invalid.' ), 400 );
    }

    // Basic sanitization & validation
    $invoice = isset($order_data['invoice']) ? sanitize_text_field( $order_data['invoice'] ) : '';
    $recipient_name = isset($order_data['recipient_name']) ? sanitize_text_field( $order_data['recipient_name'] ) : '';
    $recipient_address = isset($order_data['recipient_address']) ? sanitize_textarea_field( $order_data['recipient_address'] ) : '';
    $recipient_phone = isset($order_data['recipient_phone']) ? preg_replace('/[^0-9]/', '', $order_data['recipient_phone']) : '';
    $cod_amount = isset($order_data['cod_amount']) ? floatval( $order_data['cod_amount'] ) : 0;
    $delivery_type = isset($order_data['delivery_type']) ? intval( $order_data['delivery_type'] ) : 0;
    $item_description = isset($order_data['item_description']) ? sanitize_text_field( $order_data['item_description'] ) : '';
    $note = isset($order_data['note']) ? sanitize_text_field( $order_data['note'] ) : '';

    // Validate phone (Bangladeshi 11-digit starting with 01)
    if ( ! preg_match( '/^01\d{9}$/', $recipient_phone ) ) {
        wp_send_json_error( array( 'message' => 'Recipient phone invalid. Expecting Bangladeshi 11-digit number starting with 01.' ), 422 );
    }

    // Prepare payload for external API — adjust keys per API spec
    $payload = array(
        'invoice' => $invoice,
        'recipient_name' => $recipient_name,
        'recipient_address' => $recipient_address,
        'recipient_phone' => $recipient_phone,
        'cod_amount' => $cod_amount,
        'delivery_type' => $delivery_type,
        'item_description' => $item_description,
        'note' => $note,
    );

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Api-Key' => $api_key,
            'Secret-Key' => $secret_key,
        ),
        'body' => wp_json_encode( $payload ),
        'method' => 'POST',
        'timeout' => 45,
    );

    $response = wp_remote_post( $base_url, $args );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( array( 'message' => 'API call failed: ' . $response->get_error_message() ) );
    }

    $body = wp_remote_retrieve_body( $response );
    $result = json_decode( $body, true );

    if ( $result && isset( $result['status'] ) && intval( $result['status'] ) === 200 ) {
        wp_send_json_success( $result );
    } else {
        wp_send_json_error( $result ?: array( 'message' => 'Unknown API error', 'raw_body' => $body ) );
    }

    wp_die();
}
add_action( 'wp_ajax_steadfast_booking_api_call', 'steadfast_booking_api_call' );

// End of file
