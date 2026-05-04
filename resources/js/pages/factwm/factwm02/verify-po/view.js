import axios from "axios";
import ViewTable from "./view-table";

class View {
    #verifyPoEndpoint = 'FACTWM/ts/verify-po';

    constructor() {
        this.table = new ViewTable();
    }

    init() {
        this.table.init();
        this.#events();
    }

    #events() {
        this.#onClickEvents();
    }

    #onClickEvents() {
        const self = this;

        $(document).on('click', '#match', function () {
            const selectedMonth = self.table.getSelectedGrNumbers();

            axios.post('/general/store-cache', {
                key: 'selected_gr_verify_po',
                payload: selectedMonth.toString()
            })
                .then(() => {
                    window.location.href = `/${self.#verifyPoEndpoint}/ocr`;
                })
                .catch((error) => {
                    console.log(error)
                })
        });
    }
}

new View().init();
