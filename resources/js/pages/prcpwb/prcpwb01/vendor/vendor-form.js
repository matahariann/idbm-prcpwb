import axios from 'axios';
import { _formToJson, _sanitizeMins, _showInvalidError, toast } from '../../../../helpers';

export default class VendorForm {
    #vendorTable = $('#prcpwbf002-table');
    #vendorId = null;
    #vendorModal = new bootstrap.Modal(document.getElementById('vendor-modal'));
    #vendorForm = document.getElementById('vendor-form');
    #endPoint = '/PRCPWB/bd/master-vendor';

    constructor() {
        this.#events();
        _sanitizeMins();
    }

    #events() {
        const self = this;

        this.#vendorForm.addEventListener('submit', function (e) {
            e.preventDefault();
            self.#submitForm();
        });
    }

    async openModal(vendorId = null) {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');

        this.#vendorForm.reset();
        this.#vendorId = vendorId;

        // Reset import ke default (false / No)
        $('#import-no').prop('checked', true);

        if (vendorId) {
            await this.#getVendorById();
        }

        this.#vendorModal.show();
    }

    #submitForm() {
        const jsonData = _formToJson(this.#vendorForm);

        let url = this.#endPoint;

        // Ambil nilai import dari radio button
        jsonData['import'] = $('#import-check').is(':checked') ? 'TRUE' : 'FALSE';

        if (this.#vendorId) {
            url += `/${this.#vendorId}`;
            jsonData['_method'] = 'PUT';
        }

        axios
            .post(url, jsonData)
            .then(response => {
                this.#vendorTable.DataTable().ajax.reload();
                toast.success(response.data.message);
                this.#vendorModal.hide();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            });
    }

    async #getVendorById() {
        const response = await axios.get(this.#endPoint + `/${this.#vendorId}`);
        const data = response.data.data;
        const isImport = data.VIMPORT === 'TRUE';

        $('#vendor_no').val(data.VVENDORNO);
        $('#vendor_name').val(data.VVENDORNAME);
        $('#contact').val(data.VCONTACT);
        $('#address').val(data.VADDRESS);
        $('#import-check').prop('checked', isImport);
    }
}