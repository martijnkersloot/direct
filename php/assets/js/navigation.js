$('#tabs a.item').click(function() {
    if($(this).hasClass('disabled'))
    {
        return false;
    }
    else
    {
        $('#tabs a.item.active').removeClass('active');
        $('#wrapper > .tab').removeClass('active');

        var item = $(this).attr('data-item');
        $('#' + item).addClass('active');
        $(this).addClass('active');
    }
});

$(document).on('click', '#out-tabs a.item', function(e) {
    if($(this).hasClass('disabled'))
    {
        return false;
    }
    else
    {
        $('#out-tabs a.item.active').removeClass('active');
        $('#out-results > .grid > .tab').removeClass('active');

        var item = $(this).attr('data-item');
        $('#' + item).addClass('active');
        $(this).addClass('active');

        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    }
});

$(document).on('click', '.file-results a.item', function() {
    if($(this).hasClass('disabled'))
    {
        return false;
    }
    else {
        var file = $(this).attr('data-file');
        var item = $(this).attr('data-item');

        $('#out-' + file + ' .file-results-wrapper .menu a.item.active').removeClass('active');
        $('#out-' + file + ' .file-results-wrapper > .tab').removeClass('active');

        $('#' + item).addClass('active');
        $(this).addClass('active');

        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    }
});

$('.btn-next').click(function() {
    $('#tabs a.active').next().click();
    $('.tab.active').find('.focus').focus();
});

$('.btn-prev').click(function() {
    $('#tabs a.active').prev().click();
    $('.tab.active').find('.focus').focus();
});