export default class Loader {
    static show(mode) {
        // if (mode === 'list') {
        //     $('#list-folder').show();
        //     $('#grid-folder').hide();
        //     $('#doc-loading').hide();
        // } else {
        //     $('#grid-folder').hide();
        //     $('#list-folder').hide();
        //     $('#doc-loading').show();
        // }
        $('#grid-folder').hide();
        $('#list-folder').hide();
        $('#doc-loading').show();
    }

    static hide(mode) {
        $('#doc-loading').hide();
        if (mode === 'grid') {
            $('#grid-folder').show();
        } else {
            $('#list-folder').show();
        }
    }
}
