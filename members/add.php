<?php
// members/add.php
$pageTitle = 'Add Member';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $dob        = $_POST['date_of_birth'];
    $gender     = $_POST['gender'];
    $join_date  = $_POST['join_date'];
    $status     = 'Inactive';
    $photo_path = null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strtotime($join_date) > strtotime(date('Y-m-d'))) {
        $error = "Join date cannot be in the future.";
    } elseif ($dob && strtotime($dob) > strtotime(date('Y-m-d', strtotime('-16 years')))) {
        $error = "Member must be at least 16 years old.";
    } else {
        $dupStmt = $pdo->prepare("SELECT id FROM members WHERE email = ?");
        $dupStmt->execute([$email]);
        if ($dupStmt->fetch()) {
            $error = "Email already exists!";
        } else {
            // Handle photo upload (file or base64 camera capture)
            $uploadDir = __DIR__ . '/../assets/uploads/photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (!empty($_POST['photo_data'])) {
                // Camera capture: base64 PNG
                $data = $_POST['photo_data'];
                if (preg_match('/^data:image\/\w+;base64,/', $data)) {
                    $data = substr($data, strpos($data, ',') + 1);
                    $imageData = base64_decode($data);
                    // Temporary filename, will rename after INSERT
                    $tmpName = $uploadDir . 'tmp_' . time() . '.jpg';
                    file_put_contents($tmpName, $imageData);
                    $photo_path = 'assets/uploads/photos/' . basename($tmpName);
                }
            } elseif (!empty($_FILES['photo']['name'])) {
                // File upload
                $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
                if (in_array($_FILES['photo']['type'], $allowed) && $_FILES['photo']['size'] < 5 * 1024 * 1024) {
                    $tmpName = $uploadDir . 'tmp_' . time() . '.jpg';
                    move_uploaded_file($_FILES['photo']['tmp_name'], $tmpName);
                    $photo_path = 'assets/uploads/photos/' . basename($tmpName);
                } else {
                    $error = "Invalid photo. Use JPG/PNG under 5MB.";
                }
            }

            if (!isset($error)) {
                $membership_id = generateMembershipId($pdo);

                $sql  = "INSERT INTO members (full_name, email, phone, address, date_of_birth, gender, join_date, status, membership_id, photo_path, password)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$full_name, $email, $phone, $address, $dob, $gender, $join_date, $status, $membership_id, $photo_path])) {
                    $new_id = $pdo->lastInsertId();
                    // Rename temp photo to final name
                    if ($photo_path && strpos($photo_path, 'tmp_') !== false) {
                        $final = $uploadDir . $new_id . '.jpg';
                        rename($uploadDir . basename($photo_path), $final);
                        $pdo->prepare("UPDATE members SET photo_path = ? WHERE id = ?")->execute(['assets/uploads/photos/' . $new_id . '.jpg', $new_id]);
                    }
                    redirect('/members/index.php');
                } else {
                    $error = "Failed to add member.";
                }
            }
        }
    }
}
?>

<div class="card" style="max-width:860px;margin:0 auto;">
    <div class="card-header">
        <h3 class="card-title">New Member Registration</h3>
        <a href="index.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" style="margin:16px 22px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="padding:22px;">

        <!-- Photo Capture Widget -->
        <div class="form-group">
            <label>Member Photo</label>
            <div id="photo-widget" style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                <!-- Preview -->
                <div style="position:relative;">
                    <div id="photo-preview" style="width:120px;height:120px;border-radius:var(--radius-lg);background:var(--bg-elevated);border:2px dashed var(--border-strong);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                        <i class="fas fa-user" style="font-size:40px;color:var(--text-muted);" id="photo-icon"></i>
                        <img id="preview-img" src="" style="display:none;width:100%;height:100%;object-fit:cover;">
                    </div>
                </div>
                <!-- Controls -->
                <div style="display:flex;flex-direction:column;gap:10px;justify-content:center;">
                    <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0;">
                        <i class="fas fa-upload"></i> Upload File
                        <input type="file" name="photo" id="photo-file" accept="image/*" style="display:none;" onchange="handleFileUpload(this)">
                    </label>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openCamera()">
                        <i class="fas fa-camera"></i> Use Camera
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" id="clear-photo-btn" onclick="clearPhoto()" style="display:none;color:var(--danger);">
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
            </div>
            <input type="hidden" name="photo_data" id="photo-data">

            <!-- Camera Modal -->
            <div id="camera-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:1000;align-items:center;justify-content:center;flex-direction:column;gap:16px;">
                <video id="camera-feed" autoplay playsinline style="width:320px;height:320px;border-radius:var(--radius-lg);object-fit:cover;border:2px solid var(--accent);"></video>
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn btn-primary" onclick="capturePhoto()"><i class="fas fa-camera"></i> Capture</button>
                    <button type="button" class="btn btn-ghost" onclick="closeCamera()"><i class="fas fa-times"></i> Cancel</button>
                </div>
                <canvas id="snap-canvas" style="display:none;"></canvas>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" max="<?= date('Y-m-d', strtotime('-16 years')) ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Join Date *</label>
                <input type="date" name="join_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label>Initial Status</label>
            <input type="text" name="status" class="form-control" value="Inactive" readonly>
            <div class="form-hint">Membership ID will be auto-generated. Status starts as Inactive until a plan is added.</div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Member</button>
    </form>
</div>

<script>
let cameraStream = null;

function handleFileUpload(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        showPreview(e.target.result);
        document.getElementById('photo-data').value = ''; // clear camera data, use file
    };
    reader.readAsDataURL(input.files[0]);
}

function showPreview(src) {
    const img = document.getElementById('preview-img');
    img.src = src;
    img.style.display = 'block';
    document.getElementById('photo-icon').style.display = 'none';
    document.getElementById('clear-photo-btn').style.display = 'block';
}

function clearPhoto() {
    document.getElementById('preview-img').style.display = 'none';
    document.getElementById('preview-img').src = '';
    document.getElementById('photo-icon').style.display = 'block';
    document.getElementById('clear-photo-btn').style.display = 'none';
    document.getElementById('photo-data').value = '';
    document.getElementById('photo-file').value = '';
}

function openCamera() {
    const modal = document.getElementById('camera-modal');
    modal.style.display = 'flex';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(stream => {
            cameraStream = stream;
            document.getElementById('camera-feed').srcObject = stream;
        })
        .catch(() => {
            alert('Camera access denied or unavailable.');
            closeCamera();
        });
}

function closeCamera() {
    if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
    document.getElementById('camera-modal').style.display = 'none';
}

function capturePhoto() {
    const video = document.getElementById('camera-feed');
    const canvas = document.getElementById('snap-canvas');
    canvas.width = 320; canvas.height = 320;
    canvas.getContext('2d').drawImage(video, 0, 0, 320, 320);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    document.getElementById('photo-data').value = dataUrl;
    document.getElementById('photo-file').value = '';
    showPreview(dataUrl);
    closeCamera();
}
</script>

<?php require_once '../includes/footer.php'; ?>
