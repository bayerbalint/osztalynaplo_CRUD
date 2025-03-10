<?php

session_start();
include_once "html.php";
include_once "db.php";

showHead("Admin", '<link rel="stylesheet" href="/fontawesome/css/all.css">');
showBodyStart();
showButtonsStart("admin.php");
showAdminButtons();
showButtonsEnd();
handleAdminQueries();
showBodyEnd();
showHTMLEnd();

function handleAdminQueries(){
    $subjects = getSubjectsTable();
    $students = getStudentsTable();
    $marks = getMarksTable();
    $classes = getClassesTable();

    if (isset($_POST["adminClasses"])){
        showAdminTable($classes, 'classes');
        $_SESSION["selectedValues"] = $classes;
        $_SESSION["selectedTable"] = "classes";
    }

    if (isset($_POST["adminMarks"])) {
        showAdminTable($marks, 'marks');
        $_SESSION["selectedValues"] = $marks;
        $_SESSION["selectedTable"] = "marks";
    }

    if (isset($_POST["adminSubjects"])) {
        showAdminTable($subjects, 'subjects');
        $_SESSION["selectedValues"] = $subjects;
        $_SESSION["selectedTable"] = "subjects";
    }

    if (isset($_POST["adminStudents"])) {
        showAdminTable($students, 'students');
        $_SESSION["selectedValues"] = $students;
        $_SESSION["selectedTable"] = "students";
    }

    if (isset($_POST["adminNew"])){
        showAdminNew();
    }

    if (isset($_POST["newOk"])) {
        $fieldNames = "";
        $values = "";
        foreach (array_keys($_SESSION["selectedValues"][0]) as $fieldName) {
            if ($fieldName != array_keys($_SESSION["selectedValues"][0])[0]){
                $fieldNames .= $fieldName . ",";
                $values .= "'" . $_POST[$fieldName] . "',";
            }
        }
        adminInsert(substr($fieldNames, 0, -1), substr($values, 0, -1));
        $functionName = "get" . ucfirst($_SESSION["selectedTable"]) . "Table";
        $_SESSION["selectedValues"] = $functionName();
        showAdminTable($_SESSION["selectedValues"], $_SESSION["selectedTable"]);
    }

    if (isset($_POST["changeOk"])) {
        $fieldValuePairs = "";
        foreach (array_keys($_SESSION["selectedValues"][0]) as $fieldName) {
            if ($fieldName != array_keys($_SESSION["selectedValues"][0])[0]) {
                $fieldValuePairs .= $fieldName . " = ";
                $fieldValuePairs .= "'" . $_POST[$fieldName] . "',";
            }
        }
        adminUpdate($_SESSION["changingID"], substr($fieldValuePairs, 0, -1));
        $functionName = "get" . ucfirst($_SESSION["selectedTable"]) . "Table";
        $_SESSION["selectedValues"] = $functionName();
        showAdminTable($_SESSION["selectedValues"], $_SESSION["selectedTable"]);
    }

    if (isset($_POST["back"])){
        header("Location: /index.php");
    }

    if (isset($_SESSION["selectedValues"])){
        handleAdminActions();
    }
}

function handleAdminActions(){
    foreach ($_SESSION["selectedValues"] as $value) {
        if (isset($_POST["Edit_" . $value["id"]])) {
            showAdminChange($value["id"]);
            $_SESSION["changingID"] = $value["id"];
        }
        if (isset($_POST["Delete_" . $value["id"]])) {
            adminDelete($value["id"]);
            $functionName = "get" . ucfirst($_SESSION["selectedTable"]) . "Table";
            $_SESSION["selectedValues"] = $functionName();
            showAdminTable($_SESSION["selectedValues"], $_SESSION["selectedTable"]);
        }
    }
}