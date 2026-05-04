
// import OCRTable from "./ocr-table";
// import OtherFiles from "./ocr-other-files";
import axios from "axios";
import { _showInvalidError, toast } from "../../../../helpers";
import { showLoadingSwal, closeSwal } from "../../../../swallLoading";
// import Validation from "./validation";

class OCR {
    #verifyPoEndPoint = 'FACTWM/ts/verify-po';
    #submitFinalPreview = document.getElementById('submit-final-preview');
    #modalSubmitted = new bootstrap.Modal(document.getElementById('submittedModal'));
    #modalTnc = new bootstrap.Modal(document.getElementById('tncDelivery'))
    #payload = window.APP_CONFIG.payload;

    constructor() {

    }

    init() {
        this.#onSubmit();
        this.#clickEvents();
    }

    #onSubmit() {
        const self = this;
        let IID = $('#IID').val();

        this.#submitFinalPreview.addEventListener('click', async function (e) {
            e.preventDefault();

            let notes = $('#notes').val();

            const result = await Swal.fire({
                title: 'Submit Info',
                text: 'Do you want to submit Invoice?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('notes', notes);
                formData.append('payload', JSON.stringify(self.#payload));

                showLoadingSwal();
                axios
                    .post(`/${self.#verifyPoEndPoint}/submit-final-preview/${IID}`, formData)
                    .then(response => {
                        const data = response.data.data;
                        self.#clearDraftCacheAfterSubmit();
                        toast.success(response.data.message);
                        self.#modalSubmitted.show();
                    })
                    .catch(error => {
                        toast.error(error.response?.data?.message || 'Error');
                        // window.location.href = `/${self.#verifyPoEndPoint}/view`;
                    }).finally(() => {
                        closeSwal();
                    })
            }
        });

    }

    #clickEvents() {
        const self = this;
        let IID = $('#IID').val();
        $(document).on('click', '#btn-ok-submitted', function () {
            self.#modalSubmitted.hide();
            window.location.href = `/${self.#verifyPoEndPoint}/view`;
            // window.location.href = `/${self.#verifyPoEndPoint}/preview-pdf/${IID}`;
        });

        $(document).on('change', '#hyperlink', function (e) {
            if ($(this).is(':checked')) {
                self.#modalTnc.show();
            }
        })

        $('#tncDelivery').on('shown.bs.modal', function () {

            const $body = $('#tncDelivery .modal-body');

            // reset
            $('#closeTncDeliveryModal').prop('disabled', true);
            $body.scrollTop(0);

            $body.off('scroll').on('scroll', function () {

                const scrollTop = this.scrollTop;
                const scrollHeight = this.scrollHeight;
                const innerHeight = this.clientHeight;

                if (scrollTop + innerHeight >= scrollHeight - 5) {
                    $('#closeTncDeliveryModal').prop('disabled', false);
                }
            });
        });


        $(document).on('click', '#closeTncDeliveryModal', function () {
            self.#modalTnc.hide();
            $('#hyperlink').prop('disabled', true);
            $('#submit-final-preview').prop('disabled', false);
        })
    }

    #clearDraftCacheAfterSubmit() {
        sessionStorage.removeItem('verify_po_ocr_form_draft');

        const cachePrefixes = [
            'laravel_cache_verify_po_last_draft_',
            'verify_po_last_draft_',
        ];

        const removeByPrefix = (storage) => {
            const keys = [];
            for (let i = 0; i < storage.length; i += 1) {
                const key = storage.key(i);
                if (cachePrefixes.some(prefix => key?.startsWith(prefix))) {
                    keys.push(key);
                }
            }
            keys.forEach(key => storage.removeItem(key));
        };

        removeByPrefix(localStorage);
        removeByPrefix(sessionStorage);
    }
}

new OCR().init();
