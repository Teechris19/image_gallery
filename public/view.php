<?php
/**
 * Single Image View Page - Enhanced
 */

define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/application/core/config.php';
require_once APP_ROOT . '/application/core/database.php';
require_once APP_ROOT . '/application/functions/general.php';
require_once APP_ROOT . '/application/functions/images.php';
require_once APP_ROOT . '/application/functions/auth.php';

initializeDatabase();
session_start();

$id = get_input('id');
if (!$id) {
    redirect('index.php');
}

$image = get_image_full($id);
if (!$image) {
    show_error('Image not found', 404);
}

// Increment view count
increment_views($id);

$current_user = get_logged_in_user();
$is_liked = $current_user ? has_liked($id, $current_user['id']) : false;
$user_rating = $current_user ? get_user_rating($id, $current_user['id']) : null;
$is_artist = $current_user && $current_user['id'] == $image['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($image['title']) ?> - <?= e(APP_NAME) ?></title>
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
            width: 2rem;
            height: 2rem;
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
                    <a href="index.php" class="flex items-center space-x-2 text-slate-400 hover:text-purple-400 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span class="font-medium">Back to Gallery</span>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-lg shadow-purple-500/25">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent"><?= e(APP_NAME) ?></h1>
                    </div>
                    <div class="w-32"></div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Image -->
                <div class="lg:col-span-2">
                    <div class="glass-card rounded-3xl overflow-hidden shadow-xl">
                        <img src="<?= image_url($image['filename']) ?>"
                             alt="<?= e($image['title']) ?>"
                             class="w-full h-auto">
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Info Card -->
                    <div class="glass-card rounded-3xl p-6">
                        <h2 class="text-2xl font-bold text-white mb-2"><?= e($image['title']) ?></h2>
                        
                        <!-- Artist -->
                        <a href="artist.php?user=<?= e($image['artist_username']) ?>"
                           class="flex items-center gap-3 mb-6 p-3 rounded-xl glass hover:bg-slate-800/50 transition-all">
                            <?php if ($image['artist_avatar']): ?>
                                <img src="<?= BASE_URL . 'uploads/profiles/' . e($image['artist_avatar']) ?>" alt="<?= e($image['artist_username']) ?>" class="w-12 h-12 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-semibold">
                                    <?= strtoupper(substr($image['artist_username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-white font-medium"><?= e($image['artist_name']) ?></p>
                                <p class="text-slate-400 text-sm">@<?= e($image['artist_username']) ?></p>
                            </div>
                        </a>

                        <!-- Description -->
                        <?php if ($image['description']): ?>
                            <p class="text-slate-300 mb-6"><?= nl2br(e($image['description'])) ?></p>
                        <?php endif; ?>

                        <!-- Details -->
                        <div class="space-y-3 py-4 border-y border-slate-700">
                            <?php if ($image['category_name']): ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                        <span>Category</span>
                                    </span>
                                    <span class="text-purple-400 font-medium"><?= e($image['category_name']) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Uploaded</span>
                                </span>
                                <span class="text-white"><?= date('M j, Y', strtotime($image['uploaded_at'])) ?></span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <span>Views</span>
                                </span>
                                <span class="text-white"><?= number_format($image['views']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Rating Card -->
                    <div class="glass-card rounded-3xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-slate-400">Your Rating</span>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-bold text-white"><?= $image['avg_rating'] ?></span>
                                <span class="text-slate-400 text-sm">(<?= $image['rating_count'] ?> votes)</span>
                            </div>
                        </div>
                        <?php if ($current_user): ?>
                            <div class="star-rating" id="star-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button onclick="submitRating(<?= $i ?>)" data-star="<?= $i ?>">
                                        <svg class="<?= ($user_rating && $i <= $user_rating) || (!$user_rating && $i <= round($image['avg_rating'])) ? 'star-filled' : 'star-empty' ?>" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-slate-400 text-sm">
                                <a href="#" onclick="openAuthModal(); return false;" class="text-purple-400 hover:underline">Login</a> to rate this artwork
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="glass-card rounded-3xl p-6 space-y-3">
                        <button id="like-btn" onclick="toggleLike()" class="like-btn <?= $is_liked ? 'liked' : '' ?> flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl glass border border-slate-700 text-white font-semibold transition-all hover:border-red-500/50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span id="like-count"><?= $image['like_count'] ?></span>
                            <span><?= $is_liked ? 'Liked' : 'Like' ?></span>
                        </button>

                        <button onclick="shareArtwork()" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl glass border border-slate-700 text-white font-semibold transition-all hover:border-purple-500/50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            Share
                        </button>

                        <a href="javascript:void(0)" onclick="downloadImage(<?= $image['id'] ?>, '<?= image_url($image['filename']) ?>', '<?= e($image['original_name']) ?>')"
                           class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-gradient-to-r from-cyan-600 to-blue-600 text-white font-semibold rounded-xl transition-all shadow-lg shadow-cyan-500/25 hover:shadow-xl hover:shadow-cyan-500/35">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download
                        </a>

                        <?php if ($is_artist): ?>
                            <form action="delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this artwork? This cannot be undone.');">
                                <input type="hidden" name="id" value="<?= $image['id'] ?>">
                                <button type="submit" class="flex items-center justify-center gap-2 w-full py-3 px-4 bg-gradient-to-r from-red-600 to-pink-600 text-white font-semibold rounded-xl transition-all shadow-lg shadow-red-500/25 hover:shadow-xl hover:shadow-red-500/35">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete Artwork
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const imageId = <?= $image['id'] ?>;
        const isLoggedIn = <?= $current_user ? 'true' : 'false' ?>;

        function submitRating(rating) {
            if (!isLoggedIn) {
                window.location.href = 'index.php#login';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'rate');
            formData.append('image_id', imageId);
            formData.append('rating', rating);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('#star-rating').querySelectorAll('button').forEach((btn, i) => {
                        const svg = btn.querySelector('svg');
                        if (i < rating) {
                            svg.classList.remove('star-empty');
                            svg.classList.add('star-filled');
                        } else {
                            svg.classList.remove('star-filled');
                            svg.classList.add('star-empty');
                        }
                    });
                }
            })
            .catch(error => console.error('Rating error:', error));
        }

        function toggleLike() {
            if (!isLoggedIn) {
                window.location.href = 'index.php#login';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'like');
            formData.append('image_id', imageId);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const btn = document.getElementById('like-btn');
                    const count = document.getElementById('like-count');
                    count.textContent = data.like_count;
                    if (data.liked) {
                        btn.classList.add('liked');
                        btn.querySelector('span:last-child').textContent = 'Liked';
                    } else {
                        btn.classList.remove('liked');
                        btn.querySelector('span:last-child').textContent = 'Like';
                    }
                }
            })
            .catch(error => console.error('Like error:', error));
        }

        function shareArtwork() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }

        function downloadImage(imageId, imageUrl, filename) {
            // Track download in database
            const formData = new FormData();
            formData.append('action', 'track_download');
            formData.append('image_id', imageId);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Then initiate download
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            })
            .catch(error => {
                console.error('Download tracking error:', error);
                // Still download even if tracking fails
                const link = document.createElement('a');
                link.href = imageUrl;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }

        function openAuthModal() {
            window.location.href = 'index.php';
        }
    </script>
    
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
        <a href="<?= $current_user ? 'downloads.php' : 'javascript:void(0)' ?>" class="bottom-nav-item" onclick="<?= !$current_user ? "window.location.href='index.php'; return false;" : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Downloads</span>
        </a>
        <a href="<?= $current_user ? 'profile.php' : 'javascript:void(0)' ?>" class="bottom-nav-item" onclick="<?= !$current_user ? "window.location.href='index.php'; return false;" : '' ?>">
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
</body>
</html>
