<!-- Modal -->
<div class="modal fade" id="tncDelivery" tabindex="-1" aria-labelledby="tncDeliveryLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="tncDeliveryLabel">
                    Syarat dan Ketentuan Delivery Supplier
                </h5>
                {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
            </div>

            <div class="modal-body" style="height: 400px; overflow-y:scroll;">
                {!! $tnc_verify_po_non_po !!}
            </div>

            <div class="modal-footer">
                {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Tutup
                </button> --}}
                <button type="button" class="btn btn-primary" id="closeTncDeliveryModal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
