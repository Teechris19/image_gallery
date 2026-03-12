<?php
/**
 * Artist Profile Page
 * Displays artist info, stats, and their artworks
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

initializeDatabase();
session_start();

$username = get_input('user');
if (!$username) {
    redirect('index.php');
}

$artist = get_user_by_username($username);
if (!$artist) {
    show_error('Artist not found', 404);
}

$images = get_artist_images($username);
$stats = get_user_stats($artist['id']);
$current_user = get_logged_in_user();
$is_following = $current_user ? is_following($current_user['id'], $artist['id']) : false;
$is_own_profile = $current_user && $current_user['id'] === $artist['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($artist['username']) ?> - Artist Profile | <?= e(APP_NAME) ?></title>
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
        .masonry-grid {
            column-count: 4;
            column-gap: 1.5rem;
        }
        @media (max-width: 1280px) {
            .masonry-grid { column-count: 3; }
        }
        @media (max-width: 1024px) {
            .masonry-grid { column-count: 2; }
        }
        @media (max-width: 640px) {
            .masonry-grid { column-count: 1; }
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
                        <?php if ($current_user): ?>
                            <a href="upload.php" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35">
                                Upload
                            </a>
                            <a href="profile.php" class="px-4 py-2 rounded-xl glass hover:bg-slate-800/50 transition-all">
                                My Profile
                            </a>
                        <?php else: ?>
                            <a href="index.php" class="px-5 py-2.5 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all">
                                Login
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Artist Header -->
            <div class="glass-card rounded-3xl p-8 mb-12">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <!-- Avatar -->
                    <div class="relative">
                        <?php if ($artist['avatar']): ?>
                            <img src="<?= BASE_URL . 'uploads/profiles/' . e($artist['avatar']) ?>" alt="<?= e($artist['username']) ?>"
                                 class="w-32 h-32 rounded-full object-cover border-4 border-purple-500/30 shadow-xl shadow-purple-500/20">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center text-white font-bold text-5xl border-4 border-purple-500/30 shadow-xl shadow-purple-500/20">
                                <?= strtoupper(substr($artist['username'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-col md:flex-row items-center gap-4 mb-4">
                            <h1 class="text-4xl font-bold text-white"><?= e($artist['username']) ?></h1>
                            <?php if ($artist['portfolio_url']): ?>
                                <a href="<?= e($artist['portfolio_url']) ?>" target="_blank" 
                                   class="flex items-center gap-2 px-4 py-2 rounded-xl glass text-purple-400 hover:text-purple-300 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    Portfolio
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($artist['location']): ?>
                            <p class="text-slate-400 mb-3 flex items-center justify-center md:justify-start gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <?= e($artist['location']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($artist['bio']): ?>
                            <p class="text-slate-300 max-w-2xl"><?= e($artist['bio']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Follow Button & Stats -->
                    <div class="flex flex-col items-center gap-4">
                        <?php if (!$is_own_profile): ?>
                            <button id="follow-btn" onclick="toggleFollow()" 
                                    class="px-8 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg <?= $is_following ? 'bg-slate-700 text-white hover:bg-slate-600' : 'bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white hover:shadow-xl hover:shadow-purple-500/35' ?>">
                                <span id="follow-text"><?= $is_following ? 'Following' : 'Follow' ?></span>
                            </button>
                        <?php else: ?>
                            <a href="profile.php" class="px-8 py-3 rounded-xl font-semibold bg-slate-700 text-white hover:bg-slate-600 transition-all">
                                Edit Profile
                            </a>
                        <?php endif; ?>

                        <!-- Stats -->
                        <div class="flex gap-8">
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white" id="stat-artworks"><?= $stats['artworks'] ?></p>
                                <p class="text-slate-400 text-sm">Artworks</p>
                            </div>
                            <div class="text-center">
                                <p class="text-3xl font-bold text-white" id="stat-followers"><?= $stats['followers'] ?></p>
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

            <!-- Artist's Artworks -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">Artworks by <?= e($artist['username']) ?></h2>
                
                <?php if (empty($images)): ?>
                    <div class="glass-card rounded-3xl p-16 text-center">
                        <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center">
                            <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-400">No artworks yet</p>
                    </div>
                <?php else: ?>
                    <div class="masonry-grid">
                        <?php foreach ($images as $image): ?>
                            <div class="masonry-item glass-card rounded-2xl overflow-hidden cursor-pointer group" 
                                 onclick="window.location.href='view.php?id=<?= $image['id'] ?>'">
                                <img src="<?= thumb_url($image['filename']) ?>"
                                     alt="<?= e($image['title']) ?>"
                                     loading="lazy">
                                <div class="p-4">
                                    <h3 class="text-white font-semibold text-lg truncate"><?= e($image['title']) ?></h3>
                                    <div class="flex items-center justify-between mt-2">
                                        <?php if ($image['category_name']): ?>
                                            <span class="text-xs px-2 py-1 rounded-full bg-purple-500/20 text-purple-300">
                                                <?= e($image['category_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-slate-400 text-sm"><?= date('M j, Y', strtotime($image['uploaded_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        const artistId = <?= $artist['id'] ?>;
        const isFollowing = <?= $is_following ? 'true' : 'false' ?>;

        function toggleFollow() {
            const formData = new FormData();
            formData.append('action', isFollowing ? 'unfollow' : 'follow');
            formData.append('user_id', artistId);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const followBtn = document.getElementById('follow-btn');
                    const followText = document.getElementById('follow-text');
                    const followersStat = document.getElementById('stat-followers');
                    
                    if (data.following) {
                        followBtn.classList.remove('bg-slate-700');
                        followBtn.classList.add('bg-gradient-to-r', 'from-purple-600', 'via-pink-600', 'to-orange-500');
                        followText.textContent = 'Following';
                    } else {
                        followBtn.classList.remove('bg-gradient-to-r', 'from-purple-600', 'via-pink-600', 'to-orange-500');
                        followBtn.classList.add('bg-slate-700');
                        followText.textContent = 'Follow';
                    }
                    followersStat.textContent = data.followers;
                } else if (data.requires_auth) {
                    window.location.href = 'index.php#login';
                }
            })
            .catch(error => console.error('Follow error:', error));
        }
    </script>
</body>
</html>
