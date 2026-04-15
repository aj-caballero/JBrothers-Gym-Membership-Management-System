<?php
// members/edit.php
$pageTitle = 'Edit Member';
require_once '../includes/header.php';

$id     = (int)($_GET['id'] ?? 0);
$stmt   = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) redirect('/members/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_action   = trim((string) ($_POST['form_action'] ?? 'update'));

    if ($form_action === 'revoke_membership') {
        try {
            $pdo->beginTransaction();

            $activeStmt = $pdo->prepare("SELECT id FROM memberships WHERE member_id = ? AND status = 'Active' FOR UPDATE");
            $activeStmt->execute([$id]);
            $activeMemberships = $activeStmt->fetchAll();

            if (empty($activeMemberships)) {
                throw new Exception('No active membership found to revoke.');
            }

            $cancelStmt = $pdo->prepare("UPDATE memberships SET status = 'Cancelled' WHERE member_id = ? AND status = 'Active'");
            $cancelStmt->execute([$id]);

            $memberStatusStmt = $pdo->prepare("UPDATE members SET status = 'Inactive' WHERE id = ?");
            $memberStatusStmt->execute([$id]);

            $pdo->commit();
            redirect('/members/edit.php?id=' . $id . '&revoked=1');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }

    if ($form_action !== 'revoke_membership') {
    $full_name     = trim($_POST['full_name']);
    $email         = trim($_POST['email']);
    $phone         = trim($_POST['phone']);
    $address       = trim($_POST['address']);
    $dob           = $_POST['date_of_birth'];
    $gender        = $_POST['gender'];
    $status        = $_POST['status'];
    $photo_path    = $member->photo_path; // keep existing by default

    $dupStmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
    $dupStmt->execute([$email, $id]);
    if ($dupStmt->fetch()) {
        $error = "Email already in use by another member.";
    } else {
        // Handle new photo
        $uploadDir = __DIR__ . '/../assets/uploads/photos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (!empty($_POST['photo_data'])) {
            $data = $_POST['photo_data'];
            if (preg_match('/^data:image\/\w+;base64,/', $data)) {
                $data      = substr($data, strpos($data, ',') + 1);
                $imageData = base64_decode($data);
                $savePath  = $uploadDir . $id . '.jpg';
                file_put_contents($savePath, $imageData);
                $photo_path = 'assets/uploads/photos/' . $id . '.jpg';
            }
        } elseif (!empty($_FILES['photo']['name'])) {
            $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
            if (in_array($_FILES['photo']['type'], $allowed) && $_FILES['photo']['size'] < 5 * 1024 * 1024) {
                $savePath = $uploadDir . $id . '.jpg';
                move_uploaded_file($_FILES['photo']['tmp_name'], $savePath);
                $photo_path = 'assets/uploads/photos/' . $id . '.jpg';
            } else {
                $error = "Invalid photo. Use JPG/PNG under 5MB.";
            }
        }

        if (!isset($error)) {
            $sql    = "UPDATE members SET full_name=?,email=?,phone=?,address=?,date_of_birth=?,gender=?,status=?,photo_path=? WHERE id=?";
            $params = [$full_name,$email,$phone,$address,$dob,$gender,$status,$photo_path,$id];
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                redirect('/members/index.php');
            } else {
                $error = "Failed to update member.";
            }
        }
    }
    }
}

$photoUrl = getMemberPhotoUrl($member->photo_path);
?>

<div class="card" style="max-width:860px;margin:0 auto;">
    <div class="card-header">
        <h3 class="card-title">Edit Member: <?= htmlspecialchars($member->full_name) ?></h3>
        <a href="index.php" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" style="margin:16px 22px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($_GET['revoked'])): ?>
        <div class="alert alert-success" style="margin:16px 22px 0;">Membership revoked successfully. Active memberships were cancelled.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="padding:22px;">
        <input type="hidden" name="form_action" value="update">

        <!-- Membership ID display -->
        <div class="form-group">
            <label>Membership ID</label>
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="text" class="form-control" value="<?= htmlspecialchars($member->membership_id ?? '—') ?>" readonly style="font-family:monospace;font-weight:600;color:var(--accent);max-width:220px;">
                <span style="font-size:12px;color:var(--text-muted);">Auto-assigned, cannot be changed</span>
            </div>
        </div>

        <!-- Photo Widget -->
        <div class="form-group">
            <label>Member Photo</label>
            <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
                <div style="position:relative;">
                    <div id="photo-preview" style="width:120px;height:120px;border-radius:var(--radius-lg);background:var(--bg-elevated);border:2px dashed var(--border-strong);overflow:hidden;flex-shrink:0;">
                        <?php if ($photoUrl): ?>
                            <img id="preview-img" src="<?= htmlspecialchars($photoUrl) ?>?v=<?= time() ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-user" style="font-size:40px;color:var(--text-muted);" id="photo-icon"></i>
                            </div>
                            <img id="preview-img" src="" style="display:none;width:100%;height:100%;object-fit:cover;">
                        <?php endif; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;justify-content:center;">
                    <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0;">
                        <i class="fas fa-upload"></i> Upload New
                        <input type="file" name="photo" id="photo-file" accept="image/*" style="display:none;" onchange="handleFileUpload(this)">
                    </label>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openCamera()">
                        <i class="fas fa-camera"></i> Use Camera
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
                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($member->full_name) ?>" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($member->email) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($member->phone) ?>">
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= $member->date_of_birth ?>">
            </div>
        </div>

<div class="form-row">
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="Male"   <?= $member->gender==='Male'   ? 'selected':'' ?>>Male</option>
                    <option value="Female" <?= $member->gender==='Female' ? 'selected':'' ?>>Female</option>
                    <option value="Other"  <?= $member->gender==='Other'  ? 'selected':'' ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="Active"    <?= $member->status==='Active'    ? 'selected':'' ?>>Active</option>
                    <option value="Inactive"  <?= $member->status==='Inactive'  ? 'selected':'' ?>>Inactive</option>
                    <option value="Expired"   <?= $member->status==='Expired'   ? 'selected':'' ?>>Expired</option>
                    <option value="Suspended" <?= $member->status==='Suspended' ? 'selected':'' ?>>Suspended</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($member->address) ?></textarea>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Member</button>
            <button type="submit"
                    class="btn btn-danger"
                    name="form_action"
                    value="revoke_membership"
                    onclick="return confirm('Revoke this member\'s active membership now? This will cancel active plans and set status to Inactive.');">
                <i class="fas fa-user-slash"></i> Revoke Membership
            </button>
        </div>
    </form>
</div>

<script>
let cameraStream = null;

function handleFileUpload(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = (e) => { showPreview(e.target.result); document.getElementById('photo-data').value = ''; };
    reader.readAsDataURL(input.files[0]);
}
function showPreview(src) {
    const img = document.getElementById('preview-img');
    img.src = src; img.style.display = 'block';
    const icon = document.getElementById('photo-icon');
    if (icon) icon.style.display = 'none';
}
function openCamera() {
    document.getElementById('camera-modal').style.display = 'flex';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
        .then(s => { cameraStream = s; document.getElementById('camera-feed').srcObject = s; })
        .catch(() => { alert('Camera unavailable.'); closeCamera(); });
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
