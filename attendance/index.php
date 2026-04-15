<?php
// attendance/index.php
$pageTitle = 'Attendance Check-In';
require_once '../includes/header.php';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int) $_POST['member_id'];

    if ($member_id > 0) {
        $memberStmt = $pdo->prepare("SELECT status, full_name, deleted_at FROM members WHERE id = ?");
        $memberStmt->execute([$member_id]);
        $member = $memberStmt->fetch();

        if ($member) {
            if ($member->deleted_at !== null) {
                $error = "This membership has been archived.";
            } elseif ($member->status !== 'Active') {
                $error = "Access Denied: Membership is " . $member->status . ".";
            } else {
                $today     = date('Y-m-d');
                $checkStmt = $pdo->prepare("SELECT id FROM attendance_logs WHERE member_id = ? AND DATE(time_in) = ? AND time_out IS NULL");
                $checkStmt->execute([$member_id, $today]);
                $openLog   = $checkStmt->fetch();

                if ($openLog) {
                    $pdo->prepare("UPDATE attendance_logs SET time_out = NOW() WHERE id = ?")->execute([$openLog->id]);
                    $success = "{$member->full_name} successfully Checked OUT.";
                } else {
                    $pdo->prepare("INSERT INTO attendance_logs (member_id, time_in) VALUES (?, NOW())")->execute([$member_id]);
                    $success = "{$member->full_name} successfully Checked IN.";
                }
            }
        } else {
            $error = "Member not found.";
        }
    }
}

// Today's logs
$todayStr   = date('Y-m-d');
$logsStmt   = $pdo->query("SELECT a.*, m.full_name, m.photo_path FROM attendance_logs a JOIN members m ON a.member_id = m.id WHERE DATE(a.time_in) = '$todayStr' ORDER BY a.time_in DESC LIMIT 10");
$recentLogs = $logsStmt->fetchAll();

// Active members for dropdown (exclude archived)
$membersStmt   = $pdo->query("SELECT id, full_name, phone FROM members WHERE status = 'Active' AND deleted_at IS NULL ORDER BY full_name ASC");
$activeMembers = $membersStmt->fetchAll();
?>

<style>
.tab-btn {
    padding: 8px 18px;
    border: none;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--text-secondary);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: all 0.15s;
}
.tab-btn:hover { background: var(--bg-hover); color: var(--text-primary); }
.active-tab   { background: var(--accent-soft) !important; color: var(--accent-text) !important; }

/* Scanner container — give it real dimensions so the library can render */
#qr-reader {
    width: 100%;
    min-height: 300px;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: #000;
}
/* Style the library's internal video to fill the container */
#qr-reader video {
    width: 100% !important;
    border-radius: var(--radius-md);
}
#qr-reader img {
    display: none; /* hide the branding image */
}

/* File upload drop zone */
.qr-dropzone {
    border: 2px dashed var(--border-strong);
    border-radius: var(--radius-md);
    padding: 24px;
    text-align: center;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}
.qr-dropzone:hover,
.qr-dropzone.drag-over {
    border-color: var(--accent);
    background: var(--accent-soft);
    color: var(--accent-text);
}
.qr-dropzone input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}
.qr-dropzone .preview-img {
    max-width: 200px;
    max-height: 150px;
    border-radius: var(--radius-md);
    margin-top: 10px;
}
</style>

<!-- Tab Bar -->
<div style="display:flex;gap:4px;margin-bottom:20px;background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:4px;width:fit-content;">
    <button id="tab-manual"  class="tab-btn active-tab" onclick="switchTab('manual')">
        <i class="fas fa-list-check"></i> Manual Select
    </button>
    <button id="tab-scanner" class="tab-btn" onclick="switchTab('scanner')">
        <i class="fas fa-qrcode"></i> QR Scanner
    </button>
</div>

<div class="form-row" style="align-items:flex-start;">

    <!-- LEFT panel -->
    <div style="flex:1;">

        <!-- MANUAL TAB -->
        <div id="panel-manual">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Check In / Out</h3></div>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin:16px 22px 0;"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin:16px 22px 0;"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form id="manual-attendance-form" method="POST" style="padding:22px;">
                    <div class="form-group">
                        <label>Select Member</label>
                        <select name="member_id" class="form-control searchable-select" required>
                            <option value="">— Choose Member —</option>
                            <?php foreach ($activeMembers as $m): ?>
                                <option value="<?= $m->id ?>">
                                    <?= htmlspecialchars($m->full_name) ?> (<?= htmlspecialchars($m->phone) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;font-size:16px;padding:13px;">
                        <i class="fas fa-sign-in-alt"></i> Process Attendance
                    </button>
                </form>

                <div style="padding:0 22px 18px;text-align:center;">
                    <p style="color:var(--text-muted);font-size:13px;"><em>Auto-detects check-in or check-out.</em></p>
                    <a href="history.php" class="btn btn-ghost btn-sm" style="margin-top:8px;">View Full History</a>
                </div>
            </div>
        </div>

        <!-- QR SCANNER TAB -->
        <div id="panel-scanner" style="display:none;">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-qrcode" style="color:var(--accent);margin-right:6px;"></i> QR Code Scanner</h3>
                </div>

                <!-- Feedback banner -->
                <div id="scan-feedback" style="display:none;margin:16px 22px 0;padding:14px 18px;border-radius:var(--radius-md);font-size:14px;font-weight:600;align-items:center;gap:10px;"></div>

                <div style="padding:22px;">

                    <!-- ===== 1. CAMERA SCANNER ===== -->
                    <h4 style="margin:0 0 10px;font-size:14px;color:var(--text-secondary);"><i class="fas fa-video"></i> Camera Scanner</h4>
                    <div id="qr-reader"></div>

                    <!-- Camera controls -->
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <button id="start-scan-btn" type="button" class="btn btn-primary btn-sm" onclick="startCameraScanner()">
                            <i class="fas fa-play"></i> Start Camera
                        </button>
                        <button id="stop-scan-btn" type="button" class="btn btn-ghost btn-sm" onclick="stopCameraScanner()" style="display:none;">
                            <i class="fas fa-stop"></i> Stop Camera
                        </button>
                    </div>

                    <hr style="margin:20px 0;border-color:var(--border);">

                    <!-- ===== 2. FILE/IMAGE UPLOAD ===== -->
                    <h4 style="margin:0 0 10px;font-size:14px;color:var(--text-secondary);"><i class="fas fa-image"></i> Scan from Image</h4>
                    <div class="qr-dropzone" id="qr-dropzone">
                        <input type="file" id="qr-file-input" accept="image/jpeg,image/png,image/gif,image/webp" onchange="scanFromFile(event)">
                        <i class="fas fa-cloud-upload-alt" style="font-size:28px;display:block;margin-bottom:8px;"></i>
                        <span>Drag & drop or click to upload a QR code image</span>
                        <br><small style="opacity:0.6;">JPG, PNG, GIF, WebP supported</small>
                        <div id="file-preview"></div>
                    </div>

                    <hr style="margin:20px 0;border-color:var(--border);">

                    <!-- ===== 3. MANUAL ID FALLBACK ===== -->
                    <h4 style="margin:0 0 10px;font-size:14px;color:var(--text-secondary);"><i class="fas fa-keyboard"></i> Manual Entry</h4>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="text" id="manual-id-input" class="form-control" placeholder="Type membership ID" style="max-width:280px;" onkeydown="if(event.key==='Enter'){submitManualId();}">
                        <button type="button" class="btn btn-primary btn-sm" onclick="submitManualId()">
                            <i class="fas fa-paper-plane"></i> Process
                        </button>
                    </div>

                    <!-- Debug panel -->
                    <div style="margin-top:16px;border:1px dashed var(--border-strong);border-radius:var(--radius-md);padding:10px 12px;background:var(--bg-input);">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <strong style="font-size:12px;color:var(--text-secondary);">Debug Log</strong>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleDebug()" style="padding:4px 8px;font-size:11px;">Toggle</button>
                        </div>
                        <div id="debug-state" style="margin-top:4px;font-size:12px;color:var(--text-muted);">State: idle</div>
                        <pre id="debug-log" style="margin-top:6px;max-height:120px;overflow:auto;font-size:11px;line-height:1.4;white-space:pre-wrap;color:var(--text-secondary);">Ready.</pre>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT: Today's Activity -->
    <div style="flex:2;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Today's Activity</h3>
                <span style="font-size:12px;color:var(--text-muted);"><?= date('l, M d Y') ?></span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Member</th><th>Time In</th><th>Time Out</th><th>Status</th></tr>
                    </thead>
                    <tbody id="activity-table">
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td>
                                    <?php
                                    $photoUrl    = getMemberPhotoUrl($log->photo_path);
                                    $logParts    = explode(' ', $log->full_name);
                                    $logInitials = strtoupper(substr($logParts[0],0,1) . (isset($logParts[1]) ? substr($logParts[1],0,1) : ''));
                                    ?>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;border-radius:50%;overflow:hidden;background:var(--accent);flex-shrink:0;">
                                            <?php if ($photoUrl): ?>
                                                <img src="<?= htmlspecialchars($photoUrl) ?>" style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;"><?= $logInitials ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="td-name"><?= htmlspecialchars($log->full_name) ?></span>
                                    </div>
                                </td>
                                <td><?= date('h:i A', strtotime($log->time_in)) ?></td>
                                <td><?= $log->time_out ? date('h:i A', strtotime($log->time_out)) : '—' ?></td>
                                <td>
                                    <?php if ($log->time_out): ?>
                                        <span class="badge" style="background:#6c757d22;color:#9292a4;border:1px solid #9292a436;">Checked Out</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">Active in Gym</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fas fa-clock"></i></div>
                                    <h3>No check-ins today yet</h3>
                                </div>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- HTML5 QR decoder -->
<script src="../assets/js/vendor/html5-qrcode.min.js"></script>
<script>
(function() {
    'use strict';

    var html5Qr = null;
    var scannerRunning = false;
    var scanning = false;
    var cooldown = false;
    var lastPayload = '';
    var lastPayloadAt = 0;
    var debugVisible = true;
    var scanAttemptCount = 0;
    var scanNoResultCount = 0;

    /* ── Debug ─────────────────────────────────────────────── */
    function setState(t) {
        var el = document.getElementById('debug-state');
        if (el) el.textContent = 'State: ' + t;
    }

    function log(t) {
        console.log('[QR]', t);
        var el = document.getElementById('debug-log');
        if (!el) return;
        var stamp = new Date().toLocaleTimeString();
        var line = '[' + stamp + '] ' + t;
        el.textContent = line + '\n' + el.textContent;
        var lines = el.textContent.split('\n');
        if (lines.length > 300) el.textContent = lines.slice(0, 300).join('\n');
    }

    window.toggleDebug = function() {
        var el = document.getElementById('debug-log');
        if (!el) return;
        debugVisible = !debugVisible;
        el.style.display = debugVisible ? 'block' : 'none';
    };

    /* ── Tab switching ─────────────────────────────────────── */
    window.switchTab = function(tab) {
        document.getElementById('panel-manual').style.display  = tab === 'manual'  ? 'block' : 'none';
        document.getElementById('panel-scanner').style.display = tab === 'scanner' ? 'block' : 'none';
        document.getElementById('tab-manual').classList.toggle('active-tab',  tab === 'manual');
        document.getElementById('tab-scanner').classList.toggle('active-tab', tab === 'scanner');
        if (tab === 'manual' && scanning) stopCameraScanner();
        setState(tab === 'scanner' ? 'ready — click Start Camera' : 'manual tab');
    };

    function cleanPayload(value) {
        return String(value || '')
            .replace(/[\u0000-\u001F\u007F]+/g, ' ')
            .trim();
    }

    function extractMembershipId(value) {
        var raw = cleanPayload(value);
        if (!raw) return '';

        var gymMatch = raw.match(/GYM-\d{4}-\d{3,}/i);
        if (gymMatch && gymMatch[0]) return gymMatch[0].toUpperCase();

        var compact = raw.match(/[A-Za-z0-9_-]{4,}/);
        return compact && compact[0] ? compact[0] : raw;
    }

    function setScanButtons(isScanning) {
        var startBtn = document.getElementById('start-scan-btn');
        var stopBtn = document.getElementById('stop-scan-btn');
        if (startBtn) startBtn.style.display = isScanning ? 'none' : 'inline-flex';
        if (stopBtn) stopBtn.style.display = isScanning ? 'inline-flex' : 'none';
    }

    function ensureHtml5Qr(timeoutMs) {
        timeoutMs = timeoutMs || 5000;

        return new Promise(function(resolve, reject) {
            var startedAt = Date.now();

            function finalizeIfReady() {
                if (!window.Html5Qrcode) {
                    return false;
                }
                if (!html5Qr) {
                    html5Qr = new Html5Qrcode('qr-reader');
                }
                resolve(html5Qr);
                return true;
            }

            if (finalizeIfReady()) {
                return;
            }

            var timer = setInterval(function() {
                if (finalizeIfReady()) {
                    clearInterval(timer);
                    return;
                }

                if ((Date.now() - startedAt) >= timeoutMs) {
                    clearInterval(timer);
                    reject(new Error('html5-qrcode is not loaded.'));
                }
            }, 100);
        });
    }

    function getPreferredCameraConfig() {
        return Html5Qrcode.getCameras().then(function(cameras) {
            if (!cameras || !cameras.length) {
                return { facingMode: 'environment' };
            }

            var preferred = cameras.find(function(camera) {
                var label = String(camera.label || '').toLowerCase();
                return label.indexOf('back') !== -1 || label.indexOf('rear') !== -1 || label.indexOf('environment') !== -1;
            });

            return preferred ? preferred.id : cameras[0].id;
        }).catch(function() {
            return { facingMode: 'environment' };
        });
    }

    function startCameraScanner() {
        if (scanning) return;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showFeedback(false, 'Camera API is not available in this browser.');
            return;
        }

        scanAttemptCount = 0;
        scanNoResultCount = 0;
        setState('starting camera...');
        log('Starting camera scanner.');
        setScanButtons(false);

        setState('loading scanner library...');

        ensureHtml5Qr().then(function() {
            return getPreferredCameraConfig();
        }).then(function(cameraConfig) {
            return html5Qr.start(
                cameraConfig,
                {
                    fps: 10,
                    qrbox: { width: 240, height: 240 },
                    rememberLastUsedCamera: true,
                    aspectRatio: 1.3333
                },
                function(decodedText) {
                    scanAttemptCount += 1;
                    setState('scan success on attempt #' + scanAttemptCount);
                    log('Attempt #' + scanAttemptCount + ': QR detected -> ' + decodedText);
                    onScanSuccess(decodedText);
                },
                function() {
                    scanNoResultCount += 1;
                }
            );
        }).then(function() {
            scannerRunning = true;
            scanning = true;
            setState('scanner running');
            setScanButtons(true);
            log('Scanner running with html5-qrcode.');
        }).catch(function(finalErr) {
            scannerRunning = false;
            scanning = false;
            setScanButtons(false);
            var errorText = (finalErr && finalErr.message ? finalErr.message : String(finalErr || 'Unknown error'));
            log('Camera start failed: ' + errorText);

            if (errorText.indexOf('html5-qrcode is not loaded') !== -1) {
                setState('scanner unavailable');
                showFeedback(false, 'QR scanner library failed to load. Please check internet access and refresh.');
                return;
            }

            setState('camera error');
            showFeedback(false, 'Unable to access camera. Allow permission, then try again.');
        });
    }
    window.startCameraScanner = startCameraScanner;

    function stopCameraScanner() {
        scanning = false;
        if (!html5Qr || !scannerRunning) {
            setScanButtons(false);
            setState('scanner stopped');
            return;
        }

        html5Qr.stop().then(function() {
            scannerRunning = false;
            return html5Qr.clear();
        }).catch(function() {
            scannerRunning = false;
        }).finally(function() {
            setScanButtons(false);
            setState('scanner stopped | attempts: ' + scanAttemptCount + ', misses: ' + scanNoResultCount);
            log('Scanner stopped. Total attempts=' + scanAttemptCount + ', misses=' + scanNoResultCount + '.');
        });
    }
    window.stopCameraScanner = stopCameraScanner;

     /* ══════════════════════════════════════════════════════════
         2. FILE/IMAGE UPLOAD SCANNING
         Uses html5-qrcode image decoder
     ══════════════════════════════════════════════════════════ */

    // Drag-and-drop visual feedback
    var dropzone = document.getElementById('qr-dropzone');
    if (dropzone) {
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('drag-over');
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                processUploadedFile(files[0]);
            }
        });
    }

    window.scanFromFile = function(event) {
        var file = event.target.files && event.target.files[0];
        if (file) processUploadedFile(file);
    };

    function processUploadedFile(file) {
        // Validate file type
        var validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (validTypes.indexOf(file.type) === -1) {
            showFeedback(false, 'Invalid file type. Use JPG, PNG, GIF, or WebP.');
            return;
        }

        log('Scanning uploaded file: ' + file.name + ' (' + file.type + ')');
        setState('scanning image...');

        // Show file preview
        var previewEl = document.getElementById('file-preview');
        if (previewEl) {
            var url = URL.createObjectURL(file);
            previewEl.innerHTML = '<img src="' + url + '" class="preview-img" alt="Uploaded QR">';
        }

        var scanFilePromise = Promise.resolve();
        if (scannerRunning) {
            scanFilePromise = html5Qr.stop().then(function() {
                scannerRunning = false;
            }).catch(function() {
                scannerRunning = false;
            });
        }

        scanFilePromise.then(function() {
            return ensureHtml5Qr();
        }).then(function() {
            return html5Qr.scanFile(file, true);
        }).then(function(decodedText) {
            log('Image scan success: ' + decodedText);
            setState('image decoded');
            onScanSuccess(decodedText);
        }).catch(function(err) {
            log('Image scan failed: ' + (err && err.message ? err.message : err));
            setState('no QR in image');
            showFeedback(false, 'No QR code found in image. Try a clearer photo.');
        });

        // Reset file input
        var fileInput = document.getElementById('qr-file-input');
        if (fileInput) fileInput.value = '';
    }

    /* ══════════════════════════════════════════════════════════
       3. MANUAL ID FALLBACK
       Plain text input, no QR library involved
    ══════════════════════════════════════════════════════════ */

    window.submitManualId = function() {
        var input = document.getElementById('manual-id-input');
        if (!input) return;
        var val = input.value.trim();
        if (!val) {
            showFeedback(false, 'Please enter a membership ID.');
            return;
        }
        log('Manual ID entered: ' + val);
        setState('processing manual ID...');
        sendToBackend(val, 'manual');
        input.value = '';
    };

    /* ══════════════════════════════════════════════════════════
       SHARED: scan success, backend communication, UI
    ══════════════════════════════════════════════════════════ */

    function onScanSuccess(text) {
        if (cooldown) return;
        var payload = cleanPayload(text);
        if (!payload) return;

        var now = Date.now();
        if (payload === lastPayload && (now - lastPayloadAt) < 2500) return;
        lastPayload = payload;
        lastPayloadAt = now;

        cooldown = true;
        setTimeout(function() { cooldown = false; }, 2000);
        log('QR detected: ' + payload);
        setState('scan success on attempt #' + scanAttemptCount);
        sendToBackend(payload, 'scan');
    }

    function sendToBackend(payload, source) {
        if (!payload) { log('Empty payload, ignored.'); return; }
        log('Sending to server (' + source + '): ' + payload);
        setState('sending to server...');

        var normalized = extractMembershipId(payload);
        var body = 'membership_id=' + encodeURIComponent(normalized || payload)
            + '&qr_data=' + encodeURIComponent(payload);

        fetch('scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            log((data.success ? '✓ ' : '✗ ') + (data.message || ''));
            setState(data.success ? 'check-in/out OK ✓' : 'scan rejected');
            showFeedback(data.success, data.message, data.action);
            if (data.success) {
                if (data.recent_logs && Array.isArray(data.recent_logs)) {
                    renderActivityRows(data.recent_logs);
                } else {
                    refreshActivityTable();
                }
            }
        })
        .catch(function(err) {
            log('Server error: ' + (err.message || err));
            setState('request failed');
            showFeedback(false, 'Server error. Please try again.');
        });
    }

    /* ── Feedback banner ───────────────────────────────────── */
    function showFeedback(ok, msg, action) {
        var el = document.getElementById('scan-feedback');
        if (!el) return;
        el.style.display = 'flex';
        if (ok) {
            var isIn = action === 'in';
            el.style.background = isIn ? 'rgba(34,197,94,.15)' : 'rgba(59,130,246,.15)';
            el.style.color      = isIn ? '#22c55e' : '#3b82f6';
            el.style.border     = '1px solid ' + (isIn ? 'rgba(34,197,94,.3)' : 'rgba(59,130,246,.3)');
            el.innerHTML = '<i class="fas fa-circle-check"></i> ' + msg;
        } else {
            el.style.background = 'rgba(239,68,68,.15)';
            el.style.color      = '#ef4444';
            el.style.border     = '1px solid rgba(239,68,68,.3)';
            el.innerHTML = '<i class="fas fa-circle-exclamation"></i> ' + msg;
        }
        setTimeout(function() { el.style.display = 'none'; }, 5000);
    }

    /* ── Activity table ────────────────────────────────────── */
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>'"]/g, function(c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' }[c];
        });
    }

    function ini(name) {
        return String(name || '').split(' ').map(function(w) { return (w[0] || ''); }).join('').substring(0, 2).toUpperCase();
    }

    function renderActivityRows(logs) {
        var tb = document.getElementById('activity-table');
        if (!tb) return;
        var bIn  = '<span class="badge badge-active">Active in Gym</span>';
        var bOut = '<span class="badge" style="background:#6c757d22;color:#9292a4;border:1px solid #9292a436;">Checked Out</span>';

        if (!logs || !logs.length) {
            tb.innerHTML = '<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="fas fa-clock"></i></div><h3>No check-ins today yet</h3></div></td></tr>';
            return;
        }

        tb.innerHTML = logs.map(function(l) {
            var n = esc(l.name), tI = esc(l.time_in || '\u2014'), tO = esc(l.time_out || '\u2014');
            var ph = l.photo_url
                ? '<img src="' + esc(l.photo_url) + '" style="width:100%;height:100%;object-fit:cover;">'
                : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#000;">' + ini(l.name) + '</div>';
            return '<tr>'
                + '<td><div style="display:flex;align-items:center;gap:10px;"><div style="width:32px;height:32px;border-radius:50%;overflow:hidden;background:var(--accent);flex-shrink:0;">' + ph + '</div><span class="td-name">' + n + '</span></div></td>'
                + '<td>' + tI + '</td><td>' + tO + '</td>'
                + '<td>' + (l.time_out ? bOut : bIn) + '</td></tr>';
        }).join('');
    }

    function refreshActivityTable() {
        fetch('today_logs.php?_=' + Date.now(), { method: 'GET', cache: 'no-store' })
            .then(function(r) { return r.text(); })
            .then(function(html) {
                var tb = document.getElementById('activity-table');
                if (tb) tb.innerHTML = html;
            })
            .catch(function() { log('Activity refresh failed.'); });
    }

    setScanButtons(false);

})();
</script>

<?php require_once '../includes/footer.php'; ?>
