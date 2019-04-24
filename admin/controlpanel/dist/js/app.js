$(document).ready(function(){

    //Esconder por default los eventos
    $('#sectionEventos').hide();
    
    $('#usuariosNav').on('click', function(){

        $('#sectionEventos').hide();
        $('#sectionUsuarios').show();

        //Agregar la clase active
        $(this).parent().parent().addClass('active');

        //Remover la clase active del otro
        $('#eventosNav').parent().parent().removeClass('active');

        //Cambiar el titulo
        $('#tituloNavegacion').html('Usuarios');
    
    });

    $('#eventosNav').on('click', function(){

        $('#sectionEventos').show();
        $('#sectionUsuarios').hide();

        //Agregar la clase active
        $(this).parent().parent().addClass('active');

        //Remover la clase active del otro
        $('#usuariosNav').parent().parent().removeClass('active');

        //Cambiar el titulo
        $('#tituloNavegacion').html('Eventos');
        
    });

});