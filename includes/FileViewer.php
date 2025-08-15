<?php
class FileViewer {
    private $multimediaManager;

    public function __construct($multimediaManager) {
        $this->multimediaManager = $multimediaManager;
    }

    public function renderFileGallery($files, $options = []) {
        if (empty($files)) {
            return '<div class="no-files">No files uploaded yet.</div>';
        }

        $showDelete = $options['show_delete'] ?? false;
        $currentUserId = $options['current_user_id'] ?? null;
        $entityType = $options['entity_type'] ?? '';
        $entityId = $options['entity_id'] ?? '';

        $html = '<div class="file-gallery">';
        
        foreach ($files as $file) {
            $html .= $this->renderFileCard($file, [
                'show_delete' => $showDelete && $file['uploaded_by'] == $currentUserId,
                'entity_type' => $entityType,
                'entity_id' => $entityId
            ]);
        }
        
        $html .= '</div>';
        return $html;
    }

    public function renderFileCard($file, $options = []) {
        $showDelete = $options['show_delete'] ?? false;
        $entityType = $options['entity_type'] ?? '';
        $entityId = $options['entity_id'] ?? '';

        $html = '<div class="file-card" data-file-id="' . $file['id'] . '">';
        
        // File preview
        $html .= '<div class="file-preview">';
        if ($file['is_image']) {
            $html .= '<img src="' . htmlspecialchars($file['file_path']) . '" alt="' . htmlspecialchars($file['original_filename']) . '" class="file-image" onclick="openFileViewer(\'' . htmlspecialchars($file['file_path']) . '\', \'' . htmlspecialchars($file['original_filename']) . '\')">';
        } elseif ($file['is_video']) {
            $html .= '<video class="file-video" controls preload="metadata">';
            $html .= '<source src="' . htmlspecialchars($file['file_path']) . '" type="' . htmlspecialchars($file['mime_type']) . '">';
            $html .= 'Your browser does not support the video tag.';
            $html .= '</video>';
        } else {
            $html .= '<div class="file-icon">';
            $html .= '<i class="' . $file['icon'] . '"></i>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // File info
        $html .= '<div class="file-info">';
        $html .= '<div class="file-name" title="' . htmlspecialchars($file['original_filename']) . '">' . htmlspecialchars($file['original_filename']) . '</div>';
        $html .= '<div class="file-meta">';
        $html .= '<span class="file-size">' . $file['formatted_size'] . '</span>';
        $html .= '<span class="file-uploader">by ' . htmlspecialchars($file['uploaded_by_name']) . '</span>';
        $html .= '<span class="file-date">' . date('M j, Y', strtotime($file['created_at'])) . '</span>';
        $html .= '</div>';
        
        if (!empty($file['description'])) {
            $html .= '<div class="file-description">' . htmlspecialchars($file['description']) . '</div>';
        }
        $html .= '</div>';
        
        // File actions
        $html .= '<div class="file-actions">';
        $html .= '<a href="' . htmlspecialchars($file['file_path']) . '" target="_blank" class="btn btn-sm btn-primary" title="Download">';
        $html .= '<i class="fas fa-download"></i>';
        $html .= '</a>';
        
        if ($file['is_image']) {
            $html .= '<button class="btn btn-sm btn-secondary" onclick="openFileViewer(\'' . htmlspecialchars($file['file_path']) . '\', \'' . htmlspecialchars($file['original_filename']) . '\')" title="View">';
            $html .= '<i class="fas fa-eye"></i>';
            $html .= '</button>';
        }
        
        if ($showDelete) {
            $html .= '<button class="btn btn-sm btn-danger" onclick="deleteFile(' . $file['id'] . ', \'' . $entityType . '\', ' . $entityId . ')" title="Delete">';
            $html .= '<i class="fas fa-trash"></i>';
            $html .= '</button>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    public function renderUploadForm($entityType, $entityId, $options = []) {
        $multiple = $options['multiple'] ?? true;
        $accept = $options['accept'] ?? '';
        $maxFiles = $options['max_files'] ?? 5;
        $showDescription = $options['show_description'] ?? true;

        $html = '<div class="upload-form" data-entity-type="' . htmlspecialchars($entityType) . '" data-entity-id="' . $entityId . '">';
        
        $html .= '<div class="upload-area" id="upload-area-' . $entityType . '-' . $entityId . '">';
        $html .= '<div class="upload-prompt">';
        $html .= '<i class="fas fa-cloud-upload-alt"></i>';
        $html .= '<p>Drag and drop files here or click to browse</p>';
        $html .= '<small>Supported formats: Images (JPG, PNG, GIF), Videos (MP4, AVI, MOV), Documents (PDF, DOC, XLS)</small>';
        $html .= '</div>';
        $html .= '<input type="file" class="file-input" ' . ($multiple ? 'multiple' : '') . ' accept="' . $accept . '" style="display: none;">';
        $html .= '</div>';
        
        if ($showDescription) {
            $html .= '<div class="upload-description">';
            $html .= '<input type="text" class="form-input" placeholder="File description (optional)" id="file-description-' . $entityType . '-' . $entityId . '">';
            $html .= '</div>';
        }
        
        $html .= '<div class="upload-progress" style="display: none;">';
        $html .= '<div class="progress-bar">';
        $html .= '<div class="progress-fill"></div>';
        $html .= '</div>';
        $html .= '<div class="progress-text">0%</div>';
        $html .= '</div>';
        
        $html .= '<div class="upload-queue"></div>';
        
        $html .= '</div>';
        
        return $html;
    }

    public function renderFileViewerModal() {
        $html = '<div id="file-viewer-modal" class="modal-overlay" style="display: none;">';
        $html .= '<div class="modal file-viewer-modal">';
        $html .= '<div class="modal-header">';
        $html .= '<h3 id="file-viewer-title"></h3>';
        $html .= '<button class="modal-close" onclick="closeFileViewer()">&times;</button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<div id="file-viewer-content"></div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    public function getUploadJavaScript($entityType, $entityId) {
        $js = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeFileUpload('$entityType', $entityId);
        });
        
        function initializeFileUpload(entityType, entityId) {
            const uploadArea = document.getElementById('upload-area-' + entityType + '-' + entityId);
            if (!uploadArea) return;
            
            const fileInput = uploadArea.querySelector('.file-input');
            const uploadQueue = uploadArea.parentElement.querySelector('.upload-queue');
            const progressBar = uploadArea.parentElement.querySelector('.upload-progress');
            const progressFill = progressBar.querySelector('.progress-fill');
            const progressText = progressBar.querySelector('.progress-text');
            
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                handleFiles(files);
            });
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                handleFiles(this.files);
            });
            
            function handleFiles(files) {
                Array.from(files).forEach(file => {
                    uploadFile(file);
                });
            }
            
            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('entity_type', entityType);
                formData.append('entity_id', entityId);
                
                const description = document.getElementById('file-description-' + entityType + '-' + entityId);
                if (description && description.value) {
                    formData.append('description', description.value);
                }
                
                // Show progress
                progressBar.style.display = 'block';
                progressFill.style.width = '0%';
                progressText.textContent = '0%';
                
                // Create upload item
                const uploadItem = document.createElement('div');
                uploadItem.className = 'upload-item';
                uploadItem.innerHTML = `
                    <div class=\"upload-item-info\">
                        <span class=\"upload-item-name\">\${file.name}</span>
                        <span class=\"upload-item-status\">Uploading...</span>
                    </div>
                    <div class=\"upload-item-progress\">
                        <div class=\"upload-item-progress-fill\"></div>
                    </div>
                `;
                uploadQueue.appendChild(uploadItem);
                
                const xhr = new XMLHttpRequest();
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        progressText.textContent = Math.round(percentComplete) + '%';
                        
                        const itemProgressFill = uploadItem.querySelector('.upload-item-progress-fill');
                        itemProgressFill.style.width = percentComplete + '%';
                    }
                });
                
                xhr.addEventListener('load', function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            uploadItem.querySelector('.upload-item-status').textContent = 'Uploaded successfully';
                            uploadItem.classList.add('success');
                            
                            // Refresh file gallery
                            loadFiles(entityType, entityId);
                            
                            // Clear description
                            if (description) {
                                description.value = '';
                            }
                        } else {
                            uploadItem.querySelector('.upload-item-status').textContent = 'Upload failed: ' + response.message;
                            uploadItem.classList.add('error');
                        }
                    } else {
                        uploadItem.querySelector('.upload-item-status').textContent = 'Upload failed';
                        uploadItem.classList.add('error');
                    }
                    
                    // Hide progress after delay
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        uploadItem.remove();
                    }, 3000);
                });
                
                xhr.addEventListener('error', function() {
                    uploadItem.querySelector('.upload-item-status').textContent = 'Upload failed';
                    uploadItem.classList.add('error');
                    
                    setTimeout(() => {
                        progressBar.style.display = 'none';
                        uploadItem.remove();
                    }, 3000);
                });
                
                xhr.open('POST', 'api/upload.php');
                xhr.send(formData);
            }
        }
        
        function loadFiles(entityType, entityId) {
            fetch(`api/upload.php?entity_type=\${entityType}&entity_id=\${entityId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error loading files:', error);
                });
        }
        
        function deleteFile(fileId, entityType, entityId) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch(`api/upload.php?id=\${fileId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadFiles(entityType, entityId);
                    } else {
                        alert('Error deleting file: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting file:', error);
                    alert('Error deleting file');
                });
            }
        }
        
        function openFileViewer(filePath, fileName) {
            const modal = document.getElementById('file-viewer-modal');
            const title = document.getElementById('file-viewer-title');
            const content = document.getElementById('file-viewer-content');
            
            title.textContent = fileName;
            
            const extension = filePath.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
            
            if (imageExtensions.includes(extension)) {
                content.innerHTML = `<img src=\"\${filePath}\" alt=\"\${fileName}\" style=\"max-width: 100%; max-height: 80vh;\">`;
            } else {
                content.innerHTML = `<p>Preview not available for this file type.</p><a href=\"\${filePath}\" target=\"_blank\" class=\"btn btn-primary\">Download File</a>`;
            }
            
            modal.style.display = 'flex';
        }
        
        function closeFileViewer() {
            document.getElementById('file-viewer-modal').style.display = 'none';
        }
        </script>
        ";
        
        return $js;
    }
}
?>
