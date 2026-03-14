<?php
/**
 * User Profile Page
 * Displays user info, uploads, and liked artworks
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

initializeDatabase();
session_start();

// Check if logged in
if (!is_logged_in()) {
    redirect('index.php');
}

$current_user = get_logged_in_user();
$user_id = $current_user['id'];

// Get tab
$tab = get_input('tab', 'uploads');
if (!in_array($tab, ['uploads', 'likes'])) {
    $tab = 'uploads';
}

// Get data based on tab
if ($tab === 'uploads') {
    $images = get_user_images($user_id);
} else {
    $images = get_user_liked_images($user_id);
}

$stats = get_user_stats($user_id);
$categories = get_all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    animation: {
                        'twinkle': 'twinkle 3s ease-in-out infinite',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        twinkle: {
                            '0%, 100%': { opacity: '0.3', transform: 'scale(1)' },
                            '50%': { opacity: '1', transform: 'scale(1.2)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-15px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }
        .stars-container {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }
        .star {
            position: absolute;
            background: radial-gradient(circle, #fbbf24 0%, transparent 70%);
            border-radius: 50%;
            animation: twinkle 3s ease-in-out infinite;
        }
        .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            background: rgba(30, 41, 59, 0.95);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 10px 40px rgba(139, 92, 246, 0.15);
            transform: translateY(-4px);
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #9333ea 0%, #ec4899 50%, #f97316 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(147, 51, 234, 0.3);
        }
        .tab-btn:not(.active) {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }
        .tab-btn:not(.active):hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .masonry-grid {
            column-count: 4;
            column-gap: 1.5rem;
        }
        @media (max-width: 1280px) {
            .masonry-grid { column-count: 3; }
        }
        @media (max-width: 768px) {
            .masonry-grid { column-count: 3; }
        }
        @media (max-width: 480px) {
            .masonry-grid { column-count: 2; }
        }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1.5rem;
        }
        .masonry-item img {
            width: 100%;
            border-radius: 1rem;
            transition: transform 0.3s ease;
        }
        .masonry-item:hover img {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Stars Background -->
    <div class="stars-container">
        <?php for ($i = 0; $i < 50; $i++): ?>
            <?php
            $top = rand(0, 100);
            $left = rand(0, 100);
            $size = rand(2, 5);
            $delay = rand(0, 30) / 10;
            ?>
            <div class="star" style="width: <?= $size ?>px; height: <?= $size ?>px; top: <?= $top ?>%; left: <?= $left ?>%; animation-delay: <?= $delay ?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="relative z-10">
        <!-- Header -->
        <header class="glass sticky top-0 z-50 border-b border-slate-700/50 shadow-lg">
            <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-lg shadow-purple-500/25">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent"><?= e(APP_NAME) ?></h1>
                    </a>
                    <nav class="flex items-center space-x-4">
                        <a href="logout.php" class="px-4 py-2 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all">
                            Logout
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Profile Header -->
            <div class="glass-card rounded-3xl p-8 mb-12">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <!-- Avatar -->
                    <div class="relative group">
                        <?php if ($current_user['avatar']): ?>
                            <img src="<?= BASE_URL . 'uploads/profiles/' . e($current_user['avatar']) ?>" alt="<?= e($current_user['username']) ?>"
                                 class="w-32 h-32 rounded-full object-cover border-4 border-purple-500/30 shadow-xl shadow-purple-500/20">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center text-white font-bold text-5xl border-4 border-purple-500/30 shadow-xl shadow-purple-500/20">
                                <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Upload overlay -->
                        <label for="profile-image-upload" class="absolute inset-0 flex items-center justify-center bg-black/60 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </label>
                        <input type="file" id="profile-image-upload" accept="image/*" style="display:none;" onchange="uploadProfileImage(this)">
                        <p class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-xs text-slate-400 whitespace-nowrap">Click to change</p>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 text-center md:text-left">
                        <h1 class="text-4xl font-bold text-white mb-2"><?= e($current_user['username']) ?></h1>
                        <p class="text-slate-400 mb-3"><?= e($current_user['email']) ?></p>
                        
                        <?php if ($current_user['location']): ?>
                            <p class="text-slate-400 mb-3 flex items-center justify-center md:justify-start gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <?= e($current_user['location']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($current_user['bio']): ?>
                            <p class="text-slate-300 max-w-2xl"><?= e($current_user['bio']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Stats & Actions -->
                    <div class="flex flex-col items-center gap-4">
                        <a href="artist.php?user=<?= e($current_user['username']) ?>" class="px-8 py-3 rounded-xl font-semibold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white hover:shadow-xl hover:shadow-purple-500/35 transition-all">
                            View Public Profile
                        </a>

                        <!-- Stats -->
                        <div class="flex gap-8">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white"><?= $stats['artworks'] ?></p>
                                <p class="text-slate-400 text-sm">Artworks</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white"><?= $stats['followers'] ?></p>
                                <p class="text-slate-400 text-sm">Followers</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white"><?= $stats['following'] ?></p>
                                <p class="text-slate-400 text-sm">Following</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-4 mb-8 border-b border-slate-700 pb-4">
                <a href="profile.php?tab=uploads" class="tab-btn <?= $tab === 'uploads' ? 'active' : '' ?>" title="My Uploads">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </a>
                <a href="profile.php?tab=likes" class="tab-btn <?= $tab === 'likes' ? 'active' : '' ?>" title="Liked Artworks">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </a>
            </div>

            <!-- Content -->
            <?php if (empty($images)): ?>
                <div class="glass-card rounded-3xl p-16 text-center">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center">
                        <?php if ($tab === 'uploads'): ?>
                            <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        <?php else: ?>
                            <svg class="w-12 h-12 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">
                        <?php if ($tab === 'uploads'): ?>
                            No uploads yet
                        <?php else: ?>
                            No liked artworks
                        <?php endif; ?>
                    </h3>
                    <p class="text-slate-400 mb-6">
                        <?php if ($tab === 'uploads'): ?>
                            Start building your portfolio by uploading your first artwork.
                        <?php else: ?>
                            Artworks you like will appear here. Start exploring and liking!
                        <?php endif; ?>
                    </p>
                    <?php if ($tab === 'uploads'): ?>
                        <a href="upload.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Upload Artwork</span>
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                            <span>Browse Gallery</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="masonry-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="masonry-item glass-card rounded-2xl overflow-hidden cursor-pointer group"
                             onclick="window.location.href='view.php?id=<?= $image['id'] ?>'">
                            <img src="<?= thumb_url($image['filename']) ?>"
                                 alt="<?= e($image['title']) ?>"
                                 loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="search.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span>Search</span>
        </a>
        <a href="upload.php" class="bottom-nav-upload">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </a>
        <a href="downloads.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Downloads</span>
        </a>
        <a href="profile.php" class="bottom-nav-item active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Me</span>
        </a>
    </nav>

    <style>
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(148, 163, 184, 0.1);
            padding: 0.5rem 0.5rem;
            z-index: 100;
            display: none;
        }
        @media (max-width: 768px) {
            .bottom-nav {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            body {
                padding-bottom: 5.5rem;
            }
            main {
                padding-bottom: 6rem !important;
            }
        }
        @media (max-width: 480px) {
            body {
                padding-bottom: 6rem;
            }
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            color: #64748b;
            text-decoration: none;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            flex: 1;
        }
        .bottom-nav-item:hover {
            color: #a78bfa;
            background: rgba(139, 92, 246, 0.1);
        }
        .bottom-nav-item.active {
            color: #c4b5fd;
            background: rgba(139, 92, 246, 0.15);
        }
        .bottom-nav-item svg {
            width: 1.5rem;
            height: 1.5rem;
        }
        .bottom-nav-item span {
            font-size: 0.65rem;
            font-weight: 500;
        }
        .bottom-nav-upload {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
            margin: 0 0.5rem;
            flex: 0 0 auto;
        }
        .bottom-nav-upload svg {
            width: 1.75rem;
            height: 1.75rem;
            color: white;
        }
        .bottom-nav-upload:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.6);
        }
    </style>

    <script>
        function uploadProfileImage(input) {
            const file = input.files[0];
            if (!file) return;
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Profile image must be less than 5MB');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'upload_profile_image');
            formData.append('profile_image', file);
            
            // Show loading state
            const submitBtn = document.createElement('div');
            submitBtn.id = 'profile-upload-loading';
            submitBtn.className = 'fixed top-20 right-4 px-6 py-3 bg-purple-600 text-white rounded-xl shadow-lg z-50';
            submitBtn.innerHTML = 'Uploading...';
            document.body.appendChild(submitBtn);
            
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('profile-upload-loading')?.remove();
                
                if (data.success) {
                    // Reload page to show new avatar
                    window.location.reload();
                } else {
                    alert(data.error || 'Failed to upload profile image');
                }
            })
            .catch(error => {
                document.getElementById('profile-upload-loading')?.remove();
                console.error('Upload error:', error);
                alert('Failed to upload profile image. Please try again.');
            });
        }
    </script>
</body>
</html>
