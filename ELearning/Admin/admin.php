<?php 
include('../dbConnection.php');
if(!isset($_SESSION)){ 
  session_start(); 
}
// setting header type to json, We'll be outputting a Json data
header('Content-type: application/json');

 // Admin Login Verification
 if(!isset($_SESSION['is_admin_login'])){
   if(isset($_POST['checkLogemail']) && isset($_POST['adminLogEmail']) && isset($_POST['adminLogPass'])){
     $adminLogEmail = $_POST['adminLogEmail'];
     $adminLogPass = $_POST['adminLogPass'];
     $sql = "SELECT admin_email, admin_pass FROM admin WHERE admin_email='".$adminLogEmail."'";
     $result = $conn->query($sql);
     
     if($result->num_rows === 1){
       $row = $result->fetch_assoc();
       if(password_verify($adminLogPass, $row['admin_pass']) || $adminLogPass === $row['admin_pass']) {
         if($adminLogPass === $row['admin_pass'] && !password_verify($adminLogPass, $row['admin_pass'])) {
           $hashed = password_hash($adminLogPass, PASSWORD_DEFAULT);
           $conn->query("UPDATE admin SET admin_pass='$hashed' WHERE admin_email='$adminLogEmail'");
         }
         $_SESSION['is_admin_login'] = true;
         $_SESSION['adminLogEmail'] = $adminLogEmail;
         echo json_encode(1);
       } else {
         echo json_encode(0);
       }
     } else {
       echo json_encode(0);
     }
   }
 } else {
   // Already logged in
   echo json_encode(1);
 }

?>