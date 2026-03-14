<?php
/**
 * Downloads Page - View all downloaded images
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

// Get downloaded images
$downloads = get_user_downloads($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Downloads - <?= e(APP_NAME) ?></title>
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
        /* Masonry Grid */
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
        /* Bottom Navigation */
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
            letter-spacing: 0.02em;
        }
        @media (max-width: 400px) {
            .bottom-nav-item span {
                font-size: 0.6rem;
            }
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
                <div class="flex items-center justify-between h-16 sm:h-20">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-lg shadow-purple-500/25">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent hidden sm:block"><?= e(APP_NAME) ?></h1>
                    </a>

                    <!-- Navigation -->
                    <nav class="flex items-center space-x-2 sm:space-x-4">
                        <a href="logout.php" class="px-3 sm:px-4 py-2 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Page Title -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">My Downloads</h1>
                <p class="text-slate-400">View all images you've downloaded</p>
            </div>

            <!-- Downloads Grid -->
            <?php if (empty($downloads)): ?>
                <div class="glass-card rounded-3xl p-16 text-center max-w-2xl mx-auto">
                    <div class="w-32 h-32 mx-auto mb-8 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center animate-float">
                        <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white mb-4">No downloads yet</h2>
                    <p class="text-slate-400 mb-8">Images you download will appear here. Start exploring and downloading!</p>
                    <a href="index.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                        <span>Browse Gallery</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-slate-400">
                        Showing <span class="text-white font-semibold"><?= count($downloads) ?></span> downloaded images
                    </p>
                </div>
                
                <div class="masonry-grid">
                    <?php foreach ($downloads as $download): ?>
                        <div class="masonry-item glass-card rounded-2xl overflow-hidden cursor-pointer group"
                             onclick="window.location.href='view.php?id=<?= $download['id'] ?>'">
                            <img src="<?= thumb_url($download['filename']) ?>"
                                 alt="<?= e($download['title']) ?>"
                                 loading="lazy"
                                 class="hover:scale-105 transition-transform duration-300">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Bottom Navigation -->
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
        <a href="downloads.php" class="bottom-nav-item active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Downloads</span>
        </a>
        <a href="profile.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Me</span>
        </a>
    </nav>
</body>
</html>
