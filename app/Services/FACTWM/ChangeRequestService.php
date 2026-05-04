<?php

namespace App\Services\FACTWM;

use Illuminate\Http\Request;
use App\Models\FACTWM01\FACTWM_MSHSUPPLIER_COMMUNICATION_METHOD as SupplierUser;
use App\Models\FACTWM01\FACTWM_MSHCHANGE_REQUEST_VENDOR as ChangeRequest;
use App\Enums\ChangeRequestStatus;
use App\Enums\ChangeRequestType;
use Illuminate\Support\Facades\Auth;

class ChangeRequestService
{
    public function newMember(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'position'  => 'required|string|max:100',
            'type'      => 'required|string|max:100',
            'method'    => 'required|string|max:100',
            'username'  => 'required|string|max:100',
        ]);

        $userSupplier = Auth::user()->load(['supplierUser'])->supplierUser;
        $supplierCode = $userSupplier ? $userSupplier->VSUPPLIER_CODE : null;
        $supplierName = $userSupplier ? $userSupplier->VSUPPLIER_NAME : null;

        ChangeRequest::create([
            'VSUPPLIER_CODE'    => $supplierCode,
            'VSUPPLIER_NAME'    => $supplierName,
            'VNAME'             => $request->name,
            'VUSERNAME'         => $request->username,
            'VMETHOD_ID'        => $request->type,
            'VDESCRIPTION'      => $request->position,
            'VVALUE'            => $request->method,
            'VTYPE'             => ChangeRequestType::ADD,
            'VSTATUS'           => ChangeRequestStatus::DRAFT,
            'ISUPPLIER_ID'      => $userSupplier->ISUPPLIER_ID,
        ]);
    }

    public function updateMember(Request $request)
    {
        $request->validate([
            'Name'      => 'required|string|max:100',
            'Position'  => 'required|string|max:100',
            'Type'      => 'required|string|max:100',
            'Username'  => 'nullable|string|max:100',
            'Value'     => 'required|string|max:100',
        ], [
            'Value.required'    => 'Communication Method is required.'
        ]);

        $supplierUser = SupplierUser::findOrFail($request->Id)->toArray();

        $supplierUser['VNAME'] = $request->Name;
        $supplierUser['VUSERNAME'] = $request->Username ?? null;
        $supplierUser['VDESCRIPTION'] = $request->Position;
        $supplierUser['VMETHOD_ID'] = $request->Type;
        $supplierUser['VVALUE'] = $request->Value;
        $supplierUser['VSTATUS'] = ChangeRequestStatus::DRAFT;
        $supplierUser['VTYPE'] = ChangeRequestType::UPDATE;

        ChangeRequest::create($supplierUser);
    }

    public function deleteMember($id)
    {
        $supplierUser = SupplierUser::find($id)->toArray();
        $supplierUser['VSTATUS'] = ChangeRequestStatus::DRAFT;
        $supplierUser['VTYPE'] = ChangeRequestType::DELETE;

        ChangeRequest::create($supplierUser);
    }
}
