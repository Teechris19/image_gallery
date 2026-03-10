/**
 * Image Gallery JavaScript
 * Handles modal, zoom, rating, likes, search, and authentication
 */

// Modal state
let currentImageId = null;
let currentZoom = 1;
let currentImageIdForLike = null;
let imageDetails = null;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search with debounce
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                performSearch(this.value);
            }, 500);
        });
    }

    // Update search count
    updateSearchCount();

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeAuthModal();
            closeCategoryModal();
        }
    });

    // Close modal on backdrop click
    const modal = document.getElementById('image-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Initialize image pan on drag when zoomed
    initImagePan();
});

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const eyeIcon = document.getElementById(inputId === 'auth-password' ? 'eye-icon-auth' : 'eye-icon-confirm');
    
    if (input.type === 'password') {
        input.type = 'text';
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

/**
 * Update search results count display
 */
function updateSearchCount() {
    const countEl = document.getElementById('search-count');
    const grid = document.querySelector('.masonry-grid');
    if (countEl && grid) {
        const count = grid.querySelectorAll('.masonry-item').length;
        countEl.textContent = count > 0 ? count + ' artworks' : '';
    }
}

/**
 * Perform search via AJAX
 */
function performSearch(query) {
    const formData = new FormData();
    formData.append('action', 'search');
    formData.append('query', query);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countEl = document.getElementById('search-count');
            if (countEl) {
                countEl.textContent = data.count + ' results';
            }
        }
    })
    .catch(error => console.error('Search error:', error));
}

/**
 * Open image modal
 */
function openModal(imageId) {
    currentImageId = imageId;
    currentZoom = 1;
    imageDetails = null;

    const modal = document.getElementById('image-modal');
    const modalImage = document.getElementById('modal-image');
    
    // Get data from clicked element
    const clickedItem = event.currentTarget;
    const img = clickedItem.querySelector('img');
    const title = clickedItem.querySelector('h3')?.textContent || '';
    const artistLink = clickedItem.querySelector('[onclick*="artist.php"]');
    const artist = artistLink ? artistLink.textContent.trim() : '';
    
    // Set basic info
    modalImage.src = img.src.replace('thumbs/', '');
    modalImage.alt = title;
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-artist').textContent = artist;
    document.getElementById('modal-view-btn').href = 'view.php?id=' + imageId;
    document.getElementById('modal-artist-link').href = 'artist.php?user=' + encodeURIComponent(artist);

    // Fetch full details
    fetchImageDetails(imageId);

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Fetch full image details via AJAX
 */
function fetchImageDetails(imageId) {
    const formData = new FormData();
    formData.append('action', 'get_image_details');
    formData.append('image_id', imageId);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            imageDetails = data.image;
            populateModal(data.image);
        }
    })
    .catch(error => console.error('Fetch details error:', error));
}

/**
 * Populate modal with image details
 */
function populateModal(image) {
    document.getElementById('modal-description').textContent = image.description || 'No description provided.';
    document.getElementById('modal-category').textContent = image.category_name || 'Uncategorized';
    document.getElementById('modal-date').textContent = new Date(image.uploaded_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    document.getElementById('modal-views').textContent = (image.views || 0).toLocaleString();
    document.getElementById('modal-avg-rating').textContent = (image.avg_rating || 0).toFixed(1);
    document.getElementById('modal-rating-count').textContent = image.rating_count || 0;
    document.getElementById('modal-like-count').textContent = image.like_count || 0;
    
    currentImageIdForLike = image.id;
    updateLikeButton(image.is_liked, image.like_count);
    
    if (image.user_rating) {
        updateStarDisplay(image.user_rating);
    } else {
        resetRating();
    }
}

/**
 * Close image modal
 */
function closeModal() {
    const modal = document.getElementById('image-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentImageId = null;
    currentZoom = 1;
}

/**
 * Zoom controls
 */
function zoomIn() {
    const img = document.getElementById('modal-image');
    if (!img) return;
    currentZoom = Math.min(currentZoom + 0.5, 4);
    img.style.transform = `scale(${currentZoom})`;
    img.classList.add('zoomed');
}

function zoomOut() {
    const img = document.getElementById('modal-image');
    if (!img) return;
    currentZoom = Math.max(currentZoom - 0.5, 1);
    if (currentZoom === 1) {
        img.style.transform = 'scale(1)';
        img.classList.remove('zoomed');
    } else {
        img.style.transform = `scale(${currentZoom})`;
    }
}

function zoomReset() {
    const img = document.getElementById('modal-image');
    if (!img) return;
    currentZoom = 1;
    img.style.transform = 'scale(1)';
    img.classList.remove('zoomed');
}

/**
 * Initialize image panning when zoomed
 */
function initImagePan() {
    const container = document.getElementById('modal-image-container');
    const img = document.getElementById('modal-image');
    let isDragging = false;
    let startX, startY, translateX = 0, translateY = 0;

    if (!container || !img) return;

    container.addEventListener('mousedown', function(e) {
        if (currentZoom <= 1) return;
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        img.style.cursor = 'grabbing';
    });

    container.addEventListener('mousemove', function(e) {
        if (!isDragging || currentZoom <= 1) return;
        e.preventDefault();
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        img.style.transform = `scale(${currentZoom}) translate(${translateX / currentZoom}px, ${translateY / currentZoom}px)`;
    });

    container.addEventListener('mouseup', function() {
        isDragging = false;
        if (img) img.style.cursor = currentZoom > 1 ? 'zoom-out' : 'zoom-in';
    });

    container.addEventListener('mouseleave', function() {
        isDragging = false;
    });

    container.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            zoomIn();
        } else {
            zoomOut();
        }
    });
}

/**
 * Rating system
 */
let userRating = 0;

function submitRating(rating) {
    if (!currentImageId) return;

    const isLoggedIn = document.querySelector('a[href="profile.php"]') !== null;
    
    if (!isLoggedIn) {
        openAuthModal('login');
        return;
    }

    userRating = rating;
    updateStarDisplay(rating);

    const formData = new FormData();
    formData.append('action', 'rate');
    formData.append('image_id', currentImageId);
    formData.append('rating', rating);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modal-avg-rating').textContent = data.avg_rating.toFixed(1);
            document.getElementById('modal-rating-count').textContent = data.rating_count;
        } else if (data.requires_auth) {
            openAuthModal('login');
        } else {
            alert(data.error || 'Failed to submit rating');
        }
    })
    .catch(error => console.error('Rating error:', error));
}

function updateStarDisplay(rating) {
    const stars = document.querySelectorAll('#modal-star-rating button');
    stars.forEach((star, index) => {
        const svg = star.querySelector('svg');
        if (index < rating) {
            svg.classList.remove('star-empty');
            svg.classList.add('star-filled');
        } else {
            svg.classList.remove('star-filled');
            svg.classList.add('star-empty');
        }
    });
}

function resetRating() {
    userRating = 0;
    updateStarDisplay(0);
}

/**
 * Like system
 */
function toggleLike() {
    if (!currentImageIdForLike) return;

    const isLoggedIn = document.querySelector('a[href="profile.php"]') !== null;
    
    if (!isLoggedIn) {
        openAuthModal('login');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('image_id', currentImageIdForLike);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateLikeButton(data.liked, data.like_count);
        } else if (data.requires_auth) {
            openAuthModal('login');
        } else {
            alert(data.error || 'Failed to process like');
        }
    })
    .catch(error => console.error('Like error:', error));
}

function updateLikeButton(liked, count) {
    const btn = document.getElementById('modal-like-btn');
    const countEl = document.getElementById('modal-like-count');
    
    if (!btn || !countEl) return;

    countEl.textContent = count;
    
    if (liked) {
        btn.classList.add('liked');
    } else {
        btn.classList.remove('liked');
    }
}

/**
 * Share functionality
 */
function shareArtwork() {
    const url = window.location.origin + '/view.php?id=' + currentImageId;
    
    if (navigator.share) {
        navigator.share({
            title: document.getElementById('modal-title').textContent,
            url: url
        }).catch(console.error);
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showToast('Link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
}

/**
 * Toast notification
 */
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 px-6 py-3 bg-green-500 text-white rounded-xl shadow-lg z-50';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/**
 * Auth Modal functions
 */
function openAuthModal(mode = 'login') {
    const modal = document.getElementById('auth-modal');
    const modeField = document.getElementById('auth-mode');
    const title = document.getElementById('auth-title');
    const subtitle = document.getElementById('auth-subtitle');
    const submit = document.getElementById('auth-submit');
    const submitBtn = document.getElementById('auth-submit-btn');
    
    // Fields
    const loginUsernameField = document.getElementById('login-username-field');
    const signupFullnameField = document.getElementById('signup-fullname-field');
    const signupEmailField = document.getElementById('signup-email-field');
    const signupConfirmField = document.getElementById('signup-confirm-field');
    const forgotPasswordLink = document.getElementById('forgot-password-link');
    
    // Toggle text
    const toggleText = document.getElementById('auth-toggle-text');
    const toggleLink = document.getElementById('auth-toggle-link');
    const errorEl = document.getElementById('auth-error');
    const successEl = document.getElementById('auth-success');

    // Reset error, success and form
    errorEl.textContent = '';
    successEl.textContent = '';
    document.getElementById('auth-form').reset();

    if (mode === 'signup') {
        modeField.value = 'register';
        title.textContent = 'Create Account';
        subtitle.textContent = 'Join our art community';
        submit.textContent = 'Sign Up';
        submitBtn.classList.remove('from-purple-600', 'via-pink-600', 'to-orange-500');
        submitBtn.classList.add('from-pink-600', 'via-purple-600', 'to-purple-500');
        
        loginUsernameField.style.display = 'none';
        signupFullnameField.style.display = 'block';
        signupEmailField.style.display = 'block';
        signupConfirmField.style.display = 'block';
        forgotPasswordLink.style.display = 'none';
        
        toggleText.textContent = 'Already have an account?';
        toggleLink.textContent = 'Sign In';
    } else {
        modeField.value = 'login';
        title.textContent = 'Welcome Back';
        subtitle.textContent = 'Sign in to continue';
        submit.textContent = 'Sign In';
        submitBtn.classList.remove('from-pink-600', 'via-purple-600', 'to-purple-500');
        submitBtn.classList.add('from-purple-600', 'via-pink-600', 'to-orange-500');
        
        loginUsernameField.style.display = 'block';
        signupFullnameField.style.display = 'none';
        signupEmailField.style.display = 'none';
        signupConfirmField.style.display = 'none';
        forgotPasswordLink.style.display = 'block';
        
        toggleText.textContent = "Don't have an account?";
        toggleLink.textContent = 'Sign Up';
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAuthModal() {
    const modal = document.getElementById('auth-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function toggleAuthMode() {
    const modeField = document.getElementById('auth-mode');
    const newMode = modeField.value === 'login' ? 'signup' : 'login';
    openAuthModal(newMode);
}

function handleAuth(event) {
    event.preventDefault();
    
    const mode = document.getElementById('auth-mode').value;
    const username = document.getElementById('auth-username').value;
    const password = document.getElementById('auth-password').value;
    const email = document.getElementById('auth-email').value;
    const fullname = document.getElementById('auth-fullname').value;
    const confirmPassword = document.getElementById('auth-confirm-password').value;
    const errorEl = document.getElementById('auth-error');
    const successEl = document.getElementById('auth-success');
    const submitBtn = document.getElementById('auth-submit-btn');
    const submitText = document.getElementById('auth-submit');

    // Validation
    if (mode === 'register') {
        if (!username || username.length < 3) {
            errorEl.textContent = 'Username must be at least 3 characters';
            return;
        }
        if (!email || !email.includes('@')) {
            errorEl.textContent = 'Please enter a valid email address';
            return;
        }
        if (!password || password.length < 6) {
            errorEl.textContent = 'Password must be at least 6 characters';
            return;
        }
        if (password !== confirmPassword) {
            errorEl.textContent = 'Passwords do not match';
            return;
        }
    } else {
        if (!username || !password) {
            errorEl.textContent = 'Please enter both username/email and password';
            return;
        }
    }

    const formData = new FormData();
    formData.append('action', mode);
    formData.append('username', username);
    formData.append('password', password);
    if (mode === 'register') {
        formData.append('email', email);
        formData.append('fullname', fullname);
    }

    submitBtn.disabled = true;
    submitText.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>';

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successEl.textContent = mode === 'login' ? 'Login successful! Redirecting...' : 'Account created successfully! Redirecting...';
            errorEl.textContent = '';
            setTimeout(() => {
                closeAuthModal();
                window.location.reload();
            }, 1000);
        } else {
            errorEl.textContent = data.error || 'Authentication failed';
            successEl.textContent = '';
            submitBtn.disabled = false;
            submitText.textContent = mode === 'login' ? 'Sign In' : 'Sign Up';
        }
    })
    .catch(error => {
        console.error('Auth error:', error);
        errorEl.textContent = 'An error occurred. Please try again.';
        successEl.textContent = '';
        submitBtn.disabled = false;
        submitText.textContent = mode === 'login' ? 'Sign In' : 'Sign Up';
    });
}

/**
 * Category Modal functions
 */
function openCategoryModal() {
    const modal = document.getElementById('category-modal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCategoryModal() {
    const modal = document.getElementById('category-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('category-error').textContent = '';
}

function handleAddCategory(event) {
    event.preventDefault();
    
    const name = document.getElementById('category-name').value;
    const errorEl = document.getElementById('category-error');

    const formData = new FormData();
    formData.append('action', 'add_category');
    formData.append('name', name);

    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCategoryModal();
            window.location.reload();
        } else if (data.requires_auth) {
            openAuthModal('login');
        } else {
            errorEl.textContent = data.error || 'Failed to add category';
        }
    })
    .catch(error => {
        console.error('Category error:', error);
        errorEl.textContent = 'Failed to add category';
    });
}
