<?php
/**
 * Image Upload Page - Enhanced with metadata form
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
$categories = get_all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Artwork - <?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    animation: { 'float': 'float 6s ease-in-out infinite', 'twinkle': 'twinkle 3s ease-in-out infinite' },
                    keyframes: {
                        float: { '0%, 100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-15px)' } },
                        twinkle: { '0%, 100%': { opacity: '0.3', transform: 'scale(1)' }, '50%': { opacity: '1', transform: 'scale(1.2)' } }
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
        .stars-container { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
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
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        .drop-zone.dragover {
            border-color: #a855f7;
            background: rgba(168, 85, 247, 0.1);
            transform: scale(1.01);
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 0.75rem;
            color: white;
            placeholder: #64748b;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
        }
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Stars Background -->
    <div class="stars-container">
        <?php for ($i = 0; $i < 30; $i++): ?>
            <?php
            $top = rand(0, 100);
            $left = rand(0, 100);
            $size = rand(2, 5);
            $delay = rand(0, 30) / 10;
            ?>
            <div class="star" style="width: <?= $size ?>px; height: <?= $size ?>px; top: <?= $top ?>%; left: <?= $left ?>%; animation-delay: <?= $delay ?>s;"></div>
        <?php endfor; ?>
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <a href="index.php" class="inline-flex items-center space-x-2 text-slate-400 hover:text-purple-400 transition-colors mb-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="font-medium">Back to Gallery</span>
                </a>
                <div class="flex items-center justify-center space-x-3 mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400 flex items-center justify-center shadow-xl shadow-purple-500/25 animate-float">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 bg-clip-text text-transparent mb-2">Upload Artwork</h1>
                <p class="text-slate-400">Share your creativity with the world</p>
            </div>

            <!-- Upload Card -->
            <div class="glass-card rounded-3xl p-8 shadow-xl">
                <form id="upload-form" enctype="multipart/form-data">
                    <!-- Drop Zone -->
                    <div class="drop-zone relative border-2 border-dashed border-slate-600 rounded-2xl p-12 text-center transition-all duration-300 cursor-pointer hover:border-purple-400" onclick="document.getElementById('image').click()">
                        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.gif,.webp" required class="hidden">

                        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 flex items-center justify-center animate-float">
                            <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>

                        <p class="text-xl font-semibold text-white mb-2">Drop your artwork here</p>
                        <p class="text-slate-400 mb-4">or <span class="text-purple-400 font-medium underline">browse</span> to choose a file</p>

                        <div class="flex items-center justify-center space-x-4 text-sm text-slate-500">
                            <span class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>JPG, PNG, GIF, WebP</span>
                            </span>
                            <span class="flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Max 5MB</span>
                            </span>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div id="preview-container" class="hidden mt-6">
                        <div class="relative rounded-2xl overflow-hidden glass p-3">
                            <img id="image-preview" src="" alt="Preview" class="w-full h-64 object-cover rounded-xl shadow-md">
                            <button type="button" onclick="resetFile()" class="absolute top-5 right-5 w-8 h-8 rounded-full bg-red-500/90 backdrop-blur-sm flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Metadata Form -->
                    <div class="mt-8 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Title *</label>
                            <input type="text" name="title" id="title" required placeholder="Give your artwork a title"
                                   class="form-input">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" placeholder="Tell us about your artwork..."
                                      class="form-input resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Category</label>
                                <select name="category_id" id="category_id" class="form-input">
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Artist Name</label>
                                <input type="text" name="artist_name" id="artist_name" value="<?= e($current_user['username']) ?>"
                                       class="form-input">
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center space-x-4 mt-8">
                        <button type="submit" id="submit-btn" class="flex-1 py-4 px-6 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white font-semibold rounded-2xl transition-all duration-300 shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/35 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <span>Upload Artwork</span>
                            </span>
                        </button>
                        <a href="index.php" class="px-6 py-4 glass text-slate-300 font-semibold rounded-2xl transition-all duration-300 hover:bg-slate-800/50">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const dropZone = document.querySelector('.drop-zone');
        const fileInput = document.getElementById('image');
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('image-preview');
        const uploadForm = document.getElementById('upload-form');
        const submitBtn = document.getElementById('submit-btn');

        // Drag and drop handlers
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
        });

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length) { 
                fileInput.files = files; 
                handlePreview(files[0]);
            }
        }

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length) handlePreview(e.target.files[0]);
        });

        function handlePreview(file) {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }

        function resetFile() {
            fileInput.value = '';
            previewContainer.classList.add('hidden');
            imagePreview.src = '';
        }

        // Form submission
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('image', fileInput.files[0]);
            formData.append('title', document.getElementById('title').value);
            formData.append('description', document.getElementById('description').value);
            formData.append('category_id', document.getElementById('category_id').value);
            formData.append('artist_name', document.getElementById('artist_name').value);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="flex items-center justify-center space-x-2"><svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Uploading...</span></span>';

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php?uploaded=1';
                } else {
                    alert(data.error || 'Upload failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="flex items-center justify-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span>Upload Artwork</span></span>';
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="flex items-center justify-center space-x-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span>Upload Artwork</span></span>';
            });
        });
    </script>
    
    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="javascript:void(0)" class="bottom-nav-item" onclick="window.location.href='index.php'; return false;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span>Search</span>
        </a>
        <a href="profile.php" class="bottom-nav-item">
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
            padding: 0.75rem 1rem;
            z-index: 100;
            display: none;
            justify-content: space-around;
            align-items: center;
        }
        @media (max-width: 768px) {
            .bottom-nav {
                display: flex;
            }
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            color: #64748b;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
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
            font-size: 0.75rem;
            font-weight: 500;
        }
    </style>
</body>
</html>
