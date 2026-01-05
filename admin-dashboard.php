<?php
require_once 'admin-config.php';
requireLogin();

// Load gallery data
$galleryData = json_decode(file_get_contents(GALLERY_DATA_FILE), true);
if (!$galleryData) {
    $galleryData = ['images' => [], 'videos' => []];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery CMS Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Match main site color scheme */
            --admin-bg: #0a0a0a;
            --admin-panel: rgba(20, 10, 15, 0.85);
            --admin-border: rgba(196, 30, 58, 0.3);
            --admin-accent: #D4A574;
            --admin-crimson: #C41E3A;
            --admin-burgundy: #8B1A42;
            --admin-rose: #B8456B;
        }

        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a0f 50%, #0a0a0a 100%);
            background-attachment: fixed;
            color: var(--text-light);
            font-family: var(--font-sans);
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(139, 26, 66, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(196, 30, 58, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .admin-header {
            background: var(--admin-panel);
            border-bottom: 2px solid var(--admin-border);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(139, 26, 66, 0.2);
            position: relative;
        }

        .admin-header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-title h1 {
            font-family: var(--font-serif);
            font-size: 1.75rem;
            background: linear-gradient(135deg, var(--accent-champagne) 0%, var(--admin-accent) 40%, var(--admin-rose) 100%);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient-shift 8s ease infinite;
            filter: drop-shadow(0 0 15px rgba(196, 30, 58, 0.3));
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .admin-title p {
            margin: 0.25rem 0 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: var(--admin-accent);
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .logout-btn {
            padding: 0.625rem 1.25rem;
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #ff6b6b;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.3);
            border-color: #ff6b6b;
        }

        .admin-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
            position: relative;
            z-index: 1;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--admin-border);
        }

        .tab-btn {
            padding: 1rem 2rem;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            position: relative;
            bottom: -2px;
        }

        .tab-btn.active {
            color: var(--admin-accent);
            border-bottom-color: var(--admin-crimson);
            text-shadow: 0 0 15px rgba(196, 30, 58, 0.5);
        }

        .tab-btn:hover {
            color: var(--admin-accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .upload-section {
            background: var(--admin-panel);
            border: 2px dashed var(--admin-border);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(139, 26, 66, 0.15);
            transition: all 0.4s ease;
        }

        .upload-section:hover {
            border-color: var(--admin-crimson);
            box-shadow: 0 12px 40px rgba(196, 30, 58, 0.25);
        }

        .upload-section h2 {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--admin-accent) 0%, var(--admin-rose) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .upload-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-field label {
            color: var(--admin-accent);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-field input,
        .form-field select {
            padding: 0.875rem 1.125rem;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .form-field input:focus,
        .form-field select:focus {
            outline: none;
            border-color: var(--admin-crimson);
            box-shadow: 0 0 20px rgba(196, 30, 58, 0.25);
        }

        .file-input-wrapper {
            position: relative;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(139, 26, 66, 0.15), rgba(196, 30, 58, 0.1));
            border: 2px dashed var(--admin-accent);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.4s ease;
            gap: 0.75rem;
        }

        .file-input-label:hover {
            background: linear-gradient(135deg, rgba(139, 26, 66, 0.25), rgba(196, 30, 58, 0.2));
            border-color: var(--admin-crimson);
            box-shadow: 0 0 25px rgba(196, 30, 58, 0.3);
            transform: translateY(-2px);
        }

        .file-input-label input[type="file"] {
            display: none;
        }

        .upload-btn {
            grid-column: 1 / -1;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--admin-burgundy), var(--admin-crimson));
            color: var(--text-light);
            border: 1px solid rgba(196, 30, 58, 0.5);
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }

        .upload-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .upload-btn:hover::before {
            left: 100%;
        }

        .upload-btn:hover {
            border-color: var(--admin-accent);
            box-shadow: 0 10px 40px rgba(196, 30, 58, 0.5);
            transform: translateY(-3px);
        }

        .upload-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .gallery-list {
            background: var(--admin-panel);
            border-radius: 15px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(139, 26, 66, 0.15);
            border: 1px solid rgba(196, 30, 58, 0.1);
        }

        .gallery-list h2 {
            font-family: var(--font-serif);
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--admin-accent) 0%, var(--admin-rose) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .gallery-card {
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(196, 30, 58, 0.2);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            cursor: grab;
            backdrop-filter: blur(10px);
        }

        .gallery-card:active {
            cursor: grabbing;
        }

        .gallery-card:hover {
            border-color: var(--admin-crimson);
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(196, 30, 58, 0.4);
        }

        .card-media {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: rgba(0, 0, 0, 0.8);
        }

        .card-content {
            padding: 1rem;
        }

        .card-title {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-info {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .card-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.4);
        }

        .edit-btn:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: #60a5fa;
        }

        .delete-btn {
            background: rgba(220, 38, 38, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 38, 38, 0.4);
        }

        .delete-btn:hover {
            background: rgba(220, 38, 38, 0.3);
            border-color: #ff6b6b;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: none;
        }

        .message.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #4ade80;
        }

        .message.error {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #ff6b6b;
        }

        @media (max-width: 768px) {
            .admin-header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .upload-form {
                grid-template-columns: 1fr;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }

            .admin-container {
                padding: 0 1rem;
            }
        }

        /* Edit Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--admin-panel);
            border: 2px solid var(--admin-border);
            border-radius: 15px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(139, 26, 66, 0.4);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h2 {
            font-family: var(--font-serif);
            background: linear-gradient(135deg, var(--admin-accent) 0%, var(--admin-rose) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .close-modal {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            line-height: 1;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--text-light);
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .current-file-preview {
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        .current-file-preview video,
        .current-file-preview img {
            width: 100%;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .current-file-label {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        .file-update-hint {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <h1>🎨 Gallery CMS</h1>
                <p>Content Management System</p>
            </div>
            <div class="admin-user">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="admin-logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div id="message" class="message"></div>

        <div class="tabs">
            <button class="tab-btn active" data-tab="images">📷 Images</button>
            <button class="tab-btn" data-tab="videos">🎬 Videos</button>
        </div>

        <!-- Images Tab -->
        <div id="images-tab" class="tab-content active">
            <div class="upload-section">
                <h2>Upload New Image</h2>
                <form id="image-upload-form" class="upload-form" enctype="multipart/form-data">
                    <div class="form-field">
                        <label>Image File *</label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <span>📁 Choose Image</span>
                                <span id="image-filename">No file chosen</span>
                                <input type="file" name="image" id="image-file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff,image/svg+xml,image/x-icon,image/heic,image/heif,image/avif,image/apng" required>
                            </label>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="image-alt">Alt Text *</label>
                        <input type="text" name="alt" id="image-alt" placeholder="Description for accessibility" required>
                    </div>
                    <button type="submit" class="upload-btn">Upload Image</button>
                </form>
            </div>

            <div class="gallery-list">
                <h2>Image Gallery (<span id="images-count"><?php echo count($galleryData['images']); ?></span>)</h2>
                <div id="images-grid" class="items-grid">
                    <?php if (empty($galleryData['images'])): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>No images uploaded yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($galleryData['images'] as $image): ?>
                            <div class="gallery-card" data-id="<?php echo $image['id']; ?>" draggable="true">
                                <img src="<?php echo htmlspecialchars($image['path']); ?>" alt="<?php echo htmlspecialchars($image['alt']); ?>" class="card-media" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23333%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23fff%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                                <div class="card-content">
                                    <div class="card-title"><?php echo htmlspecialchars($image['filename']); ?></div>
                                    <div class="card-info">Order: <?php echo $image['order']; ?> | ID: <?php echo $image['id']; ?></div>
                                    <div class="card-actions">
                                        <button class="card-btn edit-btn" onclick="editImage(<?php echo $image['id']; ?>)">Edit</button>
                                        <button class="card-btn delete-btn" onclick="deleteImage(<?php echo $image['id']; ?>)">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Videos Tab -->
        <div id="videos-tab" class="tab-content">
            <div class="upload-section">
                <h2>Upload New Video</h2>
                <form id="video-upload-form" class="upload-form" enctype="multipart/form-data">
                    <div class="form-field">
                        <label>Video File *</label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <span>🎬 Choose Video</span>
                                <span id="video-filename">No file chosen</span>
                                <input type="file" name="video" id="video-file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/mpeg,video/ogg,video/3gpp,video/3gpp2,video/x-matroska,video/x-m4v" required>
                            </label>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>Thumbnail Image *</label>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">
                                <span>🖼️ Choose Thumbnail</span>
                                <span id="thumbnail-filename">No file chosen</span>
                                <input type="file" name="thumbnail" id="thumbnail-file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff,image/svg+xml,image/x-icon,image/heic,image/heif,image/avif,image/apng" required>
                            </label>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="video-title">Video Title *</label>
                        <input type="text" name="title" id="video-title" placeholder="Enter video title" required>
                    </div>
                    <button type="submit" class="upload-btn">Upload Video</button>
                </form>
            </div>

            <div class="gallery-list">
                <h2>Video Gallery (<span id="videos-count"><?php echo count($galleryData['videos']); ?></span>)</h2>
                <div id="videos-grid" class="items-grid">
                    <?php if (empty($galleryData['videos'])): ?>
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <p>No videos uploaded yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($galleryData['videos'] as $video): ?>
                            <div class="gallery-card" data-id="<?php echo $video['id']; ?>" draggable="true">
                                <video src="<?php echo htmlspecialchars($video['videoPath']); ?>" poster="<?php echo htmlspecialchars($video['thumbnailPath']); ?>" class="card-media"></video>
                                <div class="card-content">
                                    <div class="card-title"><?php echo htmlspecialchars($video['title']); ?></div>
                                    <div class="card-info">Order: <?php echo $video['order']; ?> | ID: <?php echo $video['id']; ?></div>
                                    <div class="card-actions">
                                        <button class="card-btn edit-btn" onclick="editVideo(<?php echo $video['id']; ?>)">Edit</button>
                                        <button class="card-btn delete-btn" onclick="deleteVideo(<?php echo $video['id']; ?>)">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Video Modal -->
    <div id="edit-video-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Video</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="edit-video-form" class="modal-form" enctype="multipart/form-data">
                <input type="hidden" id="edit-video-id" name="id">

                <div class="form-field">
                    <label for="edit-video-title">Video Title *</label>
                    <input type="text" name="title" id="edit-video-title" placeholder="Enter video title" required>
                </div>

                <div class="form-field">
                    <label>Current Video</label>
                    <div class="current-file-preview">
                        <video id="current-video-preview" controls style="max-height: 200px;">
                            <source id="current-video-source" src="" type="video/mp4">
                        </video>
                        <div class="current-file-label" id="current-video-filename"></div>
                    </div>
                </div>

                <div class="form-field">
                    <label>Replace Video File (optional)</label>
                    <div class="file-input-wrapper">
                        <label class="file-input-label">
                            <span>🎬 Choose New Video</span>
                            <span id="edit-video-filename">No file chosen</span>
                            <input type="file" name="video" id="edit-video-file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/mpeg,video/ogg,video/3gpp,video/3gpp2,video/x-matroska,video/x-m4v">
                        </label>
                    </div>
                    <div class="file-update-hint">Leave empty to keep current video</div>
                </div>

                <div class="form-field">
                    <label>Current Thumbnail</label>
                    <div class="current-file-preview">
                        <img id="current-thumbnail-preview" src="" alt="Current thumbnail">
                        <div class="current-file-label" id="current-thumbnail-filename"></div>
                    </div>
                </div>

                <div class="form-field">
                    <label>Replace Thumbnail (optional)</label>
                    <div class="file-input-wrapper">
                        <label class="file-input-label">
                            <span>🖼️ Choose New Thumbnail</span>
                            <span id="edit-thumbnail-filename">No file chosen</span>
                            <input type="file" name="thumbnail" id="edit-thumbnail-file" accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff,image/svg+xml,image/x-icon,image/heic,image/heif,image/avif,image/apng">
                        </label>
                    </div>
                    <div class="file-update-hint">Leave empty to keep current thumbnail</div>
                </div>

                <button type="submit" class="upload-btn">Update Video</button>
            </form>
        </div>
    </div>

    <script src="admin-dashboard.js"></script>
</body>
</html>
