<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/navigation.php';

requireLogin();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = getCurrentUser();
    $currentUserId = $currentUser['id'];
    
    try {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['profile_picture']['name']);
            $extension = strtolower($fileInfo['extension'] ?? '');
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $filename = 'profile_' . $currentUserId . '_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                    // Update database with new profile picture
                    $pdo = getPDO();
                    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                    $stmt->execute([$filename, $currentUserId]);
                    $message = 'Profile picture updated successfully!';
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            } else {
                $error = 'Invalid file type. Please upload JPG, PNG, or GIF.';
            }
        }
        
        // Handle profile updates
        if (isset($_POST['full_name']) && !empty(trim($_POST['full_name']))) {
            $fullName = trim($_POST['full_name']);
            $pdo = getPDO();
            $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
            $stmt->execute([$fullName, $currentUserId]);
            
            if (empty($message)) {
                $message = 'Profile updated successfully!';
            }
        }
        
        // Handle logo upload (for super admin)
        if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK && getCurrentUserRole() === ROLE_SUPER_ADMIN) {
            $uploadDir = __DIR__ . '/../assets/images/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['app_logo']['name']);
            $extension = strtolower($fileInfo['extension'] ?? '');
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'svg'])) {
                $filename = 'logo.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $uploadPath)) {
                    $message = 'App logo updated successfully!';
                } else {
                    $error = 'Failed to upload logo.';
                }
            } else {
                $error = 'Invalid logo file type. Please upload JPG, PNG, or SVG.';
            }
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
    }
}

$currentUser = getCurrentUser();
$currentRole = getCurrentUserRole();
$roleLabel = $currentRole === ROLE_SUPER_ADMIN ? 'Super Admin' : 'Department Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profile - Internal Complaint Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/main.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/sidebar.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/darkmode.css')) ?>">
    <link rel="stylesheet" href="<?= e(baseUrl('/assets/css/saas2026.css')) ?>">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #0B2D72 0%, #1a3f8a 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            margin: 0 auto 1rem;
            display: block;
            object-fit: cover;
            background: #1e293b;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        .profile-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .profile-card:hover {
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
        
        .upload-area {
            border: 2px dashed rgba(59, 130, 246, 0.3);
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: rgba(59, 130, 246, 0.02);
        }
        
        .upload-area:hover {
            border-color: rgba(59, 130, 246, 0.6);
            background: rgba(59, 130, 246, 0.05);
        }
        
        .upload-area i {
            font-size: 3rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }
        
        .btn-modern {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-modern:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .form-control-modern {
            border-radius: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-item {
            background: rgba(59, 130, 246, 0.05);
            padding: 1rem;
            border-radius: 0.75rem;
            border-left: 4px solid #3b82f6;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #1e293b;
            font-weight: 500;
        }
        
        .alert-modern {
            border-radius: 0.75rem;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        
        .alert-success-modern {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1), rgba(22, 163, 74, 0.1));
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        
        .alert-error-modern {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php renderNavigationSidebar('profile'); ?>

    <main class="app-main">
        <div class="page-wrapper">
            <div class="profile-header glass-card fade-in-up">
                <?php 
                $profilePicture = $currentUser['profile_picture'] ?? '';
                if (!empty($profilePicture) && file_exists(__DIR__ . '/../uploads/profiles/' . $profilePicture)) {
                    echo '<img src="' . e(baseUrl('/uploads/profiles/' . $profilePicture)) . '" alt="Profile" class="profile-avatar">';
                } else {
                    echo '<div class="profile-avatar d-flex align-items-center justify-content-center"><i class="fas fa-user fa-3x"></i></div>';
                }
                ?>
                <h2 class="mb-1"><?= e((string) ($currentUser['full_name'] ?? 'User')) ?></h2>
                <p class="mb-0 opacity-75"><?= e($roleLabel) ?></p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-saas alert-success-saas mb-4 fade-in-up">
                    <i class="fas fa-check-circle me-2"></i><?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-saas alert-danger-saas mb-4 fade-in-up">
                    <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="card-saas fade-in-up" style="animation-delay: 0.1s;">
                        <div class="card-header-saas">
                            <h3 class="h5 mb-0 fw-semibold">
                                <i class="fas fa-user me-2" style="color: var(--primary-500);"></i>
                                Profile Information
                            </h3>
                        </div>
                        <div class="card-body-saas">
                            <form method="POST" enctype="multipart/form-data" class="form-saas">
                                <div class="form-group-saas">
                                    <label class="form-label-saas">Full Name</label>
                                    <input type="text" name="full_name" class="form-input-saas" 
                                           value="<?= e((string) ($currentUser['full_name'] ?? '')) ?>" required>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-item glass-card">
                                            <div class="info-label">Email</div>
                                            <div class="info-value"><?= e((string) ($currentUser['email'] ?? '')) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item glass-card">
                                            <div class="info-label">Department</div>
                                            <div class="info-value"><?= e((string) ($currentUser['department_name'] ?? 'Not Assigned')) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item glass-card">
                                            <div class="info-label">Role</div>
                                            <div class="info-value"><?= e($roleLabel) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item glass-card">
                                            <div class="info-label">Member Since</div>
                                            <div class="info-value"><?= e((string) ($currentUser['created_at'] ?? '')) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary-saas">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card-saas fade-in-up" style="animation-delay: 0.2s;">
                        <div class="card-header-saas">
                            <h3 class="h5 mb-0 fw-semibold">
                                <i class="fas fa-camera me-2" style="color: var(--accent-500);"></i>
                                Profile Picture
                            </h3>
                        </div>
                        <div class="card-body-saas">
                            <form method="POST" enctype="multipart/form-data" class="form-saas">
                                <div class="upload-area neuro-card">
                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3" style="color: var(--primary-500);"></i>
                                    <h5 class="mb-2">Upload Profile Picture</h5>
                                    <p class="text-muted mb-3">JPG, PNG or GIF (Max 5MB)</p>
                                    <input type="file" name="profile_picture" class="form-input-saas" accept="image/*" required>
                                </div>
                                <button type="submit" class="btn btn-primary-saas w-100 mt-3">
                                    <i class="fas fa-upload me-2"></i>Upload Picture
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($currentRole === ROLE_SUPER_ADMIN): ?>
                        <div class="card-saas fade-in-up" style="animation-delay: 0.3s;">
                            <div class="card-header-saas">
                                <h3 class="h5 mb-0 fw-semibold">
                                    <i class="fas fa-palette me-2" style="color: var(--warning-500);"></i>
                                    App Logo
                                </h3>
                            </div>
                            <div class="card-body-saas">
                                <form method="POST" enctype="multipart/form-data" class="form-saas">
                                    <div class="upload-area neuro-card">
                                        <i class="fas fa-image fa-3x mb-3" style="color: var(--warning-500);"></i>
                                        <h5 class="mb-2">Update App Logo</h5>
                                        <p class="text-muted mb-3">JPG, PNG or SVG (Max 2MB)</p>
                                        <input type="file" name="app_logo" class="form-input-saas" accept="image/*" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary-saas w-100 mt-3">
                                        <i class="fas fa-paint-brush me-2"></i>Update Logo
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="<?= e(baseUrl('/assets/js/sidebar.js')) ?>"></script>
</body>
</html>
