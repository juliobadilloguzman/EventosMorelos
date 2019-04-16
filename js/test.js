$(document).ready(function(){

    alert('el id del evento es: ' +  $('#id-evento').data('id'));

    $.ajax({
        url: 'http://localhost:8888/eventos/api/eventos/'+$('#id-evento').data('id'),
        cache: true,
        success: function(response){
            console.log(response);
        }
    })

});