import axios from 'axios';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers';

export default class ChangeRequestForm {
    #requestId = null;
    #requestModal = new bootstrap.Modal(document.getElementById('request-modal'));
    #requestForm = document.getElementById('request-form');
    #onSuccessCallback = null;
    #requestEndpoint = 'FACTWM/bd/change-request-vendor';
    #requestTable = $('#request-table');

    constructor(onSuccessCallback = null) {
        this.#onSuccessCallback = onSuccessCallback;
        this.#events();
    }

    openModal(requestId = null) {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');

        this.#requestForm.reset();
        this.#requestId = requestId;

        this.#requestModal.show();
    }

    #events() {
        this.#requestForm.addEventListener('submit', event => {
            event.preventDefault();
            this.#submitForm();
        });
    }

    #submitForm() {
        const formData = new FormData(this.#requestForm);

        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        data['request_type'] = 'add';

        if (this.#requestId) {
            data['_method'] = 'PUT';
        }

        const url = this.#requestEndpoint + (this.#requestId ? `/${this.#requestId}` : '');

        axios
            .post(url, data, {})
            .then(response => {
                $('#factwmf003-table').DataTable().ajax.reload();
                toast.success(response.data.message);
                if (this.#onSuccessCallback) {
                    this.#onSuccessCallback();
                }

                this.#requestModal.hide();
                this.#requestTable.DataTable().ajax.reload();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    console.log(error);
                    toast.error(error.response.data.message);
                }
            });
    }
}
