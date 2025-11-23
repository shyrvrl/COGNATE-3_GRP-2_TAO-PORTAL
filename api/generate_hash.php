<?php
$passwordToHash = 'Admin456'; // Choose a strong password //AdminPassword123 //Evaluator123 //Evaluator456 //Evaluator789
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo 'Your password is: ' . $passwordToHash . '<br>';
echo 'Your generated hash is: ' . $hashedPassword;
?>