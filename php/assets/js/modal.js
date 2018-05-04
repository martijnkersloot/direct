function createModal(title, content, cssClass)
{
    $('.ui.modal').remove();

    var html = '<div class="ui small modal ' + cssClass + '">';

    html += '<div class="header">' + title + '</div>';

    if(content.length > 0) {
        html += '<div class="content">';
        html += '<p>' + content + '</p>';
        html += '</div>';
    }
    html += '<div class="actions">';
    html += '<div class="ui ok button">Close</div>';
    html += '</div>';

    html += '</div>';

    $('body').append(html);
    $('.ui.modal') .modal('show') ;
}