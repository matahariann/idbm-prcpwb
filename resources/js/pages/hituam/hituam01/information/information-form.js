import axios from 'axios';

export default class InformationForm {
    constructor() {
        this.selectedFile = null;
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadExistingImages();
    }

    setupEventListeners() {
        // Clear PDF file input
        document.getElementById('btn-clear-pdf')?.addEventListener('click', () => {
            this.clearFileInput('VFILE_INFORMATION');
        });

        // Clear Vendor file input
        document.getElementById('btn-clear-vendor')?.addEventListener('click', () => {
            this.clearFileInput('VUPDLOAD_DATA_VENDOR');
        });

        // Upload Asset button handler
        document.getElementById('btn-upload-asset')?.addEventListener('click', () => {
            console.log('Upload button clicked');
            const fileInput = document.getElementById('VUPDLOAD_FOTO_ASSET');
            console.log('File input element:', fileInput);
            fileInput?.click();
        });

        // Handle file selection
        document.getElementById('VUPDLOAD_FOTO_ASSET')?.addEventListener('change', e => {
            console.log('File selected:', e);
            this.handleAssetFileSelect(e);
        });

        // Form submission
        document.getElementById('addInformationForm')?.addEventListener('submit', e => {
            this.handleFormSubmit(e);
        });

        // Reset form
        document.querySelector('button[type="reset"]')?.addEventListener('click', () => {
            this.resetForm();
        });
    }

    clearFileInput(inputId) {
        const fileInput = document.getElementById(inputId);
        if (fileInput) {
            fileInput.value = '';
        }
    }

    handleAssetFileSelect(event) {
        const file = event.target.files[0];

        if (!file) return;

        // Validate file
        if (!this.validateFile(file)) {
            event.target.value = '';
            return;
        }

        this.selectedFile = file;
        this.updateFileNameDisplay();
        this.renderPreviews();
    }

    validateFile(file) {
        // Check file type
        if (!this.allowedImageTypes.includes(file.type)) {
            this.showNotification(`${file.name} is not a valid image file`, 'error');
            return false;
        }

        // Check file size
        if (file.size > this.maxFileSize) {
            this.showNotification(`${file.name} exceeds maximum file size of 5MB`, 'error');
            return false;
        }

        return true;
    }

    updateFileNameDisplay() {
        const fileNameElement = document.getElementById('asset-file-name');
        if (!fileNameElement) return;

        if (this.selectedFile) {
            fileNameElement.textContent = this.selectedFile.name;
        } else {
            fileNameElement.textContent = 'No file chosen';
        }
    }

    renderPreviews() {
        const previewContainer = document.getElementById('preview-container');
        if (!previewContainer) return;

        // Clear previous new previews, keep existing ones
        const newPreviews = previewContainer.querySelectorAll('.preview-item:not(.existing-preview)');
        newPreviews.forEach(el => el.remove());

        if (!this.selectedFile) return;

        const reader = new FileReader();

        reader.onload = e => {
            const previewItem = this.createPreviewItem(e.target.result, this.selectedFile.name);
            previewContainer.appendChild(previewItem);
        };

        reader.readAsDataURL(this.selectedFile);
    }

    createPreviewItem(imageSrc, fileName) {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item position-relative';
        previewItem.style.cssText = `
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        `;

        const img = document.createElement('img');
        img.src = imageSrc;
        img.alt = fileName;
        img.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: cover;
        `;

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger position-absolute';
        removeBtn.style.cssText = `
            top: 5px;
            right: 5px;
            z-index: 10;
            padding: 0.25rem 0.5rem;
        `;
        removeBtn.innerHTML = '<i class="ti ti-trash"></i>';
        removeBtn.addEventListener('click', e => {
            e.preventDefault();
            this.removeFile();
        });

        const fileNameOverlay = document.createElement('div');
        fileNameOverlay.className = 'position-absolute bottom-0 start-0 end-0';
        fileNameOverlay.style.cssText = `
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
            font-size: 0.75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        `;
        fileNameOverlay.textContent = fileName;

        previewItem.appendChild(img);
        previewItem.appendChild(removeBtn);
        previewItem.appendChild(fileNameOverlay);

        return previewItem;
    }

    removeFile() {
        this.selectedFile = null;
        this.updateFileNameDisplay();
        this.renderPreviews();
        document.getElementById('VUPDLOAD_FOTO_ASSET').value = '';
    }

    loadExistingImages() {
        // Load existing images in edit mode
        const existingImage = document.querySelector('[data-existing-image]');
        if (existingImage) {
            const imageSrc = existingImage.getAttribute('data-existing-image');
            const fileName = existingImage.getAttribute('data-file-name') || 'Existing Image';
            const previewItem = this.createExistingPreviewItem(imageSrc, fileName);
            document.getElementById('preview-container')?.appendChild(previewItem);
        }
    }

    createExistingPreviewItem(imageSrc, fileName) {
        const previewItem = document.createElement('div');
        previewItem.className = 'preview-item existing-preview position-relative';
        previewItem.style.cssText = `
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        `;

        const img = document.createElement('img');
        img.src = imageSrc;
        img.alt = fileName;
        img.style.cssText = `
            width: 100%;
            height: 100%;
            object-fit: cover;
        `;

        const badge = document.createElement('span');
        badge.className = 'badge bg-success position-absolute';
        badge.style.cssText = 'top: 5px; left: 5px; z-index: 10;';
        badge.textContent = 'Existing';

        const fileNameOverlay = document.createElement('div');
        fileNameOverlay.className = 'position-absolute bottom-0 start-0 end-0';
        fileNameOverlay.style.cssText = `
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem;
            font-size: 0.75rem;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
        `;
        fileNameOverlay.textContent = fileName;

        previewItem.appendChild(img);
        previewItem.appendChild(badge);
        previewItem.appendChild(fileNameOverlay);

        return previewItem;
    }

    handleFormSubmit(event) {
        event.preventDefault();

        const form = event.target;

        // Validate form
        if (!form.checkValidity()) {
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        // Show loading state
        this.setLoadingState(true);

        // Create FormData
        const formData = new FormData(form);

        // Debug: Log FormData content
        console.log('FormData content:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }

        // Get form action and method
        const action = form.getAttribute('action');

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Submit form using fetch
        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json'
            }
        })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                this.setLoadingState(false);

                if (data.success) {
                    this.showNotification(data.message || 'Data saved successfully', 'success');

                    // Redirect after 1.5 seconds
                    setTimeout(() => {
                        window.location.href = '/HITUAM/bd/master-information';
                    }, 1500);
                } else {
                    this.showNotification(data.message || 'Failed to save data', 'error');

                    // Display validation errors
                    if (data.errors) {
                        this.displayValidationErrors(data.errors);
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                this.setLoadingState(false);
                this.showNotification('An error occurred. Please try again.', 'error');
            });
    }

    displayValidationErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Display new errors
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = errors[field][0];
                } else {
                    // Create feedback element if it doesn't exist
                    const feedbackEl = document.createElement('div');
                    feedbackEl.className = 'invalid-feedback d-block';
                    feedbackEl.textContent = errors[field][0];
                    input.parentElement.appendChild(feedbackEl);
                }
            }
        });
    }

    setLoadingState(isLoading) {
        const submitBtn = document.querySelector('button[type="submit"]');
        const resetBtn = document.querySelector('button[type="reset"]');

        if (submitBtn) {
            // PERBAIKAN: Simpan teks asli saat pertama kali
            if (!submitBtn.dataset.originalText) {
                submitBtn.dataset.originalText = submitBtn.querySelector('span')?.textContent || 'Submit';
            }

            submitBtn.disabled = isLoading;

            if (isLoading) {
                // Saat loading: hanya tampilkan spinner dan teks "Saving..."
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Saving...</span>
                `;
            } else {
                // Saat selesai: kembalikan ke teks asli
                submitBtn.innerHTML = `
                    <span>${submitBtn.dataset.originalText}</span>
                `;
            }
        }

        if (resetBtn) {
            resetBtn.disabled = isLoading;
        }
    }

    resetForm() {
        this.selectedFile = null;
        this.updateFileNameDisplay();
        this.renderPreviews();

        // Clear validation states
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.was-validated').forEach(el => {
            el.classList.remove('was-validated');
        });
    }

    showNotification(message, type = 'info') {
        // Using toast if available
        if (typeof toast !== 'undefined') {
            toast[type](message);
        } else if (typeof Swal !== 'undefined') {
            const iconMap = {
                success: 'success',
                error: 'error',
                warning: 'warning',
                info: 'info'
            };

            Swal.fire({
                title: type.charAt(0).toUpperCase() + type.slice(1),
                text: message,
                icon: iconMap[type] || 'info',
                confirmButtonText: 'OK',
                timer: 3000
            });
        } else {
            alert(message);
        }
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new InformationForm();
    });
} else {
    new InformationForm();
}
