$(document).ready(function() {

    $('#btn-upload').click(function() {
        $('#upload-wrapper input').click();
    });

    $('#upload-wrapper input').change(function (){
        $('#upload-wrapper form').submit();
    });

    $('#upload-wrapper form').submit(function(e) {
        $("#upload-frame").unbind();

        e.preventDefault(); // dont submit multiple times
        this.submit(); // use native js submit

        $("#upload-frame").load(function () {
            var text = $("#upload-frame").contents().text();
            console.log(text);
            var data = $.parseJSON(text);

            if (typeof data.error !== 'undefined') {
                createModal('An error occurred', data.error, 'error');
            }
            if (typeof data.success !== 'undefined') {
                getUploadedFiles();
            }
        });
    });

    $(document).on('click', 'button.remove-file', function(e) {
        var id = $(this).parent().attr('data-id');

        $.post('/api/json/uploadedfiles', {task: 'remove', id: id}, function (data) {
            if (typeof data.error === 'undefined') {
                getUploadedFiles();
            }
            else
            {
                createModal('An error occurred', data.error, 'error');
            }
        });
    });
});

var files = [];

function getUploadedFiles()
{
    $('#uploaded-files').html('<div class="ui active loader"></div>');
    $.getJSON('/api/json/uploadedfiles', function (data) {
        files = data;

        var html = '';
        $.each(data, function (i, value) {
            html += '<div class="item" data-id="' + value.id + '">';
            html += '<div><i class="file text icon"></i></div>';
            html += '<div class="middle aligned content">' + value.file + '</div>';
            html += '<button class="circular ui icon button mini red inverted remove-file"><i class="icon remove"></i></button>'
            html += '</div>';
        });

        $('#uploaded-files').html(html);
    });
}