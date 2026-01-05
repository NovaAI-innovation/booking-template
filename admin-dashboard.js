// Tab switching
const tabBtns = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const tabName = btn.dataset.tab;

        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        document.getElementById(`${tabName}-tab`).classList.add('active');
    });
});

// File input change handlers
document.getElementById('image-file')?.addEventListener('change', function(e) {
    const filename = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('image-filename').textContent = filename;
});

document.getElementById('video-file')?.addEventListener('change', function(e) {
    const filename = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('video-filename').textContent = filename;
});

document.getElementById('thumbnail-file')?.addEventListener('change', function(e) {
    const filename = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('thumbnail-filename').textContent = filename;
});

document.getElementById('edit-video-file')?.addEventListener('change', function(e) {
    const filename = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('edit-video-filename').textContent = filename;
});

document.getElementById('edit-thumbnail-file')?.addEventListener('change', function(e) {
    const filename = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('edit-thumbnail-filename').textContent = filename;
});

// Show message function
function showMessage(text, type = 'success') {
    const messageEl = document.getElementById('message');
    messageEl.textContent = text;
    messageEl.className = `message ${type}`;
    messageEl.style.display = 'block';

    setTimeout(() => {
        messageEl.style.display = 'none';
    }, 5000);
}

// Image upload form handler
document.getElementById('image-upload-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('.upload-btn');
    const originalText = submitBtn.textContent;

    submitBtn.textContent = 'Uploading...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('admin-api.php?action=upload-image', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            this.reset();
            document.getElementById('image-filename').textContent = 'No file chosen';
            setTimeout(() => location.reload(), 1500);
        } else {
            showMessage(result.message || 'Upload failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Video upload form handler
document.getElementById('video-upload-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('.upload-btn');
    const originalText = submitBtn.textContent;

    submitBtn.textContent = 'Uploading...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('admin-api.php?action=upload-video', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            this.reset();
            document.getElementById('video-filename').textContent = 'No file chosen';
            document.getElementById('thumbnail-filename').textContent = 'No file chosen';
            setTimeout(() => location.reload(), 1500);
        } else {
            showMessage(result.message || 'Upload failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Delete image function
async function deleteImage(id) {
    if (!confirm('Are you sure you want to delete this image?')) {
        return;
    }

    try {
        const response = await fetch(`admin-api.php?action=delete-image&id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    }
}

// Delete video function
async function deleteVideo(id) {
    if (!confirm('Are you sure you want to delete this video?')) {
        return;
    }

    try {
        const response = await fetch(`admin-api.php?action=delete-video&id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(result.message || 'Delete failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    }
}

// Edit image function
function editImage(id) {
    const newAlt = prompt('Enter new alt text:');
    if (newAlt === null) return;

    updateImage(id, { alt: newAlt });
}

// Edit video function - opens modal
function editVideo(id) {
    // Find video data from the card
    const videoCard = document.querySelector(`.gallery-card[data-id="${id}"]`);
    if (!videoCard) return;

    const title = videoCard.querySelector('.card-title').textContent;
    const videoElement = videoCard.querySelector('video');
    const videoSrc = videoElement.getAttribute('src');
    const thumbnailSrc = videoElement.getAttribute('poster');

    // Populate modal with current data
    document.getElementById('edit-video-id').value = id;
    document.getElementById('edit-video-title').value = title;
    document.getElementById('current-video-source').src = videoSrc;
    document.getElementById('current-video-preview').load();
    document.getElementById('current-video-filename').textContent = videoSrc.split('/').pop();
    document.getElementById('current-thumbnail-preview').src = thumbnailSrc;
    document.getElementById('current-thumbnail-filename').textContent = thumbnailSrc.split('/').pop();

    // Reset file inputs
    document.getElementById('edit-video-file').value = '';
    document.getElementById('edit-thumbnail-file').value = '';
    document.getElementById('edit-video-filename').textContent = 'No file chosen';
    document.getElementById('edit-thumbnail-filename').textContent = 'No file chosen';

    // Show modal
    document.getElementById('edit-video-modal').classList.add('active');
}

// Close edit modal
function closeEditModal() {
    document.getElementById('edit-video-modal').classList.remove('active');
}

// Close modal on overlay click
document.getElementById('edit-video-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Update image function
async function updateImage(id, data) {
    try {
        const response = await fetch(`admin-api.php?action=update-image&id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(result.message || 'Update failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    }
}

// Edit video form handler
document.getElementById('edit-video-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = this.querySelector('.upload-btn');
    const originalText = submitBtn.textContent;
    const videoId = document.getElementById('edit-video-id').value;

    submitBtn.textContent = 'Updating...';
    submitBtn.disabled = true;

    try {
        const response = await fetch(`admin-api.php?action=update-video&id=${videoId}`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message, 'success');
            closeEditModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showMessage(result.message || 'Update failed', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
});

// Drag and drop for reordering
let draggedElement = null;

const imagesGrid = document.getElementById('images-grid');
const videosGrid = document.getElementById('videos-grid');

[imagesGrid, videosGrid].forEach(grid => {
    if (!grid) return;

    grid.addEventListener('dragstart', function(e) {
        if (e.target.classList.contains('gallery-card')) {
            draggedElement = e.target;
            e.target.style.opacity = '0.5';
        }
    });

    grid.addEventListener('dragend', function(e) {
        if (e.target.classList.contains('gallery-card')) {
            e.target.style.opacity = '1';
        }
    });

    grid.addEventListener('dragover', function(e) {
        e.preventDefault();
        const afterElement = getDragAfterElement(grid, e.clientY);
        if (afterElement == null) {
            grid.appendChild(draggedElement);
        } else {
            grid.insertBefore(draggedElement, afterElement);
        }
    });

    grid.addEventListener('drop', function(e) {
        e.preventDefault();
        updateOrder(grid);
    });
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.gallery-card:not(.dragging)')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

async function updateOrder(grid) {
    const cards = grid.querySelectorAll('.gallery-card');
    const type = grid.id === 'images-grid' ? 'images' : 'videos';
    const order = [];

    cards.forEach((card, index) => {
        order.push({
            id: parseInt(card.dataset.id),
            order: index + 1
        });
    });

    try {
        const response = await fetch(`admin-api.php?action=reorder-${type}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order })
        });

        const result = await response.json();

        if (result.success) {
            showMessage('Order updated successfully', 'success');
        } else {
            showMessage('Failed to update order', 'error');
        }
    } catch (error) {
        showMessage('Network error: ' + error.message, 'error');
    }
}
