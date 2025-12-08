<?php
$passwordToHash = 'Evaluator123'; // Choose a strong password //AdminPassword123 //Evaluator123 //Evaluator456 //Evaluator789//JhonePogi
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo 'Your password is: ' . $passwordToHash . '<br>';
echo 'Your generated hash is: ' . $hashedPassword;
?>