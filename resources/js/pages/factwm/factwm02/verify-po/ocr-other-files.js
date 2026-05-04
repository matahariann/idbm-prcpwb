import Swal from "sweetalert2";
import { toast } from "../../../../helpers";

export default class OtherFiles {
    #uploadRequirementModal = new bootstrap.Modal(document.getElementById('other-file-warning-modal'));

    files = [];
    existingFiles = [];
    deletedExistingFileIds = [];

    #inputFileElement = null;
    #buttonSubmit = false;

    init() {
        this.#events();
        this.#renderOtherFileList();
    }

    #events() {
        const self = this;

        // $(document).on('click', '#other-file-upload-button', function () {
        //     self.#uploadRequirementModal.show();
        //     self.#inputFileElement = $('#other_file');
        // });

        // $(document).on('click', '#btn-accept-requirement', function () {
        //     self.#uploadRequirementModal.hide();
        //     self.#inputFileElement.click();
        // });

        $(document).on('change', '#other-file', function () {
            const file = this.files[0];

            if (!file) return;

            // Set text input value to file name (without extension)
            const fileName = file.name.replace(/\.[^/.]+$/, '');

            self.#buttonSubmit = true;

            $('#other-file-name').val(fileName);
            self.#setOtherFileNameEditable(true);
        });

        // $(document).on('change', '#invoice_file', function () {
        //     const file = this.files[0];

        //     if (!file) return;

        //     // Set text input value to file name (without extension)
        //     const fileName = file.name.replace(/\.[^/.]+$/, '');

        //     if (!self.#appendFileValidation(file, fileName)) {
        //         return;
        //     }

        //     $('#invoice-file-name').val(fileName);
        // });

        // $(document).on('change', '#tax_file', function () {
        //     console.log("file tax");
        //     const file = this.files[0];

        //     if (!file) return;

        //     // Set text input value to file name (without extension)
        //     const fileName = file.name.replace(/\.[^/.]+$/, '');

        //     if (!self.#appendFileValidation(file, fileName)) {
        //         return;
        //     }

        //     $('#tax-file-name').val(fileName);
        // });

        // $(document).on('change', '#rekap_jasa_file', function () {
        //     const file = this.files[0];

        //     if (!file) return;

        //     // Set text input value to file name (without extension)
        //     const fileName = file.name.replace(/\.[^/.]+$/, '');

        //     if (!self.#appendFileValidation(file, fileName)) {
        //         return;
        //     }

        //     $('#pph-file-name').val(fileName);
        // });

        $(document).on('click', '#add-other-file-button', function () {
            self.#appendFile();
        });

        $(document).on('click', '.delete-other-file', function () {
            const index = Number($(this).data('index'));
            self.#removeOtherFile(index);
        });

        $(document).on('click', '.delete-existing-other-file', function () {
            const id = Number($(this).data('id'));
            self.#removeExistingOtherFile(id);
        });
    }

    #appendFile() {
        const fileInput = $('#other-file')[0];
        const file = fileInput.files[0];
        const newName = $('#other-file-name').val().trim();

        if (!file) {
            toast.error('Please select a file');
            return false;
        }

        if (!this.appendFileValidation(file, newName)) {
            return;
        }

        this.#buttonSubmit = false;

        this.#storeOtherFile(file, newName);
        this.#renderOtherFileList();
        this.#resetOtherFileInputs();
    }

    appendFileValidation(file, newName) {
        const MAX_SIZE_MB = 2;
        const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

        const isPdf =
            file.type === 'application/pdf' ||
            file.name.toLowerCase().endsWith('.pdf');

        if (!file) {
            toast.error('Please select a file');
            return false;
        }

        if (!isPdf) {
            toast.error('Only PDF files are allowed');
            return false;
        }

        if (file.size > MAX_SIZE_BYTES) {
            toast.error(`File size must not exceed ${MAX_SIZE_MB} MB`);
            return false;
        }

        if (!newName) {
            toast.error('Please enter a file name');
            return false;
        }

        const isExist = this.files.some(item => item.name === newName);
        const isExistInExisting = this.existingFiles.some(item => item.name === newName);

        if (isExist || isExistInExisting) {
            toast.error('File name already exists');
            return false;
        }

        return true;
    }

    #storeOtherFile(file, newName) {
        this.files.push({
            id: Date.now(),
            file: file,
            name: newName
        });
    }

    #removeOtherFile(index) {
        if (Number.isNaN(index) || index < 0) return;
        this.files.splice(index, 1);
        this.#renderOtherFileList();
    }

    #removeExistingOtherFile(id) {
        if (Number.isNaN(id) || id <= 0) return;

        this.existingFiles = this.existingFiles.filter(item => Number(item.id) !== id);
        if (!this.deletedExistingFileIds.includes(id)) {
            this.deletedExistingFileIds.push(id);
        }

        this.#renderOtherFileList();
    }

    #resetOtherFileInputs() {
        $('#other-file').val('');
        $('#other-file-name').val('');
        this.#setOtherFileNameEditable(false);
    }

    #setOtherFileNameEditable(isEditable) {
        const $input = $('#other-file-name');
        $input.prop('readonly', !isEditable);
        $input.toggleClass('bg-light', !isEditable);
    }

    #renderOtherFileList() {
        $('#other-file-list').empty();

        $.each(this.existingFiles, function (index, item) {
            $('#other-file-list').append(`
                <div class="card col-12 col-md-2">
                    <div class="card-body justify-content-center">
                        <div class="card bg-gray d-flex align-items-center justify-content-center">
                            <i class="menu icon ti tabler-file fs-1 m-5"></i>
                        </div>
                        <div class="text-center">
                            <strong>${item.name}</strong>
                        </div>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-danger delete-existing-other-file" data-id="${item.id}">
                                <i class="menu-icon ti tabler-trash"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            `);
        });

        $.each(this.files, function (index, item) {
            $('#other-file-list').append(`
                <div class="card col-12 col-md-2">
                    <div class="card-body justify-content-center">
                        <div class="card bg-gray d-flex align-items-center justify-content-center">
                            <i class="menu icon ti tabler-file fs-1 m-5"></i>
                        </div>
                        <div class="text-center">
                            <strong>${item.name}</strong>
                        </div>
                        <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-danger delete-other-file" data-index="${index}">
                            <i class="menu-icon ti tabler-trash"></i>
                            Delete
                        </button>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    get buttonSubmit() {
        return this.#buttonSubmit;
    }


    checkButtonSubmit() {
        return this.buttonSubmit;
    }

    setExistingFiles(files = []) {
        this.existingFiles = Array.isArray(files) ? files : [];
        this.#renderOtherFileList();
    }
}
