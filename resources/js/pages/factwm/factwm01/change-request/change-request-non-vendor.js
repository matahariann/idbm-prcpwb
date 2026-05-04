import axios from 'axios';
import RequestTable from './request-table';
import { toast } from '../../../../helpers';
class ChangeRequestNonVendor {
    #endPoint = '/FACTWM/bd/change-request-vendor';
    constructor() {
        this.requestTable = new RequestTable();
    }

    init() {
        this.requestTable.initTable({ isVendor: false });
        this.#events();
    }

    #events() {
        const self = this;
        $(document).on('click', '#download-selected', function () {
            self.#downloadSelected();
        });
    }

    #downloadSelected() {
        const selectedIds = Array.from(document.querySelectorAll('input[name="selected[]"]:checked')).map(
            checkbox => checkbox.value
        );

        if (selectedIds.length < 1) {
            toast.error('Please select data first');
            return;
        }

        const data = {
            ids: selectedIds,
            _method: 'PUT'
        };

        axios
            .post(`${this.#endPoint}`, data)
            .then(response => {
                // toast.success(response.data.message);
                this.requestTable.instance.ajax.reload();
                this.requestTable.instance.button('.buttons-excel').trigger();
            })
            .catch(error => {
                // console.log(error);
                toast.error(error.response.data.message);
            });
    }
}

new ChangeRequestNonVendor().init();
