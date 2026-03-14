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
    'artist' => get_input('artist', ''),
    'sort' => get_input('sort', 'newest')
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
        /* Masonry Grid - 4 columns */
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
        /* Card content spacing */
        .masonry-item .p-4 {
            padding: 1rem;
        }
        @media (max-width: 480px) {
            .masonry-item .p-4 {
                padding: 0.75rem;
            }
        }
        .masonry-item h3 {
            font-size: 1.125rem;
            line-height: 1.5rem;
        }
        @media (max-width: 768px) {
            .masonry-item h3 {
                font-size: 1rem;
                line-height: 1.4rem;
            }
        }
        @media (max-width: 480px) {
            .masonry-item h3 {
                font-size: 0.9375rem;
                line-height: 1.3rem;
            }
        }
        .masonry-item .text-slate-400 {
            font-size: 0.875rem;
        }
        @media (max-width: 480px) {
            .masonry-item .text-slate-400 {
                font-size: 0.8125rem;
            }
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
        @media (max-width: 768px) {
            .modal-content {
                width: 100%;
                max-height: 100vh;
                margin: 0;
                border-radius: 0;
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
        @media (max-width: 768px) {
            .modal-image-container {
                border-radius: 0;
                max-height: 60vh;
            }
        }
        @media (max-width: 480px) {
            .modal-image-container {
                max-height: 50vh;
            }
        }
        .modal-image {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }
        @media (max-width: 768px) {
            .modal-image {
                max-height: 60vh;
            }
        }
        @media (max-width: 480px) {
            .modal-image {
                max-height: 50vh;
            }
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
        @media (max-width: 1024px) {
            .modal-sidebar {
                max-height: none;
                border-radius: 1.5rem 1.5rem 0 0;
            }
        }
        @media (max-width: 768px) {
            .modal-sidebar {
                padding: 1.5rem;
                gap: 1.25rem;
                border-radius: 0;
                max-height: none;
            }
        }
        @media (max-width: 480px) {
            .modal-sidebar {
                padding: 1.25rem;
                gap: 1rem;
            }
        }
        .modal-sidebar h2 {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        @media (max-width: 768px) {
            .modal-sidebar h2 {
                font-size: 1.25rem;
                line-height: 1.75rem;
            }
        }
        @media (max-width: 480px) {
            .modal-sidebar h2 {
                font-size: 1.125rem;
                line-height: 1.5rem;
            }
        }
        .modal-sidebar p, .modal-sidebar span {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        @media (max-width: 480px) {
            .modal-sidebar p, .modal-sidebar span {
                font-size: 0.8125rem;
            }
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
            text-decoration: none;
        }
        .category-pill:hover, .category-pill.active {
            background: rgba(139, 92, 246, 0.2);
            border-color: rgba(139, 92, 246, 0.5);
        }
        .category-pill.active {
            background: rgba(139, 92, 246, 0.3);
            color: #c4b5fd;
        }
        /* Header */
        header.glass {
            padding: 0 1rem;
        }
        @media (max-width: 768px) {
            header.glass {
                padding: 0 0.75rem;
            }
            header.glass .h-16 {
                height: 3.5rem;
            }
        }
        header h1 {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        @media (max-width: 768px) {
            header h1 {
                font-size: 1rem;
                line-height: 1.5rem;
            }
        }
        @media (max-width: 480px) {
            header h1 {
                font-size: 0.9375rem;
                line-height: 1.4rem;
            }
        }
        header .font-medium {
            font-size: 0.875rem;
        }
        @media (max-width: 480px) {
            header .font-medium {
                font-size: 0.8125rem;
            }
        }
        /* Main content padding */
        main {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        @media (max-width: 768px) {
            main {
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
        }
        /* Filter bar spacing */
        .filter-bar {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        @media (max-width: 768px) {
            .filter-bar {
                padding: 1rem;
                margin-bottom: 1rem;
                border-radius: 1rem;
            }
        }
        @media (max-width: 480px) {
            .filter-bar {
                padding: 0.875rem;
            }
        }
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        @media (max-width: 768px) {
            .filter-row {
                gap: 0.75rem;
            }
        }
        @media (max-width: 480px) {
            .filter-row {
                gap: 0.5rem;
            }
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        @media (max-width: 480px) {
            .filter-group {
                gap: 0.375rem;
            }
        }
        .filter-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            font-weight: 600;
        }
        @media (max-width: 480px) {
            .filter-label {
                font-size: 0.6875rem;
            }
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
        @media (max-width: 768px) {
            .filter-select {
                font-size: 0.8125rem;
                padding: 0.5rem 0.875rem;
                min-width: 140px;
            }
        }
        @media (max-width: 480px) {
            .filter-select {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem;
                min-width: 120px;
            }
        }
        .filter-search-wrapper {
            flex: 1;
            min-width: 200px;
        }
        @media (max-width: 768px) {
            .filter-search-wrapper {
                min-width: 150px;
                flex: 1 1 100%;
            }
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
        @media (max-width: 768px) {
            .filter-search {
                font-size: 0.8125rem;
                padding: 0.5rem 0.875rem 0.5rem 2.5rem;
            }
        }
        @media (max-width: 480px) {
            .filter-search {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            }
        }
        .filter-search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            pointer-events: none;
        }
        @media (max-width: 480px) {
            .filter-search-icon {
                left: 0.625rem;
                width: 1rem;
                height: 1rem;
            }
        }
        .filter-select:hover, .filter-select:focus {
            border-color: rgba(139, 92, 246, 0.5);
            outline: none;
        }
        .filter-search:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        /* Active filters tags */
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
        @media (max-width: 480px) {
            .active-filter-tag {
                font-size: 0.75rem;
                padding: 0.25rem 0.625rem;
                gap: 0.25rem;
            }
        }
        /* Apply button */
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
        @media (max-width: 768px) {
            .apply-btn {
                padding: 0.5rem 1.25rem;
                font-size: 0.8125rem;
            }
        }
        @media (max-width: 480px) {
            .apply-btn {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }
        }
        /* Results count text */
        .text-slate-400 {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .text-slate-400 {
                font-size: 0.8125rem;
            }
        }
        @media (max-width: 480px) {
            .text-slate-400 {
                font-size: 0.75rem;
            }
        }
        .active-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        @media (max-width: 480px) {
            .active-filters {
                gap: 0.375rem;
                margin-top: 0.75rem;
            }
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
            .main-content {
                padding-bottom: 5rem;
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
                <div class="flex items-center justify-between h-16 sm:h-20">
                    <a href="index.php" class="flex items-center space-x-3">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-lg shadow-purple-500/25">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-lg sm:text-2xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent hidden sm:block"><?= e(APP_NAME) ?></h1>
                    </a>

                    <!-- Search Bar in Navbar - Hidden on Mobile -->
                    <div class="hidden sm:block flex-1 max-w-xl mx-4 sm:mx-8">
                        <div class="relative">
                            <input type="text" id="navbar-search-input"
                                   placeholder="Search artworks, artists..."
                                   class="w-full px-4 sm:px-5 py-2.5 sm:py-3 pl-10 sm:pl-12 rounded-xl sm:rounded-2xl bg-slate-800/50 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:border-transparent transition-all text-sm sm:text-base"
                                   value="<?= e($filters['search']) ?>">
                            <svg class="absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

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
                            <a href="logout.php" class="px-3 sm:px-4 py-2 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </a>
                        <?php else: ?>
                            <button onclick="openAuthModal('login')" class="px-4 sm:px-5 py-2.5 rounded-xl glass text-slate-300 hover:text-white hover:bg-slate-800/50 transition-all font-medium text-sm sm:text-base">
                                Login
                            </button>
                            <button onclick="openAuthModal('signup')" class="hidden sm:inline-flex px-4 sm:px-5 py-2.5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35">
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

            <!-- Enhanced Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="index.php" id="filter-form">
                    <div class="filter-row">
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

                        <!-- Clear Filters Button -->
                        <?php if (!empty($filters['search']) || !empty($filters['category']) || isset($filters['sort'])): ?>
                            <div class="filter-group" style="justify-content: flex-end;">
                                <a href="index.php" class="px-4 py-2 rounded-xl bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white text-sm font-medium transition-all flex items-center gap-2" style="margin-top: auto;">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Active Filters Tags -->
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
                            <?php if (isset($filters['sort']) && $filters['sort'] !== 'newest'): ?>
                                <span class="active-filter-tag">
                                    Sort: <?= ucfirst($filters['sort']) ?>
                                    <a href="<?= buildFilterUrl(['sort' => '']) ?>">
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
                    Showing <span class="text-white font-semibold"><?= count($images) ?></span> artworks
                </p>
            </div>

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
    
    <!-- Auto-reload on changes (when online) -->
    <script>
        // Check for changes every 30 seconds using last-modified
        let lastModified = document.lastModified;
        let reloadTimeout = null;
        
        function checkForChanges() {
            if (!navigator.onLine) return;
            
            fetch(window.location.href, { method: 'HEAD' })
                .then(response => {
                    const newModified = response.headers.get('Last-Modified');
                    if (newModified && newModified !== lastModified) {
                        // Show reload notification
                        showReloadNotification();
                    }
                })
                .catch(() => {});
        }
        
        function showReloadNotification() {
            // Prevent multiple notifications
            if (document.getElementById('reload-notification')) return;
            
            const notification = document.createElement('div');
            notification.id = 'reload-notification';
            notification.className = 'fixed bottom-4 right-4 glass rounded-2xl p-4 border border-purple-500/30 shadow-xl z-50 animate-float';
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                    <span class="text-white font-medium">Updates available</span>
                    <button onclick="window.location.reload()" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all">
                        Reload
                    </button>
                    <button onclick="this.closest('#reload-notification').remove()" class="text-slate-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto-reload after 10 seconds if user doesn't interact
            reloadTimeout = setTimeout(() => {
                window.location.reload();
            }, 10000);
        }
        
        // Check for changes every 30 seconds
        setInterval(checkForChanges, 30000);
        
        // Also check when coming back to tab
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && navigator.onLine) {
                checkForChanges();
            }
        });
        
        // Navbar search functionality - submit on Enter
        const navbarSearch = document.getElementById('navbar-search-input');
        if (navbarSearch) {
            navbarSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const filterForm = document.getElementById('filter-form');
                    // Update the search input in the filter form
                    let filterSearch = filterForm.querySelector('input[name="search"]');
                    if (!filterSearch) {
                        // Create hidden input for search
                        filterSearch = document.createElement('input');
                        filterSearch.type = 'hidden';
                        filterSearch.name = 'search';
                        filterForm.appendChild(filterSearch);
                    }
                    filterSearch.value = this.value;
                    filterForm.submit();
                }
            });
        }
    </script>
    
    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item <?= empty($filters) || $filters === ['sort' => 'newest'] ? 'active' : '' ?>">
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
        <a href="<?= $current_user ? 'downloads.php' : 'javascript:void(0)' ?>" class="bottom-nav-item <?= strpos($_SERVER['PHP_SELF'], 'downloads.php') !== false ? 'active' : '' ?>" onclick="<?= !$current_user ? "openAuthModal('login'); return false;" : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Downloads</span>
        </a>
        <a href="<?= $current_user ? 'profile.php' : 'javascript:void(0)' ?>" class="bottom-nav-item <?= strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : '' ?>" onclick="<?= !$current_user ? "openAuthModal('login'); return false;" : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>Me</span>
        </a>
    </nav>
</body>
</html>
