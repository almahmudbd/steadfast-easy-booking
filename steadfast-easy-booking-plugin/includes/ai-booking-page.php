<?php
if (!defined('ABSPATH'))
    exit;

$has_keys = !empty(get_option('sfb_api_key')) && !empty(get_option('sfb_secret_key'));
?>

<div class="wrap"
    style="max-width: 1100px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.04);">
    <div class="settings-bar"
        style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #e6eef6;">
        <h1 style="margin:0; font-size: 18px;">Steadfast Ai Bulk Booking</h1>
        <button onclick="openSettings()" class="button">⚙️ AI Settings</button>
    </div>

    <!-- API Status Info -->
    <div id="statusStrip" style="font-size:12px; margin-bottom:15px; color:#6b7280">
        Steadfast: <span id="stStatus">
            <?php echo $has_keys ? '🟢' : '🔴'; ?>
        </span> | Gemini AI: <span id="aiStatus">🔴</span>
        <?php if (!$has_keys): ?>
            <span style="color: #ef4444; margin-left: 10px;">(API Key সেট করা নেই। <a
                    href="<?php echo admin_url('admin.php?page=steadfast-settings'); ?>">সেটিংস পেজে যান</a>)</span>
        <?php endif; ?>
    </div>

    <!-- Section 2: AI -->
    <section class="ai-box"
        style="border: 2px dashed #8b5cf6; border-radius: 12px; padding: 20px; background: #f5f3ff; margin-top: 25px; position: relative;">
        <div class="ai-badge"
            style="position: absolute; top: -12px; right: 20px; background: #8b5cf6; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;">
            AI SMART READ</div>
        <label style="display:block;margin-bottom:8px;font-weight:600; color: #8b5cf6;">২. স্মার্ট এআই রিডার (মেসেজ
            পেস্ট করুন)</label>
        <textarea id="aiInput"
            style="width: 100%; min-height: 120px; padding: 12px; border: 1px solid #c4b5fd; border-radius: 8px; box-sizing: border-box; margin-bottom: 15px; outline: none;"
            placeholder="মেসেঞ্জারের কাস্টমার মেসেজ এখানে পেস্ট করুন..."></textarea>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button id="aiExtractBtn" class="button button-primary"
                style="background: #8b5cf6; border-color: #8b5cf6;">AI দিয়ে তথ্য বের করুন ✨</button>
            <button id="extractBtn" class="button">ম্যানুয়াল এন্ট্রি</button>
            <button onclick="clearAll()" class="button">সব ক্লিয়ার</button>
        </div>
        <span id="aiLoading" style="display: none; margin-top:10px; color:#8b5cf6; font-size: 12px;">
            <span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> প্রসেসিং হচ্ছে...
        </span>
    </section>

    <!-- Table -->
    <div class="table-container" id="tableWrap" style="display:none; margin-top: 20px; overflow-x: auto;">
        <h3>অর্ডার লিস্ট (<span id="count">0</span>)</h3>
        <table class="wp-list-table widefat fixed striped" id="orderTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>নাম</th>
                    <th>ঠিকানা</th>
                    <th>ফোন</th>
                    <th>COD</th>
                    <th>মুছুন</th>
                    <th>স্ট্যাটাস</th>
                </tr>
            </thead>
            <tbody id="orderBody"></tbody>
        </table>

        <form onsubmit="return false;"
            style="margin-top:20px; background:#f9fafb; padding:15px; border-radius:10px; border:1px dashed #d1d5db">
            <label for="commonNote" style="display:block;margin-bottom:8px;font-weight:600;font-size:14px">কমন নোট
                (সবগুলো অর্ডারের জন্য)</label>
            <input type="text" id="commonNote" name="common_note" autocomplete="on" class="regular-text"
                placeholder="যেমন: সাবধানে ডেলিভারি করবেন / চেক করে নিতে বলবেন" style="width:100%; margin-bottom:10px;">
            <button id="submitBulkBtn" class="button button-primary button-large"
                style="width:100%; margin-top: 10px;">সবগুলো একসাথে বুকিং দিন</button>
        </form>
    </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="sfb-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div class="modal-content"
        style="background: #fff; padding: 25px; border-radius: 12px; width: 90%; max-width: 400px; position:relative;">
        <span onclick="closeSettings()"
            style="position: absolute; top: 10px; right: 15px; cursor: pointer; font-size: 20px;">&times;</span>
        <h3>⚙️ AI Settings</h3>
        <p style="font-size:12px; color:#6b7280">আপনার এপিআই কিগুলো ব্রাউজারে সুরক্ষিত থাকবে।</p>

        <label style="font-size:13px; font-weight:600; display:block; margin-bottom:5px;">Gemini AI Key <a
                href="https://aistudio.google.com/app/apikey" target="_blank"
                style="color:#8b5cf6; text-decoration:underline; font-size:12px;">(সংগ্রহ করুন)</a></label>
        <input type="password" id="setAiKey" class="regular-text" style="width:100%; margin-bottom:15px;">

        <div style="margin-bottom: 20px;">
            <label style="font-size:13px; font-weight:600; display:block; margin-bottom:8px;">AI Model Select:</label>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <label style="font-size:13px; cursor:pointer; display:flex; align-items:center;">
                    <input type="radio" name="aiModel" value="gemini-2.5-flash-lite" style="margin-right:8px;">
                    Gemini 2.5 Flash Lite (Fastest, High Limit)
                </label>
                <label style="font-size:13px; cursor:pointer; display:flex; align-items:center;">
                    <input type="radio" name="aiModel" value="gemini-2.0-flash" style="margin-right:8px;">
                    Gemini 2.0 Flash (Good Balance)
                </label>
            </div>
        </div>

        <div style="display:flex; gap:10px">
            <button onclick="saveSettings()" class="button button-primary" style="flex:1">Save</button>
            <button onclick="closeSettings()" class="button" style="flex:1">Close</button>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function ($) {
        // Check if sfbAjax is defined
        if (typeof sfbAjax === 'undefined') {
            console.error('Steadfast Plugin: sfbAjax is not defined. Ensure generic admin scripts are loaded.');
            return;
        }

        const LS_AI_KEY = 'geminiApiKey_user';
        const LS_MODEL_KEY = 'geminiModel_user';
        let orders = [];

        // Make functions global for inline onclick handlers
        window.orders = orders;

        // --- Data Extraction Helpers ---
        function cleanNum(str) { return String(str).replace(/[^\d]/g, ''); }
        function cleanPrefix(txt) { return txt.replace(/^(নাম|ঠিকানা|ফোন|টাকা|Name|Address|Phone|COD)[:\s-]*/gi, '').trim(); }

        window.openSettings = function () {
            $('#setAiKey').val(localStorage.getItem(LS_AI_KEY) || '');
            const savedModel = localStorage.getItem(LS_MODEL_KEY) || 'gemini-2.5-flash-lite';
            $(`input[name="aiModel"][value="${savedModel}"]`).prop('checked', true);
            $('#settingsModal').css('display', 'flex');
        };

        window.closeSettings = function () { $('#settingsModal').hide(); };

        window.saveSettings = function () {
            localStorage.setItem(LS_AI_KEY, $('#setAiKey').val().trim());
            const selectedModel = $('input[name="aiModel"]:checked').val() || 'gemini-2.5-flash-lite';
            localStorage.setItem(LS_MODEL_KEY, selectedModel);
            alert('Settings Saved!');
            updateStatus();
            closeSettings();
        };

        function updateStatus() {
            const hasAi = !!localStorage.getItem(LS_AI_KEY);
            $('#aiStatus').text(hasAi ? '🟢' : '🔴');
        }

        // Initial Status Check
        updateStatus();

        // --- Manual Entry ---
        $('#extractBtn').on('click', function () {
            const val = $('#aiInput').val().trim();
            if (!val) return;
            const chunks = val.split(/---|\n\n/).map(s => s.trim()).filter(Boolean);
            chunks.forEach(c => {
                const lines = c.split('\n').map(l => l.trim()).filter(Boolean);
                if (lines.length >= 2) {
                    orders.push({
                        name: cleanPrefix(lines[0]),
                        address: cleanPrefix(lines[1]),
                        phone: cleanNum(lines[2] || ''),
                        cod: cleanNum(lines[3] || '0'),
                        status: 'Pending'
                    });
                }
            });
            render();
            $('#aiInput').val('');
        });

        // --- AI Extraction ---
        $('#aiExtractBtn').on('click', async function () {
            const key = localStorage.getItem(LS_AI_KEY);
            const model = localStorage.getItem(LS_MODEL_KEY) || 'gemini-2.5-flash-lite';
            const text = $('#aiInput').val().trim();

            if (!key || !text) {
                alert('API Key এবং টেক্সট দিন।');
                if (!key) openSettings();
                return;
            }

            const btn = $(this);
            const originalText = btn.html();
            btn.prop('disabled', true).html('প্রসেসিং...');
            $('#aiLoading').show();

            try {
                const res = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${key}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        contents: [{ parts: [{ text: text }] }],
                        systemInstruction: { parts: [{ text: "Extract order data: name, address, phone, cod. Return ONLY valid JSON array." }] },
                        generationConfig: { responseMimeType: "application/json" }
                    })
                });
                const json = await res.json();

                if (json.error) throw new Error(json.error.message || 'API Error');
                if (!json.candidates || !json.candidates[0]) throw new Error('No content returned from AI');

                const rawText = json.candidates[0].content.parts[0].text;
                const data = JSON.parse(rawText);
                const list = Array.isArray(data) ? data : [data];

                list.forEach(o => {
                    orders.push({
                        name: o.name || 'N/A',
                        address: o.address || 'N/A',
                        phone: cleanNum(o.phone || ''),
                        cod: cleanNum(o.cod || '0'),
                        status: 'Pending'
                    });
                });

                render();
                $('#aiInput').val('');
                btn.html('✓ সফল!');
                setTimeout(() => btn.html(originalText), 2000);

            } catch (e) {
                console.error(e);
                alert('AI Error: ' + e.message);
            } finally {
                btn.prop('disabled', false);
                if (btn.html() !== '✓ সফল!') btn.html(originalText);
                $('#aiLoading').hide();
            }
        });

        // --- Render Table ---
        function render() {
            const tbody = $('#orderBody');
            tbody.empty();

            orders.forEach((o, i) => {
                tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td>${o.name}</td>
                    <td>${o.address}</td>
                    <td>${o.phone}</td>
                    <td>${o.cod}</td>
                    <td><button class="button button-small sfb-delete-order" data-index="${i}" style="color:red; border-color:red;">✕</button></td>
                    <td id="st-${i}" class="status-cell">${o.status}</td>
                </tr>
            `);
            });

            $('#count').text(orders.length);
            $('#tableWrap').toggle(orders.length > 0);
        }

        // Event delegation for delete button
        $(document).on('click', '.sfb-delete-order', function () {
            const index = $(this).data('index');
            orders.splice(index, 1);
            render();
        });

        window.clearAll = function () {
            orders = [];
            render();
        };

        // --- Bulk Submission via WP AJAX ---
        $('#submitBulkBtn').on('click', async function () {
            const note = $('#commonNote').val();

            // Disable button
            $(this).prop('disabled', true);

            for (let i = 0; i < orders.length; i++) {
                if (orders[i].status !== 'Pending') continue;

                const cell = $(`#st-${i}`);
                cell.text('⏳...');

                // Call WP AJAX
                try {
                    await new Promise((resolve, reject) => {
                        $.ajax({
                            url: sfbAjax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'sfb_create_order',
                                nonce: sfbAjax.nonce,
                                invoice: 'B' + Date.now().toString(36) + i, // Unique invoice
                                recipient_name: orders[i].name,
                                recipient_address: orders[i].address,
                                recipient_phone: orders[i].phone,
                                cod_amount: orders[i].cod,
                                note: note,
                                delivery_type: 48 // Default delivery type
                            },
                            success: function (response) {
                                if (response.success) {
                                    const d = response.data;
                                    if (d.status === 200) {
                                        orders[i].status = 'Success';
                                        const parcelId = d.consignment.consignment_id;
                                        cell.html(`<div style="background:#e6fcf5; padding:5px; border-radius:4px; border:1px solid #c2f3e1;">
                                        <div style="font-weight:bold; color:#065f46; font-size:11px;">ID: #${parcelId}</div>
                                        <button class="button button-small copy-btn" data-id="${parcelId}" style="margin-top:2px;">📋 Copy</button>
                                    </div>`);
                                    } else {
                                        cell.text('❌ Failed: ' + (d.message || 'Unknown'));
                                    }
                                } else {
                                    cell.text('❌ Error: ' + (response.data || 'Server error'));
                                }
                                resolve();
                            },
                            error: function (xhr, status, err) {
                                cell.text('⚠️ Error');
                                console.error(err);
                                resolve(); // Continue to next
                            }
                        });
                    });

                    // Small delay to be nice to server
                    await new Promise(r => setTimeout(r, 300));

                } catch (e) {
                    cell.text('⚠️ Exception');
                }
            }

            $(this).prop('disabled', false);
        });

        // Copy Handler
        $(document).on('click', '.copy-btn', function () {
            const id = $(this).data('id');
            const text = `Parcel ID: #${id}`;
            navigator.clipboard.writeText(text).then(() => {
                const btn = $(this);
                const original = btn.text();
                btn.text('✅');
                setTimeout(() => btn.text(original), 1500);
            });
        });

    });
</script>