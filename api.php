<?php

require_once "controllers/Controller.php";

header('Content-Type: application/json; charset=utf-8');
$controller = new Controller();
if ($_GET["toDo"]=== "data")
    echo json_encode($controller->getAttendanceContent());
elseif ($_GET["toDo"]==="getNameInfoClass")
    echo json_encode($controller->getAllClassesNames());
elseif ($_GET["toDo"]==="getAllNames")
    echo json_encode($controller->getAllStudentsNames());
elseif ($_GET["toDo"]==="forModal") {
    $json = json_decode(file_get_contents("php://input"));
    echo json_encode($controller->detailOfStudentOnClass($json->userId, $json->classId ));

}