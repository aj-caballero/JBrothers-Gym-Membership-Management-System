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

/* QR Scanner */
#qr-preview-wrap {
    position: relative;
    width: 100%;
    border-radius: var(--radius-md);
    overflow: hidden;
    background: #000;
    aspect-ratio: 4/3;
    max-height: 340px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#qr-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
#qr-canvas { display: none; }
.qr-aim-box {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 200px; height: 200px;
    border: 3px solid rgba(255,255,255,0.7);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.35);
    pointer-events: none;
    transition: border-color 0.2s;
}
.qr-aim-box.detected {
    border-color: #22c55e;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.35), 0 0 0 3px #22c55e;
}
#qr-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 14px;
    padding: 40px 20px;
}
#qr-placeholder i { font-size: 40px; opacity: 0.4; }
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

                    <!-- Camera preview -->
                    <div id="qr-preview-wrap">
                        <div id="qr-placeholder">
                            <i class="fas fa-camera"></i>
                            <span>Camera not started</span>
                        </div>
                        <video id="qr-video" autoplay playsinline muted style="display:none;"></video>
                        <canvas id="qr-canvas"></canvas>
                        <div class="qr-aim-box" id="qr-aim-box" style="display:none;"></div>
                    </div>

                    <!-- Controls -->
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <button id="start-scan-btn" type="button" class="btn btn-primary btn-sm" onclick="startScanner()">
                            <i class="fas fa-play"></i> Start Camera
                        </button>
                        <button id="stop-scan-btn" type="button" class="btn btn-ghost btn-sm" onclick="stopScanner()" style="display:none;">
                            <i class="fas fa-stop"></i> Stop
                        </button>
                        <select id="camera-select" class="form-control" style="max-width:220px;flex:1;" onchange="switchCameraFromSelect()">
                            <option value="">Auto-select camera</option>
                        </select>
                    </div>

                    <!-- Image upload -->
                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                        <label class="btn btn-ghost btn-sm" style="cursor:pointer;">
                            <i class="fas fa-image"></i> Scan From Image
                            <input type="file" id="qr-image-input" accept="image/*" style="display:none;" onchange="handleQrImageUpload(event)">
                        </label>
                    </div>

                    <!-- Manual ID fallback -->
                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="text" id="scanner-membership-input" class="form-control" placeholder="Type membership ID (fallback)" style="max-width:280px;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="submitScannerMembershipId()">
                            <i class="fas fa-paper-plane"></i> Process ID
                        </button>
                    </div>

                    <p style="color:var(--text-muted);font-size:12px;margin-top:10px;">
                        <i class="fas fa-info-circle"></i>
                        Click <strong>Start Camera</strong>, allow access, then aim at a QR code.
                    </p>

                    <!-- Debug panel -->
                    <div id="scan-debug-panel" style="margin-top:12px;border:1px dashed var(--border-strong);border-radius:var(--radius-md);padding:10px 12px;background:var(--bg-input);">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                            <strong style="font-size:12px;color:var(--text-secondary);">Scanner Debug</strong>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleDebugLog()" style="padding:4px 8px;">Hide</button>
                        </div>
                        <div id="scan-debug-state" style="margin-top:6px;font-size:12px;color:var(--text-muted);">State: idle</div>
                        <pre id="scan-debug-log" style="margin-top:8px;max-height:140px;overflow:auto;font-size:11px;line-height:1.4;white-space:pre-wrap;color:var(--text-secondary);">Waiting for scanner activity...</pre>
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

<!-- jsQR: pure-JS QR decoder -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
(function() {
    'use strict';

    /* ── State ──────────────────────────────────────────────── */
    var running     = false;
    var cooldown    = false;
    var stream      = null;
    var animId      = null;
    var canvasCtx   = null;
    var cameras     = [];
    var chosenCamId = '';
    var debugOn     = true;

    /* ── Cached DOM refs ───────────────────────────────────── */
    var $video, $canvas, $placeholder, $aimBox;
    var $startBtn, $stopBtn, $camSelect;
    var $debugState, $debugLog, $feedback;

    function el(id) { return document.getElementById(id); }

    function grabRefs() {
        $video       = el('qr-video');
        $canvas      = el('qr-canvas');
        $placeholder = el('qr-placeholder');
        $aimBox      = el('qr-aim-box');
        $startBtn    = el('start-scan-btn');
        $stopBtn     = el('stop-scan-btn');
        $camSelect   = el('camera-select');
        $debugState  = el('scan-debug-state');
        $debugLog    = el('scan-debug-log');
        $feedback    = el('scan-feedback');
    }

    /* ── Debug helpers ─────────────────────────────────────── */
    function setState(t) {
        if ($debugState) $debugState.textContent = 'State: ' + t;
    }

    function log(t) {
        console.log('[QR]', t);
        if (!$debugLog) return;
        var stamp = new Date().toLocaleTimeString();
        var line = '[' + stamp + '] ' + t;
        if ($debugLog.textContent.indexOf('Waiting for') !== -1) {
            $debugLog.textContent = line;
        } else {
            $debugLog.textContent = line + '\n' + $debugLog.textContent;
        }
        var lines = $debugLog.textContent.split('\n');
        if (lines.length > 20) $debugLog.textContent = lines.slice(0, 20).join('\n');
    }

    /* ── Tab switching ─────────────────────────────────────── */
    window.switchTab = function(tab) {
        el('panel-manual').style.display  = tab === 'manual'  ? 'block' : 'none';
        el('panel-scanner').style.display = tab === 'scanner' ? 'block' : 'none';
        el('tab-manual').classList.toggle('active-tab',  tab === 'manual');
        el('tab-scanner').classList.toggle('active-tab', tab === 'scanner');
        if (tab === 'manual' && running) stopScanner();
        setState(tab === 'scanner' ? 'click Start Camera' : 'manual tab');
    };

    window.toggleDebugLog = function() {
        if (!$debugLog) return;
        debugOn = !debugOn;
        $debugLog.style.display = debugOn ? 'block' : 'none';
    };

    /* ── Camera enumeration ────────────────────────────────── */
    function loadCameras() {
        if (!navigator.mediaDevices) return;
        navigator.mediaDevices.enumerateDevices().then(function(devs) {
            cameras = devs.filter(function(d) { return d.kind === 'videoinput'; });
            if (!$camSelect) return;
            $camSelect.innerHTML = '<option value="">Auto-select camera</option>';
            cameras.forEach(function(c, i) {
                var opt = document.createElement('option');
                opt.value = c.deviceId || '';
                opt.textContent = c.label || ('Camera ' + (i + 1));
                $camSelect.appendChild(opt);
            });
            if (chosenCamId) $camSelect.value = chosenCamId;
            log('Found ' + cameras.length + ' camera(s).');
        }).catch(function() {});
    }

    window.switchCameraFromSelect = function() {
        if (!$camSelect) return;
        chosenCamId = $camSelect.value || '';
        if (running) { stopScanner(); setTimeout(startScanner, 300); }
    };

    /* ── START SCANNER ─────────────────────────────────────── */
    function startScanner() {
        if (running) return;
        grabRefs();

        log('Requesting camera access...');
        setState('requesting camera...');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            log('getUserMedia not available. Use HTTPS or localhost.');
            setState('not supported');
            showFeedback(false, 'Camera API not available. Use Chrome over HTTPS or localhost.');
            return;
        }

        var constraints = chosenCamId
            ? { video: { deviceId: { exact: chosenCamId } } }
            : { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } } };

        navigator.mediaDevices.getUserMedia(constraints)
            .catch(function() {
                log('Constraint failed, retrying { video: true }...');
                return navigator.mediaDevices.getUserMedia({ video: true });
            })
            .then(function(s) {
                stream = s;
                $placeholder.style.display = 'none';
                $video.style.display = 'block';
                $video.srcObject = s;

                $video.onloadedmetadata = function() {
                    $video.play();
                    running = true;
                    if ($aimBox)   $aimBox.style.display = 'block';
                    if ($startBtn) $startBtn.style.display = 'none';
                    if ($stopBtn)  $stopBtn.style.display  = 'inline-flex';

                    // Prepare canvas once
                    $canvas.width  = $video.videoWidth;
                    $canvas.height = $video.videoHeight;
                    canvasCtx = $canvas.getContext('2d', { willReadFrequently: true });

                    log('Camera live. Scanning for QR codes...');
                    setState('scanner running');

                    loadCameras();
                    tick();
                };
            })
            .catch(function(err) {
                var msg = err.message || String(err);
                if (err.name === 'NotAllowedError')  msg = 'Camera permission denied. Click the camera icon in the address bar.';
                if (err.name === 'NotFoundError')     msg = 'No camera found on this device.';
                if (err.name === 'NotReadableError')   msg = 'Camera in use by another app.';
                log('Camera error: ' + msg);
                setState('camera error');
                showFeedback(false, msg);
            });
    }
    window.startScanner = startScanner;

    /* ── DECODE LOOP ───────────────────────────────────────── */
    function tick() {
        if (!running) return;
        if (!$video || $video.readyState < 2) {
            animId = requestAnimationFrame(tick);
            return;
        }

        var w = $video.videoWidth, h = $video.videoHeight;
        if (!w || !h) { animId = requestAnimationFrame(tick); return; }

        if ($canvas.width !== w || $canvas.height !== h) {
            $canvas.width = w;
            $canvas.height = h;
            canvasCtx = $canvas.getContext('2d', { willReadFrequently: true });
        }

        canvasCtx.drawImage($video, 0, 0, w, h);

        if (typeof jsQR !== 'undefined') {
            try {
                var imgData = canvasCtx.getImageData(0, 0, w, h);
                var code = jsQR(imgData.data, w, h, { inversionAttempts: 'dontInvert' });
                if (code && code.data) {
                    log('QR detected: ' + code.data);
                    onScanSuccess(code.data);
                    if ($aimBox) {
                        $aimBox.classList.add('detected');
                        setTimeout(function() { $aimBox.classList.remove('detected'); }, 800);
                    }
                }
            } catch(e) { /* skip frame */ }
        }

        animId = requestAnimationFrame(tick);
    }

    /* ── STOP SCANNER ──────────────────────────────────────── */
    function stopScanner() {
        running = false;
        if (animId) { cancelAnimationFrame(animId); animId = null; }
        if (stream) { stream.getTracks().forEach(function(t) { t.stop(); }); stream = null; }
        canvasCtx = null;

        if ($video) { $video.srcObject = null; $video.style.display = 'none'; }
        if ($placeholder) $placeholder.style.display = 'flex';
        if ($aimBox) $aimBox.style.display = 'none';
        if ($startBtn) $startBtn.style.display = 'inline-flex';
        if ($stopBtn)  $stopBtn.style.display  = 'none';

        log('Scanner stopped.');
        setState('scanner stopped');
    }
    window.stopScanner = stopScanner;

    /* ── Scan success (with cooldown) ──────────────────────── */
    function onScanSuccess(text) {
        if (cooldown) return;
        cooldown = true;
        setTimeout(function() { cooldown = false; }, 3000);
        setState('QR detected, sending...');
        processScan(text.trim(), 'camera');
    }

    /* ── Send to backend ───────────────────────────────────── */
    function processScan(payload, source) {
        if (!payload) return;
        log('Sending ' + source + ': ' + payload);

        fetch('scan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'membership_id=' + encodeURIComponent(payload)
        })
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            log((data.success ? 'OK: ' : 'FAIL: ') + (data.message || ''));
            setState(data.success ? 'scan OK' : 'scan rejected');
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
            log('Request failed: ' + (err.message || err));
            setState('request failed');
            showFeedback(false, 'Server error. Try again.');
        });
    }

    /* ── Image upload scanner ──────────────────────────────── */
    window.handleQrImageUpload = function(event) {
        var file = event.target.files && event.target.files[0];
        if (!file) return;
        log('Scanning image: ' + file.name);
        setState('decoding image...');

        var img = new Image();
        var url = URL.createObjectURL(file);
        img.onload = function() {
            var c = document.createElement('canvas');
            c.width = img.width; c.height = img.height;
            var ctx = c.getContext('2d');
            ctx.drawImage(img, 0, 0);
            URL.revokeObjectURL(url);
            event.target.value = '';

            if (typeof jsQR === 'undefined') { log('jsQR not loaded.'); return; }
            var code = jsQR(ctx.getImageData(0, 0, img.width, img.height).data, img.width, img.height, { inversionAttempts: 'attemptBoth' });
            if (code && code.data) {
                log('Image decoded: ' + code.data);
                processScan(code.data, 'image');
            } else {
                log('No QR found in image.');
                showFeedback(false, 'No QR code found. Try a clearer photo.');
            }
        };
        img.onerror = function() {
            URL.revokeObjectURL(url);
            event.target.value = '';
            showFeedback(false, 'Could not load image.');
        };
        img.src = url;
    };

    /* ── Manual membership ID ──────────────────────────────── */
    window.submitScannerMembershipId = function() {
        var input = el('scanner-membership-input');
        if (!input) return;
        var v = input.value.trim();
        if (!v) { showFeedback(false, 'Enter a membership ID first.'); return; }
        log('Manual ID: ' + v);
        processScan(v, 'manual');
        input.value = '';
    };

    /* ── Feedback banner ───────────────────────────────────── */
    function showFeedback(ok, msg, action) {
        if (!$feedback) grabRefs();
        if (!$feedback) return;
        $feedback.style.display = 'flex';
        if (ok) {
            var isIn = action === 'in';
            $feedback.style.background = isIn ? 'rgba(34,197,94,.15)' : 'rgba(59,130,246,.15)';
            $feedback.style.color      = isIn ? '#22c55e' : '#3b82f6';
            $feedback.style.border     = '1px solid ' + (isIn ? 'rgba(34,197,94,.3)' : 'rgba(59,130,246,.3)');
            $feedback.innerHTML = '<i class="fas fa-circle-check"></i> ' + msg;
        } else {
            $feedback.style.background = 'rgba(239,68,68,.15)';
            $feedback.style.color      = '#ef4444';
            $feedback.style.border     = '1px solid rgba(239,68,68,.3)';
            $feedback.innerHTML = '<i class="fas fa-circle-exclamation"></i> ' + msg;
        }
        setTimeout(function() { $feedback.style.display = 'none'; }, 5000);
    }

    /* ── Activity table rendering ──────────────────────────── */
    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>'"]/g, function(c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' }[c];
        });
    }

    function ini(name) {
        return String(name || '').split(' ').map(function(w) { return (w[0] || ''); }).join('').substring(0, 2).toUpperCase();
    }

    function renderActivityRows(logs) {
        var tb = el('activity-table');
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
                var tb = el('activity-table');
                if (tb) tb.innerHTML = html;
            })
            .catch(function() { log('Activity refresh failed.'); });
    }

    /* ── Init ──────────────────────────────────────────────── */
    grabRefs();
})();
</script>

<?php require_once '../includes/footer.php'; ?>
