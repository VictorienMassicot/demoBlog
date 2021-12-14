$(document).ready(function() {
    $('#table-backoffice').DataTable({
        language: {
            // url: http://localhost/PHP/10-boutique + js/dataTables.french.json
            url: '/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 1,2,5,6 ] }
        ]
    });

    $('#table-category').DataTable({
        language: {
            // url: http://localhost/PHP/10-boutique + js/dataTables.french.json
            url: '/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 1,3 ] }
        ]
    });

    $('#table-comment').DataTable({
        language: {
            // url: http://localhost/PHP/10-boutique + js/dataTables.french.json
            url: '/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 0,4 ] }
        ]
    });

    $('#table-users').DataTable({
        language: {
            // url: http://localhost/PHP/10-boutique + js/dataTables.french.json
            url: '/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 7 ] }
        ]
    });

    $('#table-profil').DataTable({
        language: {
            // url: http://localhost/PHP/10-boutique + js/dataTables.french.json
            url: '/dataTables.french.json'
        },
        "aoColumnDefs": [
            { 'bSortable': false, 'aTargets': [ 3 ] }
        ]
    });
});