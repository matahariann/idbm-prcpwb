<!-- Modal -->
<div class="modal fade" id="submittedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title w-100 text-center text-white pb-4">
                    Your invoice has been successfully submitted. <br>
                    Below is the summary from your submission:
                </h5>
                <button type="button" class="btn-close btn-primary" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <table class="table table-bordered">
                            <colgroup>
                                <col style="width: 40%">
                                <col style="width: 5%">
                                <col style="width: 55%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>Amount Sub</td>
                                    <td>:</td>
                                    <td>{{ number_format($verifyPo->INET_AMOUNT ?? 0, 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td>PPN</td>
                                    <td>:</td>
                                    <td>{{ number_format($verifyPo->IPPN ?? 0, 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td>DPP PPH</td>
                                    <td>:</td>
                                    <td>{{ number_format($verifyPo->IDPP_PPH ?? 0, 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td>DPP Nilai Lain</td>
                                    <td>:</td>
                                    <td>{{ number_format($verifyPo->IDPP ?? 0, 0, ',', '.') }}</td>
                                </tr>

                                <tr>
                                    <td>Grand Total</td>
                                    <td>:</td>
                                    <td>{{ number_format($verifyPo->ITOTAL ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-12 col-md-6">
                        <table class="table table-bordered">
                            <colgroup>
                                <col style="width: 45%">
                                <col style="width: 5%">
                                <col style="width: 50%">
                            </colgroup>
                            <tbody>
                                <tr>
                                    <td>File Invoice</td>
                                    <td>:</td>
                                    <td>
                                        @if (!empty($verifyPo->VINVOICE_FILE))
                                            <a href="{{ route('verify-po.download', [$verifyPo->IID, 'invoice']) }}"
                                                target="_blank">
                                                <i class="menu-icon ti tabler-file-type-pdf"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>File Tax</td>
                                    <td>:</td>
                                    <td>
                                        @if (!empty($verifyPo->VTAX_INVOICE_FILE))
                                            <a href="{{ route('verify-po.download', [$verifyPo->IID, 'tax']) }}"
                                                target="_blank">
                                                <i class="menu-icon ti tabler-file-type-pdf"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>File Rekap Jasa</td>
                                    <td>:</td>
                                    <td>
                                        @if (!empty($verifyPo->VREKAP_JASA_FILE))
                                            <a href="{{ route('verify-po.download', [$verifyPo->IID, 'rekap-jasa']) }}"
                                                target="_blank">
                                                <i class="menu-icon ti tabler-file-type-pdf"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>Other File</td>
                                    <td>:</td>
                                    <td>
                                        <table class="table table-bordered">
                                            <colgroup>
                                                <col style="width: 5%">
                                                <col style="width: 95%">
                                            </colgroup>
                                            <tbody>
                                                @foreach ($otherFiles as $other)
                                                    <tr>
                                                        <td>{{ $loop->index + 1 }}</td>
                                                        <td>
                                                            <a href="{{ route('verify-po.download-other-file', $other->IID) }}"
                                                                target="_blank">
                                                                <i class="menu-icon ti tabler-file-type-pdf"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Date</td>
                                    <td>:</td>
                                    <td>{{ $verifyPo->DTAX_INVOICE_DATE?->format('d-m-Y') ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btn-ok-submitted">
                    OK
                </button>
            </div>

        </div>
    </div>
</div>
