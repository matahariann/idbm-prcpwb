import axios, { formToJSON } from 'axios';
import { _formToJson, _sanitizeMins, _showInvalidError, toast } from '../../../../helpers';
import { distanceAndSkiddingToXY } from '@popperjs/core/lib/modifiers/offset';

export default class SupplierForm {
    #supplierId = null;
    #supplierModal = new bootstrap.Modal(document.getElementById('supplier-modal'));
    #importModal = new bootstrap.Modal(document.getElementById('import-modal'));
    #importErrorModal = new bootstrap.Modal(document.getElementById('error-import-modal'));
    #supplierForm = document.getElementById('supplier-form');
    #importForm = document.getElementById('import-form');
    #endPoint = '/FACTWM/bd/master-vendor';
    #supplierTable = $('#factwmf002-table');

    constructor() {
        this.#events();
        _sanitizeMins();
    }

    #events() {
        const self = this;
        $(document).on('click', '#btn-import', function () {
            self.#importModal.show();
        });

        $(document).on('click', '#template-download', function () {
            window.open(self.#endPoint + '/template');
        });

        $(document).on('click', '#npwp-check', function () {
            const checked = $(this).prop('checked');

            if (checked) {
                // Tidak punya NPWP, tampilkan NIK dan sembunyikan NPWP
                $('#nik-input').removeClass('d-none');
                $('#npwp').closest('.row').addClass('d-none');
                $('#npwp').val('');
                $('#nik').prop('disabled', false);
            } else {
                // Punya NPWP, sembunyikan NIK dan tampilkan NPWP
                $('#nik-input').addClass('d-none');
                $('#npwp').closest('.row').removeClass('d-none');
                $('#nik').val('');
                $('#npwp').prop('disabled', false);
            }
        });

        // Validasi input NIK - hanya angka dan maksimal 16 karakter
        $(document).on('input', '#nik', function () {
            let value = $(this).val();
            // Hapus karakter non-angka
            value = value.replace(/\D/g, '');
            // Batasi maksimal 16 karakter
            if (value.length > 16) {
                value = value.substring(0, 16);
            }
            $(this).val(value);
        });

        // Validasi input NPWP - hanya angka dan maksimal 16 karakter
        $(document).on('input', '#npwp', function () {
            let value = $(this).val();
            // Hapus karakter non-angka
            value = value.replace(/\D/g, '');
            // Batasi maksimal 16 karakter
            if (value.length > 16) {
                value = value.substring(0, 16);
            }
            $(this).val(value);
        });

        this.#supplierForm.addEventListener('submit', function (e) {
            e.preventDefault();
            self.#submitForm();
        });

        this.#importForm.addEventListener('submit', function (e) {
            e.preventDefault();
            self.#submitImport();
        });


    }

    async openModal(supplierId = null) {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#nik-input').addClass('d-none');
        $('#npwp').closest('.row').removeClass('d-none');

        this.#supplierForm.reset();
        this.#supplierId = supplierId;

        // Reset states
        $('#npwp-check').prop('checked', false);
        $('#pkp-no').prop('checked', true); // Default Non PKP
        $('#npwp').prop('disabled', false);
        $('#nik').prop('disabled', false);

        if (supplierId) {
            await this.#getSupplierById();
        }

        this.#supplierModal.show();
    }

    #submitForm() {
        const jsonData = _formToJson(this.#supplierForm);

        let url = this.#endPoint;

        // Ambil nilai PKP dari radio button
        jsonData['pkp'] = $('input[name="pkp"]:checked').val();

        // Jika checkbox "Tidak punya NPWP" dicentang, kirim NIK, kosongkan NPWP
        if ($('#npwp-check').prop('checked')) {
            jsonData['npwp'] = null;
            jsonData['nik'] = $('#nik').val();
        } else {
            // Jika punya NPWP, kirim NPWP, kosongkan NIK
            jsonData['npwp'] = $('#npwp').val();
            jsonData['nik'] = null;
        }

        if (this.#supplierId) {
            url += `/${this.#supplierId}`;
            jsonData['_method'] = 'PUT';
        }

        axios
            .post(url, jsonData)
            .then(response => {
                this.#supplierTable.DataTable().ajax.reload();
                toast.success(response.data.message);
                $("#btn-delete-selected").removeClass("disabled");

                this.#supplierModal.hide();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            });
    }

    #submitImport() {
        const formModal = new FormData(this.#importForm);

        axios
            .post(this.#endPoint + '/import', formModal, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                this.#importModal.hide();
                this.#supplierTable.DataTable().ajax.reload();
                toast.success(response.data.message);
            })
            .catch(error => {
                if (error.response && error.response.status === 400) {
                    this.#openImportErrorModal(error.response.data.data);
                    this.#importForm.reset();
                } else {
                    toast.error(error.response.data.message);
                    this.#importForm.reset();
                }
            })
            .finally(() => {
                this.#importForm.reset();
            });
    }

    async #getSupplierById() {
        const response = await axios.get(this.#endPoint + `/${this.#supplierId}`);
        const data = response.data.data;

        $('#supplier_code').val(data.VSUPPLIER_CODE);
        $('#supplier_name').val(data.VNAME);

        // Cek apakah vendor punya NPWP atau NIK
        const hasNIK = data.VNIK !== null && data.VNIK !== '';
        const hasNPWP = data.VNPWP !== null && data.VNPWP !== '';

        if (hasNIK && !hasNPWP) {
            // Tidak punya NPWP, punya NIK - tampilkan NIK, sembunyikan NPWP
            $('#npwp-check').prop('checked', true);
            $('#nik-input').removeClass('d-none');
            $('#npwp').closest('.row').addClass('d-none');
            $('#nik').val(data.VNIK);
            $('#npwp').val('');
        } else {
            // Punya NPWP atau keduanya kosong - tampilkan NPWP, sembunyikan NIK
            $('#npwp-check').prop('checked', false);
            $('#nik-input').addClass('d-none');
            $('#npwp').closest('.row').removeClass('d-none');
            $('#npwp').val(data.VNPWP || '');
            $('#nik').val('');
        }

        // Set PKP status dengan radio button
        if (data.BPKP === 1 || data.BPKP === true || data.BPKP === '1') {
            $('#pkp-yes').prop('checked', true);
        } else {
            $('#pkp-no').prop('checked', true);
        }
    }

    #openImportErrorModal(data) {
        this.#importModal.hide();
        this.#importErrorModal.show();
        const errorData = data.error_data.map(item => ({
            ...item,
            errors: `<ul class="text-danger">${item.errors.map(error => `<li>${error}</li>`).join('')}</ul>`
        }));

        $('#import-error-table').DataTable({
            data: errorData,
            dom:
                'r' +
                "<'table-responsive border-top'tr>" +
                "<'d-flex align-items-center justify-content-center justify-content-lg-between flex-wrap gap-2 text-center px-6 mt-6'ip>",
            columns: [
                { title: 'Row', data: 'row' },
                { title: 'Vendor Code', data: 'vendor_code' },
                { title: 'NPWP', data: 'npwp' },
                { title: 'NIK', data: 'nik' },
                { title: 'Errors', data: 'errors' }
            ],
            destroy: true,
            pageLength: 10,
            ajax: false
        });
    }
}
