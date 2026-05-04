import axios from 'axios';
import { _formToJson, _showInvalidError, toast } from '../../../../helpers';

export default class GoodReceiptForm {
    #grNoteId = null;
    #disputeModal = new bootstrap.Modal(document.getElementById('dispute-modal'));
    #disputeForm = document.getElementById('dispute-form');
    #endPoint = 'FACTWM/ts/good-receipt-notes';
    #grNotesTable = $('#factwmf006-table');

    constructor() {
        this.#events();
    }

    #events() {
        const self = this;

        // Handle file input change
        $(document).on('change', '#dispute-file', function () {
            let fileName = $(this).val().split('\\').pop();
            $('#dispute-file-label').text(fileName || 'No file choosen');
        });

        // Handle dispute form submission
        this.#disputeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            self.#submitDisputeForm();
        });

        // Handle modal close
        document.getElementById('dispute-modal').addEventListener('hidden.bs.modal', function () {
            self.#resetDisputeForm();
        });
    }

    async openModal(grNoteId = null) {
        $('.invalid-feedback').remove();
        $('.is-invalid').removeClass('is-invalid');

        this.#disputeForm.reset();
        this.#grNoteId = grNoteId;

        if (grNoteId) {
            await this.#getGrNoteById();
        }

        this.#disputeModal.show();
    }

    async #getGrNoteById() {
        try {
            const response = await axios.get(`${this.#endPoint}/${this.#grNoteId}`);
            const data = response.data.data;

            // Set form data
            $('#dispute-form').data('id', this.#grNoteId);
            $('#dispute-grn').val(data.VGR_NUMBER);
            $('#dispute-description').val('');
            $('#dispute-file').val('');
            $('#dispute-file-label').text('No file choosen');
        } catch (error) {
            console.error(error);
            toast.error('Failed to load GR Note data');
            this.#disputeModal.hide();
        }
    }

    #submitDisputeForm() {
        const id = this.#grNoteId;
        const description = $('#dispute-description').val();
        const file = $('#dispute-file')[0].files[0];

        if (!description.trim()) {
            toast.error('Description is required');
            return;
        }

        // Prepare FormData for email
        const emailFormData = new FormData();
        emailFormData.append('grNoteId', id);
        emailFormData.append('grNumber', $('#dispute-grn').val());
        emailFormData.append('description', description);
        if (file) {
            emailFormData.append('file', file);
        }

        axios
            .post(`${this.#endPoint}/dispute`, emailFormData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            .then(response => {
                toast.success('Dispute submitted and email sent successfully');
                this.#grNotesTable.DataTable().ajax.reload();
                this.#disputeModal.hide();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    _showInvalidError(error.response.data.errors);
                } else {
                    toast.error(error.response?.data?.message || 'Failed to send dispute email');
                }
            });
    }

    #resetDisputeForm() {
        this.#disputeForm.reset();
        this.#grNoteId = null;
        $('#dispute-grn').val('');
        $('#dispute-description').val('');
        $('#dispute-file').val('');
        $('#dispute-file-label').text('No file choosen');
    }
}
