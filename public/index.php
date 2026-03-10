<?php
/**
 * Image Gallery - Main Gallery Page
 * Features: Masonry grid, filtering, search, modal viewer
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

initializeDatabase();

session_start();

// Get filters from query string
$filters = [
    'category' => get_input('category', ''),
    'search' => get_input('search', ''),
    'artist' => get_input('artist', '')
];

$images = get_filtered_images($filters);
$categories = get_all_categories();
$current_user = get_logged_in_user();
$flashes = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?> - Discover Digital Art</title>
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
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
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
            column-count: 3;
            column-gap: 1.5rem;
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
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            overflow-y: auto;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            max-width: 1600px;
            width: 95%;
            max-height: 95vh;
            margin: 2rem auto;
        }
        @media (max-width: 1024px) {
            .modal-content {
                grid-template-columns: 1fr;
                max-height: none;
            }
        }
        .modal-image-container {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 1rem;
            background: rgba(0, 0, 0, 0.3);
        }
        .modal-image {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        .modal-image.zoomed {
            cursor: zoom-out;
        }
        .modal-sidebar {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            overflow-y: auto;
            max-height: 85vh;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 3rem;
            height: 3rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            z-index: 10;
        }
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }
        .zoom-controls {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.5rem;
            border-radius: 2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal-image-container:hover .zoom-controls {
            opacity: 1;
        }
        .zoom-btn {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .zoom-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .star-rating {
            display: flex;
            gap: 0.25rem;
        }
        .star-rating button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            transition: transform 0.2s ease;
        }
        .star-rating button:hover {
            transform: scale(1.2);
        }
        .star-rating svg {
            width: 1.5rem;
            height: 1.5rem;
        }
        .star-filled {
            color: #fbbf24;
        }
        .star-empty {
            color: #475569;
        }
        .like-btn.liked svg {
            fill: #ef4444;
            color: #ef4444;
        }
        .category-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            background: rgba(139, 92, 246, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.3);
            color: #a78bfa;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .category-pill:hover, .category-pill.active {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.5);
        }
        .category-pill.active {
            background: rgba(139, 92, 246, 0.3);
        }
        /* Auth Modal */
        .auth-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        .auth-modal.active {
            display: flex;
        }
        .auth-card {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(148, 163, 184, 0.1);
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
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="flex items-center space-x-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-lg shadow-purple-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h1 class="text-2xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent"><?= e(APP_NAME) ?></h1>
                        </a>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex-1 max-w-xl mx-8">
                        <div class="relative">
                            <input type="text" id="search-input" 
                                   placeholder="Search artworks, artists..." 
                                   class="w-full px-5 py-3 pl-12 rounded-2xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all"
                                   value="<?= e($filters['search']) ?>">
                            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span id="search-count" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs text-slate-400"></span>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex items-center space-x-4">
                        <?php if ($current_user): ?>
                            <a href="upload.php" class="group relative px-5 py-2.5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl overflow-hidden transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                                <span class="relative z-10 flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Upload</span>
                                </span>
                            </a>
                            <a href="profile.php" class="flex items-center space-x-2 px-4 py-2 rounded-xl glass hover:bg-slate-800/50 transition-all">
                                <?php if ($current_user['avatar']): ?>
                                    <img src="<?= e($current_user['avatar']) ?>" alt="<?= e($current_user['username']) ?>" class="w-8 h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold text-sm">
                                        <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="font-medium"><?= e($current_user['username']) ?></span>
                            </a>
                            <a href="logout.php" class="px-4 py-2 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </a>
                        <?php else: ?>
                            <button onclick="openAuthModal('login')" class="px-5 py-2.5 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all font-medium">
                                Login
                            </button>
                            <button onclick="openAuthModal('signup')" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35">
                                Sign Up
                            </button>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            <?php foreach ($flashes as $flash): ?>
                <div class="mb-6 animate-float">
                    <?php if ($flash['type'] === 'success'): ?>
                        <div class="glass rounded-2xl p-4 border border-green-500/30 bg-green-500/10">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-green-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <p class="text-green-300 font-medium"><?= e($flash['message']) ?></p>
                            </div>
                        </div>
                    <?php elseif ($flash['type'] === 'error'): ?>
                        <div class="glass rounded-2xl p-4 border border-red-500/30 bg-red-500/10">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-red-500/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </div>
                                <p class="text-red-300 font-medium"><?= e($flash['message']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Category Filters -->
            <div class="mb-8">
                <div class="flex items-center gap-3 flex-wrap">
                    <a href="index.php" class="category-pill <?= empty($filters['category']) ? 'active' : '' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        All
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?category=<?= e($cat['slug']) ?>" 
                           class="category-pill <?= $filters['category'] === $cat['slug'] ? 'active' : '' ?>">
                            <?= e($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($current_user): ?>
                        <button onclick="openCategoryModal()" class="category-pill">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Category
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Results Count -->
            <?php if (!empty($filters['search']) || !empty($filters['category']) || !empty($filters['artist'])): ?>
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-slate-400">
                        Showing <span class="text-white font-semibold"><?= count($images) ?></span> results
                        <?php if (!empty($filters['search'])): ?>
                            for "<span class="text-white font-semibold"><?= e($filters['search']) ?></span>"
                        <?php endif; ?>
                        <?php if (!empty($filters['category'])): ?>
                            in <span class="text-purple-400 font-semibold"><?= e(get_category_by_slug($filters['category'])['name'] ?? $filters['category']) ?></span>
                        <?php endif; ?>
                    </p>
                    <a href="index.php" class="text-purple-400 hover:text-purple-300 text-sm font-medium flex items-center gap-1">
                        Clear filters
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Masonry Grid -->
            <?php if (empty($images)): ?>
                <!-- Empty State -->
                <div class="glass-card rounded-3xl p-16 text-center max-w-2xl mx-auto">
                    <div class="w-32 h-32 mx-auto mb-8 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center animate-float">
                        <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white mb-4">
                        <?php if (!empty($filters['search']) || !empty($filters['category'])): ?>
                            No results found
                        <?php else: ?>
                            No artworks yet
                        <?php endif; ?>
                    </h2>
                    <p class="text-slate-400 mb-8">
                        <?php if (!empty($filters['search']) || !empty($filters['category'])): ?>
                            Try adjusting your search or filters to find what you're looking for.
                        <?php else: ?>
                            Start building your gallery by uploading your first artwork. Support for JPG, PNG, GIF, and WebP.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($filters['search']) || !empty($filters['category'])): ?>
                        <a href="index.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                            <span>Clear All Filters</span>
                        </a>
                    <?php else: ?>
                        <a href="upload.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Upload First Artwork</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="masonry-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="masonry-item glass-card rounded-2xl overflow-hidden cursor-pointer group" 
                             onclick="openModal(<?= $image['id'] ?>)">
                            <img src="<?= thumb_url($image['filename']) ?>"
                                 alt="<?= e($image['title']) ?>"
                                 loading="lazy"
                                 class="hover:scale-105 transition-transform duration-300">
                            <div class="p-4">
                                <h3 class="text-white font-semibold text-lg truncate"><?= e($image['title']) ?></h3>
                                <div class="flex items-center justify-between mt-2">
                                    <a href="artist.php?user=<?= e($image['artist_username']) ?>" 
                                       class="text-slate-400 text-sm hover:text-purple-400 transition-colors flex items-center gap-1"
                                       onclick="event.stopPropagation()">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <?= e($image['artist_name']) ?>
                                    </a>
                                    <?php if ($image['category_name']): ?>
                                        <span class="text-xs px-2 py-1 rounded-full bg-purple-500/20 text-purple-300">
                                            <?= e($image['category_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Image Modal -->
    <div class="modal" id="image-modal">
        <button class="modal-close" onclick="closeModal()">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="modal-content">
            <div class="modal-image-container" id="modal-image-container">
                <img src="" alt="" class="modal-image" id="modal-image">
                <div class="zoom-controls">
                    <button class="zoom-btn" onclick="zoomOut()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>
                    <button class="zoom-btn" onclick="zoomReset()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    </button>
                    <button class="zoom-btn" onclick="zoomIn()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="modal-sidebar">
                <div>
                    <h2 class="text-2xl font-bold text-white mb-2" id="modal-title">Artwork Title</h2>
                    <a href="#" class="text-purple-400 hover:text-purple-300 font-medium flex items-center gap-2" id="modal-artist-link">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span id="modal-artist">Artist Name</span>
                    </a>
                </div>

                <p class="text-slate-300" id="modal-description"></p>

                <div class="space-y-3 py-4 border-y border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Category
                        </span>
                        <span class="text-white" id="modal-category">-</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Uploaded
                        </span>
                        <span class="text-white" id="modal-date">-</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Views
                        </span>
                        <span class="text-white" id="modal-views">-</span>
                    </div>
                </div>

                <!-- Rating -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-slate-400">Rating</span>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold text-white" id="modal-avg-rating">0.0</span>
                            <span class="text-slate-400 text-sm">(<span id="modal-rating-count">0</span> ratings)</span>
                        </div>
                    </div>
                    <div class="star-rating" id="modal-star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button onclick="submitRating(<?= $i ?>)" data-star="<?= $i ?>">
                                <svg class="star-empty" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button class="flex-1 like-btn py-3 px-4 rounded-xl glass border border-slate-700 text-white font-semibold flex items-center justify-center gap-2 transition-all hover:border-red-500/50" 
                            id="modal-like-btn" onclick="toggleLike()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span id="modal-like-count">0</span>
                    </button>
                    <button class="py-3 px-4 rounded-xl glass border border-slate-700 text-white font-semibold flex items-center justify-center gap-2 transition-all hover:border-purple-500/50" 
                            onclick="shareArtwork()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                    </button>
                </div>

                <a href="" class="w-full py-3 px-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl text-center transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35" 
                   id="modal-view-btn">
                    View Details Page
                </a>
            </div>
        </div>
    </div>

    <!-- Auth Modal -->
    <div class="auth-modal" id="auth-modal">
        <div class="auth-card">
            <button onclick="closeAuthModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-xl shadow-purple-500/25">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2" id="auth-title">Welcome Back</h2>
                <p class="text-slate-400" id="auth-subtitle">Sign in to continue</p>
            </div>

            <form id="auth-form" onsubmit="handleAuth(event)">
                <input type="hidden" id="auth-mode" value="login">
                
                <div class="space-y-4">
                    <!-- Username field (login only) -->
                    <div id="login-username-field">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Username or Email</label>
                        <input type="text" id="auth-username" required 
                               class="w-full px-4 py-3 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Full Name field (signup only) -->
                    <div id="signup-fullname-field" style="display: none;">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                        <input type="text" id="auth-fullname" 
                               class="w-full px-4 py-3 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Email field (signup only) -->
                    <div id="signup-email-field" style="display: none;">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <input type="email" id="auth-email" 
                               class="w-full px-4 py-3 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                    </div>
                    
                    <!-- Password field -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="auth-password" required 
                                   class="w-full px-4 py-3 pr-12 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                            <button type="button" onclick="togglePasswordVisibility('auth-password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-icon-auth">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirm Password field (signup only) -->
                    <div id="signup-confirm-field" style="display: none;">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="auth-confirm-password" 
                                   class="w-full px-4 py-3 pr-12 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                            <button type="button" onclick="togglePasswordVisibility('auth-confirm-password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" id="eye-icon-confirm">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Forgot Password (login only) -->
                <div id="forgot-password-link" class="text-right">
                    <a href="#" class="text-sm text-purple-400 hover:text-purple-300">Forgot Password?</a>
                </div>

                <p class="text-red-400 text-sm mt-4" id="auth-error"></p>
                <p class="text-green-400 text-sm mt-4" id="auth-success"></p>

                <button type="submit" id="auth-submit-btn" class="w-full mt-6 py-3 px-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35">
                    <span id="auth-submit">Sign In</span>
                </button>
            </form>

            <p class="text-center text-slate-400 mt-6">
                <span id="auth-toggle-text">Don't have an account?</span>
                <button onclick="toggleAuthMode()" class="text-purple-400 hover:text-purple-300 font-medium ml-1">
                    <span id="auth-toggle-link">Sign Up</span>
                </button>
            </p>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="auth-modal" id="category-modal">
        <div class="auth-card">
            <button onclick="closeCategoryModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            
            <h2 class="text-2xl font-bold text-white mb-2">Add New Category</h2>
            <p class="text-slate-400 mb-6">Create a custom category for your artworks</p>

            <form onsubmit="handleAddCategory(event)">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Category Name</label>
                    <input type="text" id="category-name" required placeholder="e.g., Fantasy Art"
                           class="w-full px-4 py-3 rounded-xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all">
                </div>

                <p class="text-red-400 text-sm mt-4" id="category-error"></p>

                <button type="submit" class="w-full mt-6 py-3 px-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35">
                    Add Category
                </button>
            </form>
        </div>
    </div>

    <script src="js/gallery.js"></script>
</body>
</html>
