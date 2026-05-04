<div class="table-responsive">
    <table class="table table-bordered" id="purchase-detail-table">
        <thead>
            <tr class="table-light">
                <th>DESCRIPTION</th>
                <th>QTY</th>
                <th>UNIT</th>
                <th>UNIT PRICE</th>
                <th>AMOUNT</th>
                <th>ACTION</th>
            </tr>
        </thead>
        <tbody>
            @php
                // dd($item);
                $options = [];
                if (!empty($verify_non_po_list_unit)) {
                    $options = explode(',', $verify_non_po_list_unit);
                }
                // $options = ['-', 'Pcs', 'Box', 'Kg'];
            @endphp
            @if (count($nonPo->details) > 0)
                @foreach ($nonPo->details as $item)
                    @php
                        $currentUom = trim((string) ($item->VUOM ?? ''));
                        $currentUom = $currentUom !== '' ? $currentUom : '-';
                        $hasCurrentUomInOptions = in_array($currentUom, $options, true);
                    @endphp
                    <tr class="detail-item">
                        <td>
                            <input type="text" class="form-control description" placeholder="Item A"
                                value="{{ $item->VDESCRIPTION }}" autocomplete="new-password">
                        </td>
                        <td>
                            <input type="number" class="form-control qty" placeholder="100" min="0"
                                step="1" value="{{ $item->IQTY }}" autocomplete="new-password">
                        </td>
                        <td>
                            <select class="form-select unit" style="width: 100px">
                                <option value="-" {{ $currentUom === '-' ? 'selected' : '' }}>-</option>
                                @foreach ($options as $option)
                                    @php
                                        $selected = $option === $currentUom ? 'selected' : '';
                                    @endphp
                                    <option value="{{ $option }}" {{ $selected }}>{{ $option }}
                                    </option>
                                @endforeach
                                @if (!$hasCurrentUomInOptions && $currentUom !== '-')
                                    <option value="{{ $currentUom }}" selected>{{ $currentUom }}</option>
                                @endif
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control price" placeholder="2.500"
                                value="{{ number_format((int) $item->IPRICE, 0, ',', '.') }}" autocomplete="new-password">
                        </td>
                        <td>
                            <input type="text" class="form-control total" placeholder="250.000"
                                value="{{ number_format((int) ($item->IPRICE * $item->IQTY), 0, ',', '.') }}" readonly
                                autocomplete="new-password">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger remove-detail">
                                <i class="ti tabler-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            @endif
            @if ($action != 'View')
                <tr class="detail-item">
                    <td>
                        <input type="text" class="form-control description" placeholder="Item A" autocomplete="new-password">
                    </td>
                    <td>
                        <input type="number" class="form-control qty" placeholder="100" min="0" step="1"
                            autocomplete="new-password">
                    </td>
                    <td>
                        <select class="form-select unit" style="width: 100px">
                            <option value="" selected>-</option>
                            @foreach ($options as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control price" placeholder="2500" autocomplete="new-password">
                    </td>
                    <td>
                        <input type="text" class="form-control total bg-light" placeholder="250000" readonly
                            autocomplete="new-password">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-primary add-detail">
                            <i class="ti tabler-plus"></i> Add
                        </button>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
