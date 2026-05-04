class loginHistory {
    #logTable = $('#hituamf014-table');

    init() {
        const self = this;

        this.#logTable.on('init.dt', function () {
            var tfoot = self.#logTable.find('tfoot tr');
            var thead = self.#logTable.find('thead');

            // Move the tfoot row into thead (after the header row)
            tfoot.appendTo(thead);
        });

        this.#eventHandler();
    }

    #eventHandler() {
        const self = this;
        $(document).on('click', '#btn-export', function () {
            self.#logTable.DataTable().button('.buttons-excel').trigger();
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    new loginHistory().init();
});
