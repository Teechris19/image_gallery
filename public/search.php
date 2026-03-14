<?php
/**
 * Search Page - Advanced search with filters
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
    'search' => get_input('search', ''),
    'category' => get_input('category', ''),
    'artist' => get_input('artist', ''),
    'sort' => get_input('sort', 'newest')
];

$images = get_filtered_images($filters);
$categories = get_all_categories();
$current_user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - <?= e(APP_NAME) ?></title>
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
        /* Filter Bar */
        .filter-bar {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filter-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }
        .filter-select {
            padding: 0.625rem 1rem;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            color: white;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 160px;
        }
        .filter-select:hover, .filter-select:focus {
            border-color: rgba(139, 92, 246, 0.5);
            outline: none;
        }
        .filter-search-wrapper {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .filter-search {
            width: 100%;
            padding: 0.625rem 1rem 0.625rem 2.75rem;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            color: white;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .filter-search:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .filter-search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }
        .apply-btn {
            padding: 0.625rem 1.5rem;
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
        }
        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }
        .active-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            background: rgba(139, 92, 246, 0.2);
            border: 1px solid rgba(139, 92, 246, 0.4);
            border-radius: 1rem;
            font-size: 0.8125rem;
            color: #c4b5fd;
        }
        .active-filter-tag button {
            background: none;
            border: none;
            color: #a78bfa;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }
        .active-filter-tag button:hover {
            color: #f87171;
        }
        .active-filter-tag svg {
            width: 0.875rem;
            height: 0.875rem;
        }
        /* Search Suggestions */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(30, 41, 59, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            margin-top: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            z-index: 50;
            display: none;
        }
        .search-suggestions.active {
            display: block;
        }
        .suggestion-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .suggestion-item:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        .suggestion-item svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #64748b;
        }
        .suggestion-item .suggestion-text {
            flex: 1;
        }
        .suggestion-item .suggestion-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
        }
        .suggestion-item .suggestion-title {
            font-size: 0.875rem;
            color: #e2e8f0;
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
                padding-bottom: 5rem;
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
                        <?php if ($current_user): ?>
                            <a href="upload.php" class="hidden sm:inline-flex group relative px-4 sm:px-5 py-2.5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl overflow-hidden transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                                <span class="relative z-10 flex items-center space-x-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>Upload</span>
                                </span>
                            </a>
                            <a href="profile.php" class="flex items-center space-x-2 px-3 sm:px-4 py-2 rounded-xl glass hover:bg-slate-800/50 transition-all">
                                <?php if ($current_user['avatar']): ?>
                                    <img src="<?= BASE_URL . 'uploads/profiles/' . e($current_user['avatar']) ?>" alt="<?= e($current_user['username']) ?>" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover">
                                <?php else: ?>
                                    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold text-xs sm:text-sm">
                                        <?= strtoupper(substr($current_user['username'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="font-medium text-sm sm:text-base hidden md:block"><?= e($current_user['username']) ?></span>
                            </a>
                        <?php else: ?>
                            <a href="index.php" class="px-4 sm:px-5 py-2.5 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all font-medium text-sm sm:text-base">
                                Login
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-8 main-content">
            <!-- Search Title -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Search</h1>
                <p class="text-slate-400">Discover artworks, artists, and categories</p>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="search.php" id="filter-form">
                    <div class="filter-row">
                        <!-- Search Input -->
                        <div class="filter-search-wrapper">
                            <label class="filter-label">Search</label>
                            <div style="position: relative;">
                                <svg class="filter-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.25rem; height: 1.25rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" name="search" id="search-input" class="filter-search" placeholder="Search artworks, artists, categories..." value="<?= e($filters['search']) ?>" autocomplete="off">
                                <div class="search-suggestions" id="search-suggestions"></div>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="filter-group">
                            <label class="filter-label" for="category-filter">Category</label>
                            <select name="category" id="category-filter" class="filter-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sort Filter -->
                        <div class="filter-group">
                            <label class="filter-label" for="sort-filter">Sort By</label>
                            <select name="sort" id="sort-filter" class="filter-select">
                                <option value="newest" <?= (!isset($filters['sort']) || $filters['sort'] === 'newest') ? 'selected' : '' ?>>Newest First</option>
                                <option value="oldest" <?= (isset($filters['sort']) && $filters['sort'] === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                                <option value="popular" <?= (isset($filters['sort']) && $filters['sort'] === 'popular') ? 'selected' : '' ?>>Most Popular</option>
                                <option value="views" <?= (isset($filters['sort']) && $filters['sort'] === 'views') ? 'selected' : '' ?>>Most Viewed</option>
                            </select>
                        </div>

                        <!-- Apply Button -->
                        <div class="filter-group" style="justify-content: flex-end;">
                            <button type="submit" class="apply-btn">
                                Apply Filters
                            </button>
                        </div>

                        <!-- Clear Filters -->
                        <?php if (!empty($filters['search']) || !empty($filters['category']) || isset($filters['sort'])): ?>
                            <div class="filter-group" style="justify-content: flex-end;">
                                <a href="search.php" class="px-4 py-2 rounded-xl bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white text-sm font-medium transition-all flex items-center gap-2" style="margin-top: auto;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Active Filters -->
                    <?php if (!empty($filters['search']) || !empty($filters['category']) || isset($filters['sort'])): ?>
                        <div class="active-filters">
                            <span class="filter-label" style="display: flex; align-items: center;">Active:</span>
                            <?php if (!empty($filters['search'])): ?>
                                <span class="active-filter-tag">
                                    Search: "<?= e($filters['search']) ?>"
                                    <a href="<?= buildFilterUrl(['search' => '']) ?>">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($filters['category'])): ?>
                                <span class="active-filter-tag">
                                    <?= e(get_category_by_slug($filters['category'])['name'] ?? $filters['category']) ?>
                                    <a href="<?= buildFilterUrl(['category' => '']) ?>">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Results Count -->
            <div class="mb-6 flex items-center justify-between">
                <p class="text-slate-400">
                    Showing <span class="text-white font-semibold"><?= count($images) ?></span> results
                </p>
            </div>

            <!-- Masonry Grid -->
            <?php if (empty($images)): ?>
                <div class="glass-card rounded-3xl p-16 text-center max-w-2xl mx-auto">
                    <div class="w-32 h-32 mx-auto mb-8 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center animate-float">
                        <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white mb-4">No results found</h2>
                    <p class="text-slate-400 mb-8">Try adjusting your search or filters to find what you're looking for.</p>
                    <a href="search.php" class="inline-flex items-center space-x-2 px-8 py-4 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-105">
                        <span>Clear All Filters</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="masonry-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="masonry-item glass-card rounded-2xl overflow-hidden cursor-pointer group" onclick="window.location.href='view.php?id=<?= $image['id'] ?>'">
                            <img src="<?= thumb_url($image['filename']) ?>" alt="<?= e($image['title']) ?>" loading="lazy" class="hover:scale-105 transition-transform duration-300">
                            <div class="p-4">
                                <h3 class="text-white font-semibold text-lg truncate"><?= e($image['title']) ?></h3>
                                <div class="flex items-center justify-between mt-2">
                                    <a href="artist.php?user=<?= e($image['artist_username']) ?>" class="text-slate-400 text-sm hover:text-purple-400 transition-colors flex items-center gap-1" onclick="event.stopPropagation()">
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

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="search.php" class="bottom-nav-item active">
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
        <a href="<?= $current_user ? 'profile.php' : 'javascript:void(0)' ?>" class="bottom-nav-item" onclick="<?= !$current_user ? "window.location.href='index.php'; return false;" : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Me</span>
        </a>
    </nav>

    <script>
        // Search suggestions
        const searchInput = document.getElementById('search-input');
        const suggestionsBox = document.getElementById('search-suggestions');
        let debounceTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsBox.classList.remove('active');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        function fetchSuggestions(query) {
            const formData = new FormData();
            formData.append('action', 'search_suggestions');
            formData.append('query', query);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    displaySuggestions(data.suggestions);
                } else {
                    suggestionsBox.classList.remove('active');
                }
            })
            .catch(error => {
                console.error('Suggestions error:', error);
                suggestionsBox.classList.remove('active');
            });
        }

        function displaySuggestions(suggestions) {
            let html = '';
            suggestions.forEach(item => {
                let icon = '';
                let label = '';
                if (item.type === 'artist') {
                    icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                    label = 'Artist';
                } else if (item.type === 'category') {
                    icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>';
                    label = 'Category';
                } else {
                    icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>';
                    label = 'Artwork';
                }
                html += `
                    <div class="suggestion-item" onclick="selectSuggestion('${item.type}', '${item.value}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg>
                        <div class="suggestion-text">
                            <div class="suggestion-label">${label}</div>
                            <div class="suggestion-title">${item.label}</div>
                        </div>
                    </div>
                `;
            });
            suggestionsBox.innerHTML = html;
            suggestionsBox.classList.add('active');
        }

        function selectSuggestion(type, value) {
            if (type === 'artist') {
                window.location.href = 'artist.php?user=' + encodeURIComponent(value);
            } else if (type === 'category') {
                window.location.href = 'search.php?category=' + encodeURIComponent(value);
            } else {
                searchInput.value = value;
                document.getElementById('filter-form').submit();
            }
        }

        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.filter-search-wrapper')) {
                suggestionsBox.classList.remove('active');
            }
        });

        // Helper function for building filter URLs
        function buildFilterUrl(params) {
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of Object.entries(params)) {
                if (value === '' || value === null) {
                    urlParams.delete(key);
                } else {
                    urlParams.set(key, value);
                }
            }
            const query = urlParams.toString();
            return 'search.php' + (query ? '?' + query : '');
        }
    </script>
</body>
</html>
