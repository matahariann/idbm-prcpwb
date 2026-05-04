import axios from 'axios';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers';

export default class ServiceForm {
    #serviceId = null;
    #serviceModal = new bootstrap.Modal(document.getElementById('service-modal'));
    #serviceForm = document.getElementById('service-form');
    #onSuccessCallback = null;
    #serviceEndpoint = 'HITUAM/bd/master-service';

    constructor(onSuccessCallback = null) {
        this.#onSuccessCallback = onSuccessCallback;
        this.#events();
    }

    async openModal(serviceId = null) {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');
        $('#menu').empty().trigger('change');
        $('#service-title').text(serviceId ? 'Edit Service' : 'Create Service');

        this.#serviceForm.reset();
        this.#serviceId = serviceId;

        if (this.#serviceId) {
            await this.#getServiceById();
        }

        this.#serviceModal.show();
    }

    #events() {
        this.#serviceForm.addEventListener('submit', event => {
            event.preventDefault();
            this.#submitForm();
        });
    }

    #submitForm() {
        const formData = new FormData(this.#serviceForm);

        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        if (this.#serviceId) {
            data['_method'] = 'PUT';
        }

        const url = this.#serviceEndpoint + (this.#serviceId ? `/${this.#serviceId}` : '');

        axios
            .post(url, data, {})
            .then(response => {
                $('#hituamf004-table').DataTable().ajax.reload();
                toast.success(response.data.message);
                if (this.#onSuccessCallback) {
                    this.#onSuccessCallback();
                }

                this.#serviceModal.hide();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response.data.message);
                }
            });
    }

    async #getServiceById() {
        const response = await axios.get(this.#serviceEndpoint + `/${this.#serviceId}`);
        const data = response.data.data;

        $('#name').val(data.VNAME);
        $('#description').val(data.VDESC);
        $('#url').val(data.VURL);
        $('#method').val(data.VMETHOD);
        $('#begin').val(data.DBEGINEFF);
        $('#end').val(data.DENDEFF);

        const select = $('#menu');

        let option = new Option(data.menu.VAPPDESC, data.menu.IID, true, true);
        select.append(option).trigger('change');
    }
}
