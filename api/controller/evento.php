<?php

    require_once('db.php');
    require_once('../model/evento.php');
    require_once('../model/response.php');

    //Database connection

    try{

        $writeDB = DB::connectWriteDB();
        $readDB = DB::connectReadDB();

    }catch(PDOException $e){

        error_log("Connection error - ".$e,0);

        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage("Database connection error");
        $response->send();
        exit();

    }

    
    /////////////**CONSIGUE UN EVENTO EN ESPECIFICO */
    // Para probarlo en POSTMAN con el metodo GET: http://localhost:8888/eventos/api/IDdelEvento
    if(array_key_exists("eventid", $_GET)){

        //Obtiene el parametro get
        $eventid = $_GET['eventid'];

        //Verifica que el parametro sea valido
        if($eventid == '' || !is_numeric($eventid)){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            $response->addMessage("Event ID cannot be blank or must be numeric");
            $response->send();
            exit();
        }

        //*********** RESt */
        $metodo = $_SERVER['REQUEST_METHOD'];

        switch ($metodo) {

            case 'GET':
                
                try{

                    //Consulta
                    $statement = $readDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") as fechaRealizacion, categoria from eventos where id = :id');
                    $statement->bindParam(':id', $eventid, PDO::PARAM_INT);
                    $statement->execute();

                    //Contamos las filas devueltas para verificar que exista un evento
                    $rowCount = $statement->rowCount();

                    //Si no hubo eventos, mandamos una respuesta que no existe
                    if($rowCount==0){
                        $response = new Response();
                        $response->setHttpStatusCode(404);
                        $response->setSuccess(false);
                        $response->addMessage("Event not found");
                        $response->send();
                        exit();
                    }

                    //Recorremos la consulta para crear un nuevo evento
                    //Creamos el array que va a contener todo
                    while($row = $statement->fetch(PDO::FETCH_ASSOC)){

                        $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);

                        $eventosArray[] = $evento->returnEventoAsArray();

                    }

                    //returnData va a hacer el array final que se convierte a JSON
                    $returnData = array();
                    $returnData['rows_returned'] = $rowCount;
                    $returnData['eventos'] = $eventosArray;


                    //Mandamos la respuesta exitosa
                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($returnData);
                    $response->send();


                }catch(EventoException $e){

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();

                }
                catch(PDOException $e){

                    error_log("DATABASE QUERY ERROR - ".$e,0);

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage("Failed to get a event");
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
                }
            
            
                break;

            case 'DELETE':
                
                try{

                    $statement = $writeDB->prepare("DELETE from eventos WHERE id = :id");
                    $statement->bindParam(':id', $eventid, PDO::PARAM_INT);
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    if($rowCount==0){
                        $response = new Response();
                        $response->setHttpStatusCode(404);
                        $response->setSuccess(false);
                        $response->addMessage("Event not found");
                        $response->send();
                        exit();
                    }

                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(false);
                    $response->addMessage("Event deleted successfully");
                    $response->send();
                    exit();



                }catch(PDOException $e){

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage("Failed to delete the event");
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();

                }

                break;

            /////////////** ACTUALIZA UN EVENTO */
            // Para probarlo en POSTMAN con el metodo PATCH: http://localhost:8888/eventos/api/eventos/IDaACTUALIZAR
            case 'PATCH': 
                
                try{

                    if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Content Type header not set to JSON");
                        $response->send();
                        exit;
                    }

                    //obtenemos los datos que nos manden
                    $rawPatchData = file_get_contents('php://input');
                      
                    if(!$jsonData = json_decode($rawPatchData)) {
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Request body is not valid JSON");
                        $response->send();
                        exit;
                    }

                    //inicializamos cada propiedad en falso para despues verificar
                    $titulo_updated = false;
                    $descripcion_updated = false;
                    $lugar_updated = false;
                    $fechaRealizacion_updated = false;
                    $categoria_updated = false;

                    //create blank query fields string to append each field to
                    $queryFields = "";


                    /////////////////VERIFY THAT EACH ELEMENT EXISTS

                    if(isset($jsonData->titulo)) {
                        $titulo_updated = true;
                        $queryFields .= "titulo = :titulo, ";
                    }

                    if(isset($jsonData->description)) {
                        $descripcion_updated = true;
                        $queryFields .= "descripcion = :descripcion, ";
                    }

                    if(isset($jsonData->lugar)) {
                        $lugar_updated = true;
                        $queryFields .= "lugar = :lugar, ";
                    }

                    if(isset($jsonData->fechaRealizacion)) {
                        $fechaRealizacion_updated = true;
                        $queryFields .= "fechaRealizacion = :fechaRealizacion, ";
                    }

                    if(isset($jsonData->categoria)) {
                        $categoria_updated = true;
                        $queryFields .= "categoria = :categoria, ";
                    }

                    //remove the right hand comma and trailing space
                    $queryFields = rtrim($queryFields, ", ");

                    //check if any task fields supplied in JSON
                    if($titulo_updated === false && $descripcion_updated === false && $lugar_updated === false && $fechaRealizacion_updated === false && $categoria_updated === false) {
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("No event fields provided");
                        $response->send();
                        exit;
                    }

                    //consulta
                    $statement = $writeDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") as fechaRealizacion, categoria from eventos where id = :id');
                    $statement->bindParam(':id', $eventid, PDO::PARAM_INT);
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    if($rowCount === 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(404);
                        $response->setSuccess(false);
                        $response->addMessage("No event found to update");
                        $response->send();
                        exit;
                    }

                    while($row = $statement->fetch(PDO::FETCH_ASSOC)){

                        $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);

                    }

                    //////////////////FORMA EL QUERY FINAL DE UPDATE

                    $queryString = "UPDATE eventos set ".$queryFields." where id = :id";

                    //Prepara la consulta
                    $statement = $writeDB->prepare($queryString);

                    if($titulo_updated === true) {
 
                        $evento->setTitulo($jsonData->titulo);
                        // get the value back as the object could be handling the return of the value differently to
                        // what was provided
                        $up_titulo = $evento->getTitulo();
                        $statement->bindParam(':titulo', $up_titulo, PDO::PARAM_STR);

                    }

                    if($descripcion_updated === true) {
                        $evento->setDescripcion($jsonData->descripcion);
                        $up_descripcion = $evento->getDescripcion();
                        $statement->bindParam(':descripcion', $up_descripcion, PDO::PARAM_STR);
                    }

                    if($lugar_updated === true) {
                        $evento->setLugar($jsonData->lugar);
                        $up_lugar = $evento->getLugar();
                        $statement->bindParam(':lugar', $up_lugar, PDO::PARAM_STR);
                    }

                    if($fechaRealizacion_updated === true) {
                        $evento->setFechaRealizacion($jsonData->fechaRealizacion);
                        $up_fechaRealizacion = $evento->getFechaRealizacion();
                        $statement->bindParam(':fechaRealizacion', $up_fechaRealizacion, PDO::PARAM_STR);
                    }

                    if($categoria_updated === true) {
                        $evento->setCategoria($jsonData->categoria);
                        $up_categoria= $evento->getCategoria();
                        $statement->bindParam(':categoria', $up_categoria, PDO::PARAM_STR);
                    }

                    $statement->bindParam(':id', $eventid, PDO::PARAM_INT);
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    if($rowCount === 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Event not updated - given values may be the same as the stored values");
                        $response->send();
                        exit;
                    }

                    //La nueva query que nos va a retornar el evento actualizado
                    $statement = $writeDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") as fechaRealizacion, categoria from eventos where id = :id');
                    $statement->bindParam(':id', $eventid, PDO::PARAM_INT);
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    if($rowCount === 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(404);
                        $response->setSuccess(false);
                        $response->addMessage("No event found");
                        $response->send();
                        exit;
                    }

                    $eventosArray = array();

                    while($row = $statement->fetch(PDO::FETCH_ASSOC)){

                        $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);
                        $eventosArray[] = $evento->returnEventoAsArray();

                    }

                    $returnData = array();
                    $returnData['rows_returned'] = $rowCount;
                    $returnData['tasks'] = $taskArray;

                    $response = new Response();
                    $response->setHttpStatusCode(200);
                    $response->setSuccess(true);
                    $response->addMessage("Event updated");
                    $response->setData($returnData);
                    $response->send();
                    exit;


                }catch(EventoException $e){

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
    
                }
                catch(PDOException $e){
    
                    error_log("DATABASE QUERY ERROR - ".$e,0);
    
                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
                }

                break;
            
            default:
            
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage("Request method not allowed");
                $response->send();
                exit();
            
                break;
        }
        
    /////////////**CONSIGUE TODOS LOS EVENTOS CON PAGINACION */ (ESTO ES POR SI NO NECESITABA)
    // Para probarlo en POSTMAN con el metodo GET: http://localhost:8888/eventos/api/eventos/page/NUMEROdePAGINA
    }elseif(array_key_exists("page", $_GET)){

        if($_SERVER['REQUEST_METHOD'] == 'GET'){

            $page = $_GET['page'];
            
            if($page == '' || !is_numeric($page)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                $response->addMessage("Page number cannot be blank and must be numeric");
                $response->send();
                exit();
            }

            $limitPerPage = 20;

            try{

                $statement = $readDB->prepare("SELECT count(id) as totalNoOfEvents from eventos");
                $statement->execute();

                $row = $statement->fetch(PDO::FETCH_ASSOC);

                $eventCount = intval($row['totalNoOfEvents']);

                $numOfPages = ceil($eventCount/$limitPerPage);

                if($numOfPages == 0){
                    $numOfPages=1;
                }

                if($page > $numOfPages || $page == 0){
                    $response = new Response();
                    $response->setHttpStatusCode(404);
                    $response->setSuccess(false);
                    $response->addMessage("Page not found");
                    $response->send();
                    exit();
                }

                $offset = ($page == 1 ? 0 : (20*($page-1)));


                $statement = $readDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") AS fechaRealizacion, categoria FROM eventos limit :pglimit offset :offset');
                $statement->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
                $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
                $statement->execute();

                $rowCount = $statement->rowCount();

                $eventosArray[] = array();

                while($row = $statement->fetch(PDO::FETCH_ASSOC)){

                    $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);

                    $eventosArray[] = $evento->returnEventoAsArray();
                }

                $returnData = array();
                $returnData['rows_returned'] = $rowCount;
                $returnData['total_rows'] = $eventCount;
                $returnData['total_pages'] = $numOfPages;
                ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
                ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
                $returnData['eventos'] = $eventosArray;

                $response = new Response();
                $response->setHttpStatusCode(200);
                $response->setSuccess(true);
                $response->toCache(true);
                $response->setData($returnData);
                $response->send();


            }catch(EventoException $e){

                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage($e->getMessage()); 
                $response->send();
                exit();

            }
            catch(PDOException $e){

                error_log("DATABASE QUERY ERROR - ".$e,0);

                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage($e->getMessage()); 
                $response->send();
                exit();
            }
            

        }else{

            $response = new Response();
            $response->setHttpStatusCode(405);
            $response->setSuccess(false);
            $response->addMessage("Request method not allowed");
            $response->send();
            exit();

        }

    /////////////**CONSIGUE TODOS LOS EVENTOS */
    // Para probarlo en POSTMAN con el metodo GET: http://localhost:8888/eventos/api/eventos
    }elseif(empty($_GET)){ 

        $metodo = $_SERVER['REQUEST_METHOD'];

        switch ($metodo) {

            case 'GET':

                try{

                    $statement = $readDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") as fechaRealizacion, categoria from eventos');
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    $eventosArray = array();

                    while($row = $statement->fetch(PDO::FETCH_ASSOC)){

                        $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);
                        $eventosArray[] = $evento->returnEventoAsArray();
                        

                    }

                    $returnData = array();
                    $returnData['rows_returned'] = $rowCount;
                    $returnData['eventos'] = $eventosArray;

                    //Mandamos todos los eventos
                    $response = new Response();
                    $response->setHttpStatusCode(200 );
                    $response->setSuccess(true);
                    $response->toCache(true);
                    $response->setData($returnData); 
                    $response->send();
                    exit();

                }catch(EventoException $e){

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
    
                }catch(PDOException $e){
    
                    error_log("DATABASE QUERY ERROR - ".$e,0);
    
                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage("Failed to get a event");
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
                }

                break;

            /////////////**CREA UN NUEVO EVENTO */
            // Para probarlo en POSTMAN con el metodo POST: http://localhost:8888/eventos/api/eventos
            // Se le tiene que pasar un JSON en el body -> raw
            case 'POST':

                try{

                    if($_SERVER['CONTENT_TYPE'] !== 'application/json') {

                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Content Type header not set to JSON");
                        $response->send();
                        exit;
                      }
                      
                      $rawPostData = file_get_contents('php://input');
                      
                      if(!$jsonData = json_decode($rawPostData)) {
                 
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage("Request body is not valid JSON");
                        $response->send();
                        exit;
                      }

                    if(!isset($jsonData->titulo) || !isset($jsonData->descripcion) || !isset($jsonData->categoria)){
                        $response = new Response();
                        $response->setHttpStatusCode(400);
                        $response->setSuccess(false);
                        $response->addMessage('Titulo, descripcion, and categoria field must be mandatory and must be provided');
                        exit();
                    }

                    $evento = new Evento(null, $jsonData->titulo,$jsonData->descripcion, (isset($jsonData->lugar) ? $jsonData->lugar : null), (isset($jsonData->fechaRealizacion) ? $jsonData->fechaRealizacion : null), $jsonData->categoria);

                    $titulo = $evento->getTitulo();
                    $descripcion = $evento->getDescripcion();
                    $lugar = $evento->getLugar();
                    $fechaRealizacion = $evento->getFechaRealizacion();
                    $categoria = $evento->getCategoria();
    

                    $statement = $writeDB->prepare('INSERT INTO eventos(titulo, descripcion, lugar, fechaRealizacion, categoria) 
                    VALUES(:titulo, :descripcion, :lugar, STR_TO_DATE(:fechaRealizacion, \'%d/%m/%Y %H:%i\'), :categoria)');
   
                    $statement->bindParam(':titulo', $titulo, PDO::PARAM_STR);
                    $statement->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                    $statement->bindParam(':lugar', $lugar, PDO::PARAM_STR);
                    $statement->bindParam(':fechaRealizacion', $fechaRealizacion, PDO::PARAM_STR);
                    $statement->bindParam(':categoria', $categoria, PDO::PARAM_STR);
                    $statement->execute();

                    $rowCount = $statement->rowCount();

                    if($rowCount == 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(500);
                        $response->setSuccess(false);
                        $response->addMessage("Failed to create event");
                        $response->send();
                        exit;
                    }

                    //obtenemos el ultimo id insertado para regresarlo en el json
                    $lastEventID = $writeDB->lastInsertId();

                    $statement = $writeDB->prepare('SELECT id, titulo, descripcion, lugar, DATE_FORMAT(fechaRealizacion, "%d/%m/%Y %H:%i") as fechaRealizacion, categoria from eventos where id = :id');
                    $statement->bindParam(':id', $lastEventID, PDO::PARAM_INT);
                    $statement->execute();

                    $rowCount = $statement->rowCount();
      
                    //verificamos si existe y si no mandamos una respuesta
                    if($rowCount === 0) {
                        $response = new Response();
                        $response->setHttpStatusCode(500);
                        $response->setSuccess(false);
                        $response->addMessage("Failed to retrieve event after creation");
                        $response->send();
                        exit;
                    }

                    $eventosArray = array();

                    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
      
                        $evento = new Evento($row['id'], $row['titulo'], $row['descripcion'], $row['lugar'], $row['fechaRealizacion'], $row['categoria']);
                        $eventosArray[] = $evento->returnEventoAsArray();

                      }

                    $returnData = array();
                    $returnData['rows_returned'] = $rowCount;
                    $returnData['eventos'] = $eventosArray;

                    $response = new Response();
                    $response->setHttpStatusCode(201);
                    $response->setSuccess(true);
                    $response->addMessage("Event created");
                    $response->setData($returnData);
                    $response->send();
                    exit;   

                    
                }catch(EventoException $e){

                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
    
                }catch(PDOException $e){
    
                    error_log("DATABASE QUERY ERROR - ".$e,0);
    
                    $response = new Response();
                    $response->setHttpStatusCode(500);
                    $response->setSuccess(false);
                    $response->addMessage($e->getMessage()); 
                    $response->send();
                    exit();
                }

                
                break;
            
            default:
                $response = new Response();
                $response->setHttpStatusCode(405);
                $response->setSuccess(false);
                $response->addMessage("Request method not allowed");
                $response->send();
                exit();
                break;
        }


    }else{

        $response = new Response();
        $response->setHttpStatusCode(404);
        $response->setSuccess(false);
        $response->addMessage("Endpoint not found");
        $response->send();
        exit();

    }




?>