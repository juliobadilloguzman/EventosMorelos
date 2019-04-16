<?php

    $id = $_GET['id'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

    <h1><?php echo $id; ?></h1>

    <input type="hidden" id="id-evento" data-id="<?php echo $id; ?>">

    <script src="http://localhost:8888/eventos/js/jquery.js"></script>
    <script src="http://localhost:8888/eventos/js/test.js"></script>

    
</body>
</html>