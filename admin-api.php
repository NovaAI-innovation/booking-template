<?php
require_once 'admin-config.php';
requireLogin();

header('Content-Type: application/json');

// Get action from query parameter
$action = $_GET['action'] ?? '';

// Helper function to read gallery data
function readGalleryData() {
    if (!file_exists(GALLERY_DATA_FILE)) {
        return ['images' => [], 'videos' => []];
    }
    $data = json_decode(file_get_contents(GALLERY_DATA_FILE), true);
    return $data ?: ['images' => [], 'videos' => []];
}

// Helper function to write gallery data
function writeGalleryData($data) {
    return file_put_contents(GALLERY_DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

// Helper function to generate unique ID
function generateId($items) {
    if (empty($items)) return 1;
    $ids = array_column($items, 'id');
    return max($ids) + 1;
}

// Helper function to sanitize filename
function sanitizeFilename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

// Upload Image
if ($action === 'upload-image') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
        exit;
    }

    $file = $_FILES['image'];
    $alt = $_POST['alt'] ?? 'Gallery image';

    // Validate file type
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type. Supported formats: JPEG, PNG, WebP, GIF, BMP, TIFF, SVG, ICO, HEIC, HEIF, AVIF, APNG']);
        exit;
    }

    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
        exit;
    }

    // Generate filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'img_' . time() . '_' . uniqid() . '.' . $extension;
    $filename = sanitizeFilename($filename);
    $filepath = UPLOAD_DIR_IMAGES . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit;
    }

    // Update gallery data
    $data = readGalleryData();
    $newImage = [
        'id' => generateId($data['images']),
        'filename' => $filename,
        'path' => 'Pics for website/' . $filename,
        'alt' => $alt,
        'order' => count($data['images']) + 1,
        'uploadedAt' => date('c')
    ];
    $data['images'][] = $newImage;

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Image uploaded successfully', 'data' => $newImage]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Upload Video
if ($action === 'upload-video') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No video uploaded or upload error']);
        exit;
    }

    if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No thumbnail uploaded or upload error']);
        exit;
    }

    $video = $_FILES['video'];
    $thumbnail = $_FILES['thumbnail'];
    $title = $_POST['title'] ?? 'Untitled Video';

    // Validate video file type
    if (!in_array($video['type'], ALLOWED_VIDEO_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Invalid video type. Supported formats: MP4, WebM, MOV, AVI, WMV, FLV, MPEG, OGG, 3GP, MKV, M4V']);
        exit;
    }

    // Validate thumbnail file type
    if (!in_array($thumbnail['type'], ALLOWED_IMAGE_TYPES)) {
        echo json_encode(['success' => false, 'message' => 'Invalid thumbnail type. Supported formats: JPEG, PNG, WebP, GIF, BMP, TIFF, SVG, ICO, HEIC, HEIF, AVIF, APNG']);
        exit;
    }

    // Validate file sizes
    if ($video['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'Video too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
        exit;
    }

    if ($thumbnail['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'Thumbnail too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
        exit;
    }

    // Generate filenames
    $videoExt = pathinfo($video['name'], PATHINFO_EXTENSION);
    $thumbExt = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
    $baseFilename = 'video_' . time() . '_' . uniqid();

    $videoFilename = sanitizeFilename($baseFilename . '.' . $videoExt);
    $thumbFilename = sanitizeFilename($baseFilename . '_thumb.' . $thumbExt);

    $videoPath = UPLOAD_DIR_VIDEOS . $videoFilename;
    $thumbPath = UPLOAD_DIR_IMAGES . $thumbFilename;

    // Move uploaded files
    if (!move_uploaded_file($video['tmp_name'], $videoPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save video']);
        exit;
    }

    if (!move_uploaded_file($thumbnail['tmp_name'], $thumbPath)) {
        unlink($videoPath); // Clean up video file
        echo json_encode(['success' => false, 'message' => 'Failed to save thumbnail']);
        exit;
    }

    // Update gallery data
    $data = readGalleryData();
    $newVideo = [
        'id' => generateId($data['videos']),
        'filename' => $videoFilename,
        'videoPath' => 'Videos for website/' . $videoFilename,
        'thumbnailPath' => 'Pics for website/' . $thumbFilename,
        'title' => $title,
        'order' => count($data['videos']) + 1,
        'uploadedAt' => date('c')
    ];
    $data['videos'][] = $newVideo;

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Video uploaded successfully', 'data' => $newVideo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Delete Image
if ($action === 'delete-image') {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }

    $data = readGalleryData();
    $imageIndex = null;
    $imageToDelete = null;

    foreach ($data['images'] as $index => $image) {
        if ($image['id'] === $id) {
            $imageIndex = $index;
            $imageToDelete = $image;
            break;
        }
    }

    if ($imageIndex === null) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit;
    }

    // Delete file
    $filePath = __DIR__ . '/' . $imageToDelete['path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Remove from data
    array_splice($data['images'], $imageIndex, 1);

    // Reorder remaining images
    foreach ($data['images'] as $index => &$image) {
        $image['order'] = $index + 1;
    }

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Delete Video
if ($action === 'delete-video') {
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
        exit;
    }

    $data = readGalleryData();
    $videoIndex = null;
    $videoToDelete = null;

    foreach ($data['videos'] as $index => $video) {
        if ($video['id'] === $id) {
            $videoIndex = $index;
            $videoToDelete = $video;
            break;
        }
    }

    if ($videoIndex === null) {
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit;
    }

    // Delete video file
    $videoPath = __DIR__ . '/' . $videoToDelete['videoPath'];
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }

    // Delete thumbnail file
    $thumbPath = __DIR__ . '/' . $videoToDelete['thumbnailPath'];
    if (file_exists($thumbPath)) {
        unlink($thumbPath);
    }

    // Remove from data
    array_splice($data['videos'], $videoIndex, 1);

    // Reorder remaining videos
    foreach ($data['videos'] as $index => &$video) {
        $video['order'] = $index + 1;
    }

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Update Image
if ($action === 'update-image') {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    $data = readGalleryData();
    $imageIndex = null;

    foreach ($data['images'] as $index => $image) {
        if ($image['id'] === $id) {
            $imageIndex = $index;
            break;
        }
    }

    if ($imageIndex === null) {
        echo json_encode(['success' => false, 'message' => 'Image not found']);
        exit;
    }

    // Update fields
    if (isset($input['alt'])) {
        $data['images'][$imageIndex]['alt'] = $input['alt'];
    }

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Image updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Update Video
if ($action === 'update-video') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
        exit;
    }

    $data = readGalleryData();
    $videoIndex = null;
    $currentVideo = null;

    foreach ($data['videos'] as $index => $video) {
        if ($video['id'] === $id) {
            $videoIndex = $index;
            $currentVideo = $video;
            break;
        }
    }

    if ($videoIndex === null) {
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit;
    }

    // Update title if provided
    if (isset($_POST['title']) && !empty($_POST['title'])) {
        $data['videos'][$videoIndex]['title'] = $_POST['title'];
    }

    // Handle video file replacement
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoFile = $_FILES['video'];

        // Validate video file type
        if (!in_array($videoFile['type'], ALLOWED_VIDEO_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Invalid video type. Supported formats: MP4, WebM, MOV, AVI, WMV, FLV, MPEG, OGG, 3GP, MKV, M4V']);
            exit;
        }

        // Validate file size
        if ($videoFile['size'] > MAX_FILE_SIZE) {
            echo json_encode(['success' => false, 'message' => 'Video too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
            exit;
        }

        // Generate new filename
        $videoExt = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
        $newVideoFilename = sanitizeFilename('video_' . time() . '_' . uniqid() . '.' . $videoExt);
        $newVideoPath = UPLOAD_DIR_VIDEOS . $newVideoFilename;

        // Move uploaded file
        if (!move_uploaded_file($videoFile['tmp_name'], $newVideoPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save new video']);
            exit;
        }

        // Delete old video file
        $oldVideoPath = __DIR__ . '/' . $currentVideo['videoPath'];
        if (file_exists($oldVideoPath)) {
            unlink($oldVideoPath);
        }

        // Update video path in data
        $data['videos'][$videoIndex]['videoPath'] = 'Videos for website/' . $newVideoFilename;
        $data['videos'][$videoIndex]['filename'] = $newVideoFilename;
    }

    // Handle thumbnail file replacement
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbnailFile = $_FILES['thumbnail'];

        // Validate thumbnail file type
        if (!in_array($thumbnailFile['type'], ALLOWED_IMAGE_TYPES)) {
            echo json_encode(['success' => false, 'message' => 'Invalid thumbnail type. Supported formats: JPEG, PNG, WebP, GIF, BMP, TIFF, SVG, ICO, HEIC, HEIF, AVIF, APNG']);
            exit;
        }

        // Validate file size
        if ($thumbnailFile['size'] > MAX_FILE_SIZE) {
            echo json_encode(['success' => false, 'message' => 'Thumbnail too large. Maximum: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB']);
            exit;
        }

        // Generate new filename
        $thumbExt = pathinfo($thumbnailFile['name'], PATHINFO_EXTENSION);
        $newThumbFilename = sanitizeFilename('video_' . time() . '_' . uniqid() . '_thumb.' . $thumbExt);
        $newThumbPath = UPLOAD_DIR_IMAGES . $newThumbFilename;

        // Move uploaded file
        if (!move_uploaded_file($thumbnailFile['tmp_name'], $newThumbPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save new thumbnail']);
            exit;
        }

        // Delete old thumbnail file
        $oldThumbPath = __DIR__ . '/' . $currentVideo['thumbnailPath'];
        if (file_exists($oldThumbPath)) {
            unlink($oldThumbPath);
        }

        // Update thumbnail path in data
        $data['videos'][$videoIndex]['thumbnailPath'] = 'Pics for website/' . $newThumbFilename;
    }

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Video updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Reorder Images
if ($action === 'reorder-images') {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['order']) || !is_array($input['order'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid order data']);
        exit;
    }

    $data = readGalleryData();

    // Update order for each image
    foreach ($input['order'] as $item) {
        $id = intval($item['id']);
        $order = intval($item['order']);

        foreach ($data['images'] as &$image) {
            if ($image['id'] === $id) {
                $image['order'] = $order;
                break;
            }
        }
    }

    // Sort images by order
    usort($data['images'], function($a, $b) {
        return $a['order'] - $b['order'];
    });

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Images reordered successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Reorder Videos
if ($action === 'reorder-videos') {
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['order']) || !is_array($input['order'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid order data']);
        exit;
    }

    $data = readGalleryData();

    // Update order for each video
    foreach ($input['order'] as $item) {
        $id = intval($item['id']);
        $order = intval($item['order']);

        foreach ($data['videos'] as &$video) {
            if ($video['id'] === $id) {
                $video['order'] = $order;
                break;
            }
        }
    }

    // Sort videos by order
    usort($data['videos'], function($a, $b) {
        return $a['order'] - $b['order'];
    });

    if (writeGalleryData($data)) {
        echo json_encode(['success' => true, 'message' => 'Videos reordered successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update gallery data']);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
exit;
?>
