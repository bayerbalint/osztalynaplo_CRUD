<?php

include_once "config.php";
include_once "db.php";
include_once "html.php";

session_start();
// session_destroy();

showHead("Osztálynapló");
showBodyStart();
showButtonsStart("index.php");

if (databaseExists()){
    showQueryButtons();
    showDatabaseDeleteButton();
    showButtonsEnd();
    handleQueries();
}else{
    showDatabaseCreateButton();
    showButtonsEnd();
    if (isset($_POST["create"])) {
        createDatabase();
        createTableSubjects();
        createTableClasses();
        createTableMarks();
        createTableStudents();
        fillTables();
        header("refresh: 0.1");
    }
}

showBodyEnd();
showScript();
showHTMLEnd();