<?php
// Démarre ou récupère la session
session_start();

// Détruit toutes les variables de session
$_SESSION = array();

// Détruit la session
session_destroy();

// Redirige vers la page de connexion après la déconnexion
header("Location: connexion.php");
exit();
?>
