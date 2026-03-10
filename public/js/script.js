/**
 * Image Gallery JavaScript
 * Handles interactive features
 */

document.addEventListener('DOMContentLoaded', function() {
    // Image preview on upload page
    const imageInput = document.getElementById('image');
    const previewContainer = document.getElementById('preview-container');
    const imagePreview = document.getElementById('image-preview');

    if (imageInput && previewContainer && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Check if it's an image
                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            } else {
                previewContainer.style.display = 'none';
                imagePreview.src = '';
            }
        });
    }

    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash');
    flashMessages.forEach(function(flash) {
        setTimeout(function() {
            flash.style.transition = 'opacity 0.5s';
            flash.style.opacity = '0';
            setTimeout(function() {
                flash.remove();
            }, 500);
        }, 5000);
    });

    // Confirm delete for any delete forms (additional safety)
    const deleteForms = document.querySelectorAll('form[action="delete.php"]');
    deleteForms.forEach(function(form) {
        if (!form.hasAttribute('onsubmit')) {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this image? This cannot be undone.')) {
                    e.preventDefault();
                }
            });
        }
    });
});
