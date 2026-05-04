import axios from 'axios';
import Quill from "quill";
import 'quill/dist/quill.snow.css';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

export default class InformationForm {
    quill;
    #information = window.APP_CONFIG?.information || null;
    constructor() {
        this.selectedFile = null;
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        this.initQuill();
        this.init();
        this.flatpickrInstances = [];
    }

    initQuill() {
        const quillContainer = document.querySelector('#VNOTES');

        if (!quillContainer) {
            return;
        }

        this.quill = new Quill(quillContainer, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        [{ 'align': [] }],
                        ['link', 'image'],
                        ['clean']
                    ],
                    handlers: {
                        image: function () {
                            const input = document.createElement('input');
                            input.type = 'file';
                            input.accept = 'image/*';
                            input.click();

                            input.onchange = () => {
                                const file = input.files[0];
                                const reader = new FileReader();

                                reader.onload = () => {
                                    const range = this.quill.getSelection();
                                    this.quill.insertEmbed(range.index, 'image', reader.result);

                                    // set width default
                                    setTimeout(() => {
                                        const imgs = this.quill.root.querySelectorAll('img');
                                        const img = imgs[imgs.length - 1];
                                        img.style.width = '800px';
                                        img.style.height = 'auto';
                                    });
                                };

                                reader.readAsDataURL(file);
                            };
                        }
                    },
                },
            },
            placeholder: 'Write your content here...'
        });
    }

    init() {
        this.setupEventListeners();
        this.changeEvents();
        this.loadExistingImages();
        this.initSelect2();
        this.initFlatpickr();
        this.selectTwoEvents();

        if (window.information) {
            console.log(window.information);
            this.quill.root.innerHTML = window.information.VNOTES ?? '';
            // Set VUSER_TYPE after quill initialized
            setTimeout(() => {
                const userType = window.information.VUSER_TYPE;
                if (userType) {
                    $('#VUSER_TYPE').val(userType).trigger('change');

                    // Set VVIEWERS if it exists
                    if (window.information.VVIEWERS && userType === 'supplier') {
                        $('#VVIEWERS').val(window.information.VVIEWERS).trigger('change');
                    }
                }
            }, 500);
        }
    }

    initFlatpickr() {
        const DTOContainer = document.querySelector('#DTO');

        if (DTOContainer) {
            flatpickr(DTOContainer, {
                mode: "single",
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }

        const DFromContainer = document.querySelector('#DFROM');

        if (DFromContainer) {
            flatpickr(DFromContainer, {
                mode: "single",
                dateFormat: "Y-m-d",
                allowInput: true
            });
        }
    }

    setupEventListeners() {
        document.getElementById('btn-clear-pdf')?.addEventListener('click', () => {
            this.clearFileInput('VFILE_INFORMATION');
        });

        document.getElementById('btn-clear-vendor')?.addEventListener('click', () => {
            this.clearFileInput('VUPDLOAD_DATA_VENDOR');
        });

        document.getElementById('btn-upload-asset')?.addEventListener('click', () => {
            const fileInput = document.getElementById('VUPDLOAD_FOTO_ASSET');
            fileInput?.click();
        });

        document.getElementById('VUPDLOAD_FOTO_ASSET')?.addEventListener('change', e => {
            this.handleAssetFileSelect(e);
        });

        document.getElementById('addInformationForm')?.addEventListener('submit', e => {
            this.handleFormSubmit(e);
        });

        document.querySelector('button[type="reset"]')?.addEventListener('click', () => {
            this.resetForm();
            // $('#VUSER_TYPE').val(null).trigger('change');
            // $('#VVIEWERS').val(null).trigger('change');
            // $('#VVIEWERS').find('option').prop('disabled', false);
        });
    }

    changeEvents() {
        $(document).on('change', '#VUSER_TYPE', function () {
            const val = $(this).val();

            $('#VVIEWERS').val(null).trigger('change');

            // Show/hide VVIEWERS based on user type
            if (val === 'supplier') {
                $('#VVIEWERS').attr('disabled', false).removeAttr('disabled');
                $('#VVIEWERS-container').show();
                // Add required attribute when supplier is selected
                $('#VVIEWERS').prop('required', true);
            } else {
                // For 'all' or 'internal', hide and disable VVIEWERS
                $('#VVIEWERS').attr('disabled', true);
                $('#VVIEWERS-container').hide();
                $('#VVIEWERS').val([]).trigger('change');
                // Remove required attribute when not supplier
                $('#VVIEWERS').prop('required', false);
            }
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

        if (!this.validateFile(file)) {
            event.target.value = '';
            return;
        }

        this.selectedFile = file;
        this.updateFileNameDisplay();
        this.renderPreviews();
    }

    validateFile(file) {
        if (!this.allowedImageTypes.includes(file.type)) {
            this.showNotification(`${file.name} is not a valid image file`, 'error');
            return false;
        }

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

        if (!form.checkValidity()) {
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        this.setLoadingState(true);

        const formData = new FormData(form);
        formData.set('VNOTES', this.quill.root.innerHTML);

        // Handle VVIEWERS based on user type
        const userType = $('#VUSER_TYPE').val();
        if (userType !== 'supplier') {
            // Remove VVIEWERS from formData if not supplier
            formData.delete('VVIEWERS');
            formData.delete('VVIEWERS[]');
        }

        const action = form.getAttribute('action');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch(action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                this.setLoadingState(false);

                if (data.success) {
                    this.showNotification(data.message || 'Data saved successfully', 'success');

                    setTimeout(() => {
                        window.location.href = '/FACTWM/bd/master-information';
                    }, 1500);
                } else {
                    this.showNotification(data.message || 'Failed to save data', 'error');

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
        document.querySelectorAll('.invalid-feedback').forEach(el => {
            el.textContent = '';
        });
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const feedback = input.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = errors[field][0];
                } else {
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
            if (!submitBtn.dataset.originalText) {
                submitBtn.dataset.originalText = submitBtn.querySelector('span')?.textContent || 'Submit';
            }

            submitBtn.disabled = isLoading;

            if (isLoading) {
                submitBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span>Saving...</span>
                `;
            } else {
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
        const data = this.#information;

        // =====================
        // EDIT MODE (PUT)
        // =====================
        if (data) {
            // Reset Notes (Quill)
            this.quill.root.innerHTML = data.VNOTES ?? '';

            // Reset file upload
            this.selectedFile = null;
            this.updateFileNameDisplay(true); // true = existing file
            this.renderPreviews(true);

            // Reset date
            document.getElementById('DFROM').value = data.DFROM?.substring(0, 10) ?? '';
            document.getElementById('DTO').value = data.DTO?.substring(0, 10) ?? '';

            const userType = $('#VUSER_TYPE');

            if (data?.VUSER_TYPE) {
                userType.val(data.VUSER_TYPE).trigger('change.select2');
                if (data?.VUSER_TYPE == 'supplier') {
                    $('#VVIEWERS').attr('disabled', false).removeAttr('disabled');
                } else {
                    $('#VVIEWERS').attr('disabled', true);
                }
            } else {
                userType.val(null).trigger('change.select2');
            }

            const viewer = $('#VVIEWERS');

            if (data?.VVIEWERS) {
                const selected = Array.isArray(data.VVIEWERS)
                    ? data.VVIEWERS
                    : data.VVIEWERS.split(',');

                viewer.val(selected).trigger('change.select2');
            } else {
                viewer.val([]).trigger('change.select2');
            }


            // =====================
            // CREATE MODE (POST)
            // =====================
        } else {
            this.quill.setText('');
            this.selectedFile = null;
            this.updateFileNameDisplay();
            this.renderPreviews();
            document.getElementById('addInformationForm').reset();
        }

        // =====================
        // Clear validation UI
        // =====================
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        document.querySelectorAll('.was-validated').forEach(el => {
            el.classList.remove('was-validated');
        });
    }

    showNotification(message, type = 'info') {
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

    initSelect2() {
        $('#VUSER_TYPE').each(function () {
            const $select = $(this);
            $select.select2({
                placeholder: 'Select User Type',
                allowClear: true
            });
        });

        $('#VVIEWERS').each(function () {
            const $select = $(this);
            $select.select2({
                placeholder: 'Select Supplier',
                allowClear: true
            });
        });
    }

    selectTwoEvents() {
        $('#VVIEWERS').on('select2:select', function (e) {
            const selectedValue = e.params.data.id;
            const currentValues = $(this).val() || [];

            if (selectedValue === 'all') {
                $(this).val(['all']).trigger('change');

                $(this).find('option').each(function () {
                    if ($(this).val() !== 'all') {
                        $(this).prop('disabled', true);
                    }
                });
            } else {
                if (currentValues.includes('all')) {
                    const newValues = currentValues.filter(val => val !== 'all');
                    $(this).val(newValues).trigger('change');
                }
            }
        });

        $('#VVIEWERS').on('select2:unselect', function (e) {
            const unselectedValue = e.params.data.id;

            if (unselectedValue === 'all') {
                $(this).find('option').prop('disabled', false);
            }
        });

        $('#VVIEWERS').on('select2:clear', function () {
            $(this).find('option').prop('disabled', false);
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new InformationForm();
});
