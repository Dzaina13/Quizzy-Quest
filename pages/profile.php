<?php
session_start();
require_once '../assets/php/profile_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Quizzy-Quest</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body class="bg-gray-100">
<div class="profile-layout" >
    <!-- Sidebar -->
    
    <!-- Main Content -->
    <div class="profile-main-container">
        <!-- Header -->
        <header class="profile-header">
            <div class="profile-header-content">
                <div class="profile-header-left">
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="profile-header-menu-btn">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="profile-header-title">Profile</h2>
                </div>
                
                <div class="profile-header-right">
             
                    <button class="profile-header-notification">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="profile-header-notification-badge">3</span>
                    </button>
                    
                    <!-- Profile Dropdown -->
                    <div class="profile-header-dropdown" x-data="{ open: false }">
                        <button @click="open = !open" class="profile-header-dropdown-btn">
                            <img class="profile-header-dropdown-avatar" 
                                 src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['username']); ?>&background=6366f1&color=fff" 
                                 alt="Profile">
                            <i class="fas fa-chevron-down profile-header-dropdown-icon"></i>
                        </button>
                        
                        <div x-show="open" 
                             x-transition:enter="profile-transition-enter"
                             x-transition:enter-start="profile-transition-enter-start"
                             x-transition:enter-end="profile-transition-enter-end"
                             x-transition:leave="profile-transition-leave"
                             x-transition:leave-start="profile-transition-leave-start"
                             x-transition:leave-end="profile-transition-leave-end"
                             @click.away="open = false"
                             class="profile-header-dropdown-menu">
                             <a href="dashboard.php" class="profile-header-dropdown-item">Dashboard</a>
                            <a href="profile.php" class="profile-header-dropdown-item">Profile</a>
                            <a href="../logout.php" class="profile-header-dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content Area -->
        <main class="profile-content-area">
            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
            <div class="profile-message success">
                <div class="profile-message-content">
                    <i class="fas fa-check-circle profile-message-icon"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
            <div class="profile-message error">
                <div class="profile-message-content">
                    <i class="fas fa-exclamation-circle profile-message-icon"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="profile-max-width">
                <!-- Profile Header -->
                <div class="profile-card spaced">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">Profile Information</h3>
                    </div>
                    <div class="profile-card-content">
                        <div class="profile-info-container">
                            <div class="profile-info-avatar-wrapper">
                                <img class="profile-info-avatar" 
                                     src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['username']); ?>&background=6366f1&color=fff&size=96" 
                                     alt="Profile Picture">
                            </div>
                            <div class="profile-info-details">
                                <h4 class="profile-info-name"><?php echo htmlspecialchars($user_data['username']); ?></h4>
                                <p class="profile-info-email"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                <p class="text-sm text-gray-500">
                                    <span class="profile-info-role-badge">
                                        <?php echo ucfirst($user_data['role']); ?>
                                    </span>
                                </p>
                                <p class="profile-info-member-since">
                                    Member since <?php echo date('F Y', strtotime($user_data['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Form -->
                <div class="profile-card" x-data="{ editMode: false }">
                    <div class="profile-card-header with-action">
                        <h3 class="profile-card-title">Edit Profile</h3>
                        <button @click="editMode = !editMode" 
                                class="profile-btn primary">
                            <i class="fas fa-edit profile-btn-icon"></i>
                            <span x-text="editMode ? 'Cancel' : 'Edit Profile'"></span>
                        </button>
                    </div>
                    
                    <div class="profile-card-content">
                        <form method="POST" action="">
                            <div class="profile-form-grid">
                                <!-- Username -->
                                <div class="profile-form-group">
                                    <label for="username" class="profile-form-label">Username</label>
                                    <input type="text" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo htmlspecialchars($user_data['username']); ?>"
                                           :disabled="!editMode"
                                           required
                                           class="profile-form-input disabled">
                                </div>
                                
                                <!-- Email -->
                                <div class="profile-form-group">
                                    <label for="email" class="profile-form-label">Email</label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user_data['email']); ?>"
                                           :disabled="!editMode"
                                           required
                                           class="profile-form-input disabled">
                                </div>
                                
                                <!-- Role (Read-only) -->
                                <div class="profile-form-group">
                                    <label for="role" class="profile-form-label">Role</label>
                                    <input type="text" 
                                           id="role" 
                                           value="<?php echo ucfirst($user_data['role']); ?>"
                                           disabled
                                           class="profile-form-input readonly">
                                </div>
                                
                                <!-- Created At (Read-only) -->
                                <div class="profile-form-group">
                                    <label for="created_at" class="profile-form-label">Member Since</label>
                                    <input type="text" 
                                           id="created_at" 
                                           value="<?php echo date('F j, Y', strtotime($user_data['created_at'])); ?>"
                                           disabled
                                           class="profile-form-input readonly">
                                </div>
                            </div>
                            
                            <!-- Save Button -->
                            <div x-show="editMode" class="profile-form-actions">
                                <button type="button" 
                                        @click="editMode = false"
                                        class="profile-btn secondary">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="profile-btn primary">
                                    <i class="fas fa-save profile-btn-icon"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="profile-card spaced-top" x-data="{ showPasswordForm: false }">
                    <div class="profile-card-header with-action">
                        <div>
                            <h3 class="profile-card-title">Security</h3>
                            <p class="profile-card-subtitle">Manage your password and security settings</p>
                        </div>
                        <button @click="showPasswordForm = !showPasswordForm" 
                                class="profile-btn warning">
                            <i class="fas fa-key profile-btn-icon"></i>
                            <span x-text="showPasswordForm ? 'Cancel' : 'Change Password'"></span>
                        </button>
                    </div>
                    
                    <div x-show="showPasswordForm" 
                         x-transition:enter="profile-transition-password"
                         x-transition:enter-start="profile-transition-password-start"
                         x-transition:enter-end="profile-transition-password-end"
                         x-transition:leave="profile-transition-password-leave"
                         x-transition:leave-start="profile-transition-password-leave-start"
                         x-transition:leave-end="profile-transition-password-leave-end"
                         class="profile-card-content with-border">
                        <form method="POST" action="" x-data="{ 
                            newPassword: '', 
                            confirmPassword: '', 
                            passwordMatch: true,
                            checkPasswords() {
                                this.passwordMatch = this.newPassword === this.confirmPassword || this.confirmPassword === '';
                            }
                        }">
                            <div class="profile-form-password-container">
                                <div class="profile-form-group">
                                    <label for="current_password" class="profile-form-label">Current Password</label>
                                    <input type="password" 
                                           id="current_password" 
                                           name="current_password" 
                                           required
                                           class="profile-form-input warning">
                                </div>
                                
                                <div class="profile-form-group">
                                    <label for="new_password" class="profile-form-label">New Password</label>
                                    <input type="password" 
                                           id="new_password" 
                                           name="new_password" 
                                           x-model="newPassword"
                                           @input="checkPasswords()"
                                           minlength="6"
                                           required
                                           class="profile-form-input warning">
                                    <p class="profile-form-help">Minimum 6 characters</p>
                                </div>
                                
                                <div class="profile-form-group">
                                    <label for="confirm_password" class="profile-form-label">Confirm New Password</label>
                                    <input type="password" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           x-model="confirmPassword"
                                           @input="checkPasswords()"
                                           required
                                           :class="!passwordMatch ? 'profile-form-input error' : 'profile-form-input warning'">
                                    <p x-show="!passwordMatch" class="profile-form-error">Passwords do not match</p>
                                </div>
                            </div>
                            
                            <div class="profile-form-actions">
                                <button type="button" 
                                        @click="showPasswordForm = false"
                                        class="profile-btn secondary">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        name="change_password"
                                        :disabled="!passwordMatch"
                                        class="profile-btn warning disabled">
                                    <i class="fas fa-save profile-btn-icon"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Statistics -->
                <div class="profile-card spaced-top">
                    <div class="profile-card-header">
                        <h3 class="profile-card-title">Account Statistics</h3>
                    </div>
                    <div class="profile-card-content">
                        <div class="profile-stats-grid">
                            <div class="profile-stat-item">
                                <div class="profile-stat-icon-wrapper blue">
                                    <i class="fas fa-question-circle profile-stat-icon blue"></i>
                                </div>
                                <h4 class="profile-stat-value"><?php echo $quiz_count; ?></h4>
                                <p class="profile-stat-label">Quizzes Created</p>
                            </div>
                            
                            <div class="profile-stat-item">
                                <div class="profile-stat-icon-wrapper green">
                                    <i class="fas fa-users profile-stat-icon green"></i>
                                </div>
                                <h4 class="profile-stat-value"><?php echo $session_count; ?></h4>
                                <p class="profile-stat-label">Sessions Hosted</p>
                            </div>
                            
                            <div class="profile-stat-item">
                                <div class="profile-stat-icon-wrapper purple">
                                    <i class="fas fa-calendar profile-stat-icon purple"></i>
                                </div>
                                <h4 class="profile-stat-value"><?php echo $days_active; ?></h4>
                                <p class="profile-stat-label">Days Active</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>