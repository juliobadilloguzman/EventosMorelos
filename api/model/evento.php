<?php

    class EventoException extends Exception{}
    
    class Evento{

        //Atributos

        private $_id;
        private $_titulo;
        private $_descripcion;
        private $_lugar;
        private $_fechaRealizacion;
        private $_categoria;
        private $_imagen;

        public function __construct($id, $titulo, $descripcion, $lugar, $fechaRealizacion, $categoria){

            $this->setID($id);
            $this->setTitulo($titulo);
            $this->setDescripcion($descripcion);
            $this->setLugar($lugar);
            $this->setFechaRealizacion($fechaRealizacion);
            $this->setCategoria($categoria);

        }

        //Getters

        public function getID(){
            return $this->_id;
        }

        public function getTitulo(){
            return $this->_titulo;
        }

        public function getDescripcion(){
            return $this->_descripcion;
        }

        public function getLugar(){
            return $this->_lugar;
        }

        public function getFechaRealizacion(){
            return $this->_fechaRealizacion;
        }

        public function getCategoria(){
            return $this->_categoria;
        }

        public function getImagen(){
            return $this->_imagen;
        }

        //Setters (Cada uno con las validaciones necesarias)

        public function setID($id){

            $this->_id=$id;

        }

        public function setTitulo($titulo){

            if(strlen($titulo) < 0 || strlen($titulo) > 255){
                throw new EventoException("Event titulo error");
            }

            $this->_titulo=$titulo;
        }

        public function setDescripcion($descripcion){

            if(($descripcion !== null) && strlen($descripcion) > 16777215){
                throw new EventoException("Event descripcion error");
            }

            $this->_descripcion=$descripcion;

        }

        public function setLugar($lugar){

            if(strlen($lugar) < 0){
                throw new EventoException("Event lugar error");
            }

            $this->_lugar=$lugar;

        }

        public function setFechaRealizacion($fechaRealizacion){

            if(($fechaRealizacion !== null) && date_format(date_create_from_format('d/m/Y H:i', $fechaRealizacion), 'd/m/Y H:i') !== $fechaRealizacion ){
                throw new EventoException("Event fechaRealizacion date time error");
            }

            $this->_fechaRealizacion=$fechaRealizacion;

        }

        public function setCategoria($categoria){

            if(strlen($categoria) < 0){
                throw new EventoException("Event cateogira error");
            }

            $this->_categoria=$categoria;

        }

        public function returnEventoAsArray(){

            $evento = array();
            $evento['id'] = $this->getID();
            $evento['titulo'] = $this->getTitulo();
            $evento['descripcion'] = $this->getDescripcion();
            $evento['lugar'] = $this->getLugar();
            $evento['fechaRealizacion'] = $this->getFechaRealizacion();
            $evento['categoria'] = $this->getCategoria();

            return $evento;
        }

    }



?>